<?php

/**
 * Polaczenie z daemonem
 *
 * @author thejoyboy    thejoyboy ( at ) wp.pl
 */
class DaemonConnection
{
    /**
     * Unikalny identyfikator polaczenia
     * @var long
     */
    private $id;
    
    /**
     * Socket wykorzystywany do komunikacji
     * @var resource (Socket)
     */
    private $sock;
    
    /**
     * Czas (timestamp) nawiazania polaczenia
     * @var long
     */
    private $time;
    
    /**
     * Monitorowane zdarzenie
     * @var resource (buffered event)
     */
    private $eventBuffer = null;
    
    /**
     * Bufor wejsciowy
     * @var string
     */
    private $readBuffer = '';
    
    
    public function __construct($pId, $pSock = null)
    {
        $this->id    = $pId;
        $this->sock  = $pSock;
        $this->time  = time();
    }
    
    public function __destruct()
    {
        $this->close();
    }
    
    
    public function getId()
    {
        return $this->id;
    }
    
    public function setEventBuffer($pBuffer)
    {
        $this->eventBuffer = $pBuffer;
    }
    
    public function getEventBuffer()
    {
        return $this->eventBuffer;
    }
    
    public function getReadBuffer()
    {
        return $this->readBuffer;
    }
    
    public function setReadBuffer($pBuffer)
    {
        $this->readBuffer = $pBuffer;
    }
    
    /**
     * Wczytanie zawartosci z podanego bufora badz bufora zdarzenia
     * @param resource $pBuffer                bufor z ktorego wczytujemy dane (opcjonalny)
     * @return long                            liczba wczytanych bajtow
     */
    public function read($pBuffer = null)
    {
        if (null === $pBuffer)
        {
            $pBuffer = $this->eventBuffer;
        }
    
        $length = 0;
        while ($buf = event_buffer_read($pBuffer, 1024))
        {
            $this->readBuffer .= $buf;
            $length += strlen($buf);
        }
    
        return $length;
    }
    
    /**
     * Wyslanie podanej wiadomosci do podlaczonego klienta
     * @param string $pMessage                 wiadomosc do wyslania
     * @return boolean                         czy wiadomosc zostala wyslana
     */
    public function send($pMessage)
    {
        if (strlen($pMessage) > 1)
        {
            if ("\r\n" !== substr($pMessage, -2))
            {
                $pMessage .= "\r\n";
            }
    
            event_buffer_write($this->eventBuffer, $pMessage, strlen($pMessage));
            $this->flush();
        }
    
        return true;
    }
    
    /**
     * Wyczyszczenie zawartosci bufora wyjsciowego
     * @return boolean                         czy wyczyszczono bufor wyjsciowy
     */
    public function flush()
    {
        $this->readBuffer      = '';
    
        return true;
    }
    
    /**
     * Zamkniecie polaczenia
     * @return boolean                         czy zamknieto polaczenie
     */
    public function close()
    {
        if (is_resource($this->eventBuffer) and 'buffer event' == get_resource_type($this->eventBuffer))
        {
            event_buffer_disable($this->eventBuffer, EV_READ | EV_WRITE);
            event_buffer_free($this->eventBuffer);
        }
    
        if (is_resource($this->sock))
        {
            fclose($this->sock);
        }
    
        return true;
    }
}

/**
 * Daemon obslugujacy komunikacje z podlaczonymi klientami
 * Obsluga wielu polaczen realizowana jest dzieki wykorzystaniu biblioteki libevent
 *
 * Daemon kontroluje liczbe nawiazanych polaczen, jesli zostanie ona przekroczona 
 * (AbstractDaemon::$maxConnections) przyjmowanie nowych polaczen zostanie wstrzymane
 *
 * Domyslnie ustawiony zostal timeout = 10 sek dla obslugi poszczegolnych zdarzen
 * Ustawienie wartosci 0 spowoduje wylaczenie timeoutow
 *
 * @see    http://php.net/manual/en/book.libevent.php 
 * @author thejoyboy    thejoyboy ( at ) wp.pl
 */
abstract class AbstractDaemon
{
    /**
     * Socket wykorzystywny do komunikacji
     * @var resource (Socket)
     */
    protected $sock;
    
    /**
     * Adres hosta serwera, np. 0.0.0.0
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
     * Lista aktywnych polaczen
     * @var array
     */
    protected $connections = array();
    
    
    public function __construct($pHost = '0.0.0.0', $pPort = 1234)
    {
        if ( !extension_loaded('sockets'))
        {
            trigger_error(__METHOD__ . '(): The sockets extension is not loaded', E_USER_ERROR);
        }
        if ( !extension_loaded('libevent'))
        {
            trigger_error(__METHOD__ . '(): The libevent extension is not loaded', E_USER_ERROR);
        }
    
        $this->host = $pHost;
        $this->port = $pPort;
    }
    
    public function __destruct()
    {
        if (is_resource($this->sock))
        {
            fclose($this->sock);
        }
    }
    
    
    /**
     * Logowanie wiadomosci
     * @param string $pMsg                         logowana wiadomosc
     */
    abstract protected function log($pMsg);
    
    /**
     * Obsluga przychodzacych polaczen
     */
    final public function listen()
    {
        $this->sock = stream_socket_server('tcp://' . $this->host . ':' . $this->port, $errno, $errstr);
        stream_set_blocking($this->sock, 0);
    
        $this->log("Listening on " . $this->host . ":" . $this->port . "...");
    
        $base  = event_base_new();
        $event = event_new();
    
        event_set($event, $this->sock, EV_READ | EV_PERSIST, [$this, 'onConnection'], $base);
        event_base_set($event, $base);
        event_add($event);
        event_base_loop($base);
    }
    
    /**
     * Obsluga nowych polaczen
     * @param resource (stream) $pSock             socket wykorzystywany do komunikacji
     * @param int $pFlag                           flaga okreslajaca zdarzenie 
     *                                             {EV_TIMEOUT, EV_SIGNAL, EV_READ, EV_WRITE, EV_PERSIST}
     * @param resource (event base) $pBase         zdarzenie bazowe
     * @return boolean                             czy obsluzono nowe polaczenie
     */
    private function onConnection($pSock, $pFlag, $pBase)
    {
        // jesli za duzo polaczen, czekamy az beda jakies dostepne
        if (count($this->connections) == $this->maxConnections)
        {
            return false;
        }
    
        // unikalny identyfikator polaczenia
        static $connection_id = 0;
        $connection_id++;
    
        $sock = stream_socket_accept($pSock);
        stream_set_blocking($sock, 0);
    
        $buffer = event_buffer_new(
                $sock, 
                [$this, 'onRead'], 
                null, 
                [$this, 'onError'], 
                $connection_id
        );
        event_buffer_base_set($buffer, $pBase);
        if ($this->timeout > 0)
        {
            event_buffer_timeout_set($buffer, $this->timeout, $this->timeout);
        }
        event_buffer_watermark_set($buffer, EV_READ, 0, 0xffffff);
        event_buffer_enable($buffer, EV_READ | EV_PERSIST);
        
        $connection = new DaemonConnection($connection_id, $sock);
        $connection->setEventBuffer($buffer);
        $this->setConnection($connection);
        
        $this->log("New connection (id = " . $connection_id . ")...");
        
        return true;
    }
    
    /**
     * Obsluga zdarzen typu "read"
     * Zawartosc wejscia wczytywana jest do bufora obslugiwanego polaczenie
     * @param resource (buffer event) $pBuffer     obslugiwane zdarzenie
     * @param long $pConnId                        identyfikator polaczenia
     * @return boolean                             czy obsluzono zdarzenie read
     */
    private function onRead($pBuffer, $pConnId)
    {
        // wczytujemy wiadomosc od polaczonego klienta do bufora polaczenia
        $connection = $this->getConnectionById($pConnId);
        if ( !is_null($connection))
        {
            $connection->read($pBuffer);
            $this->setConnection($connection);
            
            $this->doWork($pConnId);
        }
        
        return true;
    }
    
    /**
     * Obsluga bledow zdarzen
     * @param resource (buffer event) $pBuffer     obslugiwane zdarzenie
     * @param long $pConnId                        identyfikator polaczenia
     * @return boolean                             czy obsluzono zdarzenie write
     */
    private function onError($pBuffer, $pError, $pConnId)
    {
        // rozpoznajemy typ bledu
        $error = 'Unknown';
        if ($pError & EVBUFFER_EOF)
        {
            $error = 'EVBUFFER_EOF';
        }
        else if ($pError & EVBUFFER_ERROR)
        {
            $error = 'EVBUFFER_ERROR';
        }
        else if ($pError & EVBUFFER_TIMEOUT)
        {
            $error = 'EVBUFFER_TIMEOUT';
        }         
        
        $this->log("Error: " . $error . " (id = " . $pConnId . ")...");

        // zamykamy polaczenie
        $this->closeConnection($pConnId);
        
        return true;
    }
    
    /**
     * Ustawienie (badz aktualizacja) polaczenia na liscie aktualnych polaczen
     * @param DaemonConnection $pConnection        polaczenie z daemonem
     */
    protected function setConnection(DaemonConnection $pConnection)
    {
        $connection_id = $pConnection->getId();
        if ( !empty($connection_id))
        {
            $this->connections[$connection_id] = $pConnection;
        }
    }

    /**
     * Zamykanie polaczenia o podanym identyfikatorze
     * @param long $pConnId                        identyfikator polaczenia
     * @return boolean                             czy zamknieto polaczenie
     */
    protected function closeConnection($pConnId)
    {
        $connection = $this->getConnectionById($pConnId);
        if ( !is_null($connection))
        {
            $connection->close();
            if (isset($this->connections[$pConnId]))
            {
                unset($this->connections[$pConnId]);
            }
        
            $this->log("Closing connection (id = " . $pConnId . ")...");
            
            return true;
        }
        
        return false;
    }
    
    /**
     * Pobranie polaczenia o podanym identyfikatorze 
     * (z puli aktywnych polaczen z daemonem)
     * @param long $pConnId                        identyfikator polaczenia
     * @return DaemonConnection                    polaczenie z daemonem
     */
    protected function getConnectionById($pConnId)
    {
        $connection = null;
        if ( !empty($pConnId) and !empty($this->connections[$pConnId]) and
        ($this->connections[$pConnId] instanceof DaemonConnection))
        {
            $connection = $this->connections[$pConnId];
        }
    
        return $connection;
    }
}

/**
 * Prosty daemon obslugujacy komunikacje z podlaczonymi klientami - pobiera wiadomosc,
 * zamienia male litery na wielkie i odsyla wiadomosc do podlaczonego klienta.
 *
 * @author thejoyboy    thejoyboy ( at ) wp.pl
 */
class SimpleEventDaemon extends AbstractDaemon
{
    /**
     * Max dlugosc wczytywanej wiadomosci
     * @var int
     */
    const MAX_SIZE = 1000;
    
    /**
     * Logowanie wiadomosci - tutaj wyswietlanie komunikatu w konsoli
     * @param string $pMsg                         logowana wiadomosc
     */
    protected function log($pMsg)
    {
        printf("[" . date("Y-m-d H:i:s") . "] %s\n", $pMsg);
    }
    
    /**
     * Wczytanie zawartosci bufora danego polaczenia
     * @param DaemonConnection $pConnection        polaczenie z daemonem
     * @return string                              zawartosc bufora
     */
    private function read(DaemonConnection & $pConnection)
    {
        $input     = $pConnection->getReadBuffer();
        $input_len = strlen($input);
        
        // wczytywanie danych do bufora konczy sekwencja \r\n badz
        // przekroczono max rozmiar wczytanych danych (SimpleEventDaemon::MAX_SIZE)
        if ($input_len > self::MAX_SIZE or
            ($input_len > 4 and false !== strpos($input, "\r\n", $input_len - 2)))
        {
            // pobieramy zawartosc bufora wejsciowego oraz czyscimy go
            $input = $pConnection->getReadBuffer();
            $pConnection->setReadBuffer(null);
            
            return trim($input);
        }
    
        return null;
    }
    
    /**
     * Obsluga komunikacji z klientem 
     * @param long $pConnId                        identyfikator polaczenia
     * @return boolean                             czy obsluzono zdarzenie prawidlowo 
     */
    protected function doWork($pConnId)
    {
        $connection = $this->getConnectionById($pConnId);
        if ( !is_null($connection))
        {
            $input = $this->read($connection);
            if ($input)
            {
                // po wpisaniu komendy QUIT konczymy obsluge polaczenia
                if ('QUIT' == strtoupper($input))
                {
                    $this->closeConnection($pConnId);
                }
                else
                {
                    // zamieniamy male litery na wielkie
                    $connection->send(strtoupper($input));
                    $this->setConnection($connection);
                }
            }
            
            return true;
        }
        
        return false;
    }
}

$daemon = new SimpleEventDaemon();
$daemon->listen();
