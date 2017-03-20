---
layout: post
title: "Warstwowa architektura w Domain Driven Design"
description: "Jako Software Architect dość często słyszę pytania w stylu gdzie ten fragment kodu umieścić?, czy to jest odpowiedzialność tej klasy? albo *potrzebujemy wydelegować wykonanie akcji do innego obszaru aplikacji..."
headline: 
modified: 2017-03-20
category: architecture
tags: [ddd, domain driven design, layered architecture, eric evans, vaughn vernon]
comments: true
featured: false
---

Jako Software Architect dość często słyszę pytania w stylu *gdzie ten fragment kodu umieścić?*, *czy to jest odpowiedzialność tej klasy?* albo *potrzebujemy wydelegować wykonanie akcji do innego obszaru aplikacji...* Odpowiedzi udzielane na te pytania mają na celu pomóc tak zorganizować kod, aby był czytelny, testowalny, łatwy do utrzymania, a realizacja nowych wymagań była prosta. Mogłoby się wydawać, że wystarczy wytwarzać SOLIDny kod i mamy odpowiedzi na wszystkie te pytania. Jednak czy to wystarczy? Autorzy koncepcji *Domain Driven Design* poszli o jeden krok dalej promując ideę ***Layered Architecture***.

Sam pomysł podziału aplikacji na warstwy i komunikacji pomiędzy nimi, to nic odkrywczego. Wszystkim bardzo dobrze znany jest wzorzec MVC (ang. *Model - View - Controller*). Ponadto, wielu z nas, na pewno spoktało się z takimi modelami jak *Entity - Boundry - Interactor* (*Clean Architecture*), *Model - View - Presenter* czy *Action - Domain - Responser*. Z kolei **Eric Evans** w książce *[DDD: Tackling Complexity in the Heart of Software](https://www.amazon.com/Domain-Driven-Design-Tackling-Complexity-Software/dp/0321125215)* (tzw. *Blue Book*) przedstawia model charakterystyczny dla Domain Driven Design, który składa się z warstw: ***User Interface***, ***Application***, ***Domain*** oraz ***Infrastructure***.

Zależność pomiędzy poszczególnymi warstwami przedstawiona została na poniższym schemacie:

![Domain Driven Design Layered Architecture](http://dddsample.sourceforge.net/images/layers.jpg)

<span style="text-align: left;">(*źródło: dddsample.sourceforge.net*)</span>                                                                                                                                                                                                                                                                                                                                                                     
   
Komunikacja między warstwami może odbywać się tylko w jednym kierunku tj. niższe warstwy nic nie wiedzą o tych powyżej, komunikacja ma miejsce z góry na dół, np. UI → Application, Application → Domain. Wyjątkiem o tej reguły jest Infrastructure, do której mogą odwoływać się wszystkie pozostałe warstwy.   

### User Interface Layer

Warstwa odpowiedzialna za komunikację z użytkownikiem oraz prezentację informacji, bez względu czy to aplikacja webowa, desktopowa czy natywna aplikacja mobilna.

Przykładowo dla aplikacji webowych, UI Layer obejmuje swoim zasięgiem przede wszystkim kontrolery (np. *UserController*), ale mogą tutaj również znaleźć się różnego rodzaju prezentery (np. *UserPresenter*).

### Application Layer

Zapewnia komunikację z Domain Layer, zarówno z pojedynczym, jak również steruje przepływem danych pomiędzy wieloma *Domain Service*. Z uwagi, iż obiekty domenowe nie mogą wyciekać poza obszar domenowy (np. aby uniemożliwić transfer środków z jednego konta na drugie czy też bezpośrednie wykonywanie zapytań SQL na poziomie interface użytkownika), domenowe obiekty utworzone na poziomie DL opakowuje w **DTO** (ang. *Data Transfer Object*) i w tej postaci zwraca je do warstwy UI.

W tym obszarze będą występowały takie byty jak **Application Service** (np. *UserApplicationService*) oraz wszelkiego rodzaju obiekty powiązane z DTO (np. *UserDTO*, *UserDTOAssembler* itd.). Dodatkowo, zwłaszcza jeśli stosujemy podejście **CQRS** (ang. *Command Query Responsibility Segregation*), znajdziemy tutaj definicje poszczególnych Command i Query modelując w ten sposób niejako biznesowe przypadki użycia (np. *RegisterUserCommand*)

### Domain Layer

Tutaj realizowana jest logika biznesowa. Na tym poziomie będą definiowane encje (ang. *Entity*), VO (ang. *Value Object*) oraz repozytoria (ang. *Repository*). Jeśli chodzi o repozytoria, tutaj zostanie zdefiniowany wyłącznie interface a jego implementacja będzie realizowana na poziomie *Infrastructure Layer*  .

Za wykonanie logiki biznesowej zgodnie ze specyfikacją będą dbały **Domain Service**, które będą zapewniały interakcje pomiędzy określonymi obiektami domenowymi, o ile taka komunikacja będzie wymagana. Generalnie poszczególne serwisy nie będą bezpośrednio komunikowały się między sobą, ale nic nie stoi na przeszkodzie, aby posiadały zależności do różnego rodzaju interface'ów, które (i tak) mogą być implementowane przez inny DS. Przykładowo, *UserService* korzysta z implementacji inteface'u *PasswordReminder* realizowanego przez *PasswordReminderService* (Infrastructure Layer).

Warto dodać, że w przypadku **Event Sourcingu**, to w tej wastwie znajdziemy definicje zdarzeń (ang. *Events*). Powszechną praktyką jest również oznaczanie wszystkich wyjątków domenowych z pomocą tzw. **Exception Marker Interface**, tak aby można było łatwo identyfikować wyjątki z tej domeny, np. *UserDomainException*. 

### Infrastructure Layer

Wspomaga komunikację pomiędzy warstwami, odpowiada za przechowywanie oraz dostęp do danych (ang. *persistance*, *storage*). Ponadto umożliwia dostęp do innych zasobów systemowych, np. AMQP, Redis, Ceph, Elasticsearch, loggery, cache, system plików, różnego rodzaju systemy odpowiedzialne za wysyłkę wiadomości itd.

### Application Service vs Command Handler

Istnieją dwie szkoły, wynikające niejako z różnych potrzeb, jak obsłużyć flow w warstwie aplikacji. Jedna strategia to taka, gdzie w warstwie *Application Layer* funkcjonuje *Application Service* realizujący wymagania biznesowe za pomocą dedykowanych metod. W razie potrzeby może zwrócić wymagane dane, np. liczbę usuniętych kategorii, do których przypisany był usuwany użytkownik lub identyfikator zarejestrowanego użytkownika (o ile nie został wcześniej wygenerowany). Drugie podejście to takie, gdzie dla każdego ze zdefiniowanych Commands, został podpięty (via *Command Bus*) dedykowany **CommandHandler** obsługujący taki przypadek użycia. Podstawowa różnica względem poprzedniej strategii jest taka, że w tym przypadku nic nie może zostać zwrócone z metody - typowe podejście *CQRS*.
  
Każda ze wspomnianych strategii ma swoich zwolenników, jak i przeciwników, ale tak naprawdę sensowność zastosowania danego podejścia może być wynikiem tego, jakie są w danym przypadku oczekiwania oraz po trosze również polityka generowania indentyfikatorów. Przykładowo, jeżeli po zarejestrowaniu nowego użytkownika chcemy zwrócić jego identyfikator, a przyjęliśmy politykę generowania identyfikatorów jako te zwracane przez storage po zapisie rekordu, strategia oparta o Command Handlery nie sprawdzi się. Flow oparty o Application Service jest zdecydowanie łatwiejszy do zrozumienia i śledzenia. Zaletą drugiego podejścia jest *Separation of Concerns* i skupienie wyłącznie na jednym aspekcie (SOLID - SRP).
   
Ostatecznie jednak, w więkoszści aplikacji, kończymy i tak na hybrydzie tj. wiele serwisów z pojedynczą metodą realizującą danych przypadek biznesowy (analogicznie do Command Handlerów), a do tego serwis (bądź kilka), występujący zarówno w pierwszej jak i drugiej strategii, realizujących żądania typu Query.     

### Architektura warstwowa a walidacja

W jednym z poprzednich [artykułów](http://tswiackiewicz.github.io/inside-the-source-code/architecture/o-walidacji-slow-kilka/) poruszyłem temat walidacji. Przekładając to na opisany tutaj podział na warstwy, walidacja będzie miała miejsce na poziomie *Application Layer*, zanim dany *Command* zostanie przetworzony. Przykładowo, taka walidacja może być realizowana za pomocą dedykowanych *Command Validatorów*. 
   
Jako podsumowanie, aby dobrze zobrazować organizację kodu, zamieszczam strukturę katalgów, która w zależności od implementacji, przełoży się na namespace czy też pakiety:

{% highlight bash %}
src/
├── Application
│   └── User
│       ├── Command
│       │   ├── RegisterUserCommand.php
│       │   ├── RegisterUserCommandHandler.php
│       │   ├── RegisterUserCommandValidator.php
│       │   ├── UnregisterUserCommand.php
│       │   ├── UnregisterUserCommandHandler.php
│       │   ├── UnregisterUserCommandValidator.php
│       │   ├── UpdateUserCommand.php
│       │   ├── UpdateUserCommandHandler.php
│       │   └── UpdateUserCommandValidator.php
│       ├── UserApplicationService.php
│       ├── UserDTO.php
│       └── UserDTOAssembler.php
├── DomainModel
│   └── User
│       ├── Event
│       │   ├── UserRegisteredEvent.php
│       │   ├── UserUnregisteredEvent.php
│       │   └── UserUpdatedEvent.php
│       ├── User.php
│       ├── UserAddress.php
│       ├── UserDomainException.php                 (Interface)
│       ├── UserFactory.php
│       ├── UserLogger.php                          (Interface)
│       ├── UserRepository.php                      (Interface)
│       └── UserService.php
├── Infrastructure
│   ├── Logger
│   │   └── User
│   │       └── UserSyslogLogger.php
│   └── Persistence
│       └── User
│           └── UserDbRepository.php
└── UI
    ├── Cli
    │   └── User
    │       └── RemoveInactiveUsersCommand.php      (Symfony Console Command)
    └── Web
        └── User
            ├── UserController.php
            └── UserPresenter.php
{% endhighlight %}  

<br />Przydatne linki:

* [https://www.infoq.com/articles/ddd-in-practice](https://www.infoq.com/articles/ddd-in-practice)      
* [https://dzone.com/articles/responsibilities-application](https://dzone.com/articles/responsibilities-application)      
* [https://archfirst.org/domain-driven-design/6/](https://archfirst.org/domain-driven-design/6/)      
* [https://ajlopez.wordpress.com/2008/09/12/layered-architecture-in-domain-driven-design/](https://ajlopez.wordpress.com/2008/09/12/layered-architecture-in-domain-driven-design/)      
* [http://softwareengineering.stackexchange.com/questions/319885/comunicating-between-layers-in-ddd](http://softwareengineering.stackexchange.com/questions/319885/comunicating-between-layers-in-ddd)      
* [http://www.joaopauloseixas.com/howtodoit.net/?p=2638](http://www.joaopauloseixas.com/howtodoit.net/?p=2638)     
* [http://tpierrain.blogspot.com/2016/04/hexagonal-layers.html](http://tpierrain.blogspot.com/2016/04/hexagonal-layers.html)      
* [https://www.mirkosertic.de/blog/2013/04/domain-driven-design-example/](https://www.mirkosertic.de/blog/2013/04/domain-driven-design-example/)      
* [http://www.codingthearchitecture.com/2016/04/25/layers_hexagons_features_and_components.html](http://www.codingthearchitecture.com/2016/04/25/layers_hexagons_features_and_components.html)      
* [https://lostechies.com/jimmybogard/2008/08/21/services-in-domain-driven-design/](https://lostechies.com/jimmybogard/2008/08/21/services-in-domain-driven-design/)      
* [https://www.slideshare.net/_leopro_/clean-architecture-with-ddd-layering-in-php-35793127](https://www.slideshare.net/_leopro_/clean-architecture-with-ddd-layering-in-php-35793127)      
* [http://stackoverflow.com/questions/5881872/ddd-how-the-layers-should-be-organized](http://stackoverflow.com/questions/5881872/ddd-how-the-layers-should-be-organized)      
* [http://dddsample.sourceforge.net/architecture.html](http://dddsample.sourceforge.net/architecture.html)      
* [https://devhub.io/repos/mikaelmattsson-php-ddd-example](https://devhub.io/repos/mikaelmattsson-php-ddd-example)      
* [https://buildplease.com/pages/repositories-dto/](https://buildplease.com/pages/repositories-dto/)      
* [https://dev.to/0x13a/building-a-php-command-bus](https://dev.to/0x13a/building-a-php-command-bus)
* [https://gnugat.github.io/2016/05/11/towards-cqrs-command-bus.html](https://gnugat.github.io/2016/05/11/towards-cqrs-command-bus.html)     
* [https://www.sitepoint.com/command-buses-demystified-a-look-at-the-tactician-package/](https://www.sitepoint.com/command-buses-demystified-a-look-at-the-tactician-package/)    
* [http://shawnmc.cool/command-bus](http://shawnmc.cool/command-bus)      
* [https://php-and-symfony.matthiasnoback.nl/2015/01/a-wave-of-command-buses/](https://php-and-symfony.matthiasnoback.nl/2015/01/a-wave-of-command-buses/)
* [https://php-and-symfony.matthiasnoback.nl/2015/01/responsibilities-of-the-command-bus/](https://php-and-symfony.matthiasnoback.nl/2015/01/responsibilities-of-the-command-bus/)     
* [https://php-and-symfony.matthiasnoback.nl/2015/01/some-questions-about-the-command-bus/](https://php-and-symfony.matthiasnoback.nl/2015/01/some-questions-about-the-command-bus/)      
* [http://culttt.com/2014/11/10/creating-using-command-bus/](http://culttt.com/2014/11/10/creating-using-command-bus/)      
* [http://culttt.com/2014/09/29/creating-domain-services](http://culttt.com/2014/09/29/creating-domain-services)      
* [http://culttt.com/2014/10/20/creating-user-registration-domain-service](http://culttt.com/2014/10/20/creating-user-registration-domain-service)      
* [http://culttt.com/2014/10/27/building-password-reminder-domain-service](http://culttt.com/2014/10/27/building-password-reminder-domain-service)      
* [http://culttt.com/2014/10/06/creating-mailer-infrastructure-service](http://culttt.com/2014/10/06/creating-mailer-infrastructure-service)      
* [https://github.com/wmde/FundraisingFrontend](https://github.com/wmde/FundraisingFrontend)      
* [https://github.com/codeliner/php-ddd-cargo-sample](https://github.com/codeliner/php-ddd-cargo-sample)      
* [https://github.com/leopro/trip-planner/blob/master/src/Leopro/TripPlanner/Application/UseCase/CreateTripUseCase.php](https://github.com/leopro/trip-planner/blob/master/src/Leopro/TripPlanner/Application/UseCase/CreateTripUseCase.php)      
  