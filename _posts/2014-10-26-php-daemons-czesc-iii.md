---
layout: post
title: "PHP Daemons - część III"
description: "Jednym ze sposobów asynchronicznego przetwarzania w języku PHP jest forkowanie polegające na tworzeniu dedykowanego procesu, będącego duplikatem procesu głównego, do obsługi poszczególnych zadań, połączeń. Sposób ten został szczegółowo omówiony w poprzednim artykule z cyklu PHP Daemons. Alternatywą dla tego podejścia może być..."
headline: 
modified: 2014-10-26
category: php
tags: [asynchronous programming, daemons, epoll, kqueue, libevent, poll, event-driven programming, multitasking, php, sockets]
comments: true
featured: false
---

Jednym ze sposobów asynchronicznego przetwarzania w języku **PHP** jest forkowanie polegające na tworzeniu dedykowanego procesu, będącego duplikatem procesu głównego, do obsługi poszczególnych zadań, połączeń. Sposób ten został szczegółowo omówiony w poprzednim [artykule]({{ site.url }}/php/php-daemons-czesc-ii/) z cyklu *PHP Daemons*. Alternatywą dla tego podejścia może być zdefiniowanie pewnej ustalonej puli otwartych gniazdek (*ang. sockets*) do obsługi wielu równoległych połączeń, iterowanie po tej liście a następnie obsługa zmian statusów za pomocą mechanizmu **select()** (rozszerzenie sockets - funkcje *stream_select()* bądź *socket_select()*).

Jeśli naszym celem będzie obsługa przykładowo 1000 równoległych połączeń, iterowanie po wszystkich przygotowanych w tym celu socketach może być kosztowne i mało wydajne. Istnieje jednak dużo bardziej wydajne rozwiązanie operujące na nieblokujących gniazdkach i przełączające się na obsługę danego połączenia dopiero w momencie kiedy wystąpi taka potrzeba (bez konieczności pollingu wielu socketów i analizowania czy status uległ zmianie). Problemem tutaj jest to, iż nie jest ono takie samo we wszystkich systemach operacyjnych. Przykładowo: *epoll* w Linuxie, *kqueue* - FreeBSD / Mac OS X czy też */dev/poll* - Solaris. Zależy nam przecież na tym, aby nasza aplikacja była przenośna i nie było konieczności wprowadzania zmian przy kolejnych wdrożeniach, stąd też bazowanie na wydajniejszych wersjach mechanizmu ***select()*** może być kłopotliwe. Pomocne w tym wypadku będzie skorzystanie z biblioteki **libevent**, stanowiącej interfejs do najbardziej wydajnej implementacji poll w danym środowisku.

### Event loop

**Libevent** jest biblioteką zapewniającą mechanizm do wywoływania funkcji zwrotnych (*ang. callbacks*) w momencie wystąpienia danego zdarzenia na wskazanych deskryptorach plików bądź w przypadku osiągnięcia *timeout'u*. Przykładowo możemy przez to rozumieć, że do naszego *daemona* wysłane zostały dane i są gotowe do przetworzenia bądź zakończono operacje na tych danych i zostaną one odesłane do podłączonego klienta. Podejście takie charakterystyczne jest dla programowania asynchronicznego opartego na zdarzeniach (*ang. event-driven programming*). Centralnym punktem aplikacji opartej na tym modelu będzie główna pętla czyli tzw. ***event loop***, gdzie będziemy oczekiwali na zdarzenia i w momencie ich wystąpienia wykonywali zdefiniowane dla tych zdarzeń zadania. Zanim jednak wystartujemy nasz *event loop*, konieczne jest wykonanie następujących kroków:

1. stworzenie zdarzenia bazowego (*base_event*)
2. stworzenie zdarzenia (*event*), które zostanie powiązane z monitorowanym deskryptorem
3. zdefiniowanie monitorowanego deskryptora (*fd*) - w przypadku daemona będzie to socket
4. powiązanie zdarzenia event z deskryptorem fd oraz zarejestrowanie callback'a do obsługi tego zdarzenia
5. powiązanie zdarzeń *event* oraz *base_event*

``` php
final public function listen()
{
    $this->sock = stream_socket_server('tcp://' . $this->host . ':' . $this->port, $errno, $errstr);
    stream_set_blocking($this->sock, 0);

    $base = event_base_new();
    $event = event_new();

    event_set($event, $this->sock, EV_READ | EV_PERSIST, [$this, 'onConnection'], $base);
    event_base_set($event, $base);
    event_add($event);
    event_base_loop($base);
} 
```

### Buffered event

Podstawowym zadaniem stawianym przed implementowanym przez nas daemonem w PHP będzie obsługa wielu połączeń równocześnie. Aby postulat ten został zrealizowany, operacje *I/O* (*ang. Input / Output*) powinny być nieblokujące tj. przyjmowanie nowych połączeń nie będzie wstrzymywane na czas obsługi zdarzeń. W tym celu zastosowane zostaną buforowane zdarzenia (*ang. buffered event*) posiadające własne bufory wejścia / wyjścia (*input buffer*, *output buffer*) - przykładowo, gdy wystąpi zdarzenie typu read, dane odebrane z deskryptora trafiają do bufora wejściowego a aplikacja wraca do trybu oczekiwania na nowe zdarzenia.

``` php
private function onConnection($sock, $flag, $base)
{
    // kontrola liczby polaczen...
    
    $accepted_sock = stream_socket_accept($sock);
    stream_set_blocking($accepted_sock, 0);

    $buffer = event_buffer_new(
        $accepted_sock,
        [$this, 'onRead'],
        null,
        [$this, 'onError'],
        $connection_id
    );
    event_buffer_base_set($buffer, $base);

    if ($this->timeout > 0) {
        event_buffer_timeout_set($buffer, $this->timeout, $this->timeout);
    }

    event_buffer_watermark_set($buffer, EV_READ, 0, 0xffffff);
    event_buffer_enable($buffer, EV_READ | EV_PERSIST);

    // nawiazanie polaczenia...

    return true;
} 
```

### Callback watermark

Dla każdego ze zdefiniowanych buforowanych zdarzeń możemy zarejestrować osobne funkcje zwrotne typu read / write. Domyślnie, *read callback* wywoływana jest w momencie odebrania danych z fd, *write callback* - gdy dane z bufora wyjściowego zostaną wysłane do monitorowanego deskryptora (tutaj: socket'u). Dodatkowo możemy przedefiniować obsługę tych callback'ów ustawiając odpowiednie poziomy watermark:

* **read low-water mark** - min. liczba bajtów w buforze wejściowym (*input buffer*), która spowoduje wywołanie read callback, domyślnie 0
* **read high-water mark** - max. liczba bajtów jaka spowoduje przerwanie wczytania do bufora wejściowego (do czasu zwolnienia miejsca w buforze), domyślnie nieograniczona
* **write low-water mark** - jak tylko liczba bajtów w buforze wyjściowym (*output buffer*) spadnie poniżej tego poziomu wywołana zostanie write callback, domyślnie 0 co oznacza, że write callback nie zostanie wywołany dopóki output buffer nie zostanie opróżniony
* **write high-water mark** - znaczenie specjalne (filtrowanie zdarzeń buforowanych), brak zastosowania z poziomu PHP API

Do ustawiania poziomów watermark dla danego buforowanego zdarzenia możemy wykorzystać *event_buffer_watermark_set()*.

Dla każdego nowego połączenia z daemonem zdefiniowana zostanie nowa instancja buforowanego zdarzenia. Dodatkowo wszystkie utworzone instancje przechowywane będą w wewnętrznym cache'u celem kontroli max liczby połączeń - w momencie zamknięcia danego połączenia, odpowiednia instancja zostanie usunięta z cache'a. Jednak, aby poszczególne połączenia były obsługiwane w momencie wystąpienia zdarzenia danego typu konieczne jest włączenie obsługi, zdefiniowanego dla danego połączenia, buforowanego zdarzenia.

``` php
private function onConnection($pSock, $pFlag, $pBase)
{
    // jesli za duzo polaczen, czekamy az beda jakies dostepne
    if (count($this->connections) == $this->maxConnections) {
        return false;
    }

    // unikalny identyfikator polaczenia
    static $connection_id = 0;
    $connection_id++;

    // buffered event...
    
    $connection = new DaemonConnection($connection_id, $sock);
    $connection->setEventBuffer($buffer);
    $this->setConnection($connection);

    return true;
} 
```

### Typy buforowanych zdarzeń

Włączając obsługę buforowanego zdarzenia (***event_buffer_enable()***) bądź definiując po prostu nowe zdarzenie (***event_set()***) zobligowani jesteśmy do określenia typu takiego zdarzenia. Sprowadza się to do ustawienia jednej z możliwych wartości:

* ***EV_TIMEOUT*** - oznacza zdarzenia aktywne po upływie timeoutu, flaga jest ignorowana w momencie definiowana zdarzenia (timeout definiowany jest podczas dodawania zdarzenia - *event_add()*)
* ***EV_READ*** - zdarzenia aktywne, gdy powiązany z danym zdarzeniem deskryptor jest gotowy do odczytu
* ***EV_WRITE*** - zdarzenia aktywne, gdy powiązany z danym zdarzeniem deskryptor jest gotowy do zapisu
* ***EV_SIGNAL*** - flaga wykorzystywana do obsługi sygnałow (*ang. signals*)
* ***EV_PERSIST*** - flaga oznaczająca trwałość zdarzeń: domyślnie, jeśli dane oczekujące zdarzenie stanie się aktywne (powiązane deskryptory będą gotowe do odczytu / zapisu bądź upłynął timeout), nie powróci już do stanu oczekiwania o ile nie zostanie ponownie dodane (*event_add()*) w funkcji zwrotnej obsługującej to zdarzenie. Ustawienie flagi *EV_PERSIST* spowoduje trwałość tego zdarzenia tj. automatycznie przełączy się w stan oczekiwania po wywołaniu callback'a. Dodatkowo, pomimo flagi *EV_PERSIST*, istnieje możliwość wyjścia ze stanu oczekiwania wywołując *event_del()* w callback'u. Flagę *EV_PERSIST* włączamy dla zdarzeń typu read / write ustawiając *EV_READ&#124;EV_PERSIST* bądź *EV_WRITE&#124;EV_PERSIST*. Timeout zdarzeń z włączoną flagą *EV_PERSIST* jest resetowany w momencie wywołania funkcji zwrotnej, np. dla zdarzenia zdefiniowano flagi *EV_READ&#124;EV_PERSIST* oraz timeout = 5 sek. - stanie się ono aktywne kiedy fd będzie gotowy do odczytu bądź minęło 5 sekund od czasu, gdy ostatni raz było ono (zdarzenie) aktywne

Podsumowując, wykorzystanie biblioteki ***libevent*** do realizacji daemona w PHP pozwala znacznie ograniczyć zużycie zasobów, w porównaniu do rozwiązania opartego na *forkowaniu* procesów, przy identycznej liczbie obsługiwanych połączeń. Dodatkowo nie występują tutaj problemy związane z *procesami zombie* czy wieloma otwartymi połączeniami z bazą danych. Dzięki temu, że ***libevent*** stanowi interfejs do najwydajniejszej implementacji mechanizmu *select()* w danym środowisku, nie będzie potrzeby wprowadzania zmian podczas migracji z jednego środowiska do drugiego, np. z Mac OS X do Linuxa. W końcu, dzięki zastosowaniu modelu programowania asynchronicznego, co prawda wymagającego nieco innego podejścia przy projektowaniu rozwiązania, możliwa jest obsługa wielu równoległych procesów w jedno-procesowym (wynikającym z natury języka PHP) środowisku.

Kompletny kod znajdziecie [tutaj](https://github.com/tswiackiewicz/SimpleEventDaemon).

Przydatne linki:

* [http://cs.brown.edu/courses/cs168/f12/handouts/async.pdf](http://cs.brown.edu/courses/cs168/f12/handouts/async.pdf)
* [http://en.wikipedia.org/wiki/Reactor_pattern](http://en.wikipedia.org/wiki/Reactor_pattern)
* [http://en.wikipedia.org/wiki/Polling_(computer_science)](http://en.wikipedia.org/wiki/Polling_(computer_science))
* [http://en.wikipedia.org/wiki/Asynchronous_I/O](http://en.wikipedia.org/wiki/Asynchronous_I/O)
* [http://libevent.org/](http://libevent.org/)
* [https://github.com/libevent/libevent](https://github.com/libevent/libevent)
* [http://www.wangafu.net/~nickm/libevent-book/](http://www.wangafu.net/~nickm/libevent-book/)
* [http://www.kegel.com/c10k.html](http://www.kegel.com/c10k.html)
* [https://www.dartlang.org/articles/event-loop/](https://www.dartlang.org/articles/event-loop/)
* [http://programmers.stackexchange.com/questions/214889/is-an-event-loop-just-a-for-while-loop-with-optimized-polling](http://programmers.stackexchange.com/questions/214889/is-an-event-loop-just-a-for-while-loop-with-optimized-polling)
* [http://nick-black.com/dankwiki/index.php/Fast_UNIX_Servers](http://nick-black.com/dankwiki/index.php/Fast_UNIX_Servers)
* [http://www.ibm.com/developerworks/library/os-php-multitask/](http://www.ibm.com/developerworks/library/os-php-multitask/)
* [http://www.ibm.com/developerworks/aix/library/au-libev/](http://www.ibm.com/developerworks/aix/library/au-libev/)
* [http://fkelly.com/index.php/libevent-v2-primer/](http://fkelly.com/index.php/libevent-v2-primer/)
* [http://blog.si.kz/index.php/2010/02/03/libevent-for-php](http://blog.si.kz/index.php/2010/02/03/libevent-for-php)
* [http://maxbeutel.de/blog/2012/05/libevent-woes-in-php/](http://maxbeutel.de/blog/2012/05/libevent-woes-in-php/)
* [https://leanpub.com/php/read#leanpub-auto-network-daemons-using-libevent](https://leanpub.com/php/read#leanpub-auto-network-daemons-using-libevent)
* [http://toys.lerdorf.com/archives/57-ZeroMQ-+-libevent-in-PHP.html](http://toys.lerdorf.com/archives/57-ZeroMQ-+-libevent-in-PHP.html)
* [http://blog.gevent.org/2011/04/28/libev-and-libevent/](http://blog.gevent.org/2011/04/28/libev-and-libevent/)
* [http://scotdoyle.com/python-epoll-howto.html](http://scotdoyle.com/python-epoll-howto.html)
* [https://github.com/danielmunro/beehive](https://github.com/danielmunro/beehive)
* [https://github.com/fhoenig/Kellner](https://github.com/fhoenig/Kellner)
* [https://github.com/omgnull/php-libevent](https://github.com/omgnull/php-libevent)
* [https://github.com/flashmob/Guerrilla-SMTPd](https://github.com/flashmob/Guerrilla-SMTPd)
* [https://github.com/ThomasWeinert/carica-io](https://github.com/ThomasWeinert/carica-io)
* [https://github.com/vedantk/evt-server](https://github.com/vedantk/evt-server)
* [https://github.com/fkelly/usmq](https://github.com/fkelly/usmq)
* [https://github.com/Anizoptera/AzaLibEvent](https://github.com/Anizoptera/AzaLibEvent)
* [https://github.com/amphp/amp](https://github.com/amphp/amp)


