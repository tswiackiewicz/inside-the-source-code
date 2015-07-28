---
layout: post
title: "Clean code"
description: "Ostatnio miałem okazje ponownie przeczytać jedną z najważniejszych książek każdego software developera - Clean Code Uncle'a Boba. Co więcej zagadnienia związane z czystym kodem pojawiają się na niemalże każdej konferencji, przykłady: Be pragmatic, be SOLID 4Developers 2015, Przejrzysty i testowalny kod na Androidzie? Spróbujmy Clean Architecture 4Developers 2015, Core Software Design Principles for Programmers Devoxx PL 2015 itd."
headline: 
modified: 2015-08-01
category: architecture
tags: [clean code, software craftmanship, uncle bob, dry, kiss, yagni, solid, object oriented design]
comments: true
featured: false
---

Ostatnio miałem okazje ponownie przeczytać jedną z najważniejszych książek każdego software developera - *Clean Code* Uncle'a Boba. Co więcej zagadnienia związane z *czystym kodem* pojawiają się na niemalże każdej konferencji, przykłady: *Be pragmatic, be SOLID* 4Developers 2015, *Przejrzysty i testowalny kod na Androidzie? Spróbujmy Clean Architecture* 4Developers 2015, *Core Software Design Principles for Programmers* Devoxx PL 2015 itd. W końcu wszyscy lubimy pracować z dobrze zaprojektowanym, przejrzystym, łatwym w utrzymaniu i modyfikacji kodem. Osiągnięcie takiego stanu nie jest prostym zadaniem, ale możemy sobie pomóc przestrzegając ustalonych zasad. Bazując na wspomnianej książce, dostępnych przykładach i własnym doświadczeniu, postanowiłem zebrać te najważniejsze.

### DRY

Jedna z najbardziej oczywistych i jednocześnie najczęściej łamanych reguł - **Don't Repeat Yourself**. Wydaje się natualnym, aby skorzystać z istniejącego rozwiązania zamiast samemu, czasem nawet ponownie, je implementować. Wówczas łatwo o błędy, pominięcie jakiegoś przypadku użycia czy po prostu zrealizowanie tej samej funkcjonalności w inny sposób. Niestety bardzo często po prostu *lecimy z tematem* i nie zastanawiamy się, ani nie przeprowadzamy śledztwa czy interesujące nas zagadnienie nie zostało już rozwiązane. W efekcie duplikujemy kod, a wszelkie poprawki trzeba wprowadzać w wielu miejscach. 

Dobra rada - eliminujemy duplikacje w kodzie, wyodrębniajmy powtarzające się fragmenty kodu tworząc nowe funkcje oraz korzystajmy z gotowych rozwiązań.  

### KISS

Cytując klasyka (*A.Einstein*)

> If you can't explain it simply, you don't understand it well enough

Zazwyczaj najprostsze rozwiązania okazują się najskuteczniejsze, dlatego też nie komplikujmy życia sobie i innym, w końcu ktoś, nawet Ty sam, za jakiś czas będzie czytał ten kod i zastanawiał się *co podmiot liryczny miał na myśli*?  

Dobra rada - **Keep It Simple, Stupid**, czysto i przejrzyście - bez tricków, sztuczek, dodatkowych wyjaśnień czy też zbędnych optymalizacji. 

### YAGNI

Czy programista to wróżbista? Niejednokrotnie pracując nad jakimś zadaniem zostawialiśmy *furtki na przyszłość*, żeby kiedyś tam dane rozwiązanie czy inny wariant rozwiązania bądź też dodatkowe funkcjonalności były gotowe. Możemy się tłumaczyć, że znamy specyfikę naszego produktu oraz biznesu (tutaj: część organizacji odpowiedzialna za kreowanie nowych funkcjonalności wytwarzanego produktu) i prędzej czy później będziemy musieli dodać ten ekstra kawałek kodu. Tymczasem, czas leci, projekt ma swój termin, budżet itd. Jeśli nawet, pomimo tej dodatkowej pracy, udało nam się nie przekroczyć terminu, to tak czy siak klient zapłacił za dodatkową pracę której nie zamawiał. Zazwyczaj jednak występuje pierwszy scenariusz czyli termin bądź inne funkcjonalności cierpią na naszej dobrej woli. Co gorsza, jeśli do następnego sprintu przejdą punkty z poprzedniego (z powodu niezrealizowania kluczowych wymagań), koszt projektu rośnie jeszcze bardziej z powodu, mogłoby się wydawać błachego, odstępstwa od specyfikacji. Istnieje jeszcze dodatkwoe zagrożenie, że dodatkowa funkcjonalność którą przygotowaliśmy nigdy nie będzie wymagana. W efekcie uzyskamy nieużywany kawałek, a co gorsze za jakiś czas inny developer spojrzy na to i będzie zgadywał w jakim celu tutaj to się znalazło? 

Dobra rada - **You Ain't Goona Need It**, skupmy się tylko na tym co w danym momencie jest niezbędne do realizacji zadania.  

### SOLID

Jako *SOLIDny* kod możemy rozumieć *code base* zgodny z następującymi regułami:

* **Single Responsibility Principle**
* **Open/Closed Principle**
* **Liskov Substitution Principle**
* **Interface Segregation Principle**
* **Dependency Inversion Principle**

Realizacja w/w postulatów może wydawać się kosztowana, ale przyniesie wymierne korzyści w postaci przejrzystego i łatwego w modyfikacjach kodu.

Dobra rada - weźmy sobie do serca szczególnie *SRP*, dzięki czemu stopień skomplikowania naszego softu zdecydowanie zmniejszy się.

### LeBlanc's Law: Later equals never

Co masz zrobić dzisiaj, zrób jutro... Bardzo często odkładamy pewne działania na później, a efekt niemalże zawsze jest taki sam - nie zostaną one nigdy zrealizowane. Możnaby powiedzieć: *widocznie nie były aż tak niezbędne*. Prawda jednak jest taka, że późniejszy koszt ich realizacji będzie większy niż w momencie pojawienia się tych wymagań. Dodatkowo, warto się zastanowić co było powodem odłożenia ich na później - zła estymacja *story points* czy może implementacja dodatkowych *feature'ów*, które mogą przydać się później. 

Klasycznymi przykładami *I'll fix it later* mogą być:

* *I'll fix that bug later*
* *I'll write unit tests later*
* *I'll remove that copy/paste duplication later*
* *I'll remove that workaround/hot fix/complete hack later*
* *I'll make the code readable/maintainable later*

Dobra rada - świadomie i rozważnie podejmujmy decyzję odkładania realizacji zadań na później, gdyż praktycznie oznacza to automatyczne usunięcie ich z backloga  

### Zasada dobrego skauta

Koszt refactoringu całego produktu jest ogromny, przeważnie niemożliwy do przeprowadzenia. Jednak możemy ten cel osiągnąć małymi krokami każdorazowo poprawiając wyłącznie pewien jego fragment w myśl zasady:

> Leave the campground cleaner than the way you found it

Dobra rada - zawsze jest coś do poprawienia, starajmy się zostawić kod lepszym niż go zastaliśmy

### Make your code read like a story

Dużo więcej czasu poświęcamy na czytanie kodu niż go tworzenie. Zadbajmy zatem o to, aby czytanie naszego kodu było przyjemne. Po pierwsze możemy to osiągnąć stosując zasady *DRY* oraz *KISS*. Inną zalecaną praktyką może być takie podejście do budowania kolejnych funkcji, aby można było czytać kod w sposób zbliżony do układu paragrafów w książce - przechodźmy od wysokiego poziomu abstrakcji do niższych poziomów, w skrócie: najpierw metody publiczne, potem prywatne w takiej kolejności w jakiej publicze z nich korzystają. 

Dobra rada - **Step-Down Rule**

### Dobre nazwy, funkcje, klasy 

Phil Karlton stwierdził: 

> There are only two hard things in Computer Science: cache invalidation and naming things
 
Rzeczywiście dobranie dobrej nazwy może stanowić problem, niemniej zła nazwa może przysporzyć wielu problemów. Jako dobrze zdefiniowną nazwę możemy uważać taką, która:

* przedstawia intencje
* jest dokładna
* łatwa do rozróżnienia i wymówienia
* pozbawiona ozdobników
* pochodzi ze znanej dziedziny

Dobra rada - na wymyślenie *dobrej* nazwy nie poświęcajmy więcej niż 10 minut, nie bójmy się zmieniać ich nazw w razie potrzeby

Sercem naszego oprogramowania są funkcje. Oprócz dobrej nazwy wyjaśniającej, bez potrzeby komentowania, jej odpowiedzialności równie ważne jest rozumienie co dana funkcja robi. W przypadku dużych, wykraczających poza jeden ekran, i skomplikowanych funkcji ogarnięcie *big picture* może być trudne. Najważniejsze cechy dobre funkcji:

* jest mała, mieści się na jednym ekranie
* realizuje wyłącznie jedną czynność
* zawiera kod z jednego poziomu abstrakcji
* możliwie mało argumentów wejściowych
* wyłącznie argumenty wejściowe
* nie zawierają argumentów true / false - *passing boolean argument is ugly*
* do sygnalizowania błędów stosuje wyjątki
* bez side efektów

Dobra rada:

> Functions should do one thing. They should do it well. They should do it only.

W końcu, klasy, składają się z funkcji. Powinny być małe (do 200 *LOC*) i realizować pojedyncze zadania.

### Prawo Demeter

TODO: Train wrecks

### Avoid deep nesting

TODO: Return early and often

### Żółta kaczuszka

TODO: Rubber duck debugging vs bedtime debugging

### Standardy

TODO: Follow standard conventions     

### Simple design by Kent Beck

TODO: 

- passes its tests
- minimize duplications
- maximize clarity
- has fewer elements

Znaczna część z przedstawionych powyżej zasad stosowana jest przez nas nieświadomie - pisząc kod robimy to automatycznie, intuicyjnie. Pozostałe, po krótkim przemyśleniu, również są proste do wprowadzenia i nie wymagają dużego wysiłku (przynajmniej jeśli chodzi o zrozumienie). Zostały one zdefiniowane przez doświadczonych developerów na podstawie obserwacji istniejącego kodu. Nie są to zasady stworzone na siłę, tylko po to aby ułatwić naszą codzienną pracę. Bardzo dobrze podsumowuje to następujące zdanie:

> Clean code is a code that is written by someone who cares 

Jeśliby wszyscy podczas codziennej pracy uwzględniali przynajmniej część z nich, porozumiewalibyśmy się tym samym językiem, myślelibyśmy w ten sam sposób - krótko mówiąc wszystkim byłoby prościej. Stosunek czasu poświęconego na czytanie kodu wzlędem tworzenia nowego to 10:1, dlatego też staramy się skrócić czas czytania, aby więcej czasu można było poświęcić na pisanie własnego. Podsumowując,

> The only way to always go fast is to keep the code as clean as possible  

Przydatne linki:

* [http://alvinalexander.com/programming/clean-code-quotes-robert-c-martin](http://alvinalexander.com/programming/clean-code-quotes-robert-c-martin)
* [http://alvinalexander.com/programming/clean-code-book-best-quotes-lessons-learned-programming](http://alvinalexander.com/programming/clean-code-book-best-quotes-lessons-learned-programming)
* [http://henrikwarne.com/2015/01/03/book-review-clean-code/](http://henrikwarne.com/2015/01/03/book-review-clean-code/)
* [http://www.inf.fu-berlin.de/inst/ag-se/teaching/K-CCD-2014/Clean-Code-summary.pdf](http://www.inf.fu-berlin.de/inst/ag-se/teaching/K-CCD-2014/Clean-Code-summary.pdf)
* [http://www.planetgeek.ch/wp-content/uploads/2013/06/Clean-Code-V2.2.pdf](http://www.planetgeek.ch/wp-content/uploads/2013/06/Clean-Code-V2.2.pdf)
* [http://www.planetgeek.ch/wp-content/uploads/2011/02/Clean-Code-Cheat-Sheet-V1.3.pdf](http://www.planetgeek.ch/wp-content/uploads/2011/02/Clean-Code-Cheat-Sheet-V1.3.pdf)
* [http://blog.ircmaxell.com/2013/11/beyond-clean-code.html](http://blog.ircmaxell.com/2013/11/beyond-clean-code.html)
* [http://ronjeffries.com/xprog/articles/too-much-of-a-good-thing/](http://ronjeffries.com/xprog/articles/too-much-of-a-good-thing/)
* [http://blog.goyello.com/2013/01/21/top-9-principles-clean-code/](http://blog.goyello.com/2013/01/21/top-9-principles-clean-code/)
* [http://sam-koblenski.blogspot.com/2014/01/functions-should-be-short-and-sweet-but.html](http://sam-koblenski.blogspot.com/2014/01/functions-should-be-short-and-sweet-but.html)
* [http://sam-koblenski.blogspot.com/2013/11/software-practices-to-develop-by.html](http://sam-koblenski.blogspot.com/2013/11/software-practices-to-develop-by.html)
* [http://clean-code-developer.com/](http://clean-code-developer.com/)
* [https://prezi.com/gb0r02irzwrw/clean-code-part-1/](https://prezi.com/gb0r02irzwrw/clean-code-part-1/)
* [http://www.jbrains.ca/permalink/the-four-elements-of-simple-design](http://www.jbrains.ca/permalink/the-four-elements-of-simple-design)
* [http://blog.thecodewhisperer.com/2013/12/07/putting-an-age-old-battle-to-rest/](http://blog.thecodewhisperer.com/2013/12/07/putting-an-age-old-battle-to-rest/)
* [http://sebastian-malaca.blogspot.com/2013/03/you-arent-gonna-need-it.html](http://sebastian-malaca.blogspot.com/2013/03/you-arent-gonna-need-it.html)
* [http://code.tutsplus.com/tutorials/3-key-software-principles-you-must-understand--net-25161](http://code.tutsplus.com/tutorials/3-key-software-principles-you-must-understand--net-25161)
* [http://blog.goyello.com/2013/05/17/express-names-in-code-bad-vs-clean/](http://blog.goyello.com/2013/05/17/express-names-in-code-bad-vs-clean/)
* [http://martinfowler.com/bliki/Yagni.html](http://martinfowler.com/bliki/Yagni.html)
* [http://on-agile.blogspot.com/2007/04/why-you-wont-fix-it-later.html](http://on-agile.blogspot.com/2007/04/why-you-wont-fix-it-later.html)



