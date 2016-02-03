---
layout: post
title: "Elasticsearch - let's talk about mapping"
description: "Podejmując się realizacji danego zadania, bez względu na to jakiego rodzaju to zadanie, dużo łatwiej jest osiągnać wyznaczony cel jeśli zostanie on odpowiednio zdefiniowany i opisany..."
headline: 
modified: 2015-10-22
category: elasticsearch
tags: [elasticsearch, search, mapping, template, apache lucene]
comments: true
featured: false
---

Podejmując się realizacji danego zadania, bez względu na to jakiego rodzaju to zadanie, dużo łatwiej jest osiągnać wyznaczony cel jeśli zostanie on odpowiednio zdefiniowany i opisany. Przykładowo, wybierając się na zakupy do supermarketu dużo szybciej znajdziemy się przy kasie, jeśli wcześniej przygotujemy sobie listę zakupów. Dodatkowo, jest duża szansa, że unikniemy niepotrzebnych kosztów w przeciwieństwie do sytuacji, gdzie podróżowalibyśmy między sklepowymi półkami bez celu. Podobnie przedstawia się sytuacja w świecie software'u - uczestnicząc w projektach czy po prostu realizując pojedyncze zadania, gdzie znany jest wyłącznie ogólny cel, np. narzędzie do generowania raportów, z dużym prawdopodbieństwem czeka nas duża liczba poprawek, a część wykonanej przez nas pracy wyląduje w koszu.       

Nieco inaczej przedstawia się sytuacja w przypadkach specjalizowanego oprogramowania, np. bazy danych, silniki wyszukiwawcze, key-value storage itp. Tutaj szczególne znaczenie ma wydajność i skalowaność takiego rozwiązania, która może być osiągnięta wprowadzając optymalizacje bazujące na znajomości wejściowego zbioru danych. Inne algorytmy wyszukiwawcze zostaną wybrane dla danych numerycznych, a jeszcze inne dla wyszukiwania pełno-tekstowego. Oczekujemy danych wejściowych charakteryzujących się pewną strukturą czy też typem danych. Chyba, że pozwolimy, aby *inteligentny* system sam się domyślił...  
   
### Schemaless   

Większość rozwiązań z rodziny *NoSQL* została zaprojektowana w taki sposób, aby była możliwość operowania na danych bez ustalonego dla ich schematu. Z jednej strony daje nam to dużą elastyczność i znika problem utrzymywania i pilnowania definicji struktur danych. Z drugiej zaś system taki cechuje trudna integracja z innymi systemami, np. o zdefiniowanej strutkurze danych.

Przykładem systemu o powyższej charakterystyce tj. *schemaless* może być **Elasticsearch**. Dane organizowane są w postaci dokumentów *key-value* w formacie *JSON*. Pierwszy utworzony dokument wymusza typ danych przechowywanych w danym polu. Zatem *schemaless* system poniekąd domyśla się typu danych. Przykładowo, pierwszy dodany dokument w polu *"enabled"* będzie zawierał wartość *"true"*, drugi natomiast po prostu *false*. Ustawiony zostanie typ danych jako *string*, a być może lepszym wyborem byłby *boolean*? Co więcej nic nie stoi na przeszkodzie, aby kolejne dokumenty zawierały pola, które nie zostały zdefiniowane w poprzedzających dokumentach. Bądź co bądź nie mamy narzuconej struktury.
  
Jednakże *Elasticsearch* to nie tylko *worek* na dane - checmy mieć możliwość wyszukania i pobrania tych danych, w końcu korzystamy z silnika wyszukiwawczego. Tak więc prędzej czy później będziemy mieli styczność ze strukturą i formatem zaindeksowanych danych. Bardzo dobrze podsumowuje to cytat z książki *"NoSQL Distilled: A Brief Guide to the Emerging World of Polyglot Persistence" (2012)* autorstwa Pramoda J. Sadalage'a oraz Martina Fowlera:
  
> … whenever we write a program that accesses data, that program almost always relies on some form of implicit schema 

Podsumowując, nawet systemy *NoSQL* czy też *schemaless*, pomimo tego iż nie ma konieczności narzucania formatu danych, co więcej poszczególne dokumenty mogą różnić się między sobą (w zakresie struktury), to koniec końców i tak będziemy korzystali z pewnej formy reprezentacji zawartych tam danych.

### Mapping

Jak zostało wspomniane powyżej, **Elasticsearch** może ustalić typ danych na podstawie pierwszego zaindeksowanego dokumentu. Możemy jednak narzucić schemat danych, aby uzyskać lepsze, trafniejsze i bardziej odpowiadające naszym potrzebom wyniki wyszukiwania.
 
W tym celu określamy jakie pola będą zawarte w dokumentach danego typu, jaki będzie typ oraz format poszczególnych pól, w jaki sposób będą analizowane, indeksowane i wyszukiwane. Każda z podjętych decyzji będzie wpływała na zwracane rezultaty. Przykładowo, potrzebujemy wyznaczyć listę najpopularniejszych kategorii danych przechowywanych w indeksach Elasticsearcha. Nazwa danej kategorii może składać się z dowolnej liczby słów. Jeżeli pogrupujemy dokumenty w domyślnej konfiguracji, zagregowane zostaną dane na poziomie pojedynczych słów, a nie całych wyrażeń (pełnych nazw kategorii). Pożądany efekt możemy uzyskać wyłączając analizatory dla tego pola - klauzula *"index": "not_analyzed"*. Takich przypadków użycia w rzeczywistych warunkach będzie znacznie więcej, każdy z nich trzeba mieć na uwadze projektując organizację danych w dokumentach.

Przykładowy mapping (w formacie *JSON*):


{% highlight json %}
{
    "tweet" : {
        "properties" : {
            "message" : {
                "type" : "string", 
                "store" : true 
            },
            "category": {
                "type": "string",
                "index": "not_analyzed"
            }
        }
    }
}
{% endhighlight %} 

### Time-based indices

Bardzo często dokumenty w indeksach *Elasticsearch* organizowane są w kontekście czasowym, np. różnego rodzaju logi aplikacyjne, błędów czy też zapis aktywności użytkowników systemu. Podział danych na przedziały czasowe po pierwsze wpływa na czasy odpowiedzi, gdyż operujemy wyłacznie na danych z interesującego nas przedizału czasu. Dwa, bardzo łatwo możemy usuwać stare dane - po prostu usuwamy cały indeks. Możemy łatwo archiwizować dane bazując na znaczniku czasu, przenosić takie dane w inne lokalizacje itd. Mamy cały wachalrz możliwości. Przykładem aplikacji korzystającej z takich indeksów może być [Kibana](https://www.elastic.co/products/kibana). 

Pojawia się jednak jeden problem. Struktura danych w poszczególnych indeksach będzie taka sama, będą różniły się wyłącznie nazwą indeksu. Skorzystamy zatem z bardzo popularnego *copy-paste'ingu* czy poszukwamy bardziej wyszukanych rozwiązań? 

### Template

Rozwiązaniem powyższego problemu może być zastosowanie szablonów (*ang. template*). Pozwalają one automatycznie aplikować zdefiniowany wcześniej mapping dla nowo tworzonych indeksów.
 
Zasada działania takich szablonów jest bardzo prosta - dla wszystkich nowych indeksów pasujących do ustalonego wzorca ustawiany jest mapping określony w szablonie. Nowy indeks możemy utworzyć z poziomu *API Elastica* bądź dodając nowy dokument do indeksu.

Szablon może zawierać zarówno definicję typów, formatów oraz analizatorów poszczególnych pól, jak również ustawienia indeksu (np. liczba replik) czy też konfigurację własnych analizatorów. Należy jednak pamiętać o tym, że pola które nie zostaną uwzlędnione w mappingu zdefiniowanym w szablonie, a znajdą się w indeksowanych dokumentach, otrzymają mapping odganięty na podstawie pierwszego dokumentu zawierającego takie pole.

Przykład:

{% highlight bash %}
curl -XPUT "http://localhost:9200/_template/application_logs" -d '
{
    "template": "application_logs-*",
    "settings": {
        "index.analysis.analyzer.params_analyzer.type": "pattern",
        "index.analysis.analyzer.params_analyzer.pattern": "&"
    },
    "mappings": {
        "events": {
            "_source": { 
                "compress": true 
            },
            "_id": { 
                "path": "uuid" 
            },
            "properties": {
      	        "uuid": { 
      	            "type": "string", 
      	            "index": "not_analyzed" 
                },
                "type": {
                    "type": "string",
                    "index": "not_analyzed"
                },
                "description": {
                    "type": "string"
                },                    
                "stage": { 
                    "type": "string", 
                    "index": "not_analyzed" 
                },
      	        "params": {          
                    "type": "string",
                    "analyzer": "params_analyzer",
                    "fields": {
                        "raw": {
                            "type": "string",
                            "index": "not_analyzed" 
                        }
                    }
                },
                "host": { 
                    "type": "string", 
                    "index": "not_analyzed" 
                },
                "port_number": {
                    "type": "integer"
                },
                "timestamp": { 
                    "type": "date", 
                    "format": "dateOptionalTime",
                    "ignore_malformed": true
                }        
            }
        }
    }
}
'
{% endhighlight %}
 
Wracając do poruszonego w poprzedniej sekcji zagadnienia, a mianowicie identycznych mappingów dla indeksów różniących się jedynie nazwą, wystarczy w sekcji *template* szablonu podać wzorzec, zgodnie z którym dopasowana zostanie nazwa indeksu. Korzystając z przedstawionego powyżej szablonu, mapping tam zdefiniowany zostanie ustawiony dla wszystkich indeksów, których nazwa rozpoczyna się od *application_logs-*, np. *application_logs-2015.10*, *application_logs-2015.11* itd. 

### Kilka dobrych rad:

* zarządzanie mappingami indeksów poprzez szablony jest bardzo wygodne, możemy je przechowywać w dowolnym systemie kontroli wersji
* nie bójmy się eksperymentować z mappingami - możemy przygotować template, założyć pusty indeks pasujący do zdefiniowanego wzorca, a następnie za pomocą dostępnych pluginów (np. [Kopf](https://github.com/lmenezes/elasticsearch-kopf)), weryfikować czy osiągnęliśmy satysfakcjonujący nas mapping
* nowy indeks może zostać założony z poziomu *API Elastica*, ale również zostanie utworzony podczas dodawania pierwszego dokumentu do indeksu; w środowisku produkcyjnym zalecaną praktyką jest utworzenie indeksu przed dodaniem pierwszego dokumentu - będziemy mieli pewność, że docelowy indeks będzie miał pożądaną strukturę 
* jeżeli potrzebujemy, w zależności od kontekstu, aby jedno pole dokumentu zostało zaindeksowane w różny sposób (np. analizowany i nieanalizowany) skorzystajmy z *[multi-fields](https://www.elastic.co/guide/en/elasticsearch/reference/current/_multi_fields.html)*, a w payloadzie dokumentu interesująca nas wartość zostanie przekazana tylko raz
* zarządzanie szablonami odbywa się z poziomu [API](https://www.elastic.co/guide/en/elasticsearch/reference/current/indices-templates.html) *Elasticsearcha*, ale dostępne narzędzia pozwalają na dostęp do nich z poziomu panelów *www*
* zalecałbym wykorzystanie flagi *dynamic: strict*, która zabezpieczy nas przed dokumentami o strukturze innej (nadmiarowej) niż ta zdefiniowana w mappingu

Podsumowując, chociaż **Elasticsearch** reprezentuje podejście *schemaless*, nie unikniemy styczności z mappingiem i definicją przechowywanych struktur danych. Cytując klasyków *Pramod J. Sadalage*, *Martin Fowler*:

> “Essentially, a schemaless database shifts the schema into the application code that accesses it.”

Dlatego, warto poruszać się po tym obszarze swobodnie i dostosowywać formę dokumentów do naszych potrzeb.

*Elasticsearch: You know, for Search...*

  

Przydatne linki:

* [https://www.elastic.co/guide/en/elasticsearch/reference/current/indices-templates.html](https://www.elastic.co/guide/en/elasticsearch/reference/current/indices-templates.html)
* [http://svops.com/blog/elasticsearch-mappings-and-templates/](http://svops.com/blog/elasticsearch-mappings-and-templates/)
* [https://jackhanington.com/blog/2014/12/11/create-a-custom-elasticsearch-template/](https://jackhanington.com/blog/2014/12/11/create-a-custom-elasticsearch-template/)
* [http://code972.com/blog/2015/02/80-elasticsearch-one-tip-a-day-managing-index-mappings-like-a-pro](http://code972.com/blog/2015/02/80-elasticsearch-one-tip-a-day-managing-index-mappings-like-a-pro)
* [http://jontai.me/blog/2013/03/elasticsearch-index-templates/](http://jontai.me/blog/2013/03/elasticsearch-index-templates/)
* [http://blog.sematext.com/2015/01/20/custom-elasticsearch-index-templates-in-logsene/](http://blog.sematext.com/2015/01/20/custom-elasticsearch-index-templates-in-logsene/)  
* [http://saskia-vola.com/introducing-a-generic-dynamic-mapping-template-for-elasticsearch/](http://saskia-vola.com/introducing-a-generic-dynamic-mapping-template-for-elasticsearch/)  
* [http://kufli.blogspot.com/2014/11/elasticsearch-dynamic-data-mapping.html](http://kufli.blogspot.com/2014/11/elasticsearch-dynamic-data-mapping.html)  
* [http://martinfowler.com/articles/schemaless/](http://martinfowler.com/articles/schemaless/)
* [http://danielwertheim.se/2013/06/04/a-word-or-two-about-nosql-and-it-being-schemaless/](http://danielwertheim.se/2013/06/04/a-word-or-two-about-nosql-and-it-being-schemaless/)
* [http://www.slideshare.net/infinitegraph/schema-meetupfeb2014key](http://www.slideshare.net/infinitegraph/schema-meetupfeb2014key)
* [https://www.elastic.co/guide/en/elasticsearch/guide/current/time-based.html](https://www.elastic.co/guide/en/elasticsearch/guide/current/time-based.html)
* [https://www.elastic.co/use-cases/data-dog/](https://www.elastic.co/use-cases/data-dog/)
