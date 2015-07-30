---
layout: post
title: "Clean code"
description: "Ostatnio miałem okazje ponownie przeczytać jedną z najważniejszych książek każdego software developera - Clean Code Uncle'a Boba. Co więcej zagadnienia związane z czystym kodem pojawiają się na niemalże każdej konferencji, przykłady: Be pragmatic, be SOLID 4Developers 2015, Przejrzysty i testowalny kod na Androidzie? Spróbujmy Clean Architecture 4Developers 2015, Core Software Design Principles for Programmers Devoxx PL 2015 itd."
headline: 
modified: 2015-07-29
category: architecture
tags: [clean code, software craftmanship, uncle bob, dry, kiss, yagni, solid, object oriented design, best practices]
comments: true
featured: false
---

Ostatnio miałem okazje ponownie przeczytać jedną z najważniejszych książek każdego software developera - [Clean Code](http://www.amazon.com/Clean-Code-Handbook-Software-Craftsmanship/dp/0132350882) Uncle'a Boba. Co więcej zagadnienia związane z *czystym kodem* pojawiają się na niemalże każdej konferencji, przykłady: *Be pragmatic, be SOLID* 4Developers 2015, *Przejrzysty i testowalny kod na Androidzie? Spróbujmy Clean Architecture* 4Developers 2015, *Core Software Design Principles for Programmers* Devoxx PL 2015 itd. W końcu wszyscy lubimy pracować z dobrze zaprojektowanym, przejrzystym, łatwym w utrzymaniu i modyfikacji kodem. Osiągnięcie takiego stanu nie jest prostym zadaniem, ale możemy sobie pomóc przestrzegając ustalonych zasad. Bazując na wspomnianej książce, dostępnych przykładach i własnym doświadczeniu, postanowiłem zebrać te najważniejsze.

### DRY

Jedna z najbardziej oczywistych i jednocześnie najczęściej łamanych reguł - **Don't Repeat Yourself**. Wydaje się natualnym, aby skorzystać z istniejącego rozwiązania zamiast samemu, czasem nawet ponownie, je implementować. Wówczas łatwo o błędy, pominięcie jakiegoś przypadku użycia czy po prostu zrealizowanie tej samej funkcjonalności w inny sposób. Niestety bardzo często po prostu *lecimy z tematem* i nie zastanawiamy się, ani nie przeprowadzamy śledztwa czy interesujące nas zagadnienie nie zostało już rozwiązane. W efekcie duplikujemy kod, a wszelkie poprawki trzeba wprowadzać w wielu miejscach. 

Dobra rada - eliminujemy duplikacje w kodzie, wyodrębniajmy powtarzające się fragmenty kodu tworząc nowe funkcje oraz korzystajmy z gotowych rozwiązań.  

### KISS

Cytując klasyka (*A.Einstein*)

> If you can't explain it simply, you don't understand it well enough

Zazwyczaj najprostsze rozwiązania okazują się najskuteczniejsze, dlatego też nie komplikujmy życia sobie i innym, w końcu ktoś, nawet Ty sam, za jakiś czas będzie czytał ten kod i zastanawiał się *co podmiot liryczny miał na myśli*?  

Dobra rada - **Keep It Simple, Stupid**, czysto i przejrzyście - bez tricków, sztuczek, dodatkowych wyjaśnień czy też zbędnych optymalizacji. 

### YAGNI

Czy programista to wróżbista? Niejednokrotnie pracując nad jakimś zadaniem zostawialiśmy *furtki na przyszłość*, żeby kiedyś tam dane rozwiązanie czy inny wariant rozwiązania bądź też dodatkowe funkcjonalności były gotowe. Możemy się tłumaczyć, że znamy specyfikę naszego produktu oraz biznesu (tutaj: część organizacji odpowiedzialna za kreowanie nowych funkcjonalności wytwarzanego produktu) i prędzej czy później będziemy musieli dodać ten ekstra kawałek kodu. Tymczasem, czas leci, projekt ma swój termin, budżet itd. Jeśli nawet, pomimo tej dodatkowej pracy, udało nam się nie przekroczyć terminu, to tak czy siak klient zapłacił za dodatkową pracę której nie zamawiał. Zazwyczaj jednak występuje pierwszy scenariusz czyli termin bądź inne funkcjonalności cierpią na naszej dobrej woli. Co gorsza, jeśli do następnego sprintu przejdą punkty z poprzedniego (z powodu niezrealizowania kluczowych wymagań), koszt projektu rośnie jeszcze bardziej z powodu, mogłoby się wydawać błahego, odstępstwa od specyfikacji. Istnieje jeszcze dodatkowe zagrożenie, że ta extra funkcjonalność którą przygotowaliśmy nigdy nie będzie wymagana. W efekcie uzyskamy nieużywany kawałek, a co gorsze za jakiś czas inny developer spojrzy na to i będzie zgadywał w jakim celu tutaj to się znalazło? 

Dobra rada - **You Ain't Gonna Need It**, skupmy się tylko na tym co w danym momencie jest niezbędne do realizacji zadania, ale jednocześnie nie blokujmy możliwości prostego rozszerzania funkcjonalności w myśl zasady *OPC* 

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

Co masz zrobić dzisiaj, zrób jutro... Bardzo często odkładamy pewne działania na później, a efekt niemalże zawsze jest taki sam - nie zostaną one nigdy zrealizowane. Możnaby powiedzieć: *widocznie nie były aż tak niezbędne*. Prawda jednak jest taka, że późniejszy koszt ich realizacji będzie większy niż w momencie pojawienia się tych wymagań. Dodatkowo, warto się zastanowić co było powodem odłożenia ich na później - zła estymacja czy może implementacja dodatkowych feature'ów, które mogą przydać się później. 

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

Dobra rada - na wymyślenie *dobrej* nazwy nie poświęcajmy więcej niż 10 minut, nie bójmy się zmieniać ich w razie potrzeby

Sercem naszego oprogramowania są funkcje. Oprócz dobrej nazwy wyjaśniającej, bez potrzeby komentowania, jej odpowiedzialności równie ważne jest rozumienie co dana funkcja robi. W przypadku dużych, wykraczających poza jeden ekran, i skomplikowanych funkcji ogarnięcie *big picture* może być trudne. Najważniejsze cechy dobre funkcji:

* jest mała, mieści się na jednym ekranie
* realizuje wyłącznie jedną czynność
* zawiera kod z jednego poziomu abstrakcji
* możliwie mało argumentów wejściowych
* wyłącznie argumenty wejściowe (bez referencji, argumentów typu out w C#)
* nie zawierają argumentów true / false - *passing boolean argument is ugly*
* do sygnalizowania błędów stosuje wyjątki
* bez side efektów

Dobra rada:

> Functions should do one thing. They should do it well. They should do it only.

W końcu, klasy, składają się z funkcji. Powinny być małe (do 200 *LOC*) i realizować pojedyncze zadania.

### Prawo Demeter

*Zasada minimalnej wiedzy* lub *Reguła ograniczenia iterakcji* - jedna z najważniejszych zasad *Object Oriented Design* pozwalająca na zmniejszenie zależności pomiędzy poszczególnymi elementami systemu.

> Talk to Friends Not to Strangers

Podczas kodowania możesz zauważyć, że odpytujesz jeden obiekt i na podstawie jego odpowiedzi podejmujesz jakieś działania. Powinniśmy dążyć do tego, aby obiekty informować o tym czego my oczekujemy od nich (aby zrobiły coś za nas) zamiast najpierw odpytać o stan, potem sprawdzić warunek i na tej podstawie kazać im coś zrobić. 

Przykład, zamiast:

``` php
if ($user->isAdmin()) {
    $message = $user->adminMessage;	
} else {
    $message = $user->userText;
}
```

lepiej:

``` php
$message = $user->getMessage();
```

**Prawo Demeter** mówi, że metoda danego obiektu może odwoływać się wyłącznie do metod należących do:

* tego samego obiektu
* dowolnego parametru przekazanego do niej
* dowolnego obiektu przez nią stworzonego
* dowolnego atrybutu klasy, do której należy dana metoda

Dzięki temu ograniczymy powiązania pomiędzy obiektami, kod będzie łatwiejszy do zrozumienia a ponadto unikniemy niebezpiecznych konstrukcji znanych jako *Train wrecks* gdzie tak naprawdę nie wiemy na jakich obiektach operujemy:

``` php
$path = $user->getAvatar()->getPhoto()->getPath();
```

Jako wady ścisłego stosowania *Law of Demeter* możemy wspomnieć fakt, iż może prowadzić do powstania wielu metod, których jedyną odpowiedzialnością będzie delegowanie wykonywania operacji a w efekcie nasz interfejs będzie się rozrastał.

Jednak mimo to, dobra rada - unikajmy **Train wrecks**, stosujmy (rozważnie) zasadę minimalnej wiedzy 


### Avoid deep nesting

Bardzo prosta zasada - unikajmy wielopoziomowych zagłębień. 

Zamiast:

``` php
function foo() {

    // ...
 
    if (is_writable($folder)) {
        if ($fp = fopen($file_path,'w')) {
            if ($stuff = get_some_stuff()) {
                if (fwrite($fp,$stuff)) {
                    // ...
                } else {
                    return false;
                }
            } else {
                return false;
            }
        } else {
            return false;
        }
    } else {
        return false;
    }
}
```

lepiej

``` php
function foo() {

    // ...
 
    if (!is_writable($folder)) {
        return false;
    }
 
    if (!$fp = fopen($file_path,'w')) {
        return false;
    }
 
    if (!$stuff = get_some_stuff()) {
        return false;
    }
 
    if (fwrite($fp,$stuff)) {
        // ...
    } else {
        return false;
    }
}
```

Dobra rada - *Return early and often*

### Żółta kaczuszka

*Rubber duck debugging* nie jest może praktyką związaną bezpośrednio z pisanym kodem, ale jest to działanie bardzo często wpływające na jego jakość. W dużym skrócie technika ta polega na tym, że w momencie rozwiązywania zagadnienia, jego analizowania bądź projektowania dochodzimy do momentu, gdzie napotykamy jakąś blokadę. Kolejne minuty, a czasem i godziny, poświęcone na usunięcie tej blokady nie przynoszą efektu. Wówczas z pomocą przychodzi nam wspomniana *żółta kaczuszka* - przedstawiamy problem koledze / koleżance bądź po prostu analizujemy go *na głos* i zanim przedstawiamy problem do końca, sami odpowiemy sobie na pytanie. Technika ta naprawdę jest bardzo skuteczna. Oczywiście nie daje 100% skuteczności, ale w wielu przypadkach działa i to działa dobrze. W trakcie takiego *debuggowania* możemy spojrzeć na zagadnenie z innej strony, możemy dostrzecx potencjalne zagrożenia i słabe strony. Wszystko to pozytywnie wpływa na nasz *code base*.

Inną pokrewną techniką jest tzw. *bedtime debugging* czyli wróćmy do naszego projektu jak trochę ochłoniemy, *prześpimy się z tematem*. Nawet nie jesteśmy świadomi ile dobrych pomysłów, przemyśleń rodzi się w trakcie snu, pod prysznicem czy podczas jazdy samochodem.

Dobra rada - nie bójmy się analizować zagadnień na głos czy też wrócać do nich następnego dnia

### Standardy

Zdefiniowane standardów, dobrych praktyk i stosowanie się do nich w ramach zespołu / projektu ma bardzo duże znaczenie. Dzięki temu wszyscy uczestnicy tego przedsięwzięcia poruszają się swobodnie, wszyscy mówią tym samym *językiem* a kod wygląda tak jakby pisała go jedna osoba. Druga strona medalu to nowe osoby dołączające do projektu. Nie dokładajmy im dodatkowego trudu opanowania wymyślonych reguł, mają przecież do ogarnięcia zagadnienia związane z produktem, domeną. Dlatego też nie twórzmy wyłącznie wygodnych dla siebie praktyk, np. prefix *p* przed nazwą parametru wejściowego metody - stosujmy konwencje powszechne dla wielu języków, frameworków, środowisk. 

Dobra rada - przyjrzyjmy się powszechnie stosowanym standardom i dobrym praktykom charakterystycznym dla naszego języka, np. *PHP* oraz drugiego dowolnie wybranego (*Java*) i zaadoptujmy je do naszego środowiska. Ważne, aby reguły były jasno sformułowane i przejrzyste dla wszystkich uczestników projektu. **Don't Reinvent The Wheel**      

### Simple design by Kent Beck

Podczas formułowania metodologii *Extreme Programming*, Kent Beck zdefiniował zasady prostego projektu:

* przechodzi wszystkie testy
* pozbawiony duplikacji
* wyraża intencje programisty
* minimalizuje listę klas i metod

Pierwsze dwa postulaty nie wymagają komentarza, z kolei *wyraża intencje programisty* objawia się w postaci:

* sugestywnych nazw
* niewielkich funkcji i klas
* stosowania wzorców projektowych
* pisania testów jednostkowych

Zatem, zdefiniowane przez Becka reguły tak naprawdę spinają w sobie wszystkie omówione powyżej praktyki.  

Podsumowując, znaczna część z przedstawionych powyżej zasad stosowana jest przez nas nieświadomie - pisząc kod robimy to automatycznie, intuicyjnie. Pozostałe, po krótkim przemyśleniu, również są proste do wprowadzenia i nie wymagają dużego wysiłku (przynajmniej jeśli chodzi o zrozumienie). Zostały one zdefiniowane przez doświadczonych developerów na podstawie obserwacji istniejącego kodu. Nie są to zasady stworzone na siłę, tylko po to aby ułatwić naszą codzienną pracę. Bardzo dobrze podsumowuje to następujące zdanie:

> Clean code is a code that is written by someone who cares 

Jeśliby wszyscy podczas codziennej pracy uwzględniali przynajmniej część z nich, porozumiewalibyśmy się tym samym językiem, myślelibyśmy w ten sam sposób - krótko mówiąc wszystkim byłoby prościej. Stosunek czasu poświęconego na czytanie kodu wzlędem tworzenia nowego to 10:1, dlatego też staramy się skrócić czas czytania, aby więcej czasu można było poświęcić na pisanie własnego. 

Tylko w ten sposób osiągniemy nasz cel, czyli

> The only way to always go fast is to keep the code as clean as possible  

Przydatne linki:

* [http://alvinalexander.com/programming/clean-code-quotes-robert-c-martin](http://alvinalexander.com/programming/clean-code-quotes-robert-c-martin)
* [http://alvinalexander.com/programming/clean-code-book-best-quotes-lessons-learned-programming](http://alvinalexander.com/programming/clean-code-book-best-quotes-lessons-learned-programming)
* [http://henrikwarne.com/2015/01/03/book-review-clean-code/](http://henrikwarne.com/2015/01/03/book-review-clean-code/)
* [http://www.itiseezee.com/?cat=5](http://www.itiseezee.com/?cat=5)
* [http://www.inf.fu-berlin.de/inst/ag-se/teaching/K-CCD-2014/Clean-Code-summary.pdf](http://www.inf.fu-berlin.de/inst/ag-se/teaching/K-CCD-2014/Clean-Code-summary.pdf)
* [http://www.planetgeek.ch/wp-content/uploads/2013/06/Clean-Code-V2.2.pdf](http://www.planetgeek.ch/wp-content/uploads/2013/06/Clean-Code-V2.2.pdf)
* [http://www.planetgeek.ch/wp-content/uploads/2011/02/Clean-Code-Cheat-Sheet-V1.3.pdf](http://www.planetgeek.ch/wp-content/uploads/2011/02/Clean-Code-Cheat-Sheet-V1.3.pdf)
* [http://blog.ircmaxell.com/2013/11/beyond-clean-code.html](http://blog.ircmaxell.com/2013/11/beyond-clean-code.html)
* [http://ronjeffries.com/xprog/articles/too-much-of-a-good-thing/](http://ronjeffries.com/xprog/articles/too-much-of-a-good-thing/)
* [http://blog.goyello.com/2013/01/21/top-9-principles-clean-code/](http://blog.goyello.com/2013/01/21/top-9-principles-clean-code/)
* [http://sam-koblenski.blogspot.com/2014/01/functions-should-be-short-and-sweet-but.html](http://sam-koblenski.blogspot.com/2014/01/functions-should-be-short-and-sweet-but.html)
* [http://sam-koblenski.blogspot.com/2013/11/software-practices-to-develop-by.html](http://sam-koblenski.blogspot.com/2013/11/software-practices-to-develop-by.html)
* [http://clean-code-developer.com/](http://clean-code-developer.com/)
* [http://www.jbrains.ca/permalink/the-four-elements-of-simple-design](http://www.jbrains.ca/permalink/the-four-elements-of-simple-design)
* [http://blog.thecodewhisperer.com/2013/12/07/putting-an-age-old-battle-to-rest/](http://blog.thecodewhisperer.com/2013/12/07/putting-an-age-old-battle-to-rest/)
* [http://sebastian-malaca.blogspot.com/2013/03/you-arent-gonna-need-it.html](http://sebastian-malaca.blogspot.com/2013/03/you-arent-gonna-need-it.html)
* [http://code.tutsplus.com/tutorials/3-key-software-principles-you-must-understand--net-25161](http://code.tutsplus.com/tutorials/3-key-software-principles-you-must-understand--net-25161)
* [http://blog.goyello.com/2013/05/17/express-names-in-code-bad-vs-clean/](http://blog.goyello.com/2013/05/17/express-names-in-code-bad-vs-clean/)
* [http://martinfowler.com/bliki/Yagni.html](http://martinfowler.com/bliki/Yagni.html)
* [http://on-agile.blogspot.com/2007/04/why-you-wont-fix-it-later.html](http://on-agile.blogspot.com/2007/04/why-you-wont-fix-it-later.html)
* [http://www.sitepoint.com/introduction-to-the-law-of-demeter/](http://www.sitepoint.com/introduction-to-the-law-of-demeter/)
* [http://itcraftsman.pl/powiedz-nie-pytaj-czyli-prawo-demeter/](http://itcraftsman.pl/powiedz-nie-pytaj-czyli-prawo-demeter/)
* [http://martinfowler.com/bliki/BeckDesignRules.html](http://martinfowler.com/bliki/BeckDesignRules.html)
* [https://vimeo.com/97541185](https://vimeo.com/97541185)



