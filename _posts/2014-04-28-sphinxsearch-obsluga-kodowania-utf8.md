---
layout: post
title: "SphinxSearch - obsługa kodowania UTF-8"
description: "Dawno minęły czasy kiedy w sieci królował tylko jeden język - angielski. Obecnie znaczna część dużych serwisów internetowych może pochwalić się obsługą wielu języków, aby dotrzeć do jak największej liczby odbiorców. W związku z tym konieczna jest obsługa znaków charakterystycznych dla poszczególnych języków, np. znaków diakrytycznych języka polskiego..."
headline: 
modified: 2014-04-28
category: sphinxsearch
tags: [sphinx, search, indexer, sphinxsearch, utf8]
comments: true
featured: false
share: true
---

Dawno minęły czasy kiedy w sieci królował tylko jeden język - angielski. Obecnie znaczna część dużych serwisów internetowych może pochwalić się obsługą wielu języków, aby dotrzeć do jak największej liczby odbiorców. W związku z tym konieczna jest obsługa znaków charakterystycznych dla poszczególnych języków, np. znaków diakrytycznych języka polskiego, co można uzyskać dzięki kodowaniu ***UTF-8***. A jak wygląda wsparcie *UTF-8* przez ***SphinxSearch***?

W trakcie procesu indeksowania *Sphinx* pobiera kolejne dokumenty z podanego źródła (baza danych, XML), rozbija na poszczególne słowa (tokeny) oraz dokonuje ich konwersji, np. zamiana wielkich liter na małe. Aby przeprowadzić wspomniany proces prawidłowo niezbędne jest określenie kodowania tekstu źródłowego, określenia zbioru liter, mapy konwersji znaków (np. polskich znaków diakrytycznych na ich odpowiedniki *bez ogonków*) oraz zbioru separatorów. *Sphinx* umożliwia skonfigurowanie każdego z indeksów osobno.

Obsługa kodowania realizowana jest przez opcję ***charset_type*** (sekcja *index*). Domyślną wartością jest *sbcs*, ale od wersji *2.2* opcja ta traktowana będzie jako przestarzała (*ang. deprecated*) i jedynym (domyślnym) kodowaniem w ten sposób będzie ***UTF-8***.

Jednak samo ustawienie kodowania nie rozwiązuje wszystkich problemów. Domyślnie wszystkie znaki, oprócz liter angielskiego, rosyjskiego alfabetu oraz cyfr, traktowane są jako separatory. Dodatkowo wielkie oraz małe litery są rozróżniane, tak samo jak polskie znaki diakrytyczne *z ogonkami* i *bez ogonków*. Z punktu widzenia działania naszej aplikacji chcielibyśmy, aby użytkownik działał intuicyjnie i nie zastanawiał się czy wpisując frazę *Gdańsk* otrzyma także wyniki zawierające *Gdansk* oraz *gdansk*. Należy pamiętać o tym, że wybrane kodowanie oraz lista znaków traktowanych jako litery (wraz z ich ewentualnymi przekształceniami) będzie wpływało na indeksowanie dokumentów oraz wyszukiwanie (parsowanie zapytań i rozbijanie na poszczególne słowa). W związku z tym, oprócz wyboru kodowania (*charset_type*) konieczne jest zdefiniowane znaków traktowanych jako litery wraz z ich konwersją (wielkich liter na małe, polskich znaków diakrytycznych *z ogonkami* na ich odpowiedniki *bez ogonków* itd.).

Umożliwia to opcja ***charset_table*** (również sekcja *index*):

{% highlight apache %}
#############################################################################
## index definition
#############################################################################

index test_utf8
{
	source 		= src1
	path 		= /var/lib/sphinxsearch/data/test_utf8
	min_word_len 	= 2
 
	# charset definition and case folding rules "table"
	# optional, default value depends on charset_type
	#
	# defaults are configured to include English and Russian characters only
	# you need to change the table to include additional ones
	# this behavior MAY change in future versions
	#
	# 'sbcs' default value is
	# charset_table = 0..9, A..Z->a..z, _, a..z, U+A8->U+B8, U+B8, \
	# U+C0..U+DF->U+E0..U+FF, U+E0..U+FF
	#
	# 'utf-8' default value is
	charset_table 	= 0..9, A..Z->a..z, _, a..z, \
			U+410..U+42F->U+430..U+44F, U+430..U+44F
}
{% endhighlight %}

Podsumowując powyższe rozważania, obsługę języka polskiego (polskie znaki diakrytyczne, zamiana wielkich liter na małe oraz polskich znaków diakrytycznych na ich odpowiedniki *bez ogonków*) uzyskamy dzięki następującej konfiguracji:

{% highlight apache %}
#############################################################################
## index definition
#############################################################################
 
index test_pl_chars1
{
	source 		= src1
	path 		= /var/lib/sphinxsearch/data/test_pl_chars1
	min_word_len 	= 2
 
	# Polish case folding
	charset_table 	= 0..9, A..Z->a..z, a..z, U+0143->U+0144, \
			U+0104->U+0105, U+0106->U+0107, U+0118->U+0119, \
			U+0141->U+0142, U+00D3->U+00F3, U+015A->U+015B, \
			U+0179->U+017A, U+017B->U+017C, U+0105, U+0107, \
			U+0119, U+0142, U+00F3, U+015B, U+017A, U+017C, \
			U+0144
}
 
index test_pl_chars2
{
	source 		= src1
	path 		= /var/lib/sphinxsearch/data/test_pl_chars2
	min_word_len 	= 2
 
	# Polish case folding with mapping to a..z chars e.g ą->a, Ć->c etc.
	charset_table 	= 0..9, A..Z->a..z, a..z, U+0104->a, U+0105->a, \
			U+0106->c, U+0107->c, U+0118->e, U+0119->e, \
			U+0141->l, U+0142->l, U+00D3->o, U+00F3->o, \
			U+0143->n, U+0144->n, U+015A->s, U+015B->s, \
			U+0179->z, U+017A->z, U+017B->z, U+017C->z
}
{% endhighlight %}

Zainteresowanych tematem (oraz obsługą innych języków niż angielski, polski czy rosyjski) odsyłam do lektury:

* [http://sphinxsearch.com/wiki/doku.php?id=charset_tables](http://sphinxsearch.com/wiki/doku.php?id=charset_tables)
* [https://github.com/tom--/Collation-to-Charset-Table](https://github.com/tom--/Collation-to-Charset-Table)
* [http://sphinxsearch.com/forum/view.html?id=1133](http://sphinxsearch.com/forum/view.html?id=1133)
* [http://thefsb.wordpress.com/2010/12/](http://thefsb.wordpress.com/2010/12/)
* [http://sphinxsearch.com/blog/2013/09/11/deprecations-and-changes-in-the-2-2-series/](http://sphinxsearch.com/blog/2013/09/11/deprecations-and-changes-in-the-2-2-series/)
* [http://sphinxsearch.com/docs/current.html#charsets](http://sphinxsearch.com/docs/current.html#charsets)
* [http://sphinxsearch.com/docs/2.1.7/conf-charset-type.html](http://sphinxsearch.com/docs/2.1.7/conf-charset-type.html)
* [http://www.ivinco.com/blog/sphinx-in-action-how-sphinx-handles-text-during-indexing/](http://www.ivinco.com/blog/sphinx-in-action-how-sphinx-handles-text-during-indexing/)
* [http://sphinxsearch.com/forum/view.html?id=9312](http://sphinxsearch.com/forum/view.html?id=9312)


