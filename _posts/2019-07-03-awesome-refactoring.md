---
layout: post
title: "Potęga refactoringu"
description: "Zastanawialiście się kiedyś jak szybko Wasz kod stanie się legacy? Przecież dołożyliśmy wszelkich starań, aby był zgodny ze sztuką, po prostu dobrej jakości, zgadza się? Jednak czy to jakość kodu tutaj jest najważniejsza..."
headline: 
modified: 2019-07-03
category: architecture
tags: [refactoring, strangler pattern, rewrite, legacy, Joshua Kerievsky, Martin Fowler]
comments: true
featured: false
---

Zastanawialiście się kiedyś jak szybko Wasz kod stanie się legacy? Przecież dołożyliśmy wszelkich starań, aby był zgodny ze sztuką, po prostu dobrej jakości, zgadza się? Jednak czy to jakość kodu tutaj jest najważniejsza - co z wartością biznesową? Po dłuższym namyśle możemy stwierdzić, że najważniejszym celem każdego programisty jest dostarczenie wartości biznesowej, poprzez napisanie prawidłowo działającego oprogramowania, które rozwiązuje problemy naszego klienta. Nie ma tutaj ani słowa o jakości kodu, czyli napisany właśnie przez nas kod jest niekoniecznie najwyższej jakości a może legacy?

Z powyższego wstępu możemy wynioskować, że nasz kod od razu stał się **legacy**. Pewnie większość z Was poprzez legacy rozumie kod, który powstał lata temu, pozbawiony testów, z brakami w dokumentacji - krótko mówiąc taki, od którego najchętniej trzymalibyśmy się daleka. Z drugiej strony natomiast, spójrzmy na nasz kod napisany pół roku temu. Czy nadal nam się podoba? Czy nadal go rozumiemy? Czy z łatwością dodalibyśmy nową funkcjonalność albo naprawili błąd? Przecież jesteśmy profesjonalistami i zadbaliśmy o wszystkie cechy *dobrego kodu*. Dobrego czyli właściwie jakiego?

No właśnie, czytając tego posta, kolega (lub koleżanka) z biurka obok właśnie przygotowuje kodzik realizujący zadanie z bieżącego sprintu. Przejdzie on inspekcję (znaną także jako [Code Review](http://tswiackiewicz.github.io/inside-the-source-code/architecture/effective-code-reviews/)), więc wszelkie niedoskonałości zostaną wyłapane. Krótko mówiąc powstanie kod zgodnie z wiedzą, umiejętnościami oraz zrozumieniem tematu takim jakim aktualnie dysponujemy, czyli najlepszy jaki w danym momencie jesteśmy w stanie dostarczyć. Natomiast, gdy wrócimy do tego kodu za kilka sprintów (np. wprowadzając do zespołu nowego kolegę/koleżankę) zdąży się on zestarzeć - z naszego puntku widzenia będzie (już) legacy. Czy możemy temu zapobiec, ale cały czas kod był *przyjemny*?

### Nieustanne doskonalenie kodu

Wrócmy jeszce na chwilę do jakości kodu. Jako koderzy chcemy pracować z dobrym kodem, do tego nie trzeba nikogo przekonywać czy zachęcać - pozostaje tylko taki kod pisać. Istnieje jeszcze druga strona barykady czyli tzw *biznes*. Jeśli mieliście okazję z nimi rozmawiać, pewnie niejednokrotnie spotkaliście się z dość chłodnym przyjęciem stwierdzenia, że poprawiono jakość kodu, ale nadal działa tak samo. Zatem o co tyle zachodu? Czy warto inwestować czas i środki, aby to osiągnąć?

Odpowiedź brzmi TAK, warto! Ważne, aby spojrzeć na problem z różnych perspektyw. Każdy z interesariuszy (bizes, analitycy, zespół developerski, ...) przez jakość rozumie co innego. Pewnego dnia, kod niskiej jakości po prostu się zemści - wyskoczy błąd, którego nie przetestowaliśmy, aplikacja przestanie działać efektywnie czy po prostu z uwagi na przyjete rozwiązanie nie będziemy w stanie dodać nowej funkcjonalności bądź wydanie nowej wersji produktu zajmie nam dwa lata zamiast kilku tygodni.

Aby temu zapobiec należy dążyć do nieustannego doskonalenia kodu, a pośrednio również produtku. Możemy osiągnąć to poprzez technikę zwaną **refactoringiem**.       

### Systematyczny vs strategiczny

Czym właściwie jest ten *refactoring*? Jest to zestaw **dobrych praktyk** (oraz **wzorców**) poprawiających szeroko rozumiany design kodu, a więc przede wszystkim jego jakość. Podstawą tych działań jest przyrostowe wprowadzanie małych zmian a następnie jak najszybsze integrowanie ich z resztą kodu. Bardzo często te pojedyncze zmiany określane są mianem *too small to be worth doing*, ale ich suma daje zauważalną różnicę. Aplikowane modyfikacje są niemalże atomowe, aby zachować dotychczasowe działanie produktu a przy tym dawać bezpieczeństwo oraz pełną kontrolę nad zmianami.

![Czym jest refactoring?](https://cdn-images-1.medium.com/max/800/1*x3AVN-DAvSqwRNMgWiUYqw.png)

Generalnie jako ludzie boimy się zmian. Jednak jeśli będą one małe i do tego kontrolowane damy się jakoś do nich przekonać. Im więcej zmian wprowadzamy, tych małych, dobrych, tym... bardziej nam się to podoba i nierzadko znajdujemy potrzebę ich wprowadzania. Dlaczego nie uczynić by z tego procesu działania rutynowego? Moglibyśmy stopniowo wprowadzać małe zmiany, tak aby ich suma dała efekt wow. Katrina Owen powiedziała nawet 

> Small refactorings are like making a low cost investment that always pays dividends. Take advantage of that every time.

Takie zmiany, realizowane w codzinnej pracy, czasem znane jako BSR (*ang.* ***Boy Scout Rule***), możemy określić mianem **systematycznego** refactoringu - krok po kroku dążymy do celu. Jak go osiągniemy w dużej mierze zależy od nas. Aby pamiętać o tym rutynowym zabiegu możemy zapisać sobie go w DoD (*ang. Definition of Done*) albo dodać do checklisty *Code Review* lub po prostu działajmy w myśl zasady 

> Always refactor before implementing new features

Nie wszystkie zmiany da się wprowadzać krok po kroku, ponieważ albo za długo będziemy czekali na efekt końcowy, albo nie mamy pełnego obrazu i ciężko zrobić pierwszy krok, albo... powód może być jeszcze inny. Koniec końców musimy zarządzić taką zmianą znaczy się ją zaplanować - refactoring **strategiczny**.
 
### Refactoring vs (big) rewrite

Skoro zatem planujemy refactoring (ten *strategiczny*) to czym on różni się od przepisania od podstaw wybranej części aplikacji? Możnaby rzecz niewiele, także w tym przypadku musimy poznać jak działa aktualnie aplikacja, gdzie są jej słabości, mocne strony oraz ułożyć działania krok po kroku. Należy zwrócić jednak uwagę na jeden szczegół - cała zabawa z refactoringiem polega na tym, aby zmiany były do ogarnięcia (czytaj zrozumienia i przetestowania) oraz dało się je prosto i szybko zintegrować z istniejącym kodem. Zależy nam na osiągnięciu rezultatu jak najszybciej, tak aby cały czas można było pracować nad produktem. Dlatego też mówimy stanowcze NIE wszelkim długo-żyjącym branchom i związanymi z tym dużami kosztami integracji. Refactoring ma dać nam poprawę jakości oraz komfort ciągłej pracy. 

Stąd też niezwykle istotne jest podczas planowania refactoringu na dłuższą skalę przewidywanie kierunku prac oraz stopniowa integracja z istniejącym już kodem. Z oczywistych względów przy takim planowanym refactorze, zmieniony kod może nie być od razu (w pełni) gotowy do użycia po pierwszej czy drugiej iteracji (np. niektóre prace/etapy muszą być poprzedzone innymi pracami), ale tak czy siak kod powienien być maksymalnie szybko złączony z całością. Pojawia się myśl - ale jak, skoro nasz zrefactorowany kod nie jest jeszcze skończony? Pomocne mogą być mechanizmy [feature toggle](https://martinfowler.com/articles/feature-toggles.html) czy też [branch by abstraction](https://martinfowler.com/bliki/BranchByAbstraction.html). Dzięki temu główny nurt aplikacji nie *odpływa*, a poprawiony kod już *dojrzewa*.

Na koniec porównań planowanego refactoringu oraz rewrite, należy zostawić słowo komentarza w sprawie tzw. **big rewrite** czyli przepisania całej aplikacji od nowa. Pewnie nie jeden raz o tym myśleliście, że tak byłoby lepiej, prościej czy szybciej. Jednak bardzo często ta idea to utopia - takie przepisanie całej aplikacji od zera zajmuje sporo czasu, a nie możemy sobie pozwolić na to, aby aplikacja przestała być rozwijana czy naprawiana. Możemy próbwać innego podejścia: zbieramy największe mózgi w firmie, zamykamy w pokoju i dajemy im czas i spokój na przepisanie aplikacji od nowa. W tym samym czasie inny zespół dalej rozwija i utrzymuje aktualna aplikację. Spotykamy się po pół roku z gotową, lściącą od pięknego kodu i nowości, przepisaną od podstaw aplikacją i.... okazuje się, że zachowuje się ona inaczej niż "stara" wersja. Dlaczego? Zabrało komunikacji.... 
Zatem próbujemy innego podejścia, te dwa zespoły okresowo się rotują, dbamy o nieustanną wymianę wiedzy i doświadczeń. Czy ta strategia zadziała - prawdę mówiąc: nie wiem. Jest szansa, że któryś z zespołów się wypali, developerzy którzy poobcują z ładnym kodem nie będą chcieli wracać do kodu legacy, gdzieś *odjedziemy* od głównego nurtu, czegoś nie zrozumiemy albo jak zaczniemy testować ulepszoną wersję naszego nowego Facebooka czy Instagrama to okaże się, że niekoniecznie uzyskaliśmy efekt jakiego oczekiwaliśmy.

Co na pewno zadziała? Poprawianie desingu krok po kroku oraz integrowanie na bieżąco z aktualnym kodem. Nie ma ryzyka, że miniemy się z naczelną myślą wszechobecną w produkcie czy wprowdzone zmiany coś nam popsują. W każdym momencie mamy działającą aplikację, pewne jej elementy są już gotowe, inne jeszcze nie. Potrzeba nam jedynie **cierpliwości** i **konsekwencji**.
   
> Refactoring is a part of day-to-day programming

### Strangler Pattern

Zostało już dość wyraźnie powiedziane, że zmiany powinny być małe, częste a do tego niemal natychmiast integrowane z zastanym kodem. Alternatywne rozwiązanie to takie, gdzie planujemy docelową zmianę w większej liczbie (małych) kroków, podobnie jak w pierwszym podejściu integrujemy ją szybko, ale na finalny efekt chwilę sobie poczekamy.
Oba te podejścia łączy szybka i częsta integracja oraz fakt, że nieprzerwanie mamy poprawnie działającą aplikację.

W przypadku drugiego podejścia pomocna może być technika znana jako **Strangler Pattern**, gdzie nowa wersja kodu częściowo przejmuję odpowiedzialność za stary w taki sposób, że niektóre "ścieżki" przechodzą przez nowy kod, zamiast stary. Stopniowo, krok po kroku, nowy kod niejako eliminuje stary aż do całkowitego jego zastąpienia.

![Strangler Pattern](https://paulhammant.com/images/strangulation.jpg)

<span style="text-align: left;">(*źródło: paulhammant.com*)</span>

Ta bardzo skuteczna strategia eliminacji starego kodu i zastępowania go przez ten lepszej jakości może być również z powodzeniem stosowana do testowania alterntywnych przepływów czy zachowań - *AB Testing*. Punktem wejścia dla obu przypadków będzie warstwa pośrednicząca (*proxy* bądź *router*), gdzie będzie następowało przekierowanie w opdowiednie miejsce na podstawie ustalonych kryteriów. Po skończonym refactoringu bądź testach, warstwa pośrednicząca powinna zostać wyeliminowania a sterowanie bezpośrednio przekazane do docelowego miejsca. 

### Jak przekonać biznes?

Jedno z przytoczonych powyżej stwierdzeń "zrefactorowaliśmy kod, działa bez zmian" może skutecznie zniechęcić biznes do takich przedsięwzięć. Refactoryzacja a więc zmiana struktury, architektury i jakości kodu sama w sobie niewiele da, jeśli nie będzie temu towarzyszyła wartość biznesowa. Oczywiście w pewnym sensie dla programistów zmiana mająca na celu poprawę jakości przyniesie pewną wartość - chętniej z takim kodem będziemy pracowali, być może łatwiej będzie go rozwijać czy poprawiać błedy. Natomiast co z wartością biznesową? Poprzednia wersja kodu zarabiała na siebie, a jej zrefactorowana wersja, z racji tylko iż została zrefactorowana, z dużym prawdopodbieństwem nie zacznie nagle zarabić więcej.

Najważniejsze w tym wszystkim jest zachowanie **równowagi**. Jeżeli kosztem szeroko rozumianej jakości kodu, będziemy w bardzo szybkim tempie dostarczali wartość dla klienta (nowe funkcjonalności), prędzej czy później, a raczej prędzej, takie podejście zemści się - błędami, taką organizacją kodu że jego modyfikacja (wliczając w to nowe feature'y) będzie długa, trudna lub koniec końców developerzy będą zmęczeni takim procesem wytwarzania i opuszczą firmę. Z drugiej strony natomiast, gdy przesadzimy z jakością kodu, zgodnego z wszelkimi dobrymi praktykami, wzorcami itd., a nie będzie on dostarczał wartości biznesowej - nic nam po pięknym kodzie, który na siebie nie zarobi.   

Zatem celem uzyskania wspomnianego balansu obie strony powinny ze sobą współpracować i nawzajem sobie ufać, pomagać. Każda ze stron powinna pozwolić drugiej stronie od czasu do czasu nieco przechylić szalę na swoją stronę. Przykadowo, potrzeba rynku zmusza do szybkiego wydania nowej wersji produktu, aby zaczął zarabiał pieniądze teraz a następnie dał trochę "oddechu" koderom, co by mogli spłacić dług techniczny. Innym razem wstrzymujemy się z dostarczeniem nowych wartości biznesowych, tak aby od strony technicznej można było przygotować solidną bazę pod przyszłe modyfikacje. Generalnie każdemu refactoringowi, zarówno temu systematycznemu jak i strategicznemu, powinna towarzyszyć wartość biznesowa - refactoring dla refactoringu jest niewiele wart.  

### Jak zarządzać refactoringiem?

Podstawą dobrego i skutecznego refactoringu jest **systematyczność**. W związku z tym powinniśmy zadbać o to, aby odbywał się regularnie. Na początek możemy postarać się o to by **20% naszego czasu** było poświęcone takim pracom.

Kolejny krok, zanim cokolwiek zmienimy w obecnym kodzie (bez względu czy to błąd czy nowa funkcjonalność) poprawmy go chociaż trochę - **zasada dobrego skauta** (*ang Boy Scout Rule*). Dopiszmy sobie tą praktykę do zespołowych standardów i przestrzegajmy jej.

Zapoznajmy się z popularnymi **techniakami i wzorcami refactoringu**, np. *extract method* czy *introduce Null Object* (więcej znajdziecie na [refactoring.guru](https://refactoring.guru/refactoring) oraz [Refactoring to Patterns](https://martinfowler.com/books/r2p.html)). Ich znajomość z czasem wejdzie w krew i będzie z nich korzystać w codzinnej pracy - będziecie refactorować kod nawet nie wiedząc o tym!

**Regularnie**, co pewien okres, dokonujmy przeglądu naszego kodu i typujmy kandydatów na strategiczny refactoring. Przede wszystkim wybierajmy te najbardziej problematyczne i skomplikowane fragmenty kodu. Powinniśmy się po nich poruszać sprawnie, więc jeśli ich nie rozumiemy lub co gorsza być może się *boimy*, rozpiszmy zadania i zapanujmy w końcu nad nimi.

Techniki i umiejętności refactoringu powinniśmy poznawać i rozwijąć tak samo jak inne elementy naszego programistycznego rzemioła, np. wzorce projektowe. Tylko w ten sposób dojdziemy do wprawy i będziemy w stanie skutecznie ocenić kiedy warto podejmować takie działania, a kiedy należy z nich zrezygnować. Przykładowo nie jest zalecane angażować się w takie prace pod koniec projektu, gdyż zwiększa to ryzyko niepowodzenia. Z drugiej strony natomiast bardzo dobrym momentem jest okres zaraz po wydaniu produktu, aby posprzątać to co w pośpiechu zaimplementowaliśmy niekoniecznie w zgodzie z duchem *Software Craftmanship*.

Uprzednio zaplanowany i przygotowany refactoring możemy rozpocząć od wspólnej pracy całego zespołu. W ramach **sesji refactoringu** będziemy na nowo odkrywali domenę biznesową (warto rozpoczać od warsztatów **Event Stormingu**), być może w końcu zrozumiemy pewne zachowania czy naprawimy pewne błędy. Będzie to świetna okazja, aby spojrzeć szerzej na problem, wymienić się wiedzą czy wprowadzić nowe osoby do zespołu. Zazwyczaj taka sesja ma charakter *Extreme Programming* bądź, coraz bardziej popularnego, **Mob Programmingu**.  

Ostatnia wskazówka może być pomocna dla osób ceniących sobie systematyczną i łatwą do zmierzenia pracę. W jednym z zespołów zaplanowaliśmy ramowy refactoring części aplikacji, którą się opiekowaliśmy. Rozpisaliśmy zadania, pogrupowaliśmy według obszarów aplikacji. Dodatkowo przygotowaliśmy **checklistę** z najważniejszymi punktami jakie kod "dobrej zmiany" powinien posiadać (np. modularność, niezależność, testy, Hexagonal Architecture, zgodność z firmowymi standardami, ...). Na koniec przygotowaliśmy sobie dashboard, gdzie można było monitorować postęp prac. Dzięki temu podejściu znaliśmy horyzont zmian, wiedziliśmy jak daleko od celu jesteśmy. Co iterację (sprint) jako punkt honoru braliśmy sobie, aby zrealizować kolejne zadanie przybliżające nas do *idealnego kodu*. 

Takie podejście daje nam poczucie, że mamy pewną wizję. Wymaga ona jednak ciągłej pielęgnacji, gdyż nieustannie musimy reagować na zmiany i nie da się ułożyć całego planu zmian, rozwoju od początku do końca. Co krok musimy analizować i oceniać czy nadal idziemy w dobrym kierunku. Niemniej być może warto sprobówać takiego podejścia. 
 
 
*Refactoring* to bardzo potężne narzędzie wspierające nas, koderów, w codziennym dążeniu do doskonałego kodu. Nigdy, choćby nie wiem jak bardzo byśmy się starali, nie stworzymy kodu którego nie można napisać lepiej - jak nie teraz to za dzień, dwa czy...rok. Programowanie to ciągłe dążenie do ideału, to cykl ***Red - Green - Refactor***.

Zapamiętajmy   

> Refactoring is a Development Technique, Not a Project



<br />Przydatne linki:

* [https://github.com/tswiackiewicz/awesome-refactoring](https://github.com/tswiackiewicz/awesome-refactoring)
* [https://martinfowler.com/books/refactoring.html](https://martinfowler.com/books/refactoring.html)
* [https://refactoring.com/](https://refactoring.com/)
* [https://refactoring.guru/refactoring](https://refactoring.guru/refactoring)
* [https://sourcemaking.com/refactoring](https://sourcemaking.com/refactoring)
* [https://codeclimate.com/blog/when-is-it-time-to-refactor/](https://codeclimate.com/blog/when-is-it-time-to-refactor/)
* [https://techbeacon.com/app-dev-testing/rewrites-vs-refactoring-17-essential-reads-developers](https://techbeacon.com/app-dev-testing/rewrites-vs-refactoring-17-essential-reads-developers)
* [https://github.com/RefactoringGuru/refactoring-examples](https://github.com/RefactoringGuru/refactoring-examples) 
* [https://medium.com/@maladdinsayed/advanced-techniques-and-ideas-for-better-coding-skills-d632e9f9675](https://medium.com/@maladdinsayed/advanced-techniques-and-ideas-for-better-coding-skills-d632e9f9675)
* [https://codeburst.io/write-clean-code-and-get-rid-of-code-smells-aea271f30318](https://codeburst.io/write-clean-code-and-get-rid-of-code-smells-aea271f30318)
* [https://hackernoon.com/refactor-your-php-legacy-code-real-projects-examples-da9edf03ff4b](https://hackernoon.com/refactor-your-php-legacy-code-real-projects-examples-da9edf03ff4b)
* [https://www.jamesshore.com/Agile-Book/refactoring.html](https://www.jamesshore.com/Agile-Book/refactoring.html)
* [https://dzone.com/articles/how-to-get-buy-in-for-addressing-technical-debt](https://dzone.com/articles/how-to-get-buy-in-for-addressing-technical-debt)
* [https://www.tomasvotruba.cz/blog/2019/04/15/pattern-refactoring/](https://www.tomasvotruba.cz/blog/2019/04/15/pattern-refactoring/)
* [https://www.infoq.com/articles/natural-course-refactoring/](https://www.infoq.com/articles/natural-course-refactoring/)
* [Pyramid of Refactoring](https://drive.google.com/file/d/0B_43JuqCijx2Uld3dzc4M0hCLUFmT3QtYzVET2lyX0pObnM0/view)
* [https://perfectial.com/blog/code-refactoring/](https://perfectial.com/blog/code-refactoring/)
* [http://fandry.blogspot.com/2013/06/java-code-refactoring.html](http://fandry.blogspot.com/2013/06/java-code-refactoring.html)
* [https://paulhammant.com/2013/07/14/legacy-application-strangulation-case-studies/](https://paulhammant.com/2013/07/14/legacy-application-strangulation-case-studies/)
* [https://docs.microsoft.com/en-us/azure/architecture/patterns/strangler](https://docs.microsoft.com/en-us/azure/architecture/patterns/strangler)
* [http://agilefromthegroundup.blogspot.com/2011/03/strangulation-pattern-of-choice-for.html](http://agilefromthegroundup.blogspot.com/2011/03/strangulation-pattern-of-choice-for.html)
* [https://www.altexsoft.com/blog/engineering/code-refactoring-best-practices-when-and-when-not-to-do-it/](https://www.altexsoft.com/blog/engineering/code-refactoring-best-practices-when-and-when-not-to-do-it/)
