---
layout: post
title: "Integracja między kontekstami w DDD"
description: "Pod koniec stycznia brałem udział w konferencji DDD Europe poświęconej tematyce Domain Driven Design. Była to okazja do poszerzenia wiedzy, a przede wszystkim wymiany doświadczeń z innymi uczestnikami. Właśnie w ramach Modelling with Strangers..."
headline: 
modified: 2019-02-16
category: architecture
tags: [ddd, domain driven design, layered architecture, bounded context, anticorruption layer]
comments: true
featured: false
---

Pod koniec stycznia brałem udział w konferencji [DDD Europe](https://dddeurope.com/) poświęconej tematyce *Domain Driven Design*. Była to okazja do poszerzenia wiedzy, a przede wszystkim wymiany doświadczeń z innymi uczestnikami. Właśnie w ramach *Modelling with Strangers*, przy białej tablicy, próbowaliśmy zamodelować i odpowiedzieć na pytanie: jak komunikować się z innym *Bounded Contextem*?
    
W idealnym świecie dany **Bounded Context** jest w pełni niezależny, odizolowany od otoczenia, posiada wszystkie informacje niezbędne do realizacji swoich celów biznesowych - krótko mówiąc nie potrzebuje komunikować się z innymi kontekstami. Przenosząc ten stan na środowisko microserwisów możemy w skrócie powiedzieć, że baza danych naszej usługi posiada zdublowane (np. używając do tego synchronizacji opartej o *domain events*) dane od zewnętrznych dostawców, niezbędne aby móc zapewnić poprawne działaniego naszego serwisu. 

Niestety nie zawsze mamy taki konfort... Przyczyn może być wiele, np. wysoki koszt synchronizacji / duży wolumen danych, systemy legacy czy integracja różnych technologii. W efekcie zmuszeni jesteśmy do zapewnienia tych danych na poziomie aplikacyjnym. Jak zrobić to dobrze, aby było prosto, czytelnie i elastycznie?           

### Layered Architecture

W jednym z poprzednich artykułów opisałem [architekturę warstwową](http://tswiackiewicz.github.io/inside-the-source-code/architecture/ddd-layered-architecture/) wraz z charakterystyką odpowiedzialności poszczególnych wartstw. Najważniejsza, centralna w podejściu *Clean* oraz *Hexagonal Architecture*, jest **warstwa domenowa** (*ang. Domain Layer*) - to tam rozgrywa się cała akcja, tam zdefiniowane i realizowane są wszystkie procesy biznesowe. 

Jednak w samym *sercu* naszej aplikacji bazujemy na konceptach pochodzących z naszego "świata" (*ang. Bounded Context*), a do tego oczekujemy iż te dane będą poprawne i kompletne z naszego punktu widzenia. 

Weryfikacja oraz (w razie potrzeby) wzbogacanie danych pochodzących bezpośrednio od użytkownika realizowane będzie na **warstwie aplikacyjnej** (*ang. Application Layer*). Właśnie tam komunikujemy się z serwisami zewnętrznymi, sterujemy przepływem danych oraz w końcu zlecamy wykonanie operacji na agregatach.

### Command Factory

Jedną z podstawowych idei towarzyszących Domain Driven Design, przedstawionych przeze mnie [wcześniej](http://tswiackiewicz.github.io/inside-the-source-code/architecture/o-walidacji-slow-kilka/) oraz wielokrotnie podkreślaną przez różnego rodzaju autorytety (np. *Greg Young*), jest **Always valid** - elementy domeny zawsze muszą poprawne, krótko mówiąc nie pozwalamy na tworzenie niepoprawnych obiektów. Co więcej, możemy założyć, że do naszego *Application Service* trafia poprawny oraz kompletny command. 

Najprostsze rozwiązanie: zapewnijmy, że tworzone commandy będą zawsze poprawne - użyjmy do tego fabryki. Jednym z etapów budowania commanda będzie weryfikacja (np. za pomocą wstrzykniętego walidatora) poprawności danych, a w razie potrzeby możemy uderzyć do zewnętrznego serwisu (np. celem potwierdzenia, że dla podanych identyfikatorów istnieją odpowiednie byty). Co więcej, jeśli dane pochodzące od użytkownika będą bardzo ubogie, możemy wypełnić nasz command danymi pochodzącymi z innego *Bounded Context'u*.

{% highlight php %}
/**
 * Class RegisterUserCommandFactory
 * @package TSwiackiewicz\AwesomeApp\User\Application\Command
 */
class RegisterUserCommandFactory
{
    public function fromArray(array $rawData): RegisterUser 
    {
        // fetch data from external service
        $category = $this->categoryService->findById($rawData['categoryId']);
        
        // validate if it's correct / exists
        if ($category === null) {
            throw RegisteredUserCategoryNotFoundException::forId($rawData['categoryId']);
        }
        
        // build command using external data
        return new RegisterUser(
            $rawData['name'],
            
            // ...
            
            $category->getName()
        );
    }
}
{% endhighlight %}  

Co prawda w ten sposób zapewnimy poprawne i kompletne dane, ale polegamy na tym, że zawsze command będzie budowany z użyciem fabryki.  

### Application Service

*Kontrola podstawą zaufania* - przecież nie zawsze będziemy konstruowali command za pomocą wspomnianej fabryki.

Potrzebujemy obsłużyć identyczny flow jak w przykładzie powyżej, tyle że tą odpowiedzialność przenosimy do serwisu aplikacyjnego. Zanim utworzymy agregat i wydelegujemy do niego wykonanie określonej akcji, zweryfikujemy i/lub pobierzemy dane z zewnętrznego serwisu. 

Cały czas operujemy jednak na *Application Layer*, czas zejść warstwę niżej... 

### Monolith database

Może zdarzyć się tak, iż pomimo wielu niezależnych kontekstów (serwisów), pod spodem mamy monolityczną bazę danych. Tą z pozoru niedogodność możemy obrócić na naszą korzyść - na poziomie naszej domeny bezpośrednio skorzystamy z wymaganych informacji.

Definiujemy interface repository (*Domain Layer*) i z niego korzystamy w agregacie (bezpośrednio lub posiłkując się dodatkową warstwą abstrakcji). Na poziomie **infrastruktury** (*ang. Infrastructure Layer*) zapewniamy implementację tego inteface'u odwołującą się bezpośrednio do (wspólnej) bazy danych, korzystając z danych których właścicielami przecież nie jesteśmy.

Zaproponowane rozwiązanie, z jednej strony proste w realizacji i wygodne, z drugiej natomiast nie powinno być zbyt częstą praktyką. Postępujmy zgodnie z ideą **high cohesion, loose coupling** - w tym wypadku mamy coupling na poziomie bazy, co będzie nas blokowało w dążeniu do pełnej autonomii domeny.

### Anticorruption layer

Przedstawione powyżej strategie nie sprawdzą się, jeśli podczas realizacji naszej operacji biznesowej niezbędne będzie wykonanie akcji na zewnątrz, będzie to jeden z kroków naszego flow.

W takiej sytuacji musimy przedstawić ten koncept (akcję) w języku naszej domeny, a przekładając na kod - w ramach naszej domeny definiujemy pewien interface wyrażający wspomnianą operację. Implementację tego interface'u umieszczamy w... *Application Layer*. Powodem tego jest to, że integrujemy się ze światem zewnętrznym - wspomniana implementacja będzie najpewniej jakimś adapterem, przykładem [Anticorruption Layer](https://markhneedham.com/blog/2009/07/07/domain-driven-design-anti-corruption-layer/). Ponadto w ramach naszej domeny nie chcemy wykraczać poza pojęcia w niej niezdefiniowane.

Przykład: w ramach rejestracji nowego użytkownika w naszym systemie musimy również założyć konto w serwisie partnera, ponieważ będzie on (system partnera) używany do autoryzacji użytkownika w ramach naszej aplikacji.
 
{% highlight php %}

use ExternalServiceProvider\ExternalApplication\Account\Application\AccountService;
use TSwiackiewicz\AwesomeApp\User\Domain\Identity\IdentityProvider;
use TSwiackiewicz\AwesomeApp\User\Domain\RegisteredAccount;

/**
 * Class IdentityProviderAdapter
 * @package TSwiackiewicz\AwesomeApp\User\Application\Identity
 */
class IdentityProviderAdapter implements IdentityProvider
{
    // ...
    
    public function registerIdentity(RegisteredAccountIdentity $identity): void
    {
        $this->accountService->createNew($identity->login(), $identity->token());
    }
    
    // ...
}

/**
 * Class RegisteredUsers
 * @package TSwiackiewicz\AwesomeApp\User\Domain
 */
class RegisteredUsers
{
    // ...
    
    public function registerNew(RegisteredAccount $account): RegisteredUserId
    {
        $this->identityProvider->registerIdentity($account->identity();
        
        // ...
    }
    
    // ...
}
{% endhighlight %}

To rozwiązanie ma tą niedpoważalną zalete, iż bardzo wyraźnie widzimy jak wygląda flow, a pośrednie odwołanie na zewnątrz, w tym wypadku, jest jego nieodłącznym elementem.

Czas podsumować nasze rozważania. Każda z przedstawionych powyżej propozycji będzie lepiej sprawowała się w określonych warunkach, w innych zupełnie nie będzie się sprawdzała. Jeśli chodzi o sprawdzanie poprawności oraz wzbogacanie danych - tutaj nie ma lepszego rozwiązania: wszystko zależy od Waszych preferencji, standardów oraz tego w jak dużym produkcie pracujecie.

Osobiście zachęcam Was do tego, aby domena była maksymalnie skupiona na realizacji flow binesowego, a wszelkiego rodzaju weryfikacje, jailingi, wzbogacania realizować zanim wywołanie trafi do domeny. Prawdopodobnie największym wyzwaniem będzie dobrze nazwać **koncept**, gdzie na wartstwie aplikacyjnej będzie następowała komunikacja ze światem zewnątrznym. 

> Keep your domain clean and simple   

Na koniec podziękowania dla [Thomas Ploch](https://twitter.com/tPl0ch), [Matthias Breddin](https://twitter.com/lunetics) oraz [Michał Giergielewicz](https://pl.linkedin.com/in/michalgiergielewicz) za wspólne modelowanie podczas *DDD Europe 2019* oraz inspirację do napisania tego artykułu.

<br />Przydatne linki:

* [https://martinfowler.com/bliki/BoundedContext.html](https://martinfowler.com/bliki/BoundedContext.html)
* [https://enterprisecraftsmanship.com/2016/09/13/validation-and-ddd/](https://enterprisecraftsmanship.com/2016/09/13/validation-and-ddd/)
* [http://gorodinski.com/blog/2012/05/19/validation-in-domain-driven-design-ddd/](http://gorodinski.com/blog/2012/05/19/validation-in-domain-driven-design-ddd/)
* [https://www.toptal.com/scala/context-validation-in-domain-driven-design](https://www.toptal.com/scala/context-validation-in-domain-driven-design)
* [https://jimmybogard.com/domain-command-patterns-validation/](https://jimmybogard.com/domain-command-patterns-validation/)
* [https://www.culttt.com/2014/11/26/strategies-integrating-bounded-contexts/](https://www.culttt.com/2014/11/26/strategies-integrating-bounded-contexts/)
* [https://markhneedham.com/blog/2009/07/07/domain-driven-design-anti-corruption-layer/](https://markhneedham.com/blog/2009/07/07/domain-driven-design-anti-corruption-layer/)
* [https://medium.com/nick-tune-tech-strategy-blog/sharing-databases-within-bounded-contexts-5f7ca6216097](https://medium.com/nick-tune-tech-strategy-blog/sharing-databases-within-bounded-contexts-5f7ca6216097)
  

