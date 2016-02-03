---
layout: post
title: "SphinxSearch - indeksowanie danych w formacie tsv"
description: "Aby możliwe było wygenerowanie indeksu pozwalającego na sprawne i szybkie wyszukiwanie, konieczne jest dostarczenie danych w ustalonym formacie. W przypadku silnika wyszukiwania znanego jako SphinxSearch, podstawowym źródłem danych jest baza danych, np. MySQL. Nie zawsze jednak możliwe jest dostarczenie danych w ten sposób..."
headline: 
modified: 2015-02-06
category: sphinxsearch
tags: [benchmark, indexer, performance, SphinxSearch, tsv, tsvpipe, xmlpipe]
comments: true
featured: false
---

Aby możliwe było wygenerowanie indeksu pozwalającego na sprawne i szybkie wyszukiwanie, konieczne jest dostarczenie danych w ustalonym formacie. W przypadku silnika wyszukiwania znanego jako **SphinxSearch**, podstawowym źródłem danych jest baza danych, np. *MySQL*. Nie zawsze jednak możliwe jest dostarczenie danych w ten sposób - przykładowo indeksowane dane przechowywane są na różnych serwerach bądź źródle danych nie wspieranym przez *SphinxSearch*. W takiej sytuacji przydatna jest możliwość dostarczenia danych (dla *indeksera*) w formacie <abbr title="Extensible Markup Language">XML<abbr>. Rozwiązanie to jest bardzo elastyczne (możemy generować dane w dowolny sposób), ale niestety dużo wolniejsze niż indeksowanie bezpośrednio z bazy danych. Kompromisem może być natomiast indeksowanie danych ze źródła w formacie <abbr title="Tab-separated values">TSV<abbr> - szybsze niż to z XMLa a przy tym równie elastyczne.

### tsvpipe

Definicja źródła danych w formacie **TSV** jest bardzo podobna do tej dla formatu ***xmlpipe***. Oprócz określenia typu (***tsvpipe***), komendy za pomocą której dostarczone zostaną dane (***tsvpipe_command***) niezbędne jest zdefiniowanie poszczególnych pól i atrybutów.

Przykładowo:

{% highlight apache %}
#############################################################################
## source definition
#############################################################################
 
source src5
{
    type                        = tsvpipe
    tsvpipe_command             = cat /var/lib/sphinxsearch/test.tsv
    
    tsvpipe_field               = title
    tsvpipe_field               = content
 
    tsvpipe_attr_bigint         = attr1
    tsvpipe_attr_bool           = attr2
    tsvpipe_attr_timestamp      = attr3
    tsvpipe_attr_float          = attr4
    tsvpipe_attr_json           = attr5
}
{% endhighlight %}  

Indeksowane dane powinny być zgodne z ustalonym formatem: pierwsza kolumna to identyfikator dokumentu a następnie wartości o typach danych w kolejności zdefiniowanej w konfiguracji.

{% highlight tsv %}
1   Sample title-000001 Lorem dolor ipsum sit amet quam. Sed eget velit. Suspendisse....    100001  1   1423003008  100001.0002 {"msg": "sample doc-000001"}
2   Sample title-000002 Lorem ipsum dolor sit amet felis sollicitudin mi quis enim......    100002  1   1423003008  100002.0005 {"msg": "sample doc-000002"}
3   Sample title-000003 Lorem ipsum dolor sit amet felis sollicitudin mi quis enim......    100003  1   1423003008  100003.0004 {"msg": "sample doc-000003"} 
{% endhighlight %}

### Benchmark

Pierwsze spostrzeżenie, jeśli chodzi o porównanie szybkości indeksowania danych w formacie *XML* i *TSV*, jest takie że format *XML* charakteryzuje duża nadmiarowość danych (tagi XML, konieczność escape'owania danych) która nie występuje w przypadku formatu *TSV*. Ponadto *xmlpipe* wymaga, aby poszczególne węzły dokumentu XML (oraz ich atrybuty) zostały zmapowane na pola i atrybuty zawarte w definicji indeksu wyodrębniając jednocześnie ich wartości z dostarczonego zbioru danych. Uwzględniając te założenia, przewaga w szybkości indeksowania TSV nad XML powinna rosnąć wraz ze wzrostem liczby indeksowanych dokumentów. A jak to wygląda w praktyce?

Przeprowadziłem następujące testy:

* indeksowanie z przygotowanego pliku statycznego w formacie TSV / XML
* dane dla indeksera dostarczane są dynamicznie przez skrypt generujący dane w formacie TSV / XML

Dodatkowo w pierwszym przypadku zmierzony został czas generowania danych. Oczywiście wszystkie testy przeprowadzone zostały w jednolitym środowisku, dane generowane były jednolity sposób (skrypt do generowania testowych danych [generate_data.php](https://github.com/tswiackiewicz/SphinxSearchTsvpipeBenchmark/blob/master/generate_data.php)).

Wyniki:

I. plik statyczny (test.tsv, test.xml)

| | | **100k** | **500k** | **1M** | **2.5M** | **5M** | **10M** |
|-|-|-|-|-|-|-|
| *TSV* | czas generowania danych | 1.411s | 6.890s | 13.456s | 32.921s | 67.298s | 143.932s |
| | czas indeksowania danych | 3.023s | 15.950s | 32.986s | 88.815s | 188.468s | 399.714s |
| *XML* | czas generowania danych | 1.687s | 7.693s | 15.830s | 38.672s | 78.685s | 155.954s |
| | czas indeksowania danych | 3.097s | 15.363s | 32.249s | 87.420s | 193.434s | 469.150s |
| *TSV vs XML* | czas generowania danych | <span style="color: #009900;">-16.36%</span> | <span style="color: #009900;">-10.43%</span> | <span style="color: #009900;">-14.99%</span> | <span style="color: #009900;">-14,87%</span> | <span style="color: #009900;">-14,47%</span> | <span style="color: #009900;">-7,71%</span> |
| | czas indeksowania danych | <span style="color: #009900;">-2.39%</span> | <span style="color: #FF0000;">+3.82%</span> | <span style="color: #FF0000;">+2.28%</span> | <span style="color: #FF0000;">+1.59%</span> | <span style="color: #009900;">-2.57%</span> | <span style="color: #009900;">-14.80%</span> |

II. dane generowane za pomocą skryptu (generate_data.php)

| | | **100k** | **500k** | **1M** | **2.5M** | **5M** | **10M** |
|-|-|-|-|-|-|-|
| *TSV* | czas indeksowania danych | 4.863s | 20.501s | 42.556s | 112.720s | 232.924s | 499.994s |
| *XML* | czas indeksowania danych | 4.403s | 22.061s | 45.771s | 119.788s | 245.638s | 525.838s |
| *TSV vs XML* | czas indeksowania danych | <span style="color: #FF0000;">+9.46%</span> | <span style="color: #009900;">-7.07%</span> | <span style="color: #009900;">-7.02%</span> | <span style="color: #009900;">-5.90%</span> | <span style="color: #009900;">-5.18%</span> | <span style="color: #009900;">-4.91%</span> |

Wyniki przeprowadzonych testów potwierdzają postawioną tezę - indeksowanie danych w formacie *TSV* jest szybsze niż *XML*. Różnice czasowe w szybkości indeksowania wzrastają razem z liczbą indeksowanych dokumentów. Składa się na to dłuższy czas generowania danych w formacie XML (różnice mogą sięgać nawet kilkunastu procent) oraz wyodrębniania ich z dostarczonego zbioru danych. W przypadku mniejszych zbiorów danych (pobieranych ze statycznego źródła, np. pliku) koszt wyodrębniania danych z XML jest na tyle niski, iż nie wpływa znacząco na czas generowania indeksu. Zauważalna różnica pojawia się dopiero w przypadku większych zbiorów danych. Stąd też, dla małych zbiorów danych, w przypadku generowania indeksu ze statycznego pliku bądź porównywalnych czasów generowania danych dla indeksera, czas budowania indeksu z XML może być nawet krótszy - w zależności od próby możliwe są wahania w jedną bądź drugą stronę. Drugi z analizowanych scenariuszy pokazuje, że nawet dla stosunkowo niewielkiego indeksu (*500k* dokumentów) różnica w czasie jego budowania jest już dość znacząca. Jest to dla nas o tyle istotne, iż zazwyczaj indeksy generowane będą na podstawie dynamicznie generowanego zbioru danych a nie statycznego pliku. Warto zwrócić uwagę jeszcze na jeden fakt - otóż liczba operacji <abbr title="Input Output">IO<abbr> w obu przypadkach (*TSV* vs *XML*) jest identyczna (szczegóły w załączonych szczegółowych wynikach).

Dla zainteresowanych, kompletne [wyniki](https://github.com/tswiackiewicz/SphinxSearchTsvpipeBenchmark/blob/master/tsv_xml_benchmark_results.txt) eksperymentów.

Dla prawidłowego działania wyszukiwania, *indeksowanie* jest równie istotne co usługa *wyszukiwania* - bądź co bądź zapewnia strukturę danych umożliwiającą uzyskanie wysokiej jakości wyników wyszukiwania w bardzo krótkim czasie. Dlatego też tak istotne jest, aby dane w indeksie były odpowiednio często aktualizowane, co bezpośrednio jest zależne od wydajności mechanizmu indeksowania. W związku z tym, bardzo pożądana jest możliwość elastycznego dostarczania danych dla indeksu, która realizowana jest m.in poprzez formaty XML oraz TSV. Przedstawiona analiza wskazuje TSV jako bardziej pożądany format - szybszy i prostszy. A czy Twoja aplikacja dostarcza już dane dla indeksów Sphinxa w taki sposób? 

Przydatne linki:

* [http://sphinxsearch.com/blog/2014/08/14/easy-indexing-with-tsvpipe/](http://sphinxsearch.com/blog/2014/08/14/easy-indexing-with-tsvpipe/)
* [https://github.com/stefobark/index_tsv](https://github.com/stefobark/index_tsv)
* [http://sphinxsearch.com/docs/current/tsvpipe.html](http://sphinxsearch.com/docs/current/tsvpipe.html)
* [http://sphinxsearch.com/forum/view.html?id=11964](http://sphinxsearch.com/forum/view.html?id=11964)

