---
layout: post
title: "PHP - Events collector"
description: "Podstawą działania dobrze zaprojektowanych systemów jest znajomość zdarzeń jakie tam wystąpiły. Takimi zdarzeniami mogą być przykładowo kolejne operacje wykonywane przez użytkownika..."
headline: 
modified: 2016-03-23
category: php
tags: [php, asynchronous, daemon, react, rest api, events]
comments: true
featured: false
---

Podstawą działania dobrze zaprojektowanych systemów jest znajomość *zdarzeń* jakie tam wystąpiły. Takimi zdarzeniami mogą być przykładowo kolejne operacje wykonywane przez użytkownika (zaglowanie, wyszukanie produktu, dodanie do koszyka, skompletowanie zamówienia itd.). Na podstawie takich zdarzeń mogą być generowane raporty (np. sprzedaży), budowane a następnie zapisywane obiekty w danym storage'u, przywracanie stanu historycznego czy też podejmowane określone akcje. Aby była możliwa realizacja wymienionych zadań konieczne jest rejestrowanie wszystkich, nawet tych najmniejszych, zdarzeń. Sam proces zbierania takich zdarzeń w środowisku jednowątkowych, krótko żyjących (wyłączenia na czas przetwarzania requestu) procesów jest dość prosty. Dużo bardziej skomplikowana jest obsługa agregacji zdarzeń na potrzeby podejmowania zdefiniowanyh zadań. W tym celu konieczna jest implementacja długo-żyjącego *daemona*... 
   
W kilku poprzednich [artykułach](http://tswiackiewicz.github.io/inside-the-source-code/php/php-daemons-czesc-ii/) omówiłem sposoby implementacji takiego ***daemona*** w PHP. Jednak od tego czasu minęło trochę czasu, zebrałem nowe doświadczenia, kod pisany przeze mnie dzisiaj (przynajmniej tak mi się wydaje) jest dużo lepszy niż ten implementowywany wówczas no i postanowiłem spróbować nowych rozwiązań. Tym razem, poprzednie rozwiązania oparte były o **forkowanie procesów** oraz **libevent**, postanowiłem skorzystać z gotowych frameworki wspierających wielo-wątkowość w PHP - [React PHP](https://github.com/reactphp) oraz [Icicle](https://github.com/icicleio).

Nie chciałem jednak, aby powstał kolejny przykład *hello world* czy inne stworzone na siłę rozwiązanie. Pomysł na aplikację pojawił się zanim postanowiłem przygotować niniejszy post. Otóż, problem zajawiony we wstępie jest dość powszechny i występuje w wielu organizacjach: najprostszy przypadek, rejestrujemy wszelkiego rodzaju błędy oraz anomalie, a następnie gdy liczba takich zdarzeń przekroczy wyznaczony poziom, podejmowana jest określona akcja, np. notyfikacja na maila, Slacka czy też wysłanie SMSa. W ten sposób zdefiniowaliśmy wymagania dla naszej aplikacji - rejestracja *eventów* oraz podejmowanie akcji po przekroczeniu progów. Ponadto powinna być możliwość konfiguracji takiej usługi i nie chcielibyśmy ograniczać się wyłącznie do jednej technologii (tutaj PHP), a więc potrzebny będzie uniwersalny interface - REST API. 

### React PHP

Początkowo swoje kroki skierowałem ku nowemu dla mnie rozwiązaniu tj. ***Icicle***. Niestety dość szybko okazało się, że implementacja daemona w takim kształcie jakim potrzebowałem (REST API), nie jest taka prosta, stąd po kilku wieczorach spędzonych nad kodem i dokumentacją przełączyłem się na ***React PHP*** - chyba najbardziej popularne rozwiązanie dla wielowątkowego, asynchronicznego przetwarzania dostępnego dla PHP. Nie oznacza to jednak, że *Icicle* się nie nadaje do realizacji mojego zadania czy też nie wrócę w przyszłości do zbadania ponownie zagadnienia, na tą chwilę uznałem, że ważniejsze będzie osiągnięcie wyznaczonego celu, czyli implementacja aplikacji zgodnie z wymaganiami.

### Architektura
   
Obsługa wielu requestów przez pojedynczy proces realizowana jest dzięki obsłudze najwydajniejszego rozwiązania (*socket select*, *libevent*, *libev* itd.) dla danego środowiska za pomocą *Reacta*. 

Dla pełnego obrazu warto zapoznać się z podstawowymi pojęciami *domenowymi* aplikacji:
   
* ***event*** - rodzaj zdarzenia (rejestrowanego oraz obserwowanego), np. *user_logged_in*
* ***collector*** - sposób rejestracji zdarzenia danego typu za pomocą zdefiniowanego appendera, np. *syslog*
* ***watcher*** - obserwator zdarzenia danego typu, w przypadku przekroczenia ustalonej liczby zdarzeń (agregowanych zgodnie z ustaloną polityką) danego typu podejmowana jest podpięta akcja, np. *wysłanie powiadomienia na maila, Slacka czy SMS*   

Dodawanie nowych typów zdarzeń oraz rejestracja *collector'ów* i *watcher'ów* odbywa się za pośrednictwem *REST API*. Podobnie, rejestracja nowego zdarzenia - przykład:  

{% highlight bash %}
curl -XPOST 'http://127.0.0.1:1234/event/user_logged_in/' -d '
{
    "user_id": 1,
    "ip": "192.168.0.1", 
    "login": "testuser",
    "date": "2016-03-23 12:00:00", 
    "host": "collector-node-1"     
}'
{% endhighlight %}

odpowiedź

{% highlight json %}
{
  "_id": "87501f8a-8446-48ae-a26e-15a3fd7cdb1b"
}
{% endhighlight %}

### Wydajność, skalowalność

Proponowane rozwiązanie, na tą chwilę, będzie poprawnie działało wyłącznie w przypadku *single node'a*. Rozszerzenie zakresu działania na wiele *node'ów* będzie wymagało innej organizacji agregacji wystąpień zdarzeń (np. współdzielony key-value storage) - wówczas będzie można zbadać jak niniejsze rozwiązanie podatne jest na skalowanie. Na chwilę obecną pomysłów zbadania skalowalności jest kilka: proxy *Nginx*, *ZooKeeper* oraz *Consul*.

Przeprowadzone benchmarki pokazały, iż wydajność takiego daemona opartego o React PHP kształtuje się na poziomie 850-900 requestów / sekundę. Nie jest to wynik powalający na kolana, niemniej dalsze prace nad rozwojem i być może jego sklaowaniem na więcej instancji pozwoli uzyskać dużo lepsze rezultaty.

Słowem podsumowania, frameworki pokroju *Icicle* czy *React PHP* pozwalają na implementację daemonów w PHP o stosunkowo niskim progu wejścia. Wydajność takiego rozwiązania jest średnia (ok. 10 razy mniejsza niż podobne rozwiązanie zaimplementowane w *Go*). Niemniej rozwój frameworków, samego języka PHP oraz bogatego community pozwalają mieć nadzieje, że te rezultaty wkrótce będą dużo lepsze. No i w końcu, zdarzenia obecne są niemalże w każdej aplikacji. Implementacja takiego prostego daemona, czy też skorzystanie z proponowanego przeze mnie rozwiązania, może uzupełnić naszą aplikację o tak bardzo pożądany *reactive monitoring*.

Komplenty kod znajdziecie tutaj -  [events collector](https://github.com/tswiackiewicz/events-collector).

Przydatne linki:

* [https://medium.com/async-php/reactive-php-events-d0cd866e9285#.jq2yihop8](https://medium.com/async-php/reactive-php-events-d0cd866e9285#.jq2yihop8)
* [https://medium.com/async-php/co-operative-php-multitasking-ce4ef52858a0#.x3hxsh7c1](https://medium.com/async-php/co-operative-php-multitasking-ce4ef52858a0#.x3hxsh7c1)
* [https://github.com/reactphp](https://github.com/reactphp)
* [http://www.sitepoint.com/build-a-superfast-php-server-in-minutes-with-icicle/](http://www.sitepoint.com/build-a-superfast-php-server-in-minutes-with-icicle/)
* [https://github.com/icicleio](https://github.com/icicleio)
* [https://github.com/ratchetphp/Ratchet](https://github.com/ratchetphp/Ratchet)
* [https://github.com/ReactiveX/RxPHP](https://github.com/ReactiveX/RxPHP)
* [https://github.com/recoilphp/recoil](https://github.com/recoilphp/recoil)
* [http://nikic.github.io/2012/12/22/Cooperative-multitasking-using-coroutines-in-PHP.html](http://nikic.github.io/2012/12/22/Cooperative-multitasking-using-coroutines-in-PHP.html)
* [http://marcjschmidt.de/blog/2014/02/08/php-high-performance.html](http://marcjschmidt.de/blog/2014/02/08/php-high-performance.html)

