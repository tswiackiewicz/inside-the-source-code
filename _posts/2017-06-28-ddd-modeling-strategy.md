---
layout: post
title: "Strategie modelowania w DDD"
description: "Inspiracją do publikacji poprzedniego artykułu związanego z Domain Driven Design była chęć pokazania jak można zorganizować kod w obrębie poszczególnych warstw. Przygotowany model nie ilustrował jednak w pełni flow oraz iterakcji pomiędzy poszczególnymi komponentami..."
headline: 
modified: 2017-06-28
category: architecture
tags: [ddd, domain driven design, layered architecture, cqrs, event sourcing, eric evans, vaughn vernon]
comments: true
featured: false
---

Inspiracją do publikacji [poprzedniego artykułu](http://tswiackiewicz.github.io/inside-the-source-code/architecture/ddd-layered-architecture/) związanego z *Domain Driven Design* była chęć pokazania jak można zorganizować kod w obrębie poszczególnych warstw. Przygotowany model nie ilustrował jednak w pełni flow oraz iterakcji pomiędzy poszczególnymi komponentami. Stąd też pojawił się pomysł przygotowania prostego, ale działającego kodu uwzględniającego wspomniany model.
    
Szukając przypadku użycia dla przykładowej implementacji, chciałem aby był możliwie najprostszy, a przy tym pozwalał na realizację nieco bardziej złożonych aspektów, jak choćby sprawdzanie unikalności czy użycie *Domain Service*. Wybór padł na jeden z najpopularniejszych przypadków użycia - kontekst użytkownika: rejestracja, aktywacja, zmiana hasła, wyrejestrowanie. 
    
Jako dodatkowy cel tego *eksperymentu* obrałem różne strategie modelowania, poczynając od najprostszej, poprzez flow oparty o zdarzenia, na *Event Sourcingu* kończąc. 

Zacznijmy jednak od tego, co nie zostało w pełni uwzględnione w poprzednim artykule, czyli [CQRS](https://martinfowler.com/bliki/CQRS.html).     

### CQRS

Zgodnie z koncepcją CQRS (*ang. Command Query Responsibility Segregation*) opracowaną przez [Grega Younga](https://cqrs.files.wordpress.com/2010/11/cqrs_documents.pdf) mamy rozdzielnie odczytów od zapisów. Przedstawiony [poprzednio](http://tswiackiewicz.github.io/inside-the-source-code/architecture/ddd-layered-architecture/) zarys podziału na warstwy nie realizował tego podziału w pełni - brakowało tam odseparowanego *Read Modelu*.
  
Co więcej, w związku podziałem na *Read Model* (odczyty) oraz *Write Model* (zapisy), pewne elementy języka np. *UserId* powinny być współdzielone. Zamodelowane zostało to w postaci warstwy *Shared Kernel*.
   
Rozwijając wspomniany model warstwowy o przedstawione tutaj elementy, otrzymaliśmy następującą strukturę katalogów stanowiącą bazę dla naszej implementacji:

{% highlight bash %}
├── Application
├── DomainModel
├── Infrastructure
├── ReadModel
├── SharedKernel
└── UI
{% endhighlight %}

W klasycznym podejściu logika biznesowa związana bezpośrednio z domeną realizowana jest przez *Domain Model*, a persystencja (via *repository*) odbywa się na poziomie *Application Service*. Dodatkowo, w szczególnych przypadkach, pewne zadania mogą być delegowane do wybranego *Domain Service*. *Read Model* został wyróżniony jako osobna warstwa, natomiast *Write Model* realizowany jest w obrębie *Application Layer* oraz *Domain (Model) Layer*.
   
Na poziomie *Infrastructure* znajdziemy konkretne implementacje elementów (np. repository) zarówno *Read Modelu*, jak i *Write Modelu*.    

### Zdarzenia

Nieco odmienną strategią względem opisanej w poprzedniej sekcji jest ta oparta o zdarzenia. U jej podstaw leży koncepcja, gdzie każda akcja w systemie sygnalizowana jest poprzez zdarzenie, np. zmiana hasła użytkownika spowoduje wygenerowanie zdarzenia *UserPasswordChanged*, aktywacja użytkownika - *UserActivated* itd. Dla każdego ze zdarzeń rejestrowany jest dedykowany handler, w którym to realizowana jest właściwa obsługa danego zdarzenia. 

Podstawowa różnica w stosunku do poprzedniej strategii jest taka, że w *Domain Modelu* po zrealizowaniu logiki biznesowej generowane jest zdarzenie i dla tego zdarzenia na poziomie zarejestrowanego handlera dane są persystowane. 

{% highlight php %}
/**
 * Class User
 * @package TSwiackiewicz\AwesomeApp\DomainModel\User
 */
class User extends AggregateRoot
{
    // ...
    
    /**
     * Activate user
     *
     * @throws UserException
     */
    public function activate(): void
    {
        if ($this->active) {
            throw UserException::alreadyActivated($this->id);
        }
        
        $this->active = true;
        $this->enabled = true;
        
        $this->recordThat(
            new UserActivatedEvent($this->id)
        );
    }  
       
    // ...       
}
{% endhighlight %}

*Application Service* zawiera jedynie *czyste* wywołania operacji biznesowych, np. *$user->activate()*. 

    
   
Takie podejście pozwala, aby kod w *Application Service* koncentrował się na tym, co z tego punktu widzenia jest istotne, czyli realizacji konkretnych przypadków biznesowych. Poza tym, dla danego zdarzenia możemy podpiąć dowolną ilość akcji (persystencja, logowanie, wysyłanie powiadomień, ...) bez zaśmiecania właściwego flow *Application Service* - *Separation of Concerns*.   

### Event sourcing

Ostatnia z wybranych strategii modelowania jest rozwinięciem idei flow opartego o zdarzenia. [Event Sourcing](https://martinfowler.com/eaaDev/EventSourcing.html) charakteryzuje to, że stan obiektów odtwarzany jest na podstawie strumienia zdarzeń, które wystąpiły w systemie. Tak działają chociażby systemy bankowe, gdzie aktualny stan konta to produkt kolejnych operacji: wpłaty, wypłaty, transfery, płatności itp. Dzięki temu możemy odtworzyć stan systemu w dowolnym momencie, a pondato nie tracimy informacji z kroków pośrednich (np. produkt dodany do koszyka, produkt usunięty z koszyka).

Analogiczie do poprzedniego podejścia *Read Model* jest odseparowany, dla zdarzenia rejestrowany jest handler. Zasadnicza różnica jest taka, że każde zdarzenie, które wystąpiło w systemie dodawane jest do tzw. *Event Loga* a persystencja realizowana w postaci projekcji na podstawie informacji zawartych w zdarzeniach. 

{% highlight php %}
/**
 * Interface UserProjector
 * @package TSwiackiewicz\AwesomeApp\DomainModel\User
 */
interface UserProjector
{
    // ...
    
    /**
     * @param UserActivatedEvent $event
     */
    public function projectUserActivated(UserActivatedEvent $event): void;

    // ...
}
{% endhighlight %}

W ten sposób z klas encji mogły zniknąć gettery, gdyż persystencja realizowana jest w oparciu o eventy i nie ma potrzeby pobierania aktualnego stanu encji przed zapisaniem go w wybranym storage'u. Model domenowy zawiera wyłączenie elementy logiki biznesowej.  

Cechą wspólną wszystkich omówionych tutaj strategii modelowania (klasyczna, oparta o zdarzenia oraz Event Sourcing) jest wykorzystanie koncepcji CQRS z wyraźnie odesparowanym *Read Modelem* oraz ta sama struktura katalogów. 
 
Przechodząc od podejścia klasycznego, a kończąc na podejściu opartym o *ES* możemy zobaczyć jak kod ewoluuje w stronę [SRP](http://www.oodesign.com/single-responsibility-principle.html), skupia się wyłącznie na realizacji zadań ze swojego obszaru zainteresowań. Pewne elementy w tej implementacji zostały celowo uproszczone. Założenie było takie, że kod ma być maksymalnie prosty (POPO - ang. *Plan Old PHP Object*), aby był łatwy do adaptacji w dowolnym projekcie a rozmieszczenie poszczególnych bytów na warstwach nie było zaciemniane przez implementację wymuszoną przez dany framework.

Koniec końców, możemy znaleźć dużo publikacji z teoretycznymi rozważaniami, a konkretnych przykładów jak przełożyć to na kod jest już niewiele (moją implementację znajdziecie [tutaj](https://github.com/tswiackiewicz/ddd-workshops/)). Zachęcam do eksperymentów - proponowany model można dowolnie rozwijać, upraszczać, można śmiało mieszać strategie modelowania. Wszystko zależy od potrzeb oraz skomplikowania domeny i projektu.  

Znajdź swoją strategię modelowania w Domain Driven Design!

<br />Przydatne linki:

* [https://github.com/tswiackiewicz/ddd-workshops/](https://github.com/tswiackiewicz/ddd-workshops/)  
* [https://cqrs.files.wordpress.com/2010/11/cqrs_documents.pdf](https://cqrs.files.wordpress.com/2010/11/cqrs_documents.pdf)
* [https://martinfowler.com/eaaDev/EventSourcing.html](https://martinfowler.com/eaaDev/EventSourcing.html)    
* [http://aspiringcraftsman.com/2008/01/03/art-of-separation-of-concerns/](http://aspiringcraftsman.com/2008/01/03/art-of-separation-of-concerns/)
* [https://github.com/codeliner/php-ddd-cargo-sample](https://github.com/codeliner/php-ddd-cargo-sample)
* [https://github.com/BottegaIT/ddd-leaven-v2](https://github.com/BottegaIT/ddd-leaven-v2)
* [https://github.com/VaughnVernon/IDDD_Samples](https://github.com/VaughnVernon/IDDD_Samples)
* [https://github.com/buttercup-php/protects](https://github.com/buttercup-php/protects)
