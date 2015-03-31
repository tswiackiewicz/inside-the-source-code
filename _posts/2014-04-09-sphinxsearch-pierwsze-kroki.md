---
layout: post
title: "SphinxSearch - pierwsze kroki"
description: "Obecnie praktycznie każdy serwis internetowy, prosty czy też rozbudowany, oferuje możliwość przeszukiwania jego zasobów. O ile w przypadku tych mniej zaawansowanych, z mniejszą liczbą zasobów, specjalizowane rozwiązanie nie jest niezbędne, o tyle te posiadające spory wolumen przeszukiwanych zasobów, takich wysoce wydajnych rozwiązań będą wymagały."
headline: 
modified: 2014-04-09
category: sphinxsearch
tags: [sphinx, search, sphinxsearch]
comments: true
featured: false
---

Obecnie praktycznie każdy serwis internetowy, prosty czy też rozbudowany, oferuje możliwość przeszukiwania jego zasobów. O ile w przypadku tych mniej zaawansowanych, z mniejszą liczbą zasobów, specjalizowane rozwiązanie nie jest niezbędne, o tyle te posiadające spory wolumen przeszukiwanych zasobów, takich wysoce wydajnych rozwiązań będą wymagały.

Z uwagi, iż spora część ze wspomnianych serwisów zrealizowana została z wykorzystaniem *[MySQL](http://www.mysql.com/)*, naturalnym wydaje się wykorzystanie wbudowanego mechanizmu szukania pełnotekstowego. Okazuje się, że przy dużej ilości danych, oferowana przez to rozwiązanie wydajność nie jest zadowalająca. Świetną alternatywą może być ***SphinxSearch*** - dedykowany pełnotekstowy silnik wyszukiwania charakteryzujący się wysoką wydajnością, nawet przy dużych wolumenach danych.

Samego rozwiązania nie będę szczegółowo przedstawiał, ponieważ oferowana dokumentacja jest bardzo dobra - dla zainteresowanych [http://sphinxsearch.com/](http://sphinxsearch.com/). Przedstawię jednak pozbierane, i pogrupowane wg najważniejszych bloków, przeze mnie materiały, które mogą okazać się pomocne podczas rozwiązywania problemów związanych z wyszukiwaniem za pomocą *Sphinxa*.


### OGÓLNIE

* [http://sphinxish.blogspot.com/2014/03/sphinx-resources.html](http://sphinxish.blogspot.com/2014/03/sphinx-resources.html)
* [http://www.ibm.com/developerworks/library/os-php-sphinxsearch/](http://www.ibm.com/developerworks/library/os-php-sphinxsearch/)
* [https://www.ibm.com/developerworks/library/os-sphinx/](https://www.ibm.com/developerworks/library/os-sphinx/)
* [http://www.ivinco.com/blog/five-ways-to-configure-sphinx-search-engine/](http://www.ivinco.com/blog/five-ways-to-configure-sphinx-search-engine/)
* [http://www.slideshare.net/billkarwin/practical-full-text-search-with-my-sql](http://www.slideshare.net/billkarwin/practical-full-text-search-with-my-sql)
* [http://sphinxsearch.com/blog/category/tutorials/](http://sphinxsearch.com/blog/category/tutorials/)

### ARCHITEKTURA

* [http://sphinxsearch.com/forum/view.html?id=12204](http://sphinxsearch.com/forum/view.html?id=12204)
* [http://www.nearby.org.uk/sphinx/architecture.php](http://www.nearby.org.uk/sphinx/architecture.php)
* [https://sphinxsearch.googlecode.com/svn/trunk/doc/internals-index-format.txt](https://sphinxsearch.googlecode.com/svn/trunk/doc/internals-index-format.txt)
* [http://sphinxsearch.com/blog/2013/11/15/sphinx-internals-expressions/](http://sphinxsearch.com/blog/2013/11/15/sphinx-internals-expressions/)


### INDEKSOWANIE

* [http://www.nearby.org.uk/sphinx/sphinx-tokenizing.gif](http://www.nearby.org.uk/sphinx/sphinx-tokenizing.gif)
* [http://www.ivinco.com/blog/sphinx-in-action-how-sphinx-handles-text-during-indexing/](http://www.ivinco.com/blog/sphinx-in-action-how-sphinx-handles-text-during-indexing/)
* [https://blog.engineyard.com/2009/5-tips-for-sphinx-indexing](https://blog.engineyard.com/2009/5-tips-for-sphinx-indexing)
* [http://sphinxsearch.com/blog/2012/08/14/indexing-tips-tricks/](http://sphinxsearch.com/blog/2012/08/14/indexing-tips-tricks/)
* [http://www.ivinco.com/blog/how-to-improve-sphinx-indexing-performance/](http://www.ivinco.com/blog/how-to-improve-sphinx-indexing-performance/)
* [http://blog.stunf.com/building-a-scalable-real-time-search-architecture-with-sphinx/](http://blog.stunf.com/building-a-scalable-real-time-search-architecture-with-sphinx/)
* [http://www.sanisoft.com/blog/2010/12/27/how-to-live-index-updates-in-sphinx-search/](http://www.sanisoft.com/blog/2010/12/27/how-to-live-index-updates-in-sphinx-search/)


### OPTYMALIZACJA, WYDAJNOŚĆ

* [http://sphinxsearch.com/blog/2014/03/05/wildcarding-performance/](http://sphinxsearch.com/blog/2014/03/05/wildcarding-performance/)
* [http://sphinxsearch.com/blog/2014/02/12/rt_performance_basics/](http://sphinxsearch.com/blog/2014/02/12/rt_performance_basics/)
* [http://sphinxsearch.com/files/tutorials/sphinx_config_tips_and_tricks.pdf](http://sphinxsearch.com/files/tutorials/sphinx_config_tips_and_tricks.pdf)
* [http://www.ivinco.com/blog/sphinx-rt-indexes-memory-consumption-issue/](http://www.ivinco.com/blog/sphinx-rt-indexes-memory-consumption-issue/)
* [http://sphinxsearch.com/blog/2013/11/05/sphinx-configuration-features-and-tricks/](http://sphinxsearch.com/blog/2013/11/05/sphinx-configuration-features-and-tricks/)
* [http://www.mysqlperformanceblog.com/2013/01/16/sphinx-search-performance-optimization-multi-threaded-search/](http://www.mysqlperformanceblog.com/2013/01/16/sphinx-search-performance-optimization-multi-threaded-search/)
* [http://www.mysqlperformanceblog.com/2013/01/15/sphinx-search-performance-optimization-attribute-based-filtering/](http://www.mysqlperformanceblog.com/2013/01/15/sphinx-search-performance-optimization-attribute-based-filtering/)
* [http://sphinxsearch.com/files/tutorials/sphinx_faster_phrase_queries_with_bigrams.pdf](http://sphinxsearch.com/files/tutorials/sphinx_faster_phrase_queries_with_bigrams.pdf)
* [http://notes.jschutz.net/topics/sphinx-search-engine/](http://notes.jschutz.net/topics/sphinx-search-engine/)
* [http://sphinxsearch.com/blog/2011/10/19/dist_threads-the-new-right-way-to-use-many-cores/](http://sphinxsearch.com/blog/2011/10/19/dist_threads-the-new-right-way-to-use-many-cores/)

