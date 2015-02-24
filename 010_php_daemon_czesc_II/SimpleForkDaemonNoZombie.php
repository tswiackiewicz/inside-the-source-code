<?php

/**
 * Polaczenie z daemonem
 *
 * Niektore bledy zostaly celowo wyciszone, np. socket_read(),
 * docelowo powinny zostac obsluzone
 *
 * @author thejoyboy    thejoyboy ( at ) wp.pl
 */
class DaemonConnection
{
    /**
     * Socket wykorzystywany do komunikacji
     * @var resource/Socket
     */
    protected $sock;

    /**
     * Identyfikator PID procesu obslugujacego dane polaczenie
     */
    protected $pid;


    public function __construct($pSock)
    {
        $this->sock = $pSock;
    }


    public function getPid()
    {
        return $this->pid;
    }

    public function setPid($pPid)
    {
        $this->pid = $pPid;
    }

    /**
     * Odebranie wiadomosci od podlaczonego klienta
     * @param long $pLen                       max liczba bajtow pobieranych od klienta
     * @return string                          odebrana wiadomosc
     */
    public function read($pLen = 1024)
    {
        return @socket_read($this->sock, $pLen, PHP_BINARY_READ);
    }

    /**
     * Wyslanie wiadomosci do podlaczonego klienta
     * @param string $pMsg                     wiadomosc do wyslania
     * @return long                            liczba wyslanych bajtow
     */
    public function send($pMsg)
    {
        return strlen($pMsg) > 0 ? @socket_write($this->sock, $pMsg, strlen($pMsg)) : 0;
    }

    /**
     * Zamykanie polaczenia z klientem
     * @return boolean                         czy zamknieto polaczenie
     */
    public function close()
    {
        if (is_resource($this->sock))
        {
            socket_shutdown($this->sock);
            socket_close($this->sock);
        }

        return true;
    }
}

/**
 * Daemon obslugujacy komunikacje z podlaczonymi klientami.
 * Proces glowny stanowi funkcje dispatchera - przyjmuje przychodzace polaczenie, 
 * tworzy nowy proces (forking) i przekazuje obsluge polaczenia do tego procesu.
 *
 * Komunikacja miedzy procesami realizowana jest za pomoca sygnalow (SIGINT, SIGTERM, SIGUSR2).
 *
 * Daemon zatrzymywany jest po wyslaniu sygnalu SIGINT / SIGTERM, dodatkowo ubijane sa procesy
 * dzieci (takim samym sygnalem). Proces obslugujacy dane polaczenie, konczac swoje dzialanie
 * wysyla SIGUSR2 do procesu glownego. 
 * 
 * Daemon kontroluje liczbe utworzonych procesow do obslugi polaczen, jesli zostanie ona 
 * przekroczona (AbstractDaemon::$maxConnections) przyjmowanie nowych polaczen zostanie wstrzymane.
 *
 * Domyslnie ustawiony zostal timeout = 10 sek dla polaczenia (calej sesji), 
 * ustawienie wartosci 0 spowoduje wylaczenie timeoutow
 *
 * Niektore bledy zostaly celowo wyciszone, np. socket_accept(), 
 * docelowo powinny zostac obsluzone
 *
 * @author thejoyboy    thejoyboy ( at ) wp.pl
 */
abstract class AbstractDaemon
{
    /**
     * Socket wykorzystywny do komunikacji klient-serwer
     * @var resource/Socket
     */
    protected $sock;

    /**
     * Adres hosta serwera, np. 127.0.0.1
     * @var string
     */
    protected $host;

    /**
     * Numer portu, na ktorym nasluchuje serwer
     * @var int
     */
    protected $port;
    
    /**
     * Timeout dla polaczenia
     * @var int
     */
    protected $timeout = 10;
    
    /**
     * Max liczba polaczen
     * @var int
     */
    protected $maxConnections = 100;

    /**
     * Aktualnie obslugiwane polaczenie
     * @var DaemonConnection
     */
    protected $connection = null;
    
    /**
     * Lista aktywnych polaczen
     * @var array
     */
    protected $connections = 0;

    /**
     * Identyfiaktor PID procesu glownego
     * @var long
    */
    protected $parent;


    public function __construct($pHost = '0.0.0.0', $pPort = 1234)
    {
        set_time_limit(0);
        ignore_user_abort(true);
        
        declare(ticks=1);
        
        $this->host = $pHost;
        $this->port = $pPort;

        if ( !extension_loaded('sockets'))
        {
            trigger_error(__METHOD__ . '(): The sockets extension is not loaded', E_USER_ERROR);
        }
        if ( !extension_loaded('pcntl'))
        {
            trigger_error(__METHOD__ . '(): The pcntl extension is not loaded', E_USER_ERROR);
        }
        if ( !extension_loaded('posix'))
        {
            trigger_error(__METHOD__ . '(): The posix extension is not loaded', E_USER_ERROR);
        }

        $this->parent = posix_getpid();

        // ignorujemy SIGCHLD przez proces parenta aby zapobiec procesom "zombie"
        // dodatkowo podlaczamy handler dla SIGUSR2 wysylanego przez proces childa
        // w momencie konczenia dzialania - obsluga licznika aktywnych polaczen
        pcntl_signal(SIGCHLD, SIG_IGN);
        pcntl_signal(SIGUSR2, [$this, "handleClosedConnection"]);
        
        // obsluga przychodzacych sygnalow, ograniczamy sie do SIGINT / SIGTERM (ubicie procesu daemona)
        pcntl_signal(SIGINT, [$this, "handleSignals"], false);
        pcntl_signal(SIGTERM, [$this, "handleSignals"], false);
    }


    public function getTimeout()
    {
        return $this->timeout;
    }
    
    public function setTimeout($pTimeout)
    {
        $this->timeout = $pTimeout;
    }
    
    public function getMaxConnections()
    {
        return $this->maxConnections;
    }
    
    public function setMaxConnections($pMaxConnections)
    {
        $this->maxConnections = $pMaxConnections;        
    }
        
    /**
     * Logowanie wiadomosci
     * @param string $pMsg                     logowana wiadomosc
     */
    abstract protected function log($pMsg);
    
    /**
     * Obsluga przychodzacych sygnalow, ograniczamy sie do SIGINT, SIGTERM (ubicie procesu / daemona)
     * @param int $pSigNo                      identyfikator sygnalu
     * @return boolean                         czy obsluzono sygnal poprawnie
     */
    public function handleSignals($pSigNo)
    {
        // obsluge sygnalow ograniczamy do SIGINT / SIGTERM (ubicie procesu daemona badz childa)
        if (SIGTERM == $pSigNo or SIGINT == $pSigNo)
        {
            // ubijajac proces parenta (daemona) dodatkowo ubijamy procesy dzieci (tym samym sygnalem)
            // w przypadku procesu dziecka konczymy dzialanie oraz wysylamy SIGUSR2 do parenta
            if (posix_getpid() == $this->parent)
            {
                // wysylamy SIGINT / SIGTERM do wszystkich procesow z grupy biezacego procesu (parenta)
                posix_kill(0, $pSigNo);
    
                $this->log("Daemon (PID #" . posix_getpid() . ") is shutting down...");
            }
            else
            {
                // konczymy dzialanie procesu, wysylamy SIGUSR2 do parenta
                $this->closeConnection();
            }
    
            exit(0);
        }
    
        return true;
    }
    
    /**
     * Obsluga zakonczonych polaczen
     * @param int $pSigNo                      identyfikator sygnalu (SIGUSR2)
     * @return boolean                         czy obsluzono zakonczone polaczenie poprawnie
     */
    public function handleClosedConnection($pSigNo)
    {
        // zmniejszamy licznik aktywnych polaczen (tylko proces parenta)
        if (SIGUSR2 == $pSigNo and posix_getpid() == $this->parent and $this->connections > 0)
        {
            $this->connections--;
        }
    
        return true;
    }
    
    /**
     * Obsluga timeoutow
     * @param int $pSigNo                      identyfikator sygnal (SIGALRM)
     * @return boolean                         czy obsluzono timeout poprawnie
     */
    public function handleTimeout($pSigNo)
    {
        if (SIGALRM == $pSigNo and posix_getpid() != $this->parent)
        {
            $this->closeConnection();
            exit(1);
        }
    
        return true;
    }
    
    /**
     * Utworzenie nowego polaczenia z daemonem
     * @param resource/Socket $pSock           socket wykorzystywany do komunikacji
     * @retrun DaemonConnection                polaczenie z daemonem
     */
    protected function createConnection($pSock)
    {
        return new DaemonConnection($pSock);
    }
    
    /**
     * Zamykanie polaczenia z daemonem
     * @return boolean                         czy zamknieto polaczenie
     */
    protected function closeConnection()
    {
        $this->log("Closing connection (PID #" . posix_getpid() . ")...");
    
        if (null != $this->connection and $this->connection instanceof DaemonConnection)
        {
            $this->connection->close();
        }
    
        // wysylamy do parenta SIGUSR2 zeby zmniejszyc licznik aktywnych polaczen
        // (obsluga sygnalow SIGUSR2 - AbstractDaemon::handleClosedConnection())
        posix_kill($this->parent, SIGUSR2);
    
        return true;
    }
    
    /**
     * Wstrzymanie dzialania do czasu az beda dostepne polaczenia
     * @return boolean                         czy jest dostepne polaczenie
     */
    protected function waitIfMaxConnectionsExceeded()
    {
        if (posix_getpid() == $this->parent)
        {
            // pomocnicza flaga aby nie logowac komunikatu co 1 sek
            $is_msg_logged = false;
    
            // jesli za duzo polaczen, czekamy az beda jakies dostepne
            if ($this->connections > 0 and $this->maxConnections > 1)
            {
                while ($this->connections > ($this->maxConnections - 1))
                {
                    if ( !$is_msg_logged)
                    {
                        $this->log("Too many connections (#" . ($this->maxConnections + 1) . "), waiting...");
                        $is_msg_logged = true;
                    }
                    usleep(500);
                }
            }
        }
    
        return true;
    }
    
    /**
     * Wstrzymanie dzialania do czasu zakonczenia wszystkich polaczen
     * @return boolean                         czy wszystkie polaczenia zostaly zakonczone
     */
    protected function waitIfConnectionsNotFinished()
    {
        if (posix_getpid() == $this->parent)
        {
            // pomocnicza flaga aby nie logowac komunikatu co 1 sek
            $is_msg_logged = false;
    
            // czekamy az wszystkie nawiazane polaczenia zostana zakonczone
            while ($this->connections)
            {
                if ( !$is_msg_logged)
                {
                    $this->log("Waiting for current connections (#" . $this->connections . ") to finish...");
                    $is_msg_logged = true;
                }
                usleep(500);
            }
        }
    
        return true;
    }

    /**
     * Obsluga przychodzacych polaczen
     */
    public function listen()
    {
        $this->sock = socket_create(AF_INET, SOCK_STREAM, SOL_TCP);
        socket_set_option($this->sock, SOL_SOCKET, SO_REUSEADDR, 1);
        socket_bind($this->sock, $this->host, $this->port);
        socket_listen($this->sock, $this->maxConnections);

        $this->log("Listening on " . $this->host . ":" . $this->port . "...");

        // pomocznicza flaga zapobiegajaca wyswietlaniu powtarzajacych sie komunikatow co sek
        $is_msg_logged = false;

        while (true)
        {
            // jesli za duzo polaczen, czekamy az beda jakies dostepne
            $this->waitIfMaxConnectionsExceeded();

            if (false !== ($sock = @socket_accept($this->sock)))
            {
                $this->processConnection($this->createConnection($sock));
            }
        }
        socket_close($this->sock);

        // czekamy az wszystkie aktywne polaczenia zostana zakonczone
        $this->waitIfConnectionsNotFinished();
    }

    /**
     * Wlasciwa obsluga polaczenia - realizacja zadania przez dedykowany proces
     * @return boolean                             czy wykonano zadanie
     */
    abstract protected function doWork();

    /**
     * Obsluga nowego polaczenia - utworzenie nowego procesu (za pomoca pcntl_fork)
     * Po utworzeniu procesu (przez proces glowny), delegowana do niego zostanie
     * obsluga zadania zdefiniowana w metodzie doWork(). 
     * Po wykonaniu zadania nastepuje wyjscie exit() z odpowiednim kodem 
     * (0 - success, 1 - failure, np. timeout)
     * @param DaemonConnection $pConn              obslugiwane polaczenie
     * @return boolean                             czy utworzono nowe polaczenie 
     */
    protected function processConnection(DaemonConnection $pConn)
    {
        $pid = pcntl_fork();
        if (-1 == $pid)
        {
            trigger_error(__METHOD__ . '(): Unable to fork parent process - ' . pcntl_strerror(pcntl_get_last_error()), E_USER_ERROR);
        }
        else if ($pid)
        {
            $this->connections++;
            return true;
        }

        // obsluga timeoutow
        if ($this->timeout > 0)
        {
            pcntl_signal(SIGALRM, [$this, "handleTimeout"], false);
            pcntl_alarm($this->timeout);
        }

        $this->connection = $pConn;
        $this->connection->setPid(posix_getpid());

        $this->log("New connection (PID #" . $this->connection->getPid() . ")...");

        $exit_code = 0;
        if ( !$this->doWork())
        {
            // wystapil blad podczas przetwarzania zadania
            $exit_code = 1;
        }
        
        $this->closeConnection();
        exit($exit_code);
    }
}


/**
 * Prosty daemon obslugujacy komunikacje z podlaczonymi klientami - pobiera wiadomosc,
 * zamienia male litery na wielkie i odsyla wiadomosc do podlaczonego klienta.
 * 
 * Domyslnie timeout (dla calej sesji) zastapiony zostal przez timeout bezczynnosci 
 * tj pomiedzy poszczegolnymi wiadomosciami wysylanymi przez klienta
 * 
 * @author thejoyboy    thejoyboy ( at ) wp.pl
 */
class SimpleForkDaemon extends AbstractDaemon
{
    /**
     * Max dlugosc wczytywanej wiadomosci
     * @var int
     */
    const MAX_SIZE = 1000;
    
    /**
     * Logowanie wiadomosci - tutaj wyswietlanie komunikatu w konsoli
     * @param string $pMsg                     logowana wiadomosc
     */
    protected function log($pMsg)
    {
        printf("[" . date("Y-m-d H:i:s") . "] %s\n", $pMsg);
    }
    
    /**
     * Pobranie wiadomosci od aktualnie podlaczonego klienta
     * @return string                          odebrana wiadomosc  
     */
    private function read()
    {
        // bufor wejsciowy zawierajacy wiadomosc pobrana od klienta
        $buf     = '';
        $buf_len = 0;
        
        $input = null;
        do 
        {
            // czy zakonczono wczytywanie danych do bufora
            $is_buffer_ready = false;
            
            // zawartosc bufora wczytujamy czesciami (chunks)
            $input     = $this->connection->read();
            
            $input_len = strlen($input);
            $buf_len   += $input_len;
            
            // pusty bufor badz przekroczono max dlugosc bufora
            if (null == $input or $buf_len > self::MAX_SIZE)
            {
                $is_buffer_ready = true;
            }
            
            if ( !$is_buffer_ready)
            {
                $buf .= $input;
                
                // wczytywanie do bufora konczy sekwencja \r\n
                if ($input_len > 1 and false !== strpos($input, "\r\n", $input_len - 2))
                {
                    $is_buffer_ready = true;
                }
            }
            
            // zakonczono wczytywanie do bufora
            if ($is_buffer_ready)
            {
                break;
            }
        }
        while (true);
        
        // zamknieto / zerwano polaczenie
        if (null == $input)
        {
            $this->closeConnection();
        }
        
        return trim($buf);
    }
    
    /**
     * Obsluga nowego polaczenia - pobranie komendy od klienta oraz wyslanie jej do klienta
     * w zmodyfikowanej postaci (zamiana malych liter na wielkie).
     * Komenda QUIT konczy obsluge polaczenia
     * @return boolean                         czy wykonano zadanie
     */
    protected function doWork()
    {
        while (true)
        {
            $input = $this->read();
        
            // po wpisaniu komendy QUIT konczymy obsluge polaczenia
            if ('QUIT' == strtoupper($input))
            {
                break;
            }
            else
            {
                // czyscimy poprzedni timer, ustawiamy nowy
                pcntl_alarm(0);
                pcntl_alarm($this->timeout);
        
                // zamieniamy male litery na wielkie
                $this->connection->send(strtoupper($input) . "\r\n");
            }
        }
        
        return true;
    }   

}

$daemon = new SimpleForkDaemon();
$daemon->listen();
