---
layout: post
title: "Elasticsearch - reindexing"
description: "Słynny cytat autorstwa Benjamina Franklina (Na tym świecie pewne są tylko śmierć i podatki) bardzo dobrze oddaje charakter otaczającego nas świata..."
headline: 
modified: 2015-12-10
category: elasticsearch
tags: [elasticsearch, search, reindex, apache lucene]
comments: true
featured: false
---

Słynny cytat autorstwa Benjamina Franklina *Na tym świecie pewne są tylko śmierć i podatki* bardzo dobrze oddaje charakter otaczającego nas świata. Niczego nie możemy być pewni, wszystko podlega zmianom - pewne kwestie szybciej, inne wolniej. Podobnie przedstawia się sytuacja w świecie software'u. Na tym polu wymagania także zmieniają się bardzo szybko, co więcej oczekujemy dużej elastyczności i szybkiej adaptacji do zmian. W poprzednim [artykule](http://tswiackiewicz.github.io/inside-the-source-code/elasticsearch/elasticsearch-lets-talk-about-mapping/) poświęconym **Elasticsearch'owi** wspomniałem, że pomimo tego, iż określany jest mianem *schemaless database*, to i tak wymaga zdefiniowania pewnej organizacji danych, która...podlega zmianom.
     
*Elasticsearch* został zaprojektowany i zaoptymalizwany, przede wszystkim, pod kątem bardzo małych opóźnień i bardzo krótkich czasów odpowiedzi / wyszukiwań. Cel ten udało się osiągnąć m.in. poprzez dopasowanie struktury, a więc definicji zbioru do danego przypadku. Wadą takiego podejścia jest to, że raz określony w ten sposób *mapping* danych w indeksie *NIE* może być zmieniony. Wprowadzanie zmian, a jak zostało to przedstawione powyżej, nie jest rzadką sytuacją, wymaga **przeindeksowania** dokumentów.
      
W ogólności, *reindexing* będzie miał miejsce w następujących sytuacjach:
      
- zmiana *mappingu*
- zmiana liczby *shard'ów*
- podział (*ang. split*) indeksu na mniejsze zbiory
- zmiana kluczowych parametrów konfiguracji klastra (*ang. cluster*), węzła (*ang. node*) itp. 

### Zero downtime

Dobrze wiemy, że nie możemy sobie pozwolić na przerwę w działaniu usługi, a więc opcja zmiany *mappingu* bądź konfiguracji i zaindeksowania wszystkiego od nowa nie wchodzi w grę - proces taki może, w szczególnych przypadkach, trwać bardzo długo.

W oficjalnym [blogu](https://www.elastic.co/blog/changing-mapping-with-zero-downtime) oraz innych [źródłach](https://blog.codecentric.de/en/2014/09/elasticsearch-zero-downtime-reindexing-problems-solutions/) proponowane jest rozwiązanie oparte o *alias'y*. W dużym skrócie sprowadza się to następujących kroków:

I. założenia aliasów na istniejącym indeksie (*my_index_v1*),

``` json
curl -XPOST "http://localhost:9200/_aliases" -d '
{
    "actions": [
        { "add": { "index": "my_index_v1", "alias": "read_index" }},
        { "add": { "index": "my_index_v1", "alias": "write_index" }}
    ]
}
'
```

II. zmiany aplikacji, tak aby korzystała z aliasów (*read_index*) oraz (*write_index*) zamiast bezpośrednio z nazwy indeksu

III. założenie nowego indeksu z nową konfiguracją (bądź mappingiem)

IV. dodanie nowego indeksu (*my_index_v2*) do aliasu z odczytem (*read_index*), podczas zapytań wykorzystywane będą dane z obu indeksów (*my_index_v1*, *my_index_v2*)
    
``` json
curl -XPOST "http://localhost:9200/_aliases" -d '
{
    "actions": [
        { "add": { "index": "my_index_v2", "alias": "read_index" }}
    ]
}
'
```
    
V. przepięcie aliasu z zapisem (*write_index*) na nowy indeks    

``` json
curl -XPOST "http://localhost:9200/_aliases" -d '
{
    "actions": [
        { "remove": { "index": "my_index_v1", "alias": "write_index" }},
        { "add":    { "index": "my_index_v2", "alias": "write_index" }}
    ]
}
'
```

VI. migracja historycznych danych z *my_index_v1* do *my_index_v2*

VII. odpięcie *my_index_v1* od aliasu z odczytem bądź przepięcie aplikacji, aby korzystała bezpośrednio z indeksu *my_index_v2*

Po kroku V, dane będą indeksowane do nowej struktury (*my_index_v2*), natomiast odczyt (wyszukiwanie) będzie realizowany w oparciu o dwa indeksy (stary *my_index_v1* oraz nowy *my_index_v2*). Nadal jednak pozostaje kwestia migracji danych ze starego zbioru do nowego. 

### Scan & scroll

W najprostszym podejściu dane z indeksu źródłowego będą przerzucane jeden po drugim (bądź paczkami) do nowej struktury danych. Dla małych indeksów nie będzie z tym większego problemu, natomiast dla tych o dużym wolumenie dokumetów, dalekie *offsety* będą skutkowały opóźnieniami i wydłużaniem całego procesu indeksowania.
 
Na szczęście, twórcy *Elasticsearch'a* przewidzieli taką sytuację i dodana została funkcjonalność określana jako **scan & scroll** w swoim działaniu bardzo zbliżona do kursorów znanych z baz danych (niemniej jednak z pewnymi różnicami, po szczegóły odsyłam do [dokumentacji](https://www.elastic.co/guide/en/elasticsearch/guide/current/scan-scroll.html)). Rozwiązanie jest bardzo wydajne i w znacznym stopniu pozwala skrócić czas indeksownia. 
    
Alternatywą dla opisanego powyżej mechanizmu pozostaje nadal initial feed z bazy danych bądź innego storage'u. Pozostaje pytanie: które rozwiązanie będzie wydajniejsze i będzie wymagało mniej zasobów?    

### Dostępne rozwiązania

Bez względu na to jaką strategię przeindeksowania i zapewnienia ciągłości aplikacji wybierzemy, nie unikniemy problemu związanego z migracją danych. Zajawione powyżej rozwiązanie *scan & scroll* czy też *initial feed* będzie wymagało od nas pewnej pracy, aby osiągnąć cel. Dlaczego zatem, nie skorzystać z istniejących rozwiązań umożliwiających *reindexing* z jednego zbioru do drugiego?
 
Po przeprowadzonym *research'u* mogę zarekomendować następujące rozwiązania:
  
* [https://github.com/codelibs/elasticsearch-reindexing](https://github.com/codelibs/elasticsearch-reindexing)
* [https://github.com/karussell/elasticsearch-reindex](https://github.com/karussell/elasticsearch-reindex)
* [https://github.com/allegro/elasticsearch-reindex-tool](https://github.com/allegro/elasticsearch-reindex-tool)
* [https://www.npmjs.com/package/elasticsearch-reindex](https://www.npmjs.com/package/elasticsearch-reindex)  
* [https://github.com/elastic/stream2es](https://github.com/elastic/stream2es)
* [https://github.com/martiis/elasticsearch-reindexer](https://github.com/martiis/elasticsearch-reindexer)

Szczególnie dwa pierwsze rozwiązania są godne uwagi, zwłaszcza iż dostępne są z poziomu *REST API*.

Słowo podsumowania: nie unikniemy zmian wymagań i oczekiwań względem naszej aplikacji. Prawdopodobnie za pierwszym razem nie ustalimy właściwego *mappingu* oraz nie przewidzimy wszystkich aspektów w jakich dane będą przeglądane, wyszukiwanie oraz analizowane. W związku z tym, czasami będą wymagały zmodyfikowania *schema'y* indeksu oraz migracji danych pomiędzy starą oraz nową strukturą. Dlatego tak ważny jest wybór odpowiedniej strategii przeindeksowania, aby zmiany mogłby być wykonywane odpowiednio często. 


Przydatne linki:

* [https://www.elastic.co/guide/en/elasticsearch/guide/current/reindex.html](https://www.elastic.co/guide/en/elasticsearch/guide/current/reindex.html)
* [http://david.pilato.fr/blog/2015/05/20/reindex-elasticsearch-with-logstash/](http://david.pilato.fr/blog/2015/05/20/reindex-elasticsearch-with-logstash/)
* [https://blog.codecentric.de/en/2014/09/elasticsearch-zero-downtime-reindexing-problems-solutions/](https://blog.codecentric.de/en/2014/09/elasticsearch-zero-downtime-reindexing-problems-solutions/)
* [https://www.elastic.co/blog/changing-mapping-with-zero-downtime](https://www.elastic.co/blog/changing-mapping-with-zero-downtime)
* [http://blog.sematext.com/2015/05/04/recipe-reindexing-elasticsearch-documents-with-logstash/](http://blog.sematext.com/2015/05/04/recipe-reindexing-elasticsearch-documents-with-logstash/)
* [http://allegro.tech/2015/05/elasticsearch-reindex-tool.html](http://allegro.tech/2015/05/elasticsearch-reindex-tool.html)
* [https://github.com/geronime/es-reindex](https://github.com/geronime/es-reindex)
