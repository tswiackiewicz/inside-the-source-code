---
layout: post
title: "Blogging like a hacker"
description: ""
headline: 
modified: 2015-05-18
category: sphinxsearch
tags: [jekyll, GitHub, highlightjs, disqus]
comments: true
featured: false
---

Gdy zaczynałem pisać pierwszy post na mojego bloga, myślałem tylko o tym że chcę podzielić się swoją wiedzą z innymi, wymienić doświadczenia. Nie miałem zamiaru spędzać długich godzin szukając właściwego rozwiązania do realizacji mojego celu. Potrzebowałem narzędzia, które będzie proste i przyjazne w użyciu. Dlatego też odrzuciłem te bardzo rozbudowane i skompliowane jak choćby *WordPress*. Po krótkiej chwili namysłu wybór padł na platformę blogową przygotowaną przez *Google* - **blogspot** vs **blogger**. Dość szybko okazało się jednak, że wszystko to co dostawałem *z pudełka* razem z wybraną platformą, było za mało patrząc na to czego oczekiwałem. 


### Markdown

Edytory WYSIWYG wcale nie są tak wygodnym narzędziem na jakie mogą wyglądać. Natomiast przygotowywanie artykułów w postaci *HTMLa* jest czasochłonne a sama treść artykułu ginie w gąszczu znaczników. Co więcej, pracując z kodem czy też przygotowując dokumentację, bardzo często pierwszym wyborem jest plik .md (*Markdown*). Jego składnia jest prosta i łatwa do nauki. Przygotowując taki dokument możemy skupić się wyłącznie na treści a nie sposobie w jaki zostanie ona wyświetlona.     


### Elastyczność

Z jednej strony oczekujemy, że platforma sama w sobie dostarczy gotowe do użycia szablony. Z drugiej strony, zawsze znajdą się elementy, które będą bardziej lub mniej nam pasowały - będziemy starali się dostosować je do własnych potrzeb. Edycja szablonów przez narzędzia typu *blogger* czy podobne jest niestety mało przyjazna. Co więcej, zmuszeni jesteśmy do operowania *na otwartym sercu* - surowy kod HTML, CSS itd. Oczekiwalibyśmy dużo większej elastyczności i swobody, jaką może zapewnić nam edycja plików CSS, JS, HTML tam gdzie potrzebujemy oraz korzystania z gotowych komponentów tam gdzie potrzebujemy, np. komentarze dostarczane przez [Disqus](https://disqus.com/) czy też szablony w formacie plików *YAML*.


### GitHub Pages

Jednym z najważniejszych wymagań stawianym przeze mnie było wersjonowanie kodu, w tym wypadku treści artykułów. Utrzymywanie i organizowanie różnych wersji przygotowywanego materiału na własną rękę jest dość uciążliwe. Dochodzi do tego, zawsze, niebezpieczeństwo potencjalnej utraty danych w wyniku awarii sprzętu, np. pad dysku twardego. 

Odpowiedzią na powyższy postulat jest [GitHub](https://github.com/). Dzięki integracji GitHub Pages z Jekyllem, możemy przechowywać po stronie GitHuba wersjonowny content, który następnie serwowany jest z wykorzystaniem silnika Jekyll.


### Static content

Równie ważna jest szybkość działania platformy. Zamieszczane przeze mnie artykuły powinny być w jakiś sposób przechowywane, np. w postaci wpisów w bazie danych. Jednak równie dobrze, z uwagi na brak iterakcji z użytkownikiem, mogę być organizowane w postaci statycznych plików serwowanych następnie przez serwer www. Zaletą takiego rozwiąznaia jest przede wszystkim szybkość, przenośność, skalowalnosć. Narzut związany z bazą danych nie jest tutaj niezbędny.

Do serwowania statycznej treści bardzo dobrze nadaje się **Jekyll**, a ponadto możemy postawić sobie lokalne środowisko i podglądać jak nasz post będzie docelowo wyglądał, jeszcze zanim wyląduje na serwerze.


### Konsola

No i w końcu najważniejsze - konsola. Podstawowym narzędziem każdego *software developera* jest konsola. To ona daje nam pełną kontrolę nad wszystkim co robimy i czym zarządzamy. Takiego też narzędzia poszukujemy również podczas codziennej pracy z naszym blogiem. Treść posta możemy przygotować w dowolnym edytorze, np. *vim*, szablony, arkusze styli czy surowego HTMLa również możemy edytować z poziomu konsoli. Następnie przygotowany tak dokument dodamy do repo i opublikujemy na GitHub Pages. Oczywiście również z konsoli. 


Podsumowując, korzystanie z gotowych platform wydaje się być dobrym rozwiązaniem jeśli chcemy szybko wystartować z naszym blogiem. Jednak wraz z rozwojem naszego produktu, przychodzi potrzeba nadania mu indywidualnego charakteru a więc dostosowania go wedle własnego uznania. Dodatkowo mając na uwadze wymienione powyżej wymagania, może szybko okazać się iż proces *customizacji* naszego bloga może być czasochłonny. Możemy jednak skorzystać z innych przyjanych i sprawdzonych rozwiązań a takim jest zestaw: GitHub Pages + Jekyll. Dodatkowo community wokół tego projektu jest bardzo aktywne a wachlarz dostępnych szablonów bardzo bogaty. Tym sposobem w zaledwie kilka wieczorów, w zależności od naszych potrzeb, możemy równie niskim kosztem zacząć publikować nasze posty na platformie o dużo większej elastyczności z naszym ulubionym interface'm czyli konsolą.   

Zatem, udanego blogowania! 


Przydatne linki:

* [http://www.jaridmargolin.com/why-i-decided-to-use-jekyll.html](http://www.jaridmargolin.com/why-i-decided-to-use-jekyll.html)
* [http://tom.preston-werner.com/2008/11/17/blogging-like-a-hacker.html](http://tom.preston-werner.com/2008/11/17/blogging-like-a-hacker.html)
* [http://broken.build/2014/01/18/moving-designs/](http://broken.build/2014/01/18/moving-designs/)
* [http://www.smashingmagazine.com/2014/08/01/build-blog-jekyll-github-pages/](http://www.smashingmagazine.com/2014/08/01/build-blog-jekyll-github-pages/)
* [http://ruhoh.com/](http://ruhoh.com/)
* [https://github.com/barryclark/jekyll-now](https://github.com/barryclark/jekyll-now)
* [http://joshualande.com/jekyll-github-pages-poole/](http://joshualande.com/jekyll-github-pages-poole/)
* [http://pixelcog.com/blog/2013/jekyll-from-scratch-introduction/](http://pixelcog.com/blog/2013/jekyll-from-scratch-introduction/)
* [http://pixelcog.com/blog/2013/jekyll-from-scratch-core-architecture/](http://pixelcog.com/blog/2013/jekyll-from-scratch-core-architecture/)
* [http://pixelcog.com/blog/2013/jekyll-from-scratch-extending-jekyll/](http://pixelcog.com/blog/2013/jekyll-from-scratch-extending-jekyll/)
* [http://24ways.org/2013/get-started-with-github-pages/](http://24ways.org/2013/get-started-with-github-pages/)
* [http://bitsandbites.me/blog/2014/07/01/blogging-platform-comparison/](http://bitsandbites.me/blog/2014/07/01/blogging-platform-comparison/)
* [http://jekyllrb.com/](http://jekyllrb.com/)
* [http://jekyllbootstrap.com/usage/jekyll-quick-start.html](http://jekyllbootstrap.com/usage/jekyll-quick-start.html)
* [https://pages.github.com/](https://pages.github.com/)
* [http://fortawesome.github.io/Font-Awesome/](http://fortawesome.github.io/Font-Awesome/)
* [https://developmentseed.org/blog/google-analytics-jekyll-plugin/](https://developmentseed.org/blog/google-analytics-jekyll-plugin/)
* [http://themes.jekyllbootstrap.com/preview/dinky/](http://themes.jekyllbootstrap.com/preview/dinky/)
* [https://github.com/Phlow/feeling-responsive](https://github.com/Phlow/feeling-responsive)
* [https://github.com/rosario/kasper](https://github.com/rosario/kasper)
* [http://rosario.io/2013/11/10/kasper-theme-for-jekyll.html](http://rosario.io/2013/11/10/kasper-theme-for-jekyll.html)
* [https://github.com/joshkerr/Casper](https://github.com/joshkerr/Casper)
* [https://github.com/poole/hyde](https://github.com/poole/hyde)
* [https://github.com/poole/lanyon](https://github.com/poole/lanyon)
* [https://github.com/niklasbuschmann/contrast](https://github.com/niklasbuschmann/contrast)
* [https://github.com/hmfaysal/Notepad](https://github.com/hmfaysal/Notepad)
* [https://highlightjs.org/](https://highlightjs.org/)
* [https://gist.github.com/zakkain/3203448](https://gist.github.com/zakkain/3203448)
* [http://caiwilliamson.com/web-development/2014/11/20/javascript-syntax-highlighting-in-jekyll.html](http://caiwilliamson.com/web-development/2014/11/20/javascript-syntax-highlighting-in-jekyll.html)
* [https://thedereck.github.io/gh-pages-blog/user-manual/syntax-highlighting.html](https://thedereck.github.io/gh-pages-blog/user-manual/syntax-highlighting.html)
* [http://www.greghendershott.com/2014/11/github-dropped-pygments.html](http://www.greghendershott.com/2014/11/github-dropped-pygments.html)
* [https://disqus.com/](https://disqus.com/)
* [https://help.disqus.com/customer/portal/articles/565624-adding-comment-count-links-to-your-home-page](https://help.disqus.com/customer/portal/articles/565624-adding-comment-count-links-to-your-home-page)



