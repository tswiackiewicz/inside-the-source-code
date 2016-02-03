---
layout: post
title: "Monitorowanie zmian w systemie plików"
description: "Ogólnie przyjętym wzorcem stosowanym w różnego rodzaju aplikacjach jest logowanie zdarzeń, np. wystąpienie błędu danego typu. Następnie na podstawie zalogowanych zdarzeń podejmowane są odpowiednie akcje, które będą wywoływane co ustalony interwał czasu bądź bezpośrednio po wystąpieniu zdarzenia. W pierwszym przypadku będziemy się posiłkowali CRONem, w drugim skorzystamy z..."
headline: 
modified: 2014-09-28
category: php
tags: [php, event-driven programming, events, file system, inotify]
comments: true
featured: false
---

Ogólnie przyjętym wzorcem stosowanym w różnego rodzaju aplikacjach jest logowanie zdarzeń, np. wystąpienie błędu danego typu. Następnie na podstawie zalogowanych zdarzeń podejmowane są odpowiednie akcje, które będą wywoływane co ustalony interwał czasu bądź bezpośrednio po wystąpieniu zdarzenia. W pierwszym przypadku będziemy się posiłkowali *CRONem*, w drugim skorzystamy z wbudowanych (w OS) mechanizmów reagujących na wystąpienie zdarzenia. W systemach o dużej skali, gdzie logowanych zdarzeń może być sporo oraz zależy nam na efektywnym wykorzystaniu zasobów, skorzystamy z drugiego proponowanego tutaj rozwiązania - reagowanie na zdarzenia co ustalony interwał czasu może skutkować pustymi przebiegami. Przykładem takiej biblioteki, wspieranej przez **PHP**, może być *inotify*.

### Inotify

**Inotify** jest mechanizmem, wbudowanym w kernel Linuxowy, bazującym na paradygmacie wszystko w systemach Linuxowych jest plikami - po prostu operuje na deskryptorach plików. Umożliwia monitorowanie zmian w systemie plików i reaguje natychmiast w momencie wystąpienia zdarzenia, np. utworzenia nowego pliku. Wykorzystanie tego frameworka, z poziomu API PHP, sprowadzania się do utworzenia instancji inotify oraz podpięcia *watcher'ów* monitorujących zmiany w danych katalogach.

{% highlight php %}
public function __construct()
{
    if ( !extension_loaded('inotify')) {
        trigger_error('The inotify extension is not loaded', E_USER_ERROR);
    }

    $this->inotify = inotify_init();
    stream_set_blocking($this->inotify, 0);
}

public function add($path)
{
    // podana sciezka jest nieprawidlowa
    if (empty($path) or !file_exists($path) or !is_dir($path)) {
        return false;
    }

    // deskryptor watchera monitorujacego $pPath
    $wd = false;

    // brak instancji inotify badz nie udalo sie zainicjowac watchera dla podanej sciezki
    if ( !is_resource($this->inotify) or 
        false === ($wd = inotify_add_watch($this->inotify, $path, IN_CREATE | IN_ATTRIB))
    ) {
        return false;
    }

    $this->watchDescriptors[$wd] = $path;

    return true;
} 
{% endhighlight %}

### Monitorowane zdarzenia

Wykorzystanie powyższego fragmentu kodu umożliwia reagowanie na dwa typy zdarzeń **IN_CREATE** (utworzenie nowego pliku) oraz **IN_ATTRIB** (zmiana atrybutów pliku, np. data ostatniej modyfikacji). Oczywiście, nasz monitoring nie będzie sprowadzał się wyłącznie do takich zdarzeń, dlatego też w razie potrzeby możemy dodać obsługę zdarzeń dowolnego typu. Pełną listę zdarzeń możemy znaleźć w [dokumentacji](http://php.net/manual/en/inotify.constants.php).

Kolejnym krokiem, po utworzeniu instancji *inotify* oraz zdefiniowaniu monitorowych typów zdarzeń i katalogów, jest reagowanie na te zdarzenia. Każde zarejestrowane zdarzenie w systemie plików, zwracane przez ***inotify_read()***, zawiera deskryptor podpiętego w*atcher'a* oraz dopasowaną maskę (typ zdarzenia, np. *IN_CREATE*). Na podstawie tych atrybutów możemy reagować według potrzeb, np. zwiększając licznik utworzonych plików.

{% highlight php %}
public function run()
{
    // brak instancji inotify badz nie dodano katalogow do monitorowania
    if ( !is_resource($this->inotify) or empty($this->watchDescriptors)) {
        return false;
    }

    while (true) {
        $events = inotify_read($this->inotify);

        if ( !empty($events) and is_array($events)) {
            // sprawdzamy przepelnienie bufora monitorowanych zdarzen
            $last_event = end($events);
            if (IN_Q_OVERFLOW === $last_event['mask']) {
                trigger_error('Inotify events queue overflow', E_USER_ERROR);
            }

            foreach ($events as $event) {
                // skladanie pelnej sciezki utworzonego / zmodyfikowanego pliku
                $path = '';
                if ( !empty($event['wd']) and !empty($this->watchDescriptors[$event['wd']])) {
                    $path = $this->watchDescriptors[$event['wd']];
                }

                $path .= $event['name'];

                if ($event['mask'] & IN_CREATE) {
                    $this->process('New file created: "' . $path . '"');

                    // zapamietujemy sciezke oraz czas utworzenia pliku,
                    // poniewaz podczas tworzenia nowego pliku dopasowane
                    // zostana maski IN_CREATE oraz IN_ATTRIB
                    $this->filesCreated[$path] = microtime(true);
                } elseif ($event['mask'] & IN_ATTRIB) {
                    // poniewaz podczas tworzenia pliku dopasowana zostanie rowniez maska IN_CREATE,
                    // ignorujemy takie przypadki aby nie obsluzyc jednego zdarzenia dwukrotnie
                    if (empty($this->filesCreated) or empty($this->filesCreated[$path])) {
                        $this->process('File modified: "' . $path . '"');
                    }

                    // czyscimy liste utworzonych plikow
                    // TODO: czyszczenie listy utworzonych plikow dodatkowo powinno byc
                    // realizowane co ustalony interwal czasu
                    unset($this->filesCreated[$path]);
                }
            }
        }
    }

    return true;
}
{% endhighlight %}

### Buforowanie zdarzeń

Monitorowane zmiany w systemie plików są buforowane dzięki czemu możliwa jest obsługa wielu zdarzeń równocześnie. Każdy z podłączonych watcher'ów, a więc każdy ze śledzonych katalogów, posiada własny bufor. Jeśli zdarzeń, czyli zmian w systemie plików będzie bardzo dużo bądź nie zdążymy ich obsłużyć odpowiednio szybko, może wystąpić przepełnienie bufora. Sytuacja taka sygnalizowana jest poprzez maskę ***IN_Q_OVERFLOW*** zwracaną jako jeden z atrybutów zarejestrowanych zdarzeń.

{% highlight php %}
public function run()
{
    // brak instancji inotify badz nie dodano katalogow do monitorowania
    if ( !is_resource($this->inotify) or empty($this->watchDescriptors)) {
        return false;
    }

    while (true) {
        $events = inotify_read($this->inotify);
        if ( !empty($events) and is_array($events)) {
            // sprawdzamy przepelnienie bufora monitorowanych zdarzen
            $last_event = end($events);
            if (IN_Q_OVERFLOW === $last_event['mask']) {
                trigger_error('Inotify events queue overflow', E_USER_ERROR);
            }

            // obsluga zdarzen...
        }
    }

    return true;
} 
{% endhighlight %}

Rozmiar bufora kontrolowany jest poprzez zmianę parametrów konfiguracyjnych inotify, do których możemy zaliczyć:

* **/proc/sys/fs/inotify/max_queued_events** - rozmiar bufora (osobny dla każdego z watcher'ów), jeśli zostanie przekroczony nowe zdarzenia nie będą przyjmowane ale każdorazowo zwracana będzie maska *Q_IN_OVERFLOW* (oraz wd = -1), domyślnie 16384
* **/proc/sys/fs/inotify/max_user_instances** - max liczba instancji inotify per użytkownik, domyślnie 128
* **/proc/sys/fs/inotify/max_user_watches** - max liczba watcher'ów per użytkownik, domyślnie 8192

Przedstawione powyżej rozwiązanie powala reagować na zdarzenia w aplikacji w momencie wystąpienia dzięki monitorowaniu zmian w systemie plików. Dzięki temu dostępne zasoby (CPU, pamięć, łącze itd.) wykorzystane będą efektywnie, a przy tym samo rozwiązanie jest bardzo mało obciążające dla systemu. Typy monitorowanych zdarzeń należy obsługiwać rozważnie, ponieważ jednej zmianie w systemie plików może towarzyszyć kilka zdarzeń, np. utworzeniu nowego pliku towarzyszą m.in zdarzenia typu *IN_CREATE* oraz *IN_ATTRIB*. Dodatkowo ograniczeni jesteśmy wyłącznie do systemów Linuxowych (i to nie wszystkich) oraz pojedynczego katalogu (dla poszczególnych podkatalogów trzeba zdefiniować odrębne watcher'y), ale za to proste API i małe wymagania systemowe pozwolą bardzo szybko zaadoptować to rozwiązanie w Waszych aplikacjach.

Kompletny kod omawianego w tym artykule rozwiązania dostępny jest [tutaj](https://github.com/tswiackiewicz/FileSystemEventsTracker).

Przydatne linki:

* [http://man7.org/linux/man-pages/man7/inotify.7.html](http://man7.org/linux/man-pages/man7/inotify.7.html)
* [http://ph7spot.com/musings/in-unix-everything-is-a-file](http://ph7spot.com/musings/in-unix-everything-is-a-file)
* [http://en.wikipedia.org/wiki/Everything_is_a_file](http://en.wikipedia.org/wiki/Everything_is_a_file)
* [http://www.ibm.com/developerworks/library/l-ubuntu-inotify/](http://www.ibm.com/developerworks/library/l-ubuntu-inotify/)
* [http://www.ibm.com/developerworks/library/l-inotify/](http://www.ibm.com/developerworks/library/l-inotify/)
* [http://www.linuxjournal.com/node/8478/print](http://www.linuxjournal.com/node/8478/print)
* [http://www.serverphorums.com/read.php?12,216856](http://www.serverphorums.com/read.php?12,216856)
* [http://askubuntu.com/questions/154255/how-can-i-tell-if-i-am-out-of-inotify-watches](http://askubuntu.com/questions/154255/how-can-i-tell-if-i-am-out-of-inotify-watches)
* [https://github.com/griffbrad/php-pecl-fsevents](https://github.com/griffbrad/php-pecl-fsevents)
* [http://www.opensourceforu.com/2011/04/getting-started-with-inotify/](http://www.opensourceforu.com/2011/04/getting-started-with-inotify/)
* [http://www.go4expert.com/articles/monitor-filesystem-changes-php-t29348/](http://www.go4expert.com/articles/monitor-filesystem-changes-php-t29348/)
* [http://php.net/manual/en/book.inotify.php](http://php.net/manual/en/book.inotify.php)
* [https://github.com/henrikbjorn/Lurker/blob/master/src/Lurker/Tracker/InotifyTracker.php](https://github.com/henrikbjorn/Lurker/blob/master/src/Lurker/Tracker/InotifyTracker.php)
* [https://github.com/mkraemer/react-inotify/blob/master/src/MKraemer/ReactInotify/Inotify.php](https://github.com/mkraemer/react-inotify/blob/master/src/MKraemer/ReactInotify/Inotify.php)


