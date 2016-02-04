---
layout: post
title: "PHP Daemons - część IV"
description: "W pierwszym artykule z cyklu PHP Daemons przedstawione zostało rozwiązanie oparte o mechanizm forkowania. Następnie, z uwagi na powstawanie procesów zombie, wprowadzone zostało rozwiązanie pozwalające wyeliminować ten problem. Jednak dalsze testy i eksploatacja tego daemona wykazały jego kolejną słabość..."
headline: 
modified: 2014-11-24
category: php
tags: [daemons, fifo, inter-process communication, ipc, multitasking, php, pipe, semaphore, shared memory, signals, sockets]
comments: true
featured: false
---

W pierwszym [artykule]({{ site.url }}/php/php-daemons-czesc-i/) z cyklu *PHP Daemons* przedstawione zostało rozwiązanie oparte o mechanizm *forkowania*. Następnie, z uwagi na powstawanie procesów zombie, wprowadzone zostało [rozwiązanie]({{ site.url }}/php/php-daemons-czesc-ii/) pozwalające wyeliminować ten problem. Jednak dalsze testy i eksploatacja tego *daemona* wykazały jego kolejną słabość - przy jednoczesnej obsłudze wielu połączeń, które kończą swoje działanie w tym samym czasie licznik aktywnych połączeń pomniejszany jest o jeden zamiast rzeczywistą liczbę połączeń, które zakończyły działanie w tym momencie. W efekcie, bardzo szybko może wystąpić przekroczenie max liczby połączeń (nie będziemy przyjmowali nowych połączeń), chociaż tak naprawdę będzie uruchomiony tylko jeden proces. Wspomniana sytuacja wynika z tego, że obsługa [sygnałów](http://en.wikipedia.org/wiki/Unix_signal) w systemach Unix została zaprojektowana w taki sposób, aby w danym momencie czasu, z uwagi na potencjalne wyścigi, przyjąć tylko jeden sygnał danego typu, np. *SIGUSR2*. Dlatego też konieczne jest zastosowanie innego mechanizmu do kontroli liczby aktywnych połączeń.

### IPC

Informacja o rozpoczęciu obsługi nowego połączenia bądź zakończeniu jego działania powinna zostać przekazana do procesu głównego (*ang. parent*) celem kontroli przekraczania max liczby połączeń. Zatem wymagana będzie [komunikacja między procesami](http://en.wikipedia.org/wiki/Inter-process_communication). W systemach Unixowych dostępne są następujące mechanizmy <abbr title="Inter-process communication">IPC</abbr>:

* **sygnały** (*ang. signals*)
* potoki nienazwane (*ang. pipes*)
* potoki nazwane / kolejki FIFO (*ang. First In First Out*)
* pliki i blokady (*ang. file locks*)
* kolejki wiadomości (*ang. message queues*)
* **semafory** (*ang. semaphores*)
* **pamięć współdzieloną** (*ang. shared memory*)
* **gniazdka** (*ang. sockets*)

### Shared memory

Wymienione powyżej mechanizmy obsłużone mogą być również za pomocą *PHP*. Przykładowo, *pipes* realizowane są za pomocą funkcji ***popen()*** / ***proc_open()*** / ***pclose()***, kolejki <abbr title="First In First Out">FIFO</abbr> - ***posix_mkfifo()***, locks - ***flock()*** itd. Poszczególne rozwiązania różnią się między sobą i nie każde nadaje się do obsługi danych funkcjonalności. W naszym przypadku, do implementacji globalnego licznika aktywnych połączeń, najlepszym mechanizmem będzie *pamięć współdzielona* między procesami dzieci i procesem głównym.

{% highlight php %}
public function __construct()
{
    $this->shm = shm_attach($this->getShmKey(), 512, 0666);
    $this->sem = sem_get($this->getSemKey(), 1, 0666, 1);
} 
{% endhighlight %}

**Shared memory** została wybrana, ponieważ wszystkie procesy będą działały w obrębie jednej lokalnej maszyny, dodatkowo zmiany licznika aktywnych połączeń powinny być bardzo szybkie stąd też oparcie tego mechanizmu na plikach czy gniazdkach nie będzie najlepszym wyborem. W pamięci współdzielonej przechowywana będzie liczba procesów utworzonych do obsługi poszczególnych klientów - w momencie *forkowania* (przez proces główny) licznik będzie podbijany, natomiast kończąc swoje działanie wartość ta będzie pomniejszana (przez proces dziecka).

{% highlight php %}
protected function processConnection(DaemonConnection $conn)
{
    $pid = pcntl_fork();
    if (-1 == $pid) {
        trigger_error('Unable to fork parent process - ' . pcntl_strerror(pcntl_get_last_error()), E_USER_ERROR);
    } elseif ($pid) {
        $this->connections = $this->counter->increase();
       
        return true;
    }

    // kod procesu dziecka...
}

protected function closeConnection()
{
    if (null != $this->connection and $this->connection instanceof DaemonConnection) {
        $this->connection->close();
    }

    // zmniejszamy licznik aktywnych polaczen
    $this->counter->decrease();

    return true;
} 
{% endhighlight %}

### Semafory

Szybkość pamięci współdzielonej wynika w głównej mierze z tego, oprócz faktu że realizowana jest w bezpośrednio w pamięci, iż do jej obsługi nie jest wymagana komunikacja z *kernelem*, jak to ma miejsce chociażby w przypadku potoków (*ang. pipes*). Jednak rozwiązanie to nie jest idealne - jak sugeruje sama nazwa mechanizmu, dostęp do współdzielonego obszaru pamięci będzie miało wiele procesów a więc tym samym czasie może próbować wprowadzać zmiany naszego licznika. Aby zapobiec tym wyścigom (*ang. race conditions*) konieczne będzie dodanie **synchronizacji**. Wśród dostępnych mechanizmów komunikacji między procesami dostępny jest taki mechanizm - [semafory](http://en.wikipedia.org/wiki/Semaphore_%28programming%29). Zatem schemat działania będzie następujący:

1. utworzenie nowego procesu do obsługi połączenia
2. założenie semafora - zablokowanie dostępu do wprowadzania zmian licznika
3. zwiększenie licznika
4. zwolnienie semafora - usunięcie blokady dostępu do wprowadzania zmian licznika
5. obsługa połączenia
6. założenie semafora, zmniejszenie licznika, zwolnienie semafora
7. zakończenie działania procesu dziecka

{% highlight php %}
public function get()
{
    $value = 0;

    sem_acquire($this->sem);

    $counter_key = $this->getCounterKey();
    if (is_resource($this->shm) and shm_has_var($this->shm, $counter_key)) {
        $value = (int) shm_get_var($this->shm, $counter_key);
    }
    
    sem_release($this->sem);

    return $value;
}

public function increase($value = 1)
{
    $increased_value = 0;

    sem_acquire($this->sem);

    $counter_key = $this->getCounterKey();
    if (is_resource($this->shm) and shm_has_var($this->shm, $counter_key)) {
        $value = (int) shm_get_var($this->shm, $counter_key);
    }
    
    $increased_value += $value;

    shm_put_var($this->shm, $counter_key, (int) $increased_value);
    sem_release($this->sem);

    return $value;
} 
{% endhighlight %}

Warto zwrócić uwagę jeszcze na jedną istotną kwestię. Otóż, współdzielonej pamięci nie obejmuje mechanizm *reference counter* wykorzystywany przez PHPowy <abbr title="Garbage collector">GC</abbr>. Oznacza to, iż kończąc działanie daemona nie zostanie zwolniona pamięć zarezerwowana dla segmentu shared memory. W związku z tym konieczne jest ręczne zwolnienie tej pamięci, w przeciwnym razie będzie ona zajęta do czasu restartu maszyny.

{% highlight php %}
public function cleanUp()
{
    if (is_resource($this->shm)) {
        shm_remove($this->shm);
        $this->shm = null;
    }
} 
{% endhighlight %}

Wprowadzenie licznika opartego o współdzieloną pamięć nieco zwolni szybkość działania naszego daemona z uwagi na konieczność *synchronizacji* dostępu opartej o **semafory**. Niemniej, dzięki temu, nasz daemon będzie odporny na problemy opisane powyżej (nieprawidłowe zmniejszanie licznika w przypadku równoczesnego kończenia pracy przez wiele połączeń) a oparta na tym liczniku kontrola max liczby połączeń zabezpieczy nas przed zbytnim obciążeniem maszyny. Jeśli uzyskana w ten sposób wydajność daemona będzie za mała, będziemy mogli skalować się dokładając kolejne node'y do naszego clustra. Ciekawą alternatywą dla zaproponowanego tutaj licznika aktywnych połączeń opartego o shared memory może być <abbr title="Alternative PHP Cache">APC</abbr>. Jednak, przynajmniej według mnie, bezpośrednia kontrola nad tym co dokładnie dzieje się w aplikacji jest lepszym rozwiązaniem a koniec końców APC i tak działa w oparciu o shared memory bądź *mmap* (w zależności od konfiguracji). Inne godne rozważenia alternatywy to rozwiązania oparte o */dev/shm*, *php://memory* czy też *tmpfs*. Zachęcam do eksperymentów, korzystania z proponowanego przeze mnie rozwiązania no i zgłaszania własnych uwag i wniosków.

Kompletny kod proponowanego rozwiązania znajdziecie [tutaj](https://github.com/tswiackiewicz/SimpleForkDaemonSharedMemoryCounter).

Przydatne linki:

* [http://www.linux-tutorial.info/modules.php?name=MContent&pageid=289](http://www.linux-tutorial.info/modules.php?name=MContent&pageid=289)
* [http://www.makelinux.net/books/lkd2/ch03lev1sec1](http://www.makelinux.net/books/lkd2/ch03lev1sec1)
* [http://linuxgazette.net/133/saha.html](http://linuxgazette.net/133/saha.html)
* [http://www.cis.temple.edu/~ingargio/cis307/readings/signals.html](http://www.cis.temple.edu/~ingargio/cis307/readings/signals.html)
* [http://cs-pub.bu.edu/fac/richwest/cs591_w1/notes/wk3_pt2.PDF](http://cs-pub.bu.edu/fac/richwest/cs591_w1/notes/wk3_pt2.PDF)
* [http://andrey.hristov.com/projects/php_stuff/pres/writing_parallel_apps_with_PHP.pdf](http://andrey.hristov.com/projects/php_stuff/pres/writing_parallel_apps_with_PHP.pdf)
* [http://linux.die.net/man/5/ipc](http://linux.die.net/man/5/ipc)
* [http://beej.us/guide/bgipc/output/html/singlepage/bgipc.html](http://beej.us/guide/bgipc/output/html/singlepage/bgipc.html)
* [http://tldp.org/LDP/lpg/node7.html](http://tldp.org/LDP/lpg/node7.html)
* [http://www.amazon.com/exec/obidos/ISBN=0130810819/thevanishedgalle/](http://www.amazon.com/exec/obidos/ISBN=0130810819/thevanishedgalle/)
* [http://stackoverflow.com/questions/404604/comparing-unix-linux-ipc](http://stackoverflow.com/questions/404604/comparing-unix-linux-ipc)
* [http://linux.omnipotent.net/article.php?article_id=12504](http://linux.omnipotent.net/article.php?article_id=12504)
* [http://www.workingsoftware.com.au/page/Something_Like_Threading](http://www.workingsoftware.com.au/page/Something_Like_Threading)
* [http://www.tuxradar.com/practicalphp/16/1/9](http://www.tuxradar.com/practicalphp/16/1/9)
* [http://www.cs.cf.ac.uk/Dave/C/node27.html](http://www.cs.cf.ac.uk/Dave/C/node27.html)
* [http://www.kohala.com/start/unpv22e/unpv22e.chap12.pdf](http://www.kohala.com/start/unpv22e/unpv22e.chap12.pdf)
* [https://stereochro.me/assets/uploads/notes/dcom3/shmem.pdf](https://stereochro.me/assets/uploads/notes/dcom3/shmem.pdf)
* [http://en.wikipedia.org/wiki/Memory-mapped_file](http://en.wikipedia.org/wiki/Memory-mapped_file)
* [http://www.onlamp.com/pub/a/php/2004/05/13/shared_memory.html](http://www.onlamp.com/pub/a/php/2004/05/13/shared_memory.html)
* [http://www.ibm.com/developerworks/library/os-php-shared-memory/](http://www.ibm.com/developerworks/library/os-php-shared-memory/)
* [http://php.find-info.ru/php/016/ch10lev1sec7.html](http://php.find-info.ru/php/016/ch10lev1sec7.html)
* [http://www.re-cycledair.com/php-dark-arts-shared-memory-segments-ipc](http://www.re-cycledair.com/php-dark-arts-shared-memory-segments-ipc)
* [http://www.re-cycledair.com/php-dark-arts-semaphores](http://www.re-cycledair.com/php-dark-arts-semaphores)
* [http://stackoverflow.com/questions/8631875/what-does-the-shmop-php-extension-do](http://stackoverflow.com/questions/8631875/what-does-the-shmop-php-extension-do)
* [http://werxltd.com/wp/2010/08/23/process-forking-with-php/](http://werxltd.com/wp/2010/08/23/process-forking-with-php/)
* [http://sysmagazine.com/posts/193270/](http://sysmagazine.com/posts/193270/)
* [http://pubs.opengroup.org/onlinepubs/007908799/xsh/mmap.html](http://pubs.opengroup.org/onlinepubs/007908799/xsh/mmap.html)
* [http://msdn.microsoft.com/en-us/library/ms810613.aspx](http://msdn.microsoft.com/en-us/library/ms810613.aspx)
* [http://www.devshed.com/c/a/BrainDump/Using-mmap-for-Advanced-File-IO/](http://www.devshed.com/c/a/BrainDump/Using-mmap-for-Advanced-File-IO/)
* [https://www.kernel.org/doc/Documentation/filesystems/tmpfs.txt](https://www.kernel.org/doc/Documentation/filesystems/tmpfs.txt)
* [http://www.cyberciti.biz/tips/what-is-devshm-and-its-practical-usage.html](http://www.cyberciti.biz/tips/what-is-devshm-and-its-practical-usage.html)
* [http://stephane.lesimple.fr/blog/2006-11-03/dev-shm-is-better-than-tmp.html](http://stephane.lesimple.fr/blog/2006-11-03/dev-shm-is-better-than-tmp.html)
* [http://php.net/manual/en/function.stream-socket-pair.php](http://php.net/manual/en/function.stream-socket-pair.php)
* [http://php.net/manual/en/book.apc.php](http://php.net/manual/en/book.apc.php)
* [http://git.php.net/?p=pecl/caching/apc.git;a=blob;f=TECHNOTES.txt](http://git.php.net/?p=pecl/caching/apc.git;a=blob;f=TECHNOTES.txt)
* [https://github.com/lifo101/php-ipc](https://github.com/lifo101/php-ipc)
* [https://github.com/clue-legacy/Worker](https://github.com/clue-legacy/Worker)
* [https://github.com/klaussilveira/SimpleSHM](https://github.com/klaussilveira/SimpleSHM)
* [https://github.com/jamm/Memory](https://github.com/jamm/Memory)
* [https://github.com/nanderoo/php-memory-demo](https://github.com/nanderoo/php-memory-demo)


