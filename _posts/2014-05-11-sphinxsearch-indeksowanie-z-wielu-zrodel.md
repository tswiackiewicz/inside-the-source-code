---
layout: post
title: "SphinxSearch - indeksowanie z wielu źródeł"
description: "Podstawą funkcjonowania rozwiązań wykorzystujących silniki wyszukiwania pełnotekstowego, takich jak np. SphinxSearch, jest obecność danych w indeksie. Sphinx umożliwia indeksowanie bezpośrednio z bazy bądź wykorzystując dedykowany format XML - xmlpipe2. Pierwsze rozwiązanie charakteryzuje..."
headline: 
modified: 2014-05-11
category: sphinxsearch
tags: [sphinx, indexer, xmlpiep2, sphinxsearch]
comments: true
featured: false
---

Podstawą funkcjonowania rozwiązań wykorzystujących silniki wyszukiwania pełnotekstowego, takich jak np. ***SphinxSearch***, jest obecność danych w indeksie. Sphinx umożliwia indeksowanie bezpośrednio z bazy bądź wykorzystując dedykowany format <abbr title="Extensible Markup Language">XML<abbr> - *xmlpipe2*. Pierwsze rozwiązanie charakteryzuje wysoka wydajność, drugie z kolei pozwala na większą elastyczność i dostosowanie indeksowanych danych wedle potrzeb. W przypadku, gdy dane do indeksu trafiają z jednego źródła danych, zazwyczaj naturalnym wyborem jest pierwszy z wymienionych sposobów. Czy w przypadku wielu źródeł także wybierzemy to rozwiązanie?

Jeśli także w tym przypadku zdecydujemy się na indeksowanie danych prosto z bazy, wystarczy zdefiniować poszczególne źródła (odrębne sekcje *source*) oraz podpiąć je do wybranego indeksu. Należy jednak pamiętać o zachowaniu unikalności identyfikatorów dokumentów. Przykładowym rozwiązaniem tego problemu może być postępowanie według następującego wzorca: dla pierwszego źródła przypisujemy identyfikatory z przedziału *1* do *1M*, dla drugiego *1M+1* do *2M*, kolejnego *2M+1* - *3M* itd. Ale co w przypadku, gdy jedno ze źródeł będzie zawierało więcej niż *1M* dokumentów? Dodatkowo planując strukturę indeksu musimy zadbać o to, aby znalazło się tam pole przechowujące rzeczywisty identyfikator (klucz) dokumentu, np. *doc_id*, tak aby można było skojarzyć znaleziony dokument z danym rekordem ze źródła danych.

Przed podjęciem decyzji o wyborze tego rozwiązania, warto rozważyć jeszcze jedną kwestię - jak często zmienia się lista źródeł, z których będziemy pobierali dane? Usunięcie bądź dodanie kolejnego źródła będzie skutkowało wprowadzeniem zmian w konfiguracji (*sphinx.conf*), ponownym uruchomieniem procesu Sphinxa (*searchd*) oraz załadowaniem wszystkich indeksów (jeśli flaga ***preopen*** została włączona). Zatem częsta zmiana źródeł, gdy rozmiar zaindeksowanych danych jest znaczący, może być kosztowna. Natomiast wybierając drugi sposób dostarczania danych dla indeksera tj. ***xmlpipe2*** dynamika zmian źródeł danych nie wpływa na stabilność oraz wydajność naszego systemu.

Oczywiście nadal powinniśmy zapewnić unikalność identyfikatorów dokumentów oraz warto przechowywać w indeksie wewnętrzny identyfikator dokumentu (*doc_id*). Jednak dodanie nowego źródła nie będzie wymagało zmian w konfiguracji oraz restartu procesu Sphinxa. Wystarczy, że skrypt generujący indeksowane dane w formacie *xmlpipe2* obsłuży zmianę listy źródeł danych. Co więcej, wybierając to rozwiązanie możemy dowolnie modyfikować indeksowane dane, np. konwersja polskich znaków diakrytycznych na ich odpowiedniki *bez ogonków*, zastępowanie wybranych identyfikatorów pełnymi (słownikowymi) nazwami, np. coutry_code = 1 - Poland itd. Dodatkowo nie jesteśmy ograniczani do pobierania danych wyłącznie z bazy danych. Implementując skrypt generujący dane w formacie *XML* możemy pobierać dane także ze źródeł zwracających dane w formacie XML (np. *RSS*), JSON, dane z innych usług sieciowych (np. web service) itd. 

Przykładowa konfiguracja źródła generowanego z wykorzystaniem *xmlpipe2*

{% highlight apache %}
#############################################################################
## source definition
#############################################################################
 
source src2
{
	type 			= xmlpipe2
	xmlpipe_command 	= cat /var/lib/sphinxsearch/test.xml
 
	xmlpipe_field 		= title
	xmlpipe_field 		= content
 
	xmlpipe_attr_bigint 	= attr1
	xmlpipe_attr_bool 	= attr2
	xmlpipe_attr_timestamp 	= attr3
	xmlpipe_attr_float 	= attr4
	xmlpipe_attr_json 	= attr5
 
	xmlpipe_fixup_utf8 	= 1
}
{% endhighlight %}

Wydawać by się mogło, że *xmlpipe2* jest rozwiązaniem idealnym, ale tak nie jest. Indeksowanie danych w ten sposób jest dużo wolniejsze niż w przypadku bezpośredniego pobierania z bazy danych. Niemniej, nadal jest dość wydajne. Dodatkowo *xmlpipe2* posiada ograniczenie co do rozmiaru danych dla pojedynczego pola (*max_xmlpipe2_field*) oraz typowe ograniczenia formatu XML m.in. konieczność escape'owania znaków specjalnych, np. *<, &*.

Podsumowując, Sphinx umożliwia bardzo wydajne indeksowanie bezpośrednio z bazy danych oraz nieco mniej wydajny, za to elastyczny format xmlpipe2. Żaden ze wspomnianych formatów nie zapewnia wymagania unikalności identyfikatorów indeksowanych dokumentów (w przypadku indeksowania z wielu źródeł), ale każdy z nich daje inne możliwości. Wybór rozwiązania (bezpośrednie indeksowanie z bazy vs xmlpipe2) powinniśmy dostosować każdorazowo do projektowanego systemu.

Przydatne linki:

* [http://jetpackweb.com/blog/2009/08/16/sphinx-xmlpipe2-in-php-part-i/](http://jetpackweb.com/blog/2009/08/16/sphinx-xmlpipe2-in-php-part-i/)
* [http://sphinxsearch.com/forum/view.html?id=1215](http://sphinxsearch.com/forum/view.html?id=1215)
* [http://sphinxsearch.com/wiki/doku.php?id=sphinx_xmlpipe2_tutorial2](http://sphinxsearch.com/wiki/doku.php?id=sphinx_xmlpipe2_tutorial2)
* [http://sphinxsearch.com/forum/view.html?id=3913](http://sphinxsearch.com/forum/view.html?id=3913)
* [http://www.ivinco.com/blog/scripting-in-sphinx-config/](http://www.ivinco.com/blog/scripting-in-sphinx-config/)

