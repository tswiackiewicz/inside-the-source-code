---
layout: post
title: "Efektywne przeglądy kodu"
description: "Jakiś czas temu miałem przyjemność uczestniczyć w warsztatach dotyczących dobrych praktyk przeglądów kodu. Każdy takiego rodzaju event (szkolenie, warsztaty czy też konferencja) to znakomita okazja..."
headline: 
modified: 2017-02-01
category: architecture
tags: [code review, software craftsmanship]
comments: true
featured: false
---

Jakiś czas temu miałem przyjemność uczestniczyć w warsztatach dotyczących dobrych praktyk **przeglądów kodu**. Każdy takiego rodzaju event (szkolenie, warsztaty czy też konferencja) to znakomita okazja, aby uporządkować wiedzę oraz zrobić mały research jak to wygląda w praktyce. Wszyscy doskonale wiemy jak ważną rolę odgrywają przeglądy kodu (*ang. code review*) w procesie wytwarzania oprogramowania. Jednak, jakie powinno być to code review, aby było wartościowe? Jak zapewnić, że wysoka jakość tych przeglądów będzie powtarzalna - krótko mówiąc: jakie są zasady dobrego code review?    

*Przeglądy kodu*, bez wątpienia, to najlepsze narzędzie do ograniczenia do minimum potencjalnych błędów w wytwrzanym oprogramowaniu. Ponadto, bardzo dobrze sprawdzają się jako *platforma* wymiany wiedzy. Pozwalają także, niejako przy okazji, na zmniejszanie **długu technologicznego** (*ang. technical debt*), identyfikację potencjalnych zagrożeń związanych z wydajnością czy bezpieczeństwem. Cele oraz zalety *code review* możemy wymieniać dalej, ale w zależności od doświadczenia oraz obszaru, w jakim dany recenzent się porusza (bądź czuje pewnie), w ramach takich przeglądów kodu, inne kwestie będą analizowane oraz oceniane. Co więcej, elementy na które dany recenzent zwracał uwagę w kilku poprzednich inspekcjach, w kolejnych może zupełnie zignorować. Code review jest procesem, a jak każdy proces powinien podlegać automatyzacji. Tylko w ten sposób będziemy mieli zapewnioną powtarzalną i przewidywalną jakość procesu. 

Pojawia się zatem pytanie: *w jaki sposób możemy ten proces **zautomatyzować***?
 

### Best practices

Zacznijmy od tego, że powinniśmy używać automatów, tam gdzie się da, np. *sprawdzanie formatowania kodu*. 

Aby ocenianie kodu nie było męczące, było efektywne oraz nie było wątpliwości, co do zakresu zmian, warto zadbać o takie szczegóły jak:

* powinno być krótkie i lekkie oraz przeprowadzane regularnie
* max 200-400 linii kodu czytanego *na raz*
* max 60-90 min czasu poświęconego na ocenę kodu jednorazowo
* **między autorem a recenzentem powinien być dobrze zdefiniowany kontrakt**
    * **oczekiwania**, np. *przetestowanie funkcjonalności*
    * **cele**, np. *polepszenie jakości kodu*, *przekazanie wiedzy na temat dodanej funkcjonalności*
* autor powinien dodać adnotacje do wystawionego przeglądu kodu - dodatkowe komentarze ułatwiają przeglądanie zmian, mogą zasugerować kolejność analizy zmian bądź wyjaśnić skomplikowane operacje
* zweryfikujmy czy zgłoszone uwagi zostały poprawione
     
Na koniec najważniejsze, inspekcję kodu powinna rozpocząć **rozmowa** między autorem oraz recenzentem.     

### Checklist

Powtarzalność procesu przeglądu kodu możemy osiągnąć definiując elementy, które za każdym razem należy mieć na uwadze czytając kod. Nawet doświadczeni, zaprawieni w boju recenzenci docenią fakt, iż mogą skorzystać ze ściągwaki i w razie potrzeby upewnić się, że wszystkie kluczowe aspekty zostały zbadane. W ten sposób dotarliśmy do miejsca, gdzie określimy listę obszarów analizowanych w trakcie *code review*. 

Przykładowa **checklista code review**, z podziałem na obszary, może wyglądać następująco:

#### Coding best practices

1. czy kod jest zrozumiały, czytelny?
2. czy kod jest kompletny, poprawny - robi to co ma robić i tylko to ([KISS, YAGNI](http://tswiackiewicz.github.io/inside-the-source-code/architecture/clean-code/))?
3. czy kod jest spójny? nazewnictwo, metody itd? czy użyte zostały właściwe (samoopisujące się) nazwy klas, metod, zmiennych?
4. czy kod ma pokrycie w testach? czy jest możliwe dopisanie testów?
5. czy spełnione zostały zasady ***SOLID***?
6. czy nie występuje duplikacja kodu (***DRY***)?
7. czy kod jest zgodny z dobrymi praktykami, standardami (np. wewnątrznymi organizacji), ***architecture guidelines***?
8. czy spełniona została **zasada dobrego skauta** - kod jest lepszy niż go zastałeś (eliminacja technical debt)?
9. czy dany fragment kodu nie może być zastąpiony przez gotową bibliotekę (np. z naszego repozytorium gotowych komponentów)?
10. czy dany fragment kodu nie jest na tyle ogólny, że może być zmigrowany do biblioteki (np. w ramach naszego repozytorium gotowych komponentów)?
11. czy nie występują nadmiarowe komentarze? czy może pewne fragmenty kodu wymagają komentarza?
12. czy użyte zostało słownictwo właściwe dla domeny?
13. czy operacje warunkowe (if/switch) nie są nadużywane? proste, przejrzyste, nie ma zbyt wielu poziomów zagłębienia?
14. czy występują magic numbers / strings? mogą być zastąpione przez stałe / enumy?
15. czy parametry wejściowe zostały sprawdzone? warunki brzegowe, specyficzne?
16. czy uwzględnione zostały warunki: *division by zero*, *out of bounds*, *undefined index* itd?

#### Error handling

1. czy wykorzystywane są wyjątki do sygnalizowania błędów?
2. czy wyjątki są prawidłowo obsługiwane - [Pokemon exceptions](http://wiki.c2.com/?PokemonExceptionHandling)?
3. czy komunikaty błędów są zrozumiałe i kompletne (zawierają wartościowe info, context info)?

#### Security

1. czy wrażliwe dane (np. numery kart kredytowych, loginy i hasła do baz danych) zostały usunięte z komunikatów błędów?
2. czy dane przechowywane w sesji są konieczne, wykorzystywane, bezpieczne?
3. czy parametry wejściowe zostały sprawdzone pod kątem bezpieczeństwa, np. *SQL injection*, *XSS*?
4. czy korzystamy z SQL prepared statements zamiast dynamicznych zapytań SQL?

#### Performance

1. czy otwarte zasoby (stream, połączenia z bazą danych) są zamykane?
2. czy można wykorzystać cache, lazy loading?
3. czy pętle mają koniec, nie mamy nieskończonych pętli?
4. czy można uniknąć tworzenia nadmiarowych obiektów?

#### Dead code

1. czy nie występuje martwy kod? nieużywane metody, importy/use, zmienne?
2. czy kod debugowy, dodatkowe loggery można usunąć?

Będąc recenzentem w ramach inspekcji kodu, wystarczy że w kolejnych iteracjach odpowiemy na poszczególne pytania z danej sekcji a ryzyko, że pominiemy jakieś kluczowe zagadnienie zostanie zminimalizowane.  

### Priorytety

Nie wszystkie znalezione usterki w kodzie będą tak samo ważne. Jeśli lista uwag będzie długa, warto je uporządkować według istotności.

W pierwszej kolejności należy uwzględnić:

* błędy logiczne
* problemy wydajnościowe
* związane z bezpieczeństwem
* skalowalnością
* błędy funkcjonalne
* zagadnienia związane z obsługą błędów

Następnie tematy reużywalności kodu, w dalszej kolejności problemy nazewnictwa i szeroko rozumianego coding style guide.

### Code metrics

W trakcie omawianej tutaj inspekcji kodu, oprócz checklisty oraz przyjętych standardów czy architecture guidelines, warto skorzystać z dodatkowej pomocy z postaci automatycznie generowanych [metryk kodu](https://pl.wikipedia.org/wiki/Metryka_oprogramowania).
   
Na podstawie tych **metryk**, a szczególnie ich trendu zmian, będziemy mogli ocenić jakość kodu. Przykładowo, jeśli w ramach kodu, który jest czytany, *złożoność cyklomatyczna* spadła z 47 do 13, oznacza że zmiany w kodzie idą w dobrym kierunku. Oczywiście wszelkie tego typu metrki to tylko estymacja, ale mimo pozwalają bardzo szybko (i do tego bez konieczności naszego udziału) wstępnie ocenić jakość zmian.
   
Jako przykłady takich metryk możemy podać:
   
* [złożoność cyklomatyczną](https://pl.wikipedia.org/wiki/Metryka_oprogramowania#Z.C5.82o.C5.BCono.C5.9B.C4.87_cyklomatyczna_McCabe.E2.80.99a)
* [złożoność Halsteada](https://pl.wikipedia.org/wiki/Metryka_oprogramowania#Z.C5.82o.C5.BCono.C5.9B.C4.87_Halsteada)
* [obiektowe metryki Uncle Bob'a](https://pl.wikipedia.org/wiki/Metryka_oprogramowania#Metryki_Roberta_C._Martina)
* [metryki Chidambera i Kermera](https://pl.wikipedia.org/wiki/Metryka_oprogramowania#Zestaw_metryk_CK)   

Słowem podsumowania, przeglądy kodu powinny mieć miejsce w **każdej** organizacji - pozwalają na kontrolę jakości wytwarzanego oprogramowania (eliminacja potencjalnych błędów, propagacja wiedzy w zespole, spłacanie długu technicznego). Warto zadbać o ich powatrzalność i jakość poprzez automatyzację elementów, które się da oraz korzystając z przygotowanych checklist. **Nie ma kodu idealnego**, zatem generalnie prawie zawsze, w ramach inspekcji, powinny być zgłoszone jakieś uwagi.       

Na koniec, pamiętajmy, że przystępując do przeglądu kodu **oceniamy kod a nie jego autora**!   


Przydatne linki:

* [http://mlynarze.com/zasady-dobrego-code-review/](http://mlynarze.com/zasady-dobrego-code-review/)
* [http://www.evoketechnologies.com/blog/code-review-checklist-perform-effective-code-reviews/](http://www.evoketechnologies.com/blog/code-review-checklist-perform-effective-code-reviews/)
* [http://searchitchannel.techtarget.com/tip/Reviewing-applications-for-security-Code-review-best-practices](http://searchitchannel.techtarget.com/tip/Reviewing-applications-for-security-Code-review-best-practices)
* [https://www.quora.com/What-are-some-best-practices-for-Code-Review](https://www.quora.com/What-are-some-best-practices-for-Code-Review)
* [http://docs.smartthings.com/en/latest/code-review-guidelines.html](http://docs.smartthings.com/en/latest/code-review-guidelines.html)
* [http://blogs.atlassian.com/2010/03/code_review_in_agile_teams_part_ii/](http://blogs.atlassian.com/2010/03/code_review_in_agile_teams_part_ii/)
* [https://www.future-processing.pl/blog/another-code-review-best-practices/](https://www.future-processing.pl/blog/another-code-review-best-practices/)
* [https://vladmihalcea.com/2014/02/06/code-review-best-practices/](https://vladmihalcea.com/2014/02/06/code-review-best-practices/)
* [https://www.parasoft.com/wp-content/uploads/pdf/When_Why_How_Code_Review.pdf](https://www.parasoft.com/wp-content/uploads/pdf/When_Why_How_Code_Review.pdf)
* [https://smartbear.com/learn/code-review/guide-to-code-review-process/](https://smartbear.com/learn/code-review/guide-to-code-review-process/)
* [http://support.smartbear.com/support/media/resources/cc/11_Best_Practices_for_Peer_Code_Review.pdf](http://support.smartbear.com/support/media/resources/cc/11_Best_Practices_for_Peer_Code_Review.pdf)
* [https://www.kevinlondon.com/2015/05/05/code-review-best-practices.html](https://www.kevinlondon.com/2015/05/05/code-review-best-practices.html)
* [https://dzone.com/articles/java-code-review-checklist](https://dzone.com/articles/java-code-review-checklist)
* [https://www.liberty.edu/media/1414/%5B6401%5Dcode_review_checklist.pdf](https://www.liberty.edu/media/1414/%5B6401%5Dcode_review_checklist.pdf)
* [http://courses.cs.washington.edu/courses/cse403/12wi/sections/12wi_code_review_checklist.pdf](http://courses.cs.washington.edu/courses/cse403/12wi/sections/12wi_code_review_checklist.pdf)
* [http://javarevisited.blogspot.com/2011/09/code-review-checklist-best-practice.html](http://javarevisited.blogspot.com/2011/09/code-review-checklist-best-practice.html)
* [https://github.com/mestachs/experiment/blob/master/codereview/checklist.md](https://github.com/mestachs/experiment/blob/master/codereview/checklist.md)
* [http://www.matthewjmiller.net/files/cc2e_checklists.pdf](http://www.matthewjmiller.net/files/cc2e_checklists.pdf)
* [https://www.youtube.com/watch?v=VRnMzMpSeag](https://www.youtube.com/watch?v=VRnMzMpSeag)
* [https://portal.uci.edu/uPortal/f/welcome/p/webproxy-cms-file-view.u20l1n201/max/render.uP?pP_cmsUri=public%2FAdCom%2FQA-QualityAssurance%2FcheckListJavaCodeReview.xml](https://portal.uci.edu/uPortal/f/welcome/p/webproxy-cms-file-view.u20l1n201/max/render.uP?pP_cmsUri=public%2FAdCom%2FQA-QualityAssurance%2FcheckListJavaCodeReview.xml)
* [https://openlmis.atlassian.net/wiki/display/OP/Code+Review+Checklist](https://openlmis.atlassian.net/wiki/display/OP/Code+Review+Checklist)
* [https://www.fogcreek.com/blog/increase-defect-detection-with-our-code-review-checklist-example/](https://www.fogcreek.com/blog/increase-defect-detection-with-our-code-review-checklist-example/)
* [http://www.evoketechnologies.com/blog/simple-effective-code-review-tips/](http://www.evoketechnologies.com/blog/simple-effective-code-review-tips/)
* [http://blog.fogcreek.com/increase-defect-detection-with-our-code-review-checklist-example/](http://blog.fogcreek.com/increase-defect-detection-with-our-code-review-checklist-example/)
  
      
