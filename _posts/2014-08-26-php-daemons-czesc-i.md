---
layout: post
title: "PHP Daemons - część I"
description: "Bardzo często, w zależności od rozwiązywanego problemu, stosuje się powszechnie znane rozwiązania, np. strona web - PHP, rozwiązania klient-serwer bądź daemony - język C itd. Zaletą takiego podejścia jest fakt, iż rozwiązania te zostały zoptymalizowane i wielokrotnie przetestowane w boju. Zdarza się jednak tak, że oprogramowanie wytwarzane w naszej firmie..."
headline: 
modified: 2014-08-26
category: php
tags: [php, daemons, fork, multitasking, pcntl, signals, sockets]
comments: true
featured: false
---

Bardzo często, w zależności od rozwiązywanego problemu, stosuje się powszechnie znane rozwiązania, np. strona web - *PHP*, rozwiązania klient-serwer bądź *daemony* - język C itd. Zaletą takiego podejścia jest fakt, iż rozwiązania te zostały zoptymalizowane i wielokrotnie przetestowane w boju. Zdarza się jednak tak, że oprogramowanie wytwarzane w naszej firmie skoncentrowane jest wokół jednej (bądź grupy) technologii, np. *PHP* / *LAMP*. Poznanie i wdrożenie nowej technologii może być zbyt kosztowne czy też czasochłonne. W takiej sytuacji adaptuje się istniejące rozwiązania do naszych potrzeb. Podobnie będzie w naszym przypadku - głównym *bohaterem* tego artykułu będzie **daemon** zaimplementowany w **PHP**.

### Fork

Głównym wyzwaniem przed jakim staje taki daemon jest obsługa wielu równoległych połączeń. Niestety, język PHP nie został zaprojektowany z myślą o obsłudze wielu wątkow. Zatem, pozostaje nam skorzystać z tradycyjnej obsługi wielu zadań (*ang. multitasking*) znanej z systemów *Unix* - powielanie procesów za pomocą forka (*ang. forking*): tworzony jest nowy proces (będący duplikatem procesu głównego ale z pewnymi wyjątkami), którego przetwarzanie rozpoczyna się *w miejscu* wywołania ***pcntl_fork()***, natomiast oryginalny proces kontynuuje dalej swoje działanie. W ten sposób uzyskaliśmy dwa egzemplarze naszego oryginalnego procesu rozróżniane na podstawie identyfikatora *PID* (nowy proces otrzymuje swój indywidualny *PID*). Wracając do naszego *daemona*, proces główny (*ang. parent*) odpowiedzialny będzie za przyjmowanie przychodzących połączeń, stworzenie nowego procesu (*ang. child*) i przekazanie obsługi połączenia do tego procesu. Dzięki temu każde połączenie z daemonem obsługiwane jest przez dedykowany proces a tym samym możliwa jest realizacja wielu zadań równolegle.

``` php
protected function processConnection(AbstractDaemonConnection $conn)
{
    $pid = pcntl_fork();
    if (-1 == $pid) {
        trigger_error('Unable to fork parent process: ' . pcntl_strerror(pcntl_get_last_error()), E_USER_ERROR);
    }
    elseif ($pid) {
        
        // kod procesu rodzica...
		
        return true;
    }

    // kod procesu dziecka...
} 
```

### Sygnały

Podejście takie wymusza obsługę komunikacji między procesami. Komunikacja międzyprocesowa realizowana będzie za pomocą, znanych z systemów Unixowych, **sygnałów** (*ang. signals*). Przykładowo, proces dziecko kończąc swoje działanie wysyła sygnał *SIGCHLD*, który dalej obsługiwany jest przez proces rodzica (główny) bądź proces główny kończąc swoje działanie wysyła *SIGTERM* do procesów dzieci (kończąc ich działanie).

``` php
public function __construct($host = '0.0.0.0', $port = 1234)
{
    // inicjalizacja...
    
    $this->parent = posix_getpid();

    // obsluga przychodzacych sygnalow
    pcntl_signal(SIGCHLD, [$this, "handleSignals"]);
    pcntl_signal(SIGTERM, [$this, "handleSignals"], false);
    pcntl_signal(SIGHUP, [$this, "handleSignals"], false);
    pcntl_signal(SIGINT, [$this, "handleSignals"], false);
}

public function handleSignals($sigNo, $pid = null, $status = null)
{
    // obsluga sygnalow...
}
```

### Timeouty

Warto wzbogacić naszego daemona o obsługę *timeout'ów*, ponieważ możemy dość łatwo wyczerpać pulę dostępnych połączeń jeśli wszyscy klienci zaraz po podłączeniu do daemona zaprzestaną dalszej aktywności. Do obsługi timeout'ów również wykorzystane zostaną sygnały - *SIGALRM*.

``` php
public function handleTimeout($sigNo)
{
    if (SIGALRM == $sigNo and posix_getpid() != $this->parent) {
        $this->connection->close();
        exit(1);
    }
}

protected function processConnection(AbstractDaemonConnection $conn)
{
    // jakis kod...

    // jesli obsluga timeoutow zostala wlaczona,
    // podpinamy handler do obslugi timeoutow oraz ustawiamy timer
    if ($this->connectionTimeout > 0) {
        pcntl_signal(SIGALRM, [$this, "handleTimeout"], false);
        pcntl_alarm($this->connectionTimeout);
    }

    // jakis kod...
} 
```

Aby zapobiec nadmiernemu obciążeniu naszego systemu (poprzez niekontrolowane forkowanie procesów) bądź sytuacji znanej jako [fork bomb](http://en.wikipedia.org/wiki/Fork_bomb), konieczne jest zarządzanie pulą dostępnych połączeń. Odpowiedzialny za to będzie proces główny, który przechowuje listę aktywnych połączeń. W przypadku przekroczenia dopuszczalnej liczby połączeń, wstrzymuje przyjmowanie nowych połączeń do czasu zwolnienia puli.

### Sockets

Ostatnim elementem układanki jest zapewnienie komunikacji na linii klient-serwer. Wykorzystane zostaną tutaj **sockety** (*ang. sockets*).

``` phph
public function listen()
{
    $this->sock = socket_create(AF_INET, SOCK_STREAM, SOL_TCP);
    socket_set_option($this->sock, SOL_SOCKET, SO_REUSEADDR, 1);
    socket_bind($this->sock, $this->host, $this->port);
    socket_listen($this->sock, $this->maxConnections);

    while (true) {
        // kontrola liczby polaczen...

        if (false !== ($sock = @socket_accept($this->sock))) {
            $this->processConnection($this->createNewConnection($sock));
        }
    }

    socket_close($this->sock);
} 
```

Daemon, z definicji, jest procesem działającym w tle (*ang. background*) oraz dodatkowo raz uruchomiony powinien działać zawsze - stąd w powyższym fragmencie kodu warunek *while(true)*. Dodatkowo dobrze zaimplementowany daemon powinien zapobiegać uruchomieniu wielu instancji w danym środowisku (np. poprzez *locks*) czy też nie powinien wyświetlać komunikatów na *STDOUT* (w powyższym przykładzie jedynie w celach diagnostycznych). W omawianym tutaj przykładzie przedstawione zostały jedynie podstawowe mechanizmy umożliwiające implementację daemona w PHP, ale pewne elementy nadal wymagają pracy, np. obsługa błędów czy też proces nadrzędny restartujący daemona w przypadku błędu. Tym samym zachęcam do korzystania z mojego przykładu i dostosowania go do Waszych potrzeb.

Kompletny kod znajdziecie [tutaj](https://github.com/tswiackiewicz/SimpleForkDaemon).

Przydatne linki:

* [http://kvz.io/blog/2009/01/09/create-daemons-in-php/](http://kvz.io/blog/2009/01/09/create-daemons-in-php/)
* [http://collaboradev.com/2011/03/31/php-daemons-tutorial/](http://collaboradev.com/2011/03/31/php-daemons-tutorial/)
* [http://community.spiceworks.com/topic/500944-is-using-php-as-a-daemon-a-good-idea](http://community.spiceworks.com/topic/500944-is-using-php-as-a-daemon-a-good-idea)
* [http://devzone.zend.com/209/writing-socket-servers-in-php/](http://devzone.zend.com/209/writing-socket-servers-in-php/)
* [http://www.devshed.com/c/a/PHP/Socket-Programming-With-PHP/](http://www.devshed.com/c/a/PHP/Socket-Programming-With-PHP/)
* [http://michaelcamden.me/?p=36](http://michaelcamden.me/?p=36)
* [http://www.binarytides.com/php-socket-programming-tutorial/](http://www.binarytides.com/php-socket-programming-tutorial/)
* [http://www.mullie.eu/parallel-processing-multi-tasking-php/](http://www.mullie.eu/parallel-processing-multi-tasking-php/)
* [http://code.hootsuite.com/parallel-processing-task-distribution-with-php/](http://code.hootsuite.com/parallel-processing-task-distribution-with-php/)
* [http://www.workingsoftware.com.au/page/Something_Like_Threading](http://www.workingsoftware.com.au/page/Something_Like_Threading)
* [http://www.tuxradar.com/practicalphp/16/1/4](http://www.tuxradar.com/practicalphp/16/1/4)
* [http://pleac.sourceforge.net/pleac_php/processmanagementetc.html](http://pleac.sourceforge.net/pleac_php/processmanagementetc.html)
* [http://www.tuxradar.com/practicalphp/16/0/0](http://www.tuxradar.com/practicalphp/16/0/0)
* [https://www.sharcnet.ca/help/index.php/Signal_Handling_and_Checkpointing](https://www.sharcnet.ca/help/index.php/Signal_Handling_and_Checkpointing)
* [http://www.devshed.com/c/a/php/managing-standalone-scripts-in-php/](http://www.devshed.com/c/a/php/managing-standalone-scripts-in-php/)
* [http://sysmagazine.com/posts/179075/](http://sysmagazine.com/posts/179075/)
* [https://github.com/kakserpom/phpdaemon](https://github.com/kakserpom/phpdaemon)
* [https://github.com/shaneharter/PHP-Daemon](https://github.com/shaneharter/PHP-Daemon)
* [https://github.com/lukaszkujawa/php-multithreaded-socket-server](https://github.com/lukaszkujawa/php-multithreaded-socket-server)
* [https://leanpub.com/php/](https://leanpub.com/php/)

