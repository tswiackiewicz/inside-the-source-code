---
layout: post
title: "PHP Daemons - część II"
description: "Rozwiązanie przedstawione w jednym z poprzednich artykułów pozwala na realizację daemona w PHP dzięki wykorzystaniu mechanizmu forkowania procesów. Niestety podatne jest na powstawanie tzw. procesów zombie - proces dziecko staje się procesem zombie dopóki proces główny..."
headline: 
modified: 2014-09-21
category: php
tags: [php, daemons, fork, multitasking, pcntl, signals, sockets, zombie process]
comments: true
featured: false
---

Rozwiązanie przedstawione w jednym z poprzednich [artykułów]({{ site.url }}/php/php-daemons-czesc-i/) pozwala na realizację *daemona* w **PHP** dzięki wykorzystaniu mechanizmu *forkowania* procesów. Niestety podatne jest na powstawanie tzw. **procesów zombie** - proces dziecko staje się procesem zombie dopóki proces główny nie obsłuży jego statusu wyjścia (*ang. exit code*) bądź sam (proces główny) nie zakończy działania. W efekcie, przez procesy zombie, zajmowane są *sloty* w tablicy procesów (*ang. process table*) co w kontekście długiego działania daemona będzie stanowiło problem.

### *SIGCHLD*, *SIG_IGN*

Omawiana powyżej implementacja daemona pozwala na powstawanie procesów zombie, ale również obsługuje takie procesy - w momencie nadejścia nowego połączenia z daemonem wszystkie procesy zombie oczekujące na obsługę tj. połączenia które zrealizowały swoje zadanie bądź zakończyły swoje działanie z powodu *timeoutu*, zostaną zakończone. Niestety, w okresach kiedy nie są obsługiwane żadne połączenia istnieje możliwość występowania dużej liczby procesów zombie. Rozwiązaniem tego problemu może być ignorowanie sygnałów **SIGCHLD** przez proces główny. Wówczas procesy dzieci będą umierały natychmiast, bez oczekiwania na obsługę statusu wyjścia przez proces rodzica.

``` php
public function __construct($host = '0.0.0.0', $port = 1234)
{
    // inicjacja daemona...

    // ignorujemy SIGCHLD przez proces parenta aby zapobiec procesom "zombie"
    // dodatkowo podlaczamy handler dla SIGUSR2 wysylanego przez proces childa
    // w momencie konczenia dzialania - obsluga licznika aktywnych polaczen
    pcntl_signal(SIGCHLD, SIG_IGN);
    pcntl_signal(SIGUSR2, [$this, "handleClosedConnection"]);

    // obsluga przychodzacych sygnalow, ograniczamy sie do SIGINT / SIGTERM (ubicie procesu daemona)
    pcntl_signal(SIGINT, [$this, "handleSignals"], false);
    pcntl_signal(SIGTERM, [$this, "handleSignals"], false);
} 
```

### *SIGUSR2*

Jednym z wymagań stawianych procesowi głównemu jest kontrola liczby połączeń - chcemy zapobiegać nadmiernemu tworzeniu procesów w systemie. W poprzednim rozwiązaniu kontrolowane było to poprzez listę aktywnych procesów dzieci (oraz ich identyfikatorów *PID*). Możliwe było to dzięki obsłudze *SIGCHLD*, gdzie dokładnie wiedzieliśmy który proces kończy swoje działanie. Z kolei w omawianym tutaj rozwiązaniu, zapobiegającym powstawaniu procesów zombie, nie znamy identyfikatora procesu kończącego działanie, ponieważ nie obsługujemy *SIGCHLD*. Jednak funkcjonalność tą zrealizujemy w inny sposób - proces główny będzie kontrolował liczbę aktywnych procesów zwiększając ten licznik w momencie utworzenia nowego procesu do obsługi połączenia oraz zmniejszał go obsługując **SIGUSR2** (procesy dzieci umierając będą wysyłały ten sygnał do procesu głównego). Obsługa sygnałów *SIGUSR2* również będzie następowała w momencie nadejścia nowego połączenia, ale nie będzie to stanowiło problemu gdyż zanim zostanie utworzony nowy proces, licznik aktywnych procesów zostanie zaktualizowany, a tym samym będziemy wiedzieli czy możemy obsłużyć nowe połączenie.

``` php
public function __construct($host = '0.0.0.0', $port = 1234)
{
    // inicjacja daemona...

    pcntl_signal(SIGCHLD, SIG_IGN);
    pcntl_signal(SIGUSR2, [$this, "handleClosedConnection"]);

    // deklaracja obslugi pozostalych sygnalow...
}

public function handleClosedConnection($sigNo)
{
    // zmniejszamy licznik aktywnych polaczen (tylko proces parenta)
    if (SIGUSR2 == $sigNo and posix_getpid() == $this->parent and $this->connections > 0) {
        $this->connections--;
    }

    return true;
}

protected function closeConnection()
{
    if (null != $this->connection and $this->connection instanceof DaemonConnection) {
        $this->connection->close();
    }

    // wysylamy do parenta SIGUSR2 zeby zmniejszyc licznik aktywnych polaczen,
    // obsluga sygnalow SIGUSR2 - handleClosedConnection()
    posix_kill($this->parent, SIGUSR2);
    
    return true;
} 
```

### *SIGINT*, *SIGTERM*

Do rozwiązania pozostaje jeszcze jedna kwestia - proces główny umierając, powinien także ubijać aktywne procesy dzieci. Ponieważ nie przechowujemy listy identyfikatorów aktywnych procesów przez proces główny daemona, nie możemy ubijać poszczególnych procesów wysyłając *SIGINT / SIGTERM* do poszczególnych procesów dzieci. Zamiast tego, proces parenta umierając będzie wysyłał *SIGINT / SIGTERM* do wszystkich procesów o tym samym identyfikatorze grupy procesów co proces główny. Poszczególne procesy dzieci nie zostaną do końca odłączone od procesu rodzica (nie będą stawały się liderami sesji), a więc tym samym będą posiadały ten sam identyfikator grupy procesów.

``` php
public function handleSignals($sigNo)
{
    // obsluge sygnalow ograniczamy do SIGINT / SIGTERM (ubicie procesu daemona badz childa)
    if (SIGTERM == $pSigNo or SIGINT == $pSigNo) {
        // ubijajac proces parenta (daemona) dodatkowo ubijamy procesy dzieci (tym samym sygnalem)
        // w przypadku procesu dziecka konczymy dzialanie oraz wysylamy SIGUSR2 do parenta
        if (posix_getpid() == $this->parent) {
            // wysylamy SIGINT / SIGTERM do wszystkich procesow z grupy biezacego procesu (parenta)
            posix_kill(0, $pSigNo);
        } else {
            // konczymy dzialanie procesu, wysylamy SIGUSR2 do parenta
            $this->closeConnection();
        }

       exit(0);
    }

    return true;
} 
```

Przedstawione powyżej rozwiązanie stanowi tylko jedno z możliwych rozwiązań problemów zombie. Zakres opisanych tutaj zmian jest niewielki względem oryginalnego [rozwiązania]({{ site.url }}/php/php-daemons-czesc-i/), a więc będzie prosty do wprowadzenia. Docelowe rozwiązanie i tak będzie zależało od wymagań stawianemu Waszemu *daemonowi*, niemniej moje rozwiązanie może stanowić dobrą bazę do dalszych prac.

Kompletny kod znajdziecie [tutaj](https://github.com/tswiackiewicz/SimpleForkDaemonNoZombie).

Przydatne linki:

* [http://en.wikipedia.org/wiki/Zombie_process](http://en.wikipedia.org/wiki/Zombie_process)
* [http://pleac.sourceforge.net/pleac_php/processmanagementetc.html](http://pleac.sourceforge.net/pleac_php/processmanagementetc.html)
* [http://php.net/manual/en/function.pcntl-fork.php](http://php.net/manual/en/function.pcntl-fork.php)
* [http://fixunix.com/unix/533215-how-avoid-zombie-processes.html](http://fixunix.com/unix/533215-how-avoid-zombie-processes.html)
* [http://stackoverflow.com/questions/9976441/terminating-zombie-child-processes-forked-from-socket-server](http://stackoverflow.com/questions/9976441/terminating-zombie-child-processes-forked-from-socket-server)
* [http://stackoverflow.com/questions/16238510/pcntl-fork-results-in-defunct-parent-process](http://stackoverflow.com/questions/16238510/pcntl-fork-results-in-defunct-parent-process)
* [http://unix.stackexchange.com/questions/11172/how-can-i-kill-a-defunct-process-whose-parent-is-init](http://unix.stackexchange.com/questions/11172/how-can-i-kill-a-defunct-process-whose-parent-is-init)
* [http://lubutu.com/code/spawning-in-unix](http://lubutu.com/code/spawning-in-unix)



