---
layout: post
title: "SphinxSE - Sphinx Storage Engine"
description: "Silniki wyszukiwania pełnotekstowego, a SphinxSearch z pewnością może do takich się zaliczyć, stworzone zostały z myślą sprawnego i wydajnego wyszukiwania tekstu, niekoniecznie przechowując go jednocześnie. Zatem, typowy scenariusz aplikacji korzystającej z takiego silnika wygląda następująco: 1. wyszukiwanie dokumentów za pomocą silnika wyszukiwania (np. SphinxSearch), 2. pobranie..."
headline: 
modified: 2014-06-17
category: sphinxsearch
tags: [MariaDb, MySQL, search, SphinxSE, search, sphinx, sphinxsearch]
comments: true
featured: false
---

Silniki wyszukiwania pełnotekstowego, a **SphinxSearch** z pewnością może do takich się zaliczyć, stworzone zostały z myślą sprawnego i wydajnego wyszukiwania tekstu, niekoniecznie przechowując go jednocześnie. Zatem, typowy scenariusz aplikacji korzystającej z takiego silnika wygląda następująco: 1. wyszukiwanie dokumentów za pomocą silnika wyszukiwania (np. *SphinxSearch*), 2. pobranie właściwej treści dokumentów (z bazy danych bądź innego źródła ) dla wyników znalezionych w poprzednim kroku.

Inny, często spotykany przypadek, jest taki, iż istniejącą aplikację chcemy rozszerzyć o możliwość wyszukiwania - w tym przypadku konieczne będzie wprowadzenie szeregu zmian, sposób postępowania będzie podobny do tego opisanego powyżej: wyszukiwanie, np. korzystając z API, pobranie wymaganych informacji dla znalezionych wyników. Tutaj z pomocą przychodzi rozwiązanie znane jako **SphinxSE**.

**SphinxSE** (*ang. Sphinx Storage Engine*) to jeden z dostępnych silników przechowywania (*ang. storage engine*) takich jak np. *InnoDb* czy też *MyISAM*. Nie przechowuje on jednak danych sam w sobie a jest jedynie wbudowanym klientem umożliwiającym komunikację ze Sphinxem, wykonywanie zapytań oraz pobieranie wyników. Zaletą tego rozwiązania jest to, że przedstawione powyżej dwa kroki można zredukować do jednego - w jednym kroku wyszukujemy dokumenty oraz pobieramy właściwą treść dokumentu dla znalezionych wyników. Co więcej, także w przypadku rozbudowania istniejącej aplikacji o wyszukiwanie, koszt zmian nie będzie duży - wystarczy jedynie dodać do istniejącego zapytania <abbr title="Structured Query Language">SQL<abbr> warunek zgodny ze składnią wyszukiwania *SphinxSE* i złączyć wyniki ze *Sphinxa* z rezultatami otrzymanymi przez pierwotne zapytanie do bazy.

### *ENGINE=SPHINX*

Rolę pośrednika pomiędzy search daemonem Sphinxa (*searchd*) a bazą danych (*MySQL*, *MariaDb*) pełni pomocnicza tabela o strukturze: 

{% highlight sql %}
CREATE TABLE `sphinx_results` (
    `id` bigint(20) NOT NULL,
	`weight` int(11) NOT NULL,
	`query` varchar(3072) NOT NULL,
	`attr1` bigint(20) DEFAULT '0',
	`attr2` bigint(20) DEFAULT '0',
	`attr3` bigint(20) DEFAULT '0',
	`_sph_groupby` int(11) DEFAULT '0',
	`_sph_count` int(11) DEFAULT '0',
	`_sph_distinct` int(11) DEFAULT '0',
	KEY `query` (`query`(1024))
) ENGINE=SPHINX DEFAULT CHARSET=utf8; 
{% endhighlight %}

Nazwa tabeli oczywiście jest dowolna, ale konieczne jest aby pierwsze trzy kolumny były następujących typów:

* pierwsza - *INTEGER UNSIGNED* lub *BIGINT*: identyfikator dokumentu
* druga - *INTEGER* bądź *BIGINT*: waga (score) znalezionego dokumentu
* trzecia - *VARCHAR* lub *TEXT*: zapytanie oraz powinna być indeksowana

Pozostałe, dodatkowe kolumny mogą być typów *INTEGER*, *TIMESTAMP*, *BIGINT*, *VARCHAR* lub *FLOAT* - będą reprezentowały atrybuty odpowiadające nazwom kolumn (stąd wymagana zgodność typów kolumn dla odpowiadających im atrybutom). Dodatkowo istnieją specjalne kolumny ***_sph_groupby***, ***_sph_count***, ***_sph_distinct*** zawierające liczniki dla klauzul ***@groupby***, ***@count*** oraz ***@distinct***. Ponadto, dla tabeli-pośrednika trzeba ustawić ***ENGINE=SPHINX***.

Po wysłaniu zapytania i otrzymaniu wyników ze *Sphinxa*, pomocnicza tabela będzie zawierała znalezione wyniki: identyfikator dokumentu - kolumna *id*, score wyniku - *weight* oraz wartości atrybutów w kolejnych kolumnach o nazwach odpowiadającym nazwom atrybutów. Złączając otrzymane wyniki (*sphinx_results*) z tabelą (lub tabelami) przechowującymi szczegółowe informacje o dokumentach rozwiążemy problem opisany na początku artykułu.

Przykład:

{% highlight sql %}
SELECT
	d.`id`,
	d.`title`,
	d.`content`,
	d.`created_on`
FROM
	`sphinx_results` AS sphx
JOIN
	`docs` AS d
ON
	d.`id` = sphx.`id`
WHERE
	sphx.`query` = "@content Lorem ipsum;mode=extended;index=test_sphinxse1;host=127.0.0.1;port=9312;" 
{% endhighlight %}

Ze złączenia wyników ze Sphinxa z innymi tabelami będziemy mogli zrezygnować jeśli w indeksie będziemy przechowywali (w postaci atrybutów) także wartości, w których wyszukujemy (np. *title*, *content* itd.). W tym celu konieczna jest zmiana konfiguracji indeksu:

{% highlight apache %}
#############################################################################
## source definition
#############################################################################
 
source src3 : common
{
	sql_query 		= SELECT id, attr1, attr2, attr3, \
				UNIX_TIMESTAMP(created_on) created_on, \
				title, content FROM docs WHERE \
				id >= $start AND id <= $end
 
	sql_attr_uint 		= attr1
	sql_attr_uint 		= attr2
	sql_attr_uint 		= attr3
	sql_field_string 	= title
	sql_field_string 	= content
	sql_attr_timestamp 	= created_on
}
{% endhighlight %}

W tym przypadku nasze zapytanie będzie wyglądało następująco:

{% highlight sql %}
SELECT
	`id`,
	`title`,
	`content`,
	`created_on`
FROM
	`sphinx_results`
WHERE
	`query` = "@content Lorem ipsum;mode=extended;index=test_sphinxse2;host=127.0.0.1;port=9312;"; 
{% endhighlight %}

Składania zapytań SphinxSE została szczegółowo opisana w [dokumentacji](http://sphinxsearch.com/docs/current.html#sphinxse-using), przedstawię jedynie typowe, godne uwagi przypadki:

* **filtrowanie** - w pierwszej kolejności wykonywane jest wyszukiwanie dla podanej frazy a następnie uzyskane wyniki są filtrowane po wybranym atrybucie, dlatego warto rozważyć (ze względów optymalizacyjnych) czy nie korzystniej będzie zaindeksować i przeszukiwać pole zawierające atrybut

{% highlight sql %}
SELECT
	d.`id`,
	d.`title`,
	d.`content`,
	sphx.`attr1`
FROM
	`sphinx_results` AS sphx
JOIN
	`docs` AS d
ON
	d.`id` = sphx.`id`
WHERE
	sphx.`query` = "@content Lorem ipsum;mode=extended;index=test_sphinxse1;filter=attr1,1;host=127.0.0.1;port=9312;"; 
{% endhighlight %}

* **grupowanie** - istnieje możliwość grupowania bezpośrednio po atrybucie (***groupby=attr:attr_name***) bądź korzystając z predefiniowanego ziarna czasowego (jeśli atrybut typu *timestamp*), np. ziarno dzienne (***groupby=day:attr_name***), tygodniowe (***groupby=week:attr_name***) itd. Dodatkowo *_sph_count* będzie zawierał liczbę wyników w danej grupie

{% highlight sql %}
SELECT
	`id`,
	`content`,
	`_sph_count` AS count
FROM
	`sphinx_results`
WHERE
	`query` = "@title sample;mode=extended;index=test_sphinxse2;groupby=attr:content;groupsort=@count desc;host=127.0.0.1;port=9312;"; 
{% endhighlight %}

* **escape'owanie wartości specjalnych w zapytaniach** - wartości specjalne takie jak np. *;* bądź *=* czy też *,* (przecinek) escape'ujemy za pomocą trzech */* (slash). Dodatkowo jeśli w zapytaniu pojawia się *"* (cudzysłów) a treść całego zapytania SphinxSE (query) zawieramy również pomiędzy *""*, w takim przypadku cudzysłów w szukanej frazie należy poprzedzić siedmioma znakami slash, ponieważ escape'ujemy wyrażenie */"* (trzy / dla slasha oraz kolejne trzy dla "). Przykład - szukamy frazy *"test" 'test' test% @;,=*

{% highlight sql %}
SELECT
	`id`,
	`title`,
	`content`
FROM
	`sphinx_results`
WHERE
	`query` = "@content \"\\\\\\\"test\\\\\\\" \\\'test\\\' test\\\% \\\@\\\;\\\,\\\=\";mode=extended;index=test_sphinxse2;host=127.0.0.1;port=9312;"; 
{% endhighlight %}

Podsumowując, pomimo tego iż ***SphinxSE*** nie oferuje pełnych możliwości wyszukiwania za pomocą SphinxSearch oraz wymaga znajomości specyficznej składni zapytań, warto rozważyć wykorzystanie tego rozwiązania w naszej aplikacji. Możemy w prosty i bardzo wydajny sposób, a przy tym nie wymagający dużych nakładów pracy, umożliwić zaawansowane wyszukiwanie w naszym serwisie.

Przydatne linki:

* [http://sphinxsearch.com/docs/current.html#sphinxse](http://sphinxsearch.com/docs/current.html#sphinxse)
* [http://www.slideshare.net/bytebot/mariadb-with-sphinxse](http://www.slideshare.net/bytebot/mariadb-with-sphinxse)
* [https://mariadb.com/kb/en/sphinxse/](https://mariadb.com/kb/en/sphinxse/)
* [http://www.pythian.com/blog/using-the-sphinx-search-engine-with-mysql/](http://www.pythian.com/blog/using-the-sphinx-search-engine-with-mysql/)
* [http://www.mysqlperformanceblog.com/2013/01/15/sphinx-search-performance-optimization-attribute-based-filtering/](http://www.mysqlperformanceblog.com/2013/01/15/sphinx-search-performance-optimization-attribute-based-filtering/)


