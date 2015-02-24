<?php

/**
 * Monitorowanie zmian w systemie plikow z wykorzystaniem biblioteki inotify
 *
 * @author thejoyboy    thejoyboy ( at ) wp.pl
 */
class FileSystemEventsTracker
{
    /**
     * Instancja inotify
     * @var resource/stream
     */
    protected $inotify = null;
    
    /**
     * Lista deskryptorow watcher'ow monitorujacych zmiany w katalogach
     * @var array
     */
    protected $watchDescriptors = array();
    
    /**
     * Lista nazw utworzonych plikow (dla obslugi zdarzen IN_CREATE oraz IN_ATTRIB)
     * podczas tworzenia nowego pliku
     * @var array
     */
    protected $filesCreated = array();
    
    
    public function __construct()
    {
        if ( !extension_loaded('inotify'))
        {
            trigger_error(__METHOD__ . '(): The inotify extension is not loaded', E_USER_ERROR);
        }
        
        $this->inotify = inotify_init();
        stream_set_blocking($this->inotify, 0);
    }
    
    public function __destruct()
    {
        $this->filesCreated = null;
        
        if (is_resource($this->inotify))
        {
            // usuwamy podpiete watchery
            if ( !empty($this->watchDescriptors))
            {
                foreach ($this->watchDescriptors as $wd)
                {
                    inotify_rm_watch($this->inotify, $wd);
                }
            }
            
            fclose($this->inotify);
        }
    }
    
    
    /**
     * Dodanie sciezki monitorowanego katalogu
     * Monitorowane beda zdarzenia typu IN_CREATE (utworzenie nowego pliku) oraz IN_ATTRIB 
     * (zmiana atrybutow pliku), w razie potrzeby mozna dodac obsluge zdarzen innego typu
     * @param string $pPath                    sciezka do monitorowanego katalogu
     * @return boolean                         czy dodano katalog do monitorowania zmian 
     */
    public function add($pPath)
    {
        // podana sciezka jest nieprawidlowa
        if (empty($pPath) or !file_exists($pPath) or !is_dir($pPath))
        {
            return false;
        }
        
        // deskryptor watchera monitorujacego $pPath
        $wd = false;
        
        // brak instancji inotify badz nie udalo sie zainicjowac watchera dla podanej sciezki
        if ( !is_resource($this->inotify) or false === ($wd = inotify_add_watch($this->inotify, $pPath, IN_CREATE | IN_ATTRIB)))
        {
            return false;
        }
        
        $this->watchDescriptors[$wd] = $pPath;
        
        return true;
    }
    
    /**
     * Obsluga zdarzenia, np. wyswietlenie komunikatu $pMsg na STDOUT
     * @param string $pMsg                     logowany komunikat (opcjonalny) 
     * @retrun boolean                         czy obsluzono zdarzenie prawidlowo
     */
    protected function process($pMsg = null)
    {
        if ( !empty($pMsg))
        {
            print "[" . date("Y-m-d H:i:s") . "] $pMsg\n";
        }
        
        return true;
    }
    
    /**
     * Monitorowanie zmian w katalogach dodanych za pomoca FileSystemEventsTracker::add(),
     * obslugiwane sa zdarzenia typu IN_CREATE (nowy plik) oraz IN_ATTRIB (zmiana atrybutow pliku)
     * @return boolean
     */
    public function run()
    {
        // brak instancji inotify badz nie dodano katalogow do monitorowania
        if ( !is_resource($this->inotify) or empty($this->watchDescriptors))
        {
            return false;
        }
        
        while (true)
        {
            $events = inotify_read($this->inotify);

            if ( !empty($events) and is_array($events))
            {
                // sprawdzamy przepelnienie bufora monitorowanych zdarzen
                $last_event = end($events);
                if (IN_Q_OVERFLOW === $last_event['mask'])
                {
                    trigger_error(__METHOD__ . '(): Inotify events queue overflow', E_USER_ERROR);
                }
                
                foreach ($events as $event)
                {
                    // skladanie pelnej sciezki utworzonego / zmodyfikowanego pliku
                    $path = '';
                    if ( !empty($event['wd']) and !empty($this->watchDescriptors[$event['wd']]))
                    {
                        $path = $this->watchDescriptors[$event['wd']];
                    } 
                    $path .= $event['name'];

                    if ($event['mask'] & IN_CREATE)
                    {
                        $this->process('New file created: "' . $path . '"');
                        
                        // zapamietujemy sciezke oraz czas utworzenia pliku,
                        // poniewaz podczas tworzenia nowego pliku dopasowane
                        // zostana maski IN_CREATE oraz IN_ATTRIB
                        $this->filesCreated[$path] = microtime(true);
                    }
                    else if ($event['mask'] & IN_ATTRIB)
                    {
                        // poniewaz podczas tworzenia pliku dopasowana zostanie rowniez maska IN_CREATE, 
                        // ignorujemy takie przypadki aby nie obsluzyc jednego zdarzenia dwukrotnie
                        if (empty($this->filesCreated) or empty($this->filesCreated[$path]))
                        {
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
}

$tracker = new FileSystemEventsTracker();
$tracker->add("/tmp/");
$tracker->run();
?>