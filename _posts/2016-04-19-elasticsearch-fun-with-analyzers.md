---
layout: post
title: "Elasticsearch - fun with analyzers"
description: "Podstawą działania silników wyszukiwawczych, jak choćby ElasticSearch, jest tzw. indeks odwrócony. Ta zoptymalizowana struktura danych..."
headline: 
modified: 2016-04-19
category: elasticsearch
tags: [elasticsearch, search, analyzer, apache lucene]
comments: true
featured: false
---

Podstawą działania silników wyszukiwawczych, jak choćby ***ElasticSearch***, jest tzw. *indeks odwrócony*. Ta zoptymalizowana struktura danych pozwala na bardzo wydajne wyszukiwanie dokumentów spełniających podane kryteria. Aby jednak powstał wspomniany *[inverted index](http://tswiackiewicz.github.io/inside-the-source-code/sphinxsearch/sphinxsearch-indeksowanie-dokumentow/)*, konieczne jest wyodrębnienie ***termów***, czyli podstawowych jednostek leksykalnych, z treści dokumentów. Ich ekstrakcja z tekstu źródłowego odbywa się na etapie indeksowania, a dokładnie podczas procesu analizy.

Przywołany do tablicy proces analizy, obsługiwany przez ***analizatory***, przebiega w następujących krokach:

1. nałożenie *character filters* na analizowany tekst, np. usuwanie tagów *HTML* bądź konwersja *&* na *and*
2. **tokenizacja** czyli rozbicie na *termy*
3. *token filters* - modyfikacja tokenów wyodrębnionych w poprzednim kroku, np. zamiana wielkich liter na małe

Możemy wyróżnić wbudowane *analizatory* (np. *standard*, *simple*, *keyword* itd.) oraz własne (*custom*). Definiujemy je w [mappingu](http://tswiackiewicz.github.io/inside-the-source-code/elasticsearch/elasticsearch-lets-talk-about-mapping/) indeksu. W domyślnej konfiguracji, dla pól tekstowych, aplikowany jest ***Standard Analyzer*** rozbijający wejściową frazę na tokeny wykorzystując jako separatory białe znaki oraz kilka dodatkowych znaków specjalnych jak choćby przecinek, myślnik, podłoga itp. Dodatkowo *stopwords*, czyli separatory na podstawie których rozbijany jest tekst, są usuwane a wielkie litery zamieniane na małe. Przykład, korzystając z domyślnej konfiguracji, fraza *The quick brown fox jumped over the lazy dog* zostanie rozbita na tokeny: *the*, *quick*, *brown*, *fox*, *jumped*, *over*, *the*, *lazy*, *dog*. 

Ponieważ gotowe do użycia przykłady dużo łatwiej będzie zrozumieć i zapamiętać, wybrałem kilka najpopularniejszych przypadków, gdzie porównamy efekt działania skonfigurowanego analizatora oraz *Standard Analyzera*.
  
### Not Analyzed

W szczególnych przypadkach, np. agregacja wyników z danej kategorii oraz wizualizacja ich za pomocą Kibany, potrzebujemy aby tekst wejściowy nie był analizowany czy też modyfikowany. Najprostszym rozwiązaniem będzie po prostu wyłączenie analizatorów dla interesującego nas pola - klauzula *not_analyzed*    
  
{% highlight bash %}
curl -XPUT 'http://localhost:9200/_template/fun_with_analyzers' -d '
{
    "template": "test*",
    "mappings": {
        "test": {
            "properties": {
                "content": {
                    "type": "string",
                    "fields": {
                        "raw": {
                            "type": "string",
                            "index": "not_analyzed"
                        }
                    }
                }
            }
        }
    }
}
'
{% endhighlight %}

Porównanie wyodrębnionych tokenów przez domyślnego analizatora (*Standard Analyzer*) oraz w przypadku wyłączonego analizatora dla frazy *The quick brown fox jumped over the lazy dog* :

* ***Standard Analyzer*** : *the*, *quick*, *brown*, *fox*, *jumped*, *over*, *the*, *lazy*, *dog*
* ***not_analyzed*** : *The quick brown fox jumped over the lazy dog* (pojedynczy token)
  
### Not Analyzed & lowercase

Zazwyczaj rozwiązanie przedstawione powyżej jest wystarczające dla grupowania wyników według danej kategorii. Jednak, w przypadku, gdy analizowany tekst pochodzi od użytkowników, nie mamy gwarancji że potencjalnie te same grupy będą reprezentowane w taki sam sposób.
Przykładowo: *brown*, *Brown*, *bRoWn* oraz *brOwN* to po prostu *brown*. Jednak zastosowanie poprzedniej wskazówki (wyłączenie analizatorów) spowoduje, że dla wymienionych fraz uzyskamy cztery różne grupy zamiast jednej. 

Należy tutaj zwrócić uwagę na fakt, iż analiza ma miejsce zarówno podczas indeksowania jak i wyszukiwania. Oznacza to tyle, że jeśli na etapie indeksowania wyodrębniony zostanie token *bRoWn*, a wyszukiwana będzie fraza *browN*, dokument nie zostanie odnaleziony. 

Rozwiązaniem może być przygotowanie własnego analizatora, zwracającego oryginalną frazę z wielkimi literami zamienionymi na małe, o konfiguracji przedstawionej poniżej: 

{% highlight bash %}
curl -XPUT 'http://localhost:9200/_template/fun_with_analyzers' -d '
{
    "template": "test*",
    "settings": {
    	"index": {
            "analysis": {
                "analyzer": {
                    "raw_lowercase_analyzer": {
                        "tokenizer": "keyword",
                        "filter": "lowercase"
                    }
                }
            }
        }
    },
    "mappings": {
        "test": {
            "properties": {
                "content": {
                    "type": "string",
                    "fields": {
                        "raw_lowercase": {
                            "type": "string",
                            "analyzer": "raw_lowercase_analyzer"
                        }
                    }
                }
            }
        }
    }
}
'
{% endhighlight %}

### Email

Kolejnym bardzo powszechnym przypadkiem jest obsługa adresów email - indeksowany tekst może zawierać jeden bądź wiele adresów email. Zastosowanie domyślnej konfiguracji rozbije podaną frazę wykorzystując jako separatory m.in. białe znaki oraz znak *@*. W ten sposób pełen adres email nie będzie występował na liście tokenów. Możemy temu zapobiec, definiując własny analizator, który oprócz pełnych adresów email wyekstrahuje również nazwę domeny oraz login.  

{% highlight bash %}
curl -XPUT 'http://localhost:9200/_template/fun_with_analyzers' -d '
{
    "template": "test*",
    "settings": {
        "index": {
            "analysis": {
                "filter": {
                    "email": {
                        "type": "pattern_capture",
                        "preserve_original": true,
                        "patterns": [
                            "([^@]+)",
                            "@(.+)"
                        ]
                    }
                },
                "analyzer": {
                    "email_analyzer": {
                        "tokenizer": "uax_url_email",
                        "filter": ["lowercase", "email", "unique"]
                    }
                }
            }
        }
    },	
    "mappings": {
        "test": {
            "properties": {
                "content": { 
                    "type": "string", 
                    "fields": {
                        "email": {
                            "type": "string", 
                            "analyzer": "email_analyzer"
                        }
                    } 
                }
            }
        }
    }
}
'
{% endhighlight %}

Dla potwierdzenia (analizowany tekst *user@domain.com, login@DOMAIN.COM lOgin@google.com*):

* ***Standard Analyzer*** : *user*, *domain.com*, *login*, *domain.com*, *login*, *google.com*
* ***email_analyzer*** : *user@domain.com*, *user*, *domain.com*, *login@domain.com*, *login*, *login@google.com*, *google.com*
  
### Camel Case

Dobrą praktyką stosowaną podczas implementacji w wielu popularnych językach programowania jest stosowanie notacji *CamelCase* dla nazw klas, metod, zmiennych itd. Przygotowując wyszukiwarkę kodów źródłowych w naszej organizacji, chcielibyśmy mieć możliwość wydajnego wyszukiwania, może nawet agregacji, po fragmentach nazw klas czy funkcji.
   
Będziemy zatem potrzebowali takiego analizatora, który rozbije nazwy klas po fragmentach nazw rozpoczynających się od wielkiej litery:   

{% highlight bash %}
curl -XPUT 'http://localhost:9200/_template/fun_with_analyzers' -d '
{
    "template": "test*",
    "settings": {
    	"index": {
            "analysis": {
                "analyzer": {
                    "camel_case_analyzer": {
                        "type": "pattern",
                        "pattern": "([^\\p{L}\\d]+)|(?<=\\D)(?=\\d)|(?<=\\d)(?=\\D)|(?<=[\\p{L}&&[^\\p{Lu}]])(?=\\p{Lu})|(?<=\\p{Lu})(?=\\p{Lu}[\\p{L}&&[^\\p{Lu}]])"
                    }
                }
            }
        }
    },
    "mappings": {
        "test": {
            "properties": {
                "content": { 
                    "type": "string", 
                    "fields": {
                        "camel": {
                            "type": "string",
                            "analyzer": "camel_case_analyzer"
                        }
                    } 
                }
            }
        }
    }
}
'
{% endhighlight %}

Przykład - *class CamelCaseClassNameExample { }* :

* ***Standard Analyzer*** : *class*, *camelcaseclassnameexample*
 * ***camel_case_analyzer*** : *class*, *camel*, *case*, *class*, *name*, *example*
  
### Stemmer

Czasami tokeny będą poszczególnymi słowami z analizowanej frazy to za mało, szczególnie gdy wdrażamy zaawansowaną wyszukiwarkę pełno-tekstową. Wówczas, nie tylko zależy nam na tym, aby wyszukiwana fraza nie była wrażliwa na wielkość liter, ale także na liczbę (pojedyncza vs mnoga) występujących tam wyrażeń. Dobre silniki wyszukiwawcze, a do takich możemy zaliczyć *ElasticSearcha*, zapewniają wsparcie dla przedstawionego problemu poprzez różnego rodzaju *stemmery*. 

Przykładowa konfiguracja analizatora opartego o stemmer języka angielskiego:

{% highlight bash %}
curl -XPUT 'http://localhost:9200/_template/fun_with_analyzers' -d '
{
    "template": "test*",
    "settings": {
    	"index": {
            "analysis": {
                "filter": {
                    "english_stemmer" : {
                        "type": "stemmer",
                        "name": "english"
                    }
                },
                "analyzer": {
                    "english_stemmer_analyzer": {
                        "tokenizer": "whitespace",
                        "filter": ["lowercase", "english_stemmer", "unique"]
                    }
                }
            }
        }
    },	
    "mappings": {
        "test": {
            "properties": {
                "content": { 
                    "type": "string", 
                    "fields": {
                        "english_stemmer": {
                            "type": "string",
                            "analyzer": "english_stemmer_analyzer"
                        }
                    } 
                }
            }
        }
    }
}
'
{% endhighlight %}

Porównanie analizy standardowej frazy (*The quick brown fox jumped over the lazy dog*) przez *Standard Analyzer* oraz *english_stemmer_analyzer* :

* ***Standard Analyzer*** : *the*, *quick*, *brown*, *fox*, *jumped*, *over*, *the*, *lazy*, *dog*
* ***english_stemmer_analyzer*** : *the*, *quick*, *brown*, *fox*, *jump*, *over*, *lazi*, *dog*
  
### Polish Stemmer - Stempel

Niestety *stemmer* języka polskiego nie jest dostępny *z pudełka*. Aby zapewnić funkcjonalność omówioną w poprzednim przykładzie konieczna jest instalacja dedykowanego [pluginu](https://www.elastic.co/guide/en/elasticsearch/plugins/current/analysis-stempel.html). Mając zainstalowany wspomniany plugin, możemy przystąpić do definicji analizatora opartego o stemmer języka polskiego (*Stempel*) :

{% highlight bash %}
curl -XPUT 'http://localhost:9200/_template/fun_with_analyzers' -d '
{
    "template": "test*",
    "mappings": {
        "test": {
            "properties": {
                "content": { 
                    "type": "string", 
                    "fields": {
                        "polish_stempel": {
                            "type": "string",
                            "analyzer": "polish"
                        }
                    } 
                }
            }
        }
    }
}
'
{% endhighlight %}

Przykład - *urodzić urodzony urodzona urodzeni* : 

* ***Standard Analyzer*** : *urodzić*, *urodzony*, *urodzona*, *urodzeni*
* ***polish*** : *urodzić*, *urodzo*, *urodzenie*, *urodzić*

Słowem uzupełnienia, powyższe dwa przykłady (*english stemmer* oraz *Stempel*), pokazują iż nie są to rozwiązania idealne, ale w szczególnych przypadkach mogą się sprawdzić. 
  
### HTML Strip

Indeksowanie czystego kodu HTML, a następnie wyszukiwanie w treści artykułów, nie jest odosobnionym przypadkiem. Możemy w prosty sposób, korzystając z gotowych filtrów dostarczonych razem z *elasticiem*, przygotować analizator spełniający nasze wymagania:

{% highlight bash %}
curl -XPUT 'http://localhost:9200/_template/fun_with_analyzers' -d '
{
    "template": "test*",
    "settings": {
    	"index": {
            "analysis": {
                "char_filter": {
                    "html": {
                        "type": "html_strip",
                        "read_ahead": 1024
                    }
                },
                "analyzer": {
                    "html_analyzer": {
                        "type": "custom",
                        "tokenizer": "whitespace",
                        "filter": "lowercase",
                        "char_filter": "html"
                    }
                }
            }
        }
    },	
    "mappings": {
        "test": {
            "properties": {
                "content": { 
                    "type": "string", 
                    "fields": {
                        "html": {
                            "type": "string",
                            "analyzer": "html_analyzer"
                        }
                    } 
                }
            }
        }
    }
}
'
{% endhighlight %}
  
### Synonym

Głównym źródłem wszelkiego rodzaju treści są użytkownicy serwisów internetowych. Jako autorzy takiego serwisu, nie mamy kontroli w jaki sposób interesująca nas fraza została wprowadzona. Z podobnym problemem możemy spotkać się realizując porównywarkę cenową - różni dostawcy treści dany produkt mogą opisywać w sobie tylko wygodny sposób. Przykład: *i-pod*, *i pod* oraz *ipod*. Analogicznie, jak w prezentowanym na początku artykułu analizatorze *raw_lowercase_analyzer*, wszystkie te wariacje to po prostu *ipod*. Rozwiązaniem będzie analizator korzystający ze słownika synonimów:

{% highlight bash %}
curl -XPUT 'http://localhost:9200/_template/fun_with_analyzers' -d '
{
    "template": "test*",
    "settings": {
    	"index": {
            "analysis": {
                "filter" : {
                    "synonym": {
                        "type": "synonym",
                        "synonyms": [
                            "i-pod, i pod => ipod",
                            "universe, cosmos",
                            "elastic, elasticsearch"
                        ]
                    }
                },
                "analyzer": {
                    "synonym_analyzer": {
                        "tokenizer": "standard",
                        "filter": ["lowercase", "synonym", "unique"]
                    }
                }
            }
        }
    },	
    "mappings": {
        "test": {
            "properties": {
                "content": { 
                    "type": "string", 
                    "fields": {
                        "synonym": {
                            "type": "string",
                            "analyzer": "synonym_analyzer"
                        }
                    } 
                }
            }
        }
    }
}
'
{% endhighlight %}
    
### Mapping characters filter
  
Ostatni z przykładów charakterystyczny będzie dla *naszego podwórka* tj. języka polskiego. Wymagania dla typowej wyszukiwarki obsługującej zapytania w języku polskim będą następujące:
  
* *case insensitive*
* tokenizacja na poziomie poszczególnych słów z wyszukiwanej frazy
* równe traktowanie wyrażeń z polskimi znakami diakrytycznymi, jak i bez nich, np. *zażółć* -> *zazolc*  
  
{% highlight bash %}
curl -XPUT 'http://localhost:9200/_template/fun_with_analyzers' -d '
{
    "template": "test*",
    "settings": {
    	"index": {
    	    "analysis": {
    	        "char_filter": {
                    "polish_special_letters_mapping": {
                        "type": "mapping",
                        "mappings": [
                            "ą => a",
                            "ć => c",
                            "ę => e",
                            "ł => l",
                            "ń => n",
                            "ó => o",
                            "ś => s",
                            "ź => z",
                            "ż => z"
                        ]
                    }
                },
                "analyzer": {
                    "polish_special_letters_analyzer": {
                        "type": "custom",
                        "tokenizer": "whitespace",
                        "filter": "lowercase",
                        "char_filter": "polish_special_letters_mapping"
                    }
                }
            }
        }
    },	
    "mappings": {
        "test": {
            "properties": {
                "content": { 
                    "type": "string", 
                    "fields": {
                        "polish_special_letters": {
                            "type": "string",
                            "analyzer": "polish_special_letters_analyzer"
                        }
                    } 
                }
            }
        }
    }
}
'
{% endhighlight %}

Porówanie zastosowanych analizatorów dla klasyki gatunku - *różowy słoń ma usiąść na tępych gwoździach*

* ***Standard Analyzer*** : *różowy*, *słoń*, *ma*, *usiąść*, *na*, *tępych*, *gwoździach*
* ***polish_special_letters_analyzer*** : *rozowy*, *slon*, *ma*, *usiasc*, *na*, *tepych*, *gwozdziach*

Przyszedł czas na podsumowanie - dokumentacja *ElasticSearch* jest bardzo dobra, ale wiele informacji jest rozrzucone po różnych sekcjach. Z własnego doświadczenia wiem, że często szukamy gotowej recepty jak dane zagadnienie rozwiązać, dlatego też niejako *na tacy* podaję przykładowe rozwiązania typowych problemów. Wzorując się na nich powinniśmy mieć bazę dla naszych własnych analizatorów. Zachęcam do własnych eksperymentów z analizatorami - są bardzo wygodnym narzędziem, a dobra znajomość ich działania pozwoli uzyskać wyniki zgodne z naszymi oczekiwaniami.
    
*Elasticsearch: You know, for Search...*    

Przydatne linki:

* [https://www.elastic.co/guide/en/elasticsearch/reference/2.3/analysis.html](https://www.elastic.co/guide/en/elasticsearch/reference/2.3/analysis.html)
* [https://www.elastic.co/guide/en/elasticsearch/guide/master/analysis-intro.html](https://www.elastic.co/guide/en/elasticsearch/guide/master/analysis-intro.html)
* [https://www.elastic.co/guide/en/elasticsearch/plugins/current/analysis-stempel.html](https://www.elastic.co/guide/en/elasticsearch/plugins/current/analysis-stempel.html)
* [http://solr.pl/2012/04/02/solr-4-0-i-mozliwosci-analizy-jezyka-polskiego/](http://solr.pl/2012/04/02/solr-4-0-i-mozliwosci-analizy-jezyka-polskiego/)
* [http://tswiackiewicz.github.io/inside-the-source-code/sphinxsearch/sphinxsearch-odmiana-wyrazen/](http://tswiackiewicz.github.io/inside-the-source-code/sphinxsearch/sphinxsearch-odmiana-wyrazen/)
* [http://tswiackiewicz.github.io/inside-the-source-code/sphinxsearch/sphinxsearch-indeksowanie-dokumentow/](http://tswiackiewicz.github.io/inside-the-source-code/sphinxsearch/sphinxsearch-indeksowanie-dokumentow/)

