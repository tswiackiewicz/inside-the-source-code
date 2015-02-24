<?php

/**
 * Polaczenie z daemonem
 *
 * Niektore bledy zostaly celowo wyciszone, np. socket_read(),
 * docelowo powinny zostac obsluzone
 *
 * @author thejoyboy    thejoyboy ( at ) wp.pl
 */
abstract class AbstractDaemonConnection
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
     * @param long $pLen                           max liczba bajtow pobieranych od klienta
     * @return string                              odebrana wiadomosc
     */
    public function read($pLen = 1024)
    {
        return @socket_read($this->sock, $pLen, PHP_BINARY_READ);
    }

    /**
     * Wyslanie wiadomosci do podlaczonego klienta
     * @param string $pMsg                         wiadomosc do wyslania
     * @return long                                liczba wyslanych bajtow
     */
    public function send($pMsg)
    {
        return strlen($pMsg) > 0 ? @socket_write($this->sock, $pMsg, strlen($pMsg)) : 0;
    }

    /**
     * Zamykanie polaczenia z klientem
     * @return boolean                             czy operacja powiodla sie
     */
    public function close()
    {
        socket_shutdown($this->sock);
        socket_close($this->sock);

        return true;
    }
}

/**
 * Daemon obslugujacy komunikacje z podlaczonymi klientami.
 * Proces glowny stanowi funkcje dispatchera - przyjmuje przychodzace polaczenie, tworzy
 * nowy proces (forking) i przekazuje obsluge polaczenia do tego procesu.
 *
 * Komunikacja miedzy procesami realizowana jest za pomoca sygnalow (SIGINT, SIGTERM, SIGCHLD).
 *
 * Daemon zatrzymywany jest po wyslaniu sygnalu SIGINT / SIGTERM, dodatkowo ubijane sa procesy
 * dzieci (takim samym sygnalem). Daemon kontroluje liczbe utworzonych procesow do obslugi
 * polaczen, jesli liczba zostanie przekroczona (AbstractDaemon::$maxConnections) przyjmowanie
 * nowych polaczen zostanie wstrzymane.
 *
 * Domyslnie ustawiony zostal timeout = 10 sek dla polaczenia (calej sesji)
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
     * Timeout dla polaczenia (domyslnie 10 sek)
     * Ustawienie wartosci mniejszej niz 1 spowoduje wylaczenie timeoutow
     * @var int
     */
    protected $connectionTimeout = 10;
    
    /**
     * Max liczba polaczen
     * @var int
     */
    protected $maxConnections = 100;

    /**
     * Aktualnie obslugiwane polaczenie
     * @var AbstractDaemonConnection
     */
    protected $connection = null;

    /**
     * Lista aktywnych polaczen
     * @var array
     */
    protected $connections = array();

    /**
     * Kolejka sygnalow do przetworzenia
     * @var array
    */
    protected $signals = array();

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

        // obsluga przychodzacych sygnalow
        pcntl_signal(SIGCHLD, array($this, "handleSignals"));
        pcntl_signal(SIGTERM, array($this, "handleSignals"), false);
        pcntl_signal(SIGHUP,  array($this, "handleSignals"), false);
        pcntl_signal(SIGINT, array($this, "handleSignals"), false);
    }


    public function getConnectionTimeout()
    {
        return $this->connectionTimeout;
    }
    
    public function setConnectionTimeout($pConnectionTimeout)
    {
        $this->connectionTimeout = $pConnectionTimeout;
    }
    
    public function getMaxConnections()
    {
        return $this->getMaxConnections();
    }
    
    public function setMaxConnections($pMaxConnections)
    {
        $this->maxConnections = $pMaxConnections;        
    }
    
    
    /**
     * Logowanie wiadomosci
     * @param string $pMsg                         logowana wiadomosc
     */
    abstract protected function log($pMsg);

    /**
     * Obsluga przychodzacych sygnalow (SIGINT, SIGTERM, SIGCHLD) przez proces glowny
     * @param int $pSigNo                          numer sygnalu
     * @param long $pPid                           identyfikator PID procesu
     * @param string $pStatus                      status
     */
    public function handleSignals($pSigNo, $pPid = null, $pStatus = null)
    {
        // obsluga sygnalow tylko dla procesu parenta
        if (posix_getpid() == $this->parent)
        {
            // w przypadku syngalow SIGTERM badz SIGINT wyslanych do procesu glownego,
            // ubijamy (tym samym sygnalem) procesy dzieci
            if (SIGINT == $pSigNo or SIGTERM == $pSigNo)
            {
                if ( !empty($this->connections) and is_array($this->connections))
                {
                    // wysylamy otrzymany sygnal ($pSigNo) do procesow dzieci
                    foreach ($this->connections as $pid => $created_on)
                    {
                        posix_kill($pid, $pSigNo);

                        $exitCode = pcntl_waitpid($pid, $pStatus);
                        if (-1 != $exitCode)
                        {
                            $this->log("Closed connection (PID #" . $pid . ")");
                        }
                        unset($this->connections[$pid]);
                    }
                }

                $this->log("Daemon (PID #" . posix_getpid() . ") is shutting down...");
                exit(0);
            }
            else if (SIGCHLD == $pSigNo)
            {
                if ( !$pPid)
                {
                    $pPid = pcntl_waitpid(-1, $pStatus, WNOHANG);
                }

                while ($pPid > 0)
                {
                    if ($pPid and isset($this->connections[$pPid]))
                    {
                        $exitCode = pcntl_wexitstatus($pStatus);
                        if ($exitCode != -1)
                        {
                            $this->log("Closed connection (PID #" . $pPid. ")");
                        }
                        unset($this->connections[$pPid]);
                    }
                    else if ($pPid)
                    {
                        // proces obslugujacy polaczenie zakonczyl sie zanim proces glowny zarejestrowal ze zostal uruchomiony
                        // dodajemy go do kolejki sygnalow do przetworzenia
                        $this->signals[$pPid] = $pStatus;

                        $this->log("Adding process (PID #" . $pPid . ") to the signals queue...");
                    }
                    $pPid = pcntl_waitpid(-1, $pStatus, WNOHANG);
                }
            }
        }
    }

    /**
     * Obsluga timeoutow
     * @param int $pSigNo                          odebrany sygnal
     */
    public function handleTimeout($pSigNo)
    {
        if (SIGALRM == $pSigNo and posix_getpid() != $this->parent)
        {
            $this->connection->close();
            exit(1);
        }
    }
    
    /**
     * Utworzenie nowego polaczenia z daemonem
     * @param resource/Socket $pSock               socket wykorzystywany do komunikacji
     * @retrun AbstractDaemonConnection            instancja obiektu polaczenia z daemonem
     */
    abstract protected function createNewConnection($pSock);

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
            $is_msg_logged = false;
            while (count($this->connections) > ($this->maxConnections - 1))
            {
                if ( !$is_msg_logged)
                {
                    $this->log("Too many connections (#" . ($this->maxConnections + 1) . "), waiting...");

                    $is_msg_logged = true;
                }
                sleep(1);
            }

            if (false !== ($sock = @socket_accept($this->sock)))
            {
                $this->processConnection($this->createNewConnection($sock));
            }
        }
        socket_close($this->sock);

        // czekamy az wszystkie aktywne polaczenia zostana zakonczone
        $is_msg_logged = false;
        while (count($this->connections))
        {
            if ( !$is_msg_logged)
            {
                $this->log("Waiting for current connections (#" . count($this->connections) . ") to finish...");

                $is_msg_logged = true;
            }
            sleep(1);
        }
    }

    /**
     * Zamykanie aktualnie obslugiwanego polaczenia
     */
    protected function closeConnection()
    {
        $this->log("Closing connection (PID #" . $this->connection->getPid() . ")...");

        $this->connection->close();
        exit(0);
    }

    /**
     * Wlasciwa obsluga polaczenia - realizacja zadania przez dedykowany proces
     * @return boolean                             czy wykonano zadanie
     */
    abstract protected function doWork();

    /**
     * Obsluga nowego polaczenia
     * @param AbstractDaemonConnection $pConn      obslugiwane polaczenie
     * @return boolean                             czy udalo sie obsluzyc nowe polaczenie 
     */
    protected function processConnection(AbstractDaemonConnection $pConn)
    {
        $pid = pcntl_fork();
        if (-1 == $pid)
        {
            trigger_error(__METHOD__ . '(): Unable to fork parent process - ' . pcntl_strerror(pcntl_get_last_error()), E_USER_ERROR);
        }
        else if ($pid)
        {
            $this->connections[$pid] = microtime(true);

            if (isset($this->signals[$pid]))
            {
                $this->log("Process (PID #" . $pid . ") in the signals queue, processing it now...");

                $this->handleSignals(SIGCHLD, $pid, $this->signals[$pid]);
                unset($this->signals[$pid]);
            }

            return true;
        }

        // jesli obsluga timeoutow zostala wlaczona,
        // podpinamy handler do obslugi timeoutow oraz ustawiamy timer
        if ($this->connectionTimeout > 0)
        {
            pcntl_signal(SIGALRM, array($this, "handleTimeout"), false);
            pcntl_alarm($this->connectionTimeout);
        }

        $this->connection = $pConn;
        $this->connection->setPid(posix_getpid());

        $this->log("New connection (PID #" . $this->connection->getPid() . ")...");

        $this->doWork();

        $this->closeConnection();
        
        return true;
    }
}


/**
 * Polaczenie z daemonem
 * 
 * Docelowo zaimplementowane zostana tutaj dodatkowe metody dla obslugi
 * polaczenia, np. autoryzacja uzytkownika 
 * 
 * @author thejoyboy    thejoyboy ( at ) wp.pl
 */
class SimpleForkDaemonConnection extends AbstractDaemonConnection
{
    
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
     * Utworzenie nowego polaczenia z daemonem
     * @param resource/Socket $pSock               socket wykorzystywany do komunikacji
     * @retrun SimpleForkDaemonConnection          instancja obiektu polaczenia z daemonem
     */
    protected function createNewConnection($pSock)
    {
        return new SimpleForkDaemonConnection($pSock);
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
     * Obsluga nowego polaczenia - pobranie komend od klienta oraz wyslanie do klienta
     * zmodyfikowanej komendy (zamiana malych liter na wielkie).
     * Komenda QUIT konczy obsluge polaczenia
     * @param Connection $pConn                obslugiwane polaczenie
     */
    protected function doWork()
    {
        while (true)
        {
            $input = $this->read();
        
            // po wpisaniu komendy QUIT konczymy obsluge polaczenia
            if ('QUIT' == strtoupper($input))
            {
                $this->closeConnection();
            }
            else
            {
                // czyscimy poprzedni timer, ustawiamy nowy
                pcntl_alarm(0);
                pcntl_alarm($this->connectionTimeout);
        
                // zamieniamy male litery na wielkie
                $this->connection->send(strtoupper($input) . "\r\n");
            }
        }
        
        return true;
    }   

}

$daemon = new SimpleForkDaemon();
$daemon->listen();
