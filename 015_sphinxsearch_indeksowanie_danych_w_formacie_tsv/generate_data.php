<?php

/**
 * Generowanie zrodla danych dla SphinxSearch w formacie TSV
 * Pierwsza kolumna zawiera ID dokumentu, kolejne kolumny zgodne 
 * z mappingiem indeksu tsv_test
 * @param long $pCount                         liczba generowanych dokumentow
 */
function getTsvData($pCount = 1000)
{
    $lorem_ipsum = [
	   "Lorem ipsum dolor sit amet quam. Sed eget velit. Suspendisse....",
	   "Lorem ipsum dolor sit amet interdum pellentesque sagittis lorem.",
	   "Lorem ipsum dolor sit amet ipsum dolor ac tempor risus..........",
	   "Lorem ipsum dolor sit amet ipsum. Nulla hendrerit id, lacinia...",
	   "Lorem ipsum dolor sit amet felis sollicitudin mi quis enim......"
    ];
    
    for ($i = 1; $i <= $pCount; $i++)
    {
        $row = $i . "\t";
        $row .= "Sample title-" . str_pad($i, strlen($pCount), "0", STR_PAD_LEFT) . "\t";
        $row .= $lorem_ipsum[rand(0, 4)] . "\t";
        $row .= ($pCount + $i) . "\t";
        $row .= ((0 === rand(0,1)) ? 1 : 0) . "\t";
        $row .= time() . "\t";
        $row .= ($pCount + $i) . ".000" . rand(0, 5) . "\t";
        $row .= "{\"msg\": \"sample doc-" . str_pad($i, strlen($pCount), "0", STR_PAD_LEFT) . "\"}";
        
        print "$row\n";
    }
}

/**
 * Generowanie zrodla danych dla SphinxSearch w formacie XML
 * Dane zgodne z mappingiem indeksu test_xmlpipe
 * @param long $pCount                         liczba generowanych dokumentow
 */
function getXmlData($pCount = 1000)
{
    $lorem_ipsum = [
        "Lorem ipsum dolor sit amet quam. Sed eget velit. Suspendisse....",
        "Lorem ipsum dolor sit amet interdum pellentesque sagittis lorem.",
        "Lorem ipsum dolor sit amet ipsum dolor ac tempor risus..........",
        "Lorem ipsum dolor sit amet ipsum. Nulla hendrerit id, lacinia...",
        "Lorem ipsum dolor sit amet felis sollicitudin mi quis enim......"
    ];
    
    print "<?xml version=\"1.0\" encoding=\"utf-8\"?>\n";
    print "<sphinx:docset>\n";
    
    for ($i = 1; $i <= $pCount; $i++)
    {
        $row = "<sphinx:document id=\"" . $i . "\">\n";
        $row .= "<title><![CDATA[[Sample title-" . str_pad($i, strlen($pCount), "0", STR_PAD_LEFT) . "]]></title>\n";
        $row .= "<content><![CDATA[[" . $lorem_ipsum[rand(0, 4)] . "]]></content>\n";
        $row .= "<attr1>" . ($pCount + $i) . "</attr1>\n";
        $row .= "<attr2>" . ((0 === rand(0,1)) ? 1 : 0) . "</attr2>\n";
        $row .= "<attr3>" . time() . "</attr3>\n";
        $row .= "<attr4>" . ($pCount + $i) . ".000" . rand(0, 5) . "</attr4>\n";
        $row .= "<attr5>{\"msg\": \"sample doc-" . str_pad($i, strlen($pCount), "0", STR_PAD_LEFT) . "\"}</attr5>\n";
        $row .= "</sphinx:document>";
        
        print "$row\n";
    }

    print "</sphinx:docset>\n";
}

$type  = ( !empty($argv[1]) and in_array($argv[1], ['tsv', 'xml'])) ? $argv[1] : 'tsv';
$count = !empty($argv[2]) ? $argv[2] : 100;

if ('tsv' == $type)
{
    getTsvData($count);
}
else if ('xml' == $type) 
{
    getXmlData($count);
}
?>