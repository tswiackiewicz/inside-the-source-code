---
layout: post
title: "SphinxSearch - atrybuty JSON"
description: "Wiele z istniejących aplikacji zostało zbudowanych z wykorzystaniem baz danych. Cechą charakterystyczną tego rozwiązania jest to, iż przechowywane dane zgodne są z ustalonym schematem (mappingiem) tj. zbiorem nazw i typów danych atrybutów, np. id - long, name - string itd. Podobnie przedstawia się sytuacja w przypadku, gdy bazę danych zastąpimy rozwiązaniem znanym jako SphinxSearch..."
headline: 
modified: 2014-08-09
category: sphinxsearch
tags: [json, search, sphinx, sphinxsearch]
comments: true
featured: false
---

Wiele z istniejących aplikacji zostało zbudowanych z wykorzystaniem baz danych. Cechą charakterystyczną tego rozwiązania jest to, iż przechowywane dane zgodne są z ustalonym schematem (*mappingiem*) tj. zbiorem nazw i typów danych atrybutów, np. id - long, name - string itd. Podobnie przedstawia się sytuacja w przypadku, gdy bazę danych zastąpimy rozwiązaniem znanym jako **SphinxSearch**. Tutaj również mamy jasno zdefiniowany zbiór kluczy (atrybutów) i odpowiadającym im wartości (wraz z typami danych). Dla większości przypadków taki sposób ogranizacji danych jest wystarczający, ale zdarzają się sytuacje, gdy dla danego klucza będziemy chcieli przechowywać (oraz wyszukiwać) dane o różnej strukturze w zależności od kontekstu. Przykładowo, nasza aplikacja to typowy sklep internetowy zawierający zbiór produktów. Dla większości z nich można zdefiniować wspólny zbiór cech (np. cena, nazwa, opis), ale w zależności od kategorii potrzebne będą indywidualne cechy, np. dla książek - liczba stron, laptopów - liczba zainstalowanej pamięci RAM, pojemność dysku twardego itd. Jednocześnie chcemy przechowywać produkty różnych kategorii w jednym zbiorze danych. Rozwiązaniem tego problemu jest przechowywanie zbioru atrybutów w formacie ***JSON***.

W tym celu konieczne jest wprowadzenie zmian w konfiguracji, tak aby atrybuty w formacie <abbr title="JavaScript Object Notation">JSON</abbr> były indeksowane oraz wyszukiwanie. Definiujemy zatem jeden z atrybutów: ***sql_attr_json***, ***xmlpipe_attr_json*** bądź ***rt_attr_json***.

{% highlight conf %}
#############################################################################
## source definition
#############################################################################
 
source src4 : common
{
	sql_query 		= SELECT id, attr1, attr2, attr3, \
				UNIX_TIMESTAMP(created_on) created_on, \
				title, content, json_content FROM docs WHERE \
				id >= $start AND id <= $end
 
	sql_attr_uint 		= attr1
	sql_attr_uint 		= attr2
	sql_attr_uint 		= attr3
	sql_field_string 	= title
	sql_field_string 	= content
	sql_attr_json 		= json_content
	sql_attr_timestamp 	= created_on
}
{% endhighlight %}

### *sql_attr_json*

Oprócz wspomnianej powyżej definicji atrybutów typu *sql_attr_json*, warto również zwrócić uwagę na następujące parametry konfiguracji:

* ***on_json_attr_error*** - obsługa błędów formatu JSON: *ignore_attr* (domyślna, błędy zostaną wyświetlone, indeksowanie będzie kontynuowane), *fail_index* (indeksowanie zostanie przerwane w momencie wystąpienia błędu)
* ***json_autoconv_numbers*** - wykrywanie i konwersja stringów do formatu liczb: 0 (domyślne, konwersja nie jest wykonywana), 1 (konwersja włączona); przykładowo jeśli *json_autoconv_numbers = 1*, *"obj": { "id" : 123 }* będzie reprezentowany jako *obj.id* = 123, w przeciwnym razie *obj.id* = "123"
* ***json_autoconv_keynames*** - sposób konwersji nazw kluczy wewnątrz atrybutu typu JSON: *lowercase* (zamiana na małe litery) bądź brak wartości (domyślne, konwersja wyłączona)

Przykład: indeksujemy dokumenty JSON (pole *json_content*) postaci

{% highlight json %}
{
    "name"  : "Alice",
    "uid"   : 450
}
 
{
    "name"  : "Damon",
    "uid"   : 456,
    "gid"   : 23
}
 
{
    "id"    : 1,
    "gid"   : 2,
    "title" : "some title",
    "tags": [
        "tag1", 
        "tag2",
        "tag3"
    ]
} 
{% endhighlight %}

oraz wyszukujemy

{% highlight sql %}
SELECT
	`id`,
	`title`,
	`json_content`,
	`created_on`
FROM
	test_json
WHERE
	json_content.name = 'Alice';
 
SELECT
	`id`,
	`title`,
	`json_content`,
	`created_on`
FROM
	test_json
WHERE
	json_content.uid > 455;
 
SELECT
	`id`,
	`title`,
	`json_content`,
	`created_on`
FROM
	test_json
WHERE
	json_content.uid > 455
ORDER BY
	json_content.name ASC; 
{% endhighlight %}

Więcej zapytań znajdziecie w linkach zamieszczonych na końcu wpisu. Chciałbym jednak zwrócić uwagę na następujące problemy:

* aktualna, stabilna wesja nie posiada jeszcze pełnej obsługi formatu *JSON*, pełna obłsuga dostępna od wersji *2.2.3-beta*
* **SphinxSE** nie zawiera obsługi JSON - biblioteka *ha_sphinx* dla MariaDB 5.2 / 5.3 korzysta ze SphinxSearch w wersji 0.9.9, MariaDB 5.5 - 2.0.4
* nieprawidłowa obsługa (w niektórych przypadkach) zapytań *IN()* w aktualnej, stabilnej wersji, błąd poprawiony w wersji *2.2.3-beta*

Pomimo tego, iż pełna obsługa atrybutów typu *JSON* nie jest jeszcze dostępna (w wersji stabilnej), dzięki temu rozwiązaniu otrzymujemy nowe możliwości, które będziemy mogli wykorzystać w naszej aplikacji. Zachęcam Was do wypróbowania tej funkcjonalności w Waszych aplikacjach, pozostaje nam czekać na stabilną wersję 2.2.3.

Przydatne linki:

* [http://sphinxsearch.com/blog/2013/02/07/sphinx-2-1-json-attributes/](http://sphinxsearch.com/blog/2013/02/07/sphinx-2-1-json-attributes/)
* [http://sphinxsearch.com/blog/2013/08/08/full-json-support-in-trunk/](http://sphinxsearch.com/blog/2013/08/08/full-json-support-in-trunk/)
* [http://sphinxsearch.com/docs/current.html#conf-sql-attr-json](http://sphinxsearch.com/docs/current.html#conf-sql-attr-json)
* [http://sphinxsearch.com/forum/view.html?id=10990](http://sphinxsearch.com/forum/view.html?id=10990)
* [http://sphinxsearch.com/bugs/view.php?id=1727](http://sphinxsearch.com/bugs/view.php?id=1727)
* [http://sphinxsearch.com/bugs/view.php?id=1870](http://sphinxsearch.com/bugs/view.php?id=1870)
* [http://sphinxsearch.com/bugs/view.php?id=1946](http://sphinxsearch.com/bugs/view.php?id=1946)


