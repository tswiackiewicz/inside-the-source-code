---
layout: post
title: "O walidacji słów kilka"
description: "Temat stary jak świat - walidacja. O potrzebie jej stosowania nie trzeba chyba nikogo przekonywać, ale gdzie i kiedy powinna mieć miejsce?..."
headline: 
modified: 2017-02-09
category: architecture
tags: [validation, ddd, domain driven design, specification, always valid, defensive programming, greg young, martin fowler]
comments: true
featured: false
---

Temat stary jak świat - **walidacja**. O potrzebie jej stosowania nie trzeba chyba nikogo przekonywać, ale gdzie i kiedy powinna mieć miejsce? Czy sprawdzanie danych wejściowych to odpowiedzialność warstwy prezentacji (*ang. Interface Layer*) czy może powinno być wykonywane w warstwie aplikacji (*ang. Application Layer*)? Warto zadbać o to, aby przede wszystkim była **spójna** tzn. sposób jej realizacji był taki sam w całej aplikacji. Ostatnio przeprowadziłem taką analizę na potrzeby aplikacji, z którą mam styczność w codzinnej pracy - wnioski oraz propozycję usystematyzowania tego procesu znajdziecie poniżej.
 
Projektując flow dla procesu walidacji, z jednej strony, zabezpieczamy aplikację przed nieprawidłowymi danymi - [defensive programming](https://en.wikipedia.org/wiki/Defensive_programming). Z drugiej natomiast, działamy w myśl zasady [fail fast](http://wiki.c2.com/?FailFast) - przerywamy przetwarzanie, kiedy dalszy flow nie ma sensu, kiedy biznesowe wymagania nie zostały spełnione.  

Sprawdzanie poprawności oraz spójności danych zazwyczaj realizowane jest na **wielu poziomach**, nierzadko jest powielane (nadużycia *defensive programming*). Przykładowo, na poziomie *UI* (frontend, JS) obywa się weryfikacja czy wymagane pola formularza zostały przekazane oraz czy nie przekroczono ich maksymalnej długości. Następnie na poziomie kontrolera (nadal warstwa interface, tylko że po stronie backendu) analogiczne testy mają miejsce, na wypadek gdyby obsługa JS została wyłączona, a dodatkowo weryfikujemy obecność znaków sepcjalnych w haśle. Idąc dalej, w momencie tworzenia *RegisterUserCommand* realizującego zadanie rejestracji nowego użytkownika w systemie, ponownie sprawdzamy czy podane hasło jest niepuste. W końcu, budując ***Value Object*** UserPassword, po raz kolejny sprawdzamy czy hasło jest niepuste oraz dodatkowo, czy ma wymaganą długość, czy zawiera wymagane znaki specjalne.

Sam fakt, że walidacja jest zdublowana, to nic złego, poniekąd każda warstwa odpowiada za poprawność danych na swoim poziomie. Warto jednak, tam gdzie można, wyizolować odpowiednie typy walidacji. Odnosząc się do przytoczonego powyżej flow, na etapie weryfikacji formularza, sprawdzamy jedynie czy wypełniono pole password, analogicznie postępujemy w kontrolerze. Dopiero w momencie budowania VO UserPassword sprawdzamy wymagania biznesowe dla hasła.

### Typy walidacji

Walidacja może zostać przeprowadzona w różnych miejscach, co więcej jej zakres oraz rodzaj może się różnić. 
 
Jedną z możliwych klasyfikacji może być podział na:
 
* ***proaktywna*** - nie pozwalamy na utworzenie niepoprawnego obiektu, zanim zostanie powołany do życia sprawdzamy czy taka akcja może mieć miejsce
* ***reaktywna*** - reagujemy na niepoprawne byty, co należy rozumieć w taki sposób, że najpierw tworzymy nowy obiekt a dopiero następnie sprawdzamy czy jest poprawny

{% highlight php %}
public function foo()
{
    // proactive validation, UserAlreadyExistsException is thrown
    $registeredUser = User::register('John Doe', 'user.already.exists@domain.com');
 
    // ...
 
    // reactive
    $registeredUser = User::register('John Doe', 'user.already.exists@domain.com');
    if (!$registeredUser->isValid()) {
        throw new UserAlreadyExistsException('User John Doe already exists');
    }
}
{% endhighlight %}
 
Alternatywę dla powyższego podziału możę stanowić:

* ***powierzchowna*** *(ang. superficial)* - sprawdzamy wyłącznie typy danych wejściowych, na bardzo podstawowym poziomie
* ***domenowa*** - określamy zgodność z wymaganiami biznesowymi
 
{% highlight php %}
class UserController
{
    public function registerUser(Request $request)
    {
        // superficial - if empty password, fail
        $this->commandBus->publish(
            RegisterUserCommand::fromRequest($request)
        );
    }
}
 
class RegisterUserCommandHandler
{
    public function handle(RegisterUserCommand $command)
    {
        // domain validation - if password does not contain special chars, throw exception
        $password = UserPassword::create($command->getPassword());
    }
}
{% endhighlight %}
 
### Always valid

Jedną z podstawowych idei towarzyszących ***Domain Driven Design*** oraz ***CQRS***, wielokrotnie podkreślaną przez różnego rodzaju autorytety (np. [Greg Young](http://codebetter.com/gregyoung/2009/05/22/always-valid/)), jest **Always valid** - elementy domeny zawsze muszą poprawne, krótko mówiąc nie pozwalamy na tworzenie niepoprawnych obiektów.

Stan taki możemy osiągnąć na dwa sposoby: albo będziemy weryfikowali czy możemy utworzyć obiekt bezpośrednio na etapie jego konstrukcji, albo nigdy nie pozwolimy na taką sytuację, gdy obiekt znajdzie się w niepożądanym stanie.

Pierwszy scenariusz posiada tą zaletę, że będziemy mieli pewność, że **zawsze** utworzymy poprawny obiekt, nie będzie możliwości zbudowania niepoprawnego obiektu. Powiązane jednak może być z tym takie wymaganie, jak choćby unikalność danych (identyfikator użytkownika, adres email, ...).  W efekcie będziemy zmuszeni do przekazania do obiektu zewnętrznego walidatora albo repository celem sprawdzania czy podany identyfikator istnieje w bazie. Ponadto, taki domenowy obiekt skupia się na wielu aspektach, podczas gdy jego odpowiedzialnością jest przede wszystkim realizacja logiki domenowej. Kod staje się nieczytelny, właściwe przeznaczenie klasy gdzieś się zatraca.

Spójrzmy na drugi wariant, to my kontrolujemy domenę, a zatem obiekty nie mogą powstać tak ot. Budowane będą w momencie, gdy będzie potrzebna realizacja danego zadania, więc możemy zadbać o to, aby były poprawne. Na poziomie odpowiednich warstw kontrolujemy, aby wiedza domenowa nie wypływała - nie było możliwości przeprowadzenia akcji biznesowych, np. zwiększenia stanu konta wybranego użytkownika. W tym wariancie, sprawdzenie czy podany adres email, identyfikator użytkownika są unikalne będzie proste - zanim utworzymy obiekt bądź przejdziemy do realizacji zadania, sprawdzimy czy wymagania biznesowe zostały spełnione. 
  
### Generyczne vs specyficzne reguły biznesowe

Podstawową kwestią, jeśli chodzi o określenie czy dany stan jest poprawny, jest określenie kiedy jest poprawny.

Przykładowo, z punktu widzenia encji *User*, id = 1234 jest poprawne gdyż jest dodatnią wartością liczbową, natomiast rejestrując użytkownika, taki identyfikator jest niepoprawny, bo już istnieje w bazie. Inny przykład, *User.email* = 'john.doe@gmail.com' jest akceptowalny, ponieważ jest poprawnym składniowo adresem email. Z kolei wymaganie biznesowe mówi, że adresy *gmail.com* są traktowane jako spam i taki adres jest niepoprawny. Kolejny przykład, hasło *User.password* = 'p@ssW0rd' jest poprawne z punktu widzenia zarówno Usera (niepusty string o długości przynajmniej 8 znaków, zawierający małe i wielkie litery oraz liczbę i jeden znak specjalny), jak również kontrolera bo zawiera nie pusty string.

Powyższe przykłady pokazują dwa zestawy wymagań biznesowych:

* ***generyczne*** *(ang. agnostic)* - ogólne reguły biznesowe sprawdzane w momencie tworzenia encji, np. poprawny składniowo adres email, hasło to string o min. 8 znakach z wielkimi i małymi literami oraz jednym znakiem specjalnym
* ***specyficzne*** - weryfikowane w szczególnych przypadkach, mogą być niepoprawne pomimo iż generyczne warunki zostały spełnione, np. opisany powyżej przypadek z *User.id* = 1234 bądź *User.email* = 'john.doe@gmail.com'

Generyczne reguły biznesowe powinny być sprawdzane bezpośrednio w encji lub na poziomie Value Objectów, specyficzne natomiast, w fabrykach tworzących encje albo w serwisie.

Dodatkowo do sprawdzania warunków specyficznych, jeśli mogą być współdzielone pomiędzy kilka przypadków, globalne reguły dla systemu, warto skorzystać ze wzorca [Specification](https://en.wikipedia.org/wiki/Specification_pattern).
 
Przykład weryfikacji generycznych reguł za pomocą wzorca *Specification*:
 
{% highlight php %}
class UsernameIsUnique implements UsernameSpecification
{
    /**
     * @var UserRepository
     */
    private $repository;
  
    /**
     * Create a new instance of the UsernameIsUnique specification
     *
     * @param UserRepository $repository
     */
    public function __construct(UserRepository $repository)
    {
        $this->repository = $repository;
    }
  
    /**
     * Check if the specification is satisfied
     *
     * @param Username $username
     * @return bool
     */
    public function isSatisfiedBy(Username $username)
    {
        if ($this->repository->userByUsername($username)) {
            return false;
        }
  
        return true;
    }
}
 
class UserFactory
{
    /**
     * @var UsernameSpecification
     */
    private $specification;
 
    /**
     * @param Username $username
     * @return User
     * @throws UserAlreadyExistsException
     */
    public function create(Username $username)
    {
        if (!$this->specification->isSatisfiedBy($username)) {
            throw UserAlreadyExistsException::withUsername($username->getName());
       }
 
        return User::create($username);
    }
}
{% endhighlight %} 

### Walidacja za pomocą Value Objects

Dobrą praktyką jest budowanie domenowych bytów w oparciu o *Value Object*. Z jednej strony jej konstrukcja znacznie się uprości - będziemy mieli *VO*, które z definicji są już poprawne a więc i cała encja będzie poprawna (uwzględniając generyczne wymagania biznesowe). Z drugiej strony, za pomocą VO możemy definiować ogólne reguły biznesowe, np. *UserPassword*. Bardzo łatwo w ten sposób zrealizować nasze założenia odnośnie *fail fast* - *RegisterUserCommand* składamy z Value Objects, jeśli będą niepoprawne, command nie zostanie utworzony, flow zostanie przerwany zanim połączymy się z bazą czy innym storage za pomocą repository.
 
### Sygnaliowanie błędów walidacji
 
Z uwagi na fakt, iż walidacja realizowana jest na różnych warstwach, sposób sygnalizowania błędów będzie różnił się w zależności od miejsca jego wystąpienia. Na poziomie domeny, pojedynczy błąd będzie powodował zakończenie dalszego działania (np. użytkownik o podanym adresie jest już zarejestrowany w systemie), podczas gdy błędy formularza powinny być agregowane tj. każdy błąd jest istotny, do UI przekazujemy całą kolekcję błędów, aby móc to odpowiednio zasygnalizować użytkownikowi.
   
W związku z tym możemy zdefiniować następujące reguły:
   
* warstwa **interface** (np. kontrolery): błędy sygnalizujemy z wykorzystaniem wzorca [messages](http://www.codemozzer.me/domain,validation,action,composable,messages/2015/09/26/domain_validation.html), agregujemy za pomocą specjalizowanego kontenera   
* warstwa **application** / **domain**:
    * *Value Object* - za pomocą wyjątków, np. *InvalidUserPasswordException*
    * generyczne reguły biznesowe - wyjątki, np. *InvalidEmailAddressException*
    * specyficzne reguły biznesowe - wyjątki, np. *BlacklistedEmailAddressException*
    * sprawdzanie unikalności - wyjątki, np. *UserAlreadyExistsException*
* warstwa **infrastructure**: wyjątki, np. *HttpClientConnectionException*

Wyjątkiem od powyższej reguły mogą być tutaj konstrukcje typu Command / Query - będą tworzone w warstwie interface, w ich skład będą głównie wchodziły VO a co za tym idzie pośrednio będą rzucały wyjątki w przypadku nieprawidłowości.

Warto zwrócić jeszcze uwagę na alternatywną ścieżkę dla flow opartego o rzucanie wyjątków - wykorzystanie ***zdarzeń*** (*ang. events*) do sygnalizowania błędów. Zamiast rzucania wyjątku, generowane byłoby zdarzenie, którego obsługa w większości przypadków i tak rzucałaby wyjątek. Rozwiązanie takie ma uzasadnienie w przypadku, gdy w momencie wystąpienia błędów dostępna będzie alternatywna ścieżka bądź podejmowane będą dodatkowe akcje (oprócz standardowej obsługi błędu).

W ramach podsumowania, kilka dobrych praktyk zbierających powyższe rozważania:

* w ogólności, nie jest odpowiedzialnością encji jej walidacja, tak więc w ogólności walidacja powinna mieć miejsce **poza domeną**
* sprawdzanie unikalności identyfikatorów, nazw itd realizujemy **poza encją**, na poziomie serwisu bądź fabryki - brak unikalności nie wpływa na utworzenie obiektu, nie jest to reguła generyczna
* warto stosować spójną koncepcję walidacji w obrębie danego *Bounded Context*
* zalecane jest podejście **proaktywne** tj. nie dopuszczamy do niepoprawnego stanu
* w każdym przypadku należy indywidualnie podchodzić do określenia kiedy dana encja nie będzie poprawna

Na koniec weźmy sobie do serca jedną zasadę: 

> Don’t ever let the entity get into invalid state  

Przydatne linki:

* [https://martinfowler.com/bliki/ContextualValidation.html](https://martinfowler.com/bliki/ContextualValidation.html)
* [http://codebetter.com/gregyoung/2009/05/22/always-valid/](http://codebetter.com/gregyoung/2009/05/22/always-valid/)
* [http://jeffreypalermo.com/blog/the-fallacy-of-the-always-valid-entity/](http://jeffreypalermo.com/blog/the-fallacy-of-the-always-valid-entity/)
* [http://softwareengineering.stackexchange.com/questions/258514/domain-model-validation-and-pushing-errors-to-the-model](http://softwareengineering.stackexchange.com/questions/258514/domain-model-validation-and-pushing-errors-to-the-model)
* [http://stackoverflow.com/questions/516615/validation-in-a-domain-driven-design](http://stackoverflow.com/questions/516615/validation-in-a-domain-driven-design)
* [https://lostechies.com/jimmybogard/2007/10/24/entity-validation-with-visitors-and-extension-methods/](https://lostechies.com/jimmybogard/2007/10/24/entity-validation-with-visitors-and-extension-methods/)
* [https://kacper.gunia.me/validating-value-objects/](https://kacper.gunia.me/validating-value-objects/)
* [http://culttt.com/2014/08/25/implementing-specification-pattern](http://culttt.com/2014/08/25/implementing-specification-pattern)
* [https://lostechies.com/derickbailey/2009/02/15/proactive-vs-reactive-validation-don-t-we-need-both/](https://lostechies.com/derickbailey/2009/02/15/proactive-vs-reactive-validation-don-t-we-need-both/)
* [http://verraes.net/2015/02/form-command-model-validation/](http://verraes.net/2015/02/form-command-model-validation/)
* [http://www.codemozzer.me/domain,validation,action,composable,messages/2015/09/26/domain_validation.html](http://www.codemozzer.me/domain,validation,action,composable,messages/2015/09/26/domain_validation.html)
* [http://danielwhittaker.me/2016/04/20/how-to-validate-commands-in-a-cqrs-application/](http://danielwhittaker.me/2016/04/20/how-to-validate-commands-in-a-cqrs-application/)
* [http://www.codethinked.com/Who-Knew-Domain-Validation-Was-So-Hard](http://www.codethinked.com/Who-Knew-Domain-Validation-Was-So-Hard)
* [http://gorodinski.com/blog/2012/05/19/validation-in-domain-driven-design-ddd/](http://gorodinski.com/blog/2012/05/19/validation-in-domain-driven-design-ddd/)
* [http://enterprisecraftsmanship.com/2016/09/13/validation-and-ddd/](http://enterprisecraftsmanship.com/2016/09/13/validation-and-ddd/)
* [http://www.nichesoftware.co.nz/2009/02/27/validation-and-domain-driven-design.html](http://www.nichesoftware.co.nz/2009/02/27/validation-and-domain-driven-design.html)
* [http://verraes.net/2015/01/messaging-flavours/](http://verraes.net/2015/01/messaging-flavours/)
* [https://github.com/git-josip/scala-backend/tree/master/app/com/sample/scalabackend/core/messages](https://github.com/git-josip/scala-backend/tree/master/app/com/sample/scalabackend/core/messages)
  
      
