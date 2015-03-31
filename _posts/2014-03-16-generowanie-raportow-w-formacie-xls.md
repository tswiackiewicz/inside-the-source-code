---
layout: post
title: "Generowanie raportów w formacie XLS"
description: "Bardzo często generowaniu raportów, listy kontaktów czy też innych zbiorów danych prezentowanych w postaci tabeli, towarzyszy eksport tych danych do formatu CSV bądź XLS (Excel Binary File Format). O ile w przypadku formatu CSV wygenerowanie takiego pliku jest proste, wydajne i dostępne dla każdego języka programowania, o tyle format XLS wymaga..."
headline: 
modified: 2014-03-16
category: php
tags: [php, xls, biff, ms excel]
comments: true
featured: false
---

Bardzo często generowaniu raportów, listy kontaktów czy też innych zbiorów danych prezentowanych w postaci tabeli, towarzyszy eksport tych danych do formatu <abbr title="Comma Separated Value">CSV<abbr> bądź XLS (*Excel Binary File Format*). O ile w przypadku formatu CSV wygenerowanie takiego pliku jest proste, wydajne i dostępne dla każdego języka programowania, o tyle format XLS wymaga użycia dedykowanych bibliotek, a przy tym bardzo często wydajność takiego rozwiązania nie spełnia naszych oczekiwań. 

Po analizie istniejących rozwiązań dla języka PHP (jako podstawowego języka w codziennej pracy) zdecydowałem się przyjrzeć bliżej następującym bibliotekom:

- *[PHPExcel](https://github.com/PHPOffice/PHPExcel)*
- *[PEAR::Spreadsheet_Excel_Writer](http://pear.php.net/package/Spreadsheet_Excel_Writer)*
- *[WriteExcel](http://www.bettina-attack.de/jonny/view.php/projects/php_writeexcel/)*
- *[HExcel](http://code.google.com/p/hexcel/)*
- *[MS-Excel Stream Handler](http://www.phpclasses.org/package/1919-PHP-Stream-wrapper-to-read-and-write-MS-Excel-files.html)*

Pierwsze dwa rozwiązania (*PHPExcel*, *PEAR::Spreadsheet_Excel_Writer*) to bardzo złożone i zaawansowane biblioteki oferujące niemalże pełen zakres funkcjonalności *MS Excel*. Wadą tych rozwiązań jest bardzo mała wydajność oraz częste błędy <abbr title="Out Of Memory">OOM<abbr>. *WriteExcel* to port popularnej perlowej biblioteki *Spreadsheet::WriteExcel*. Niestety nie obsługuje kodowania *UTF-8*. Natomiast ostatnie dwie pozycje (*HExcel*, *MS-Excel Stream Handler*) to bardzo proste, szybkie i wydajne rozwiązania umożliwijące zapis pojedynczego arkusza w formacie *XLS*, ale podobnie jak *WriteExcel* nie umożliwiają zapisu w *UTF-8*.

Z uwagi na wymagania wobec generowania raportów i eksportu danych do XLS (kodowanie *UTF-8*, duża wydajność oraz małe wymagania pamięciowe) postanowiłem wykorzystać kod z *WriteExcel*, *HExcel* oraz *MS-Excel Stream Handler*, ale z taką różnicą, że finalne rozwiązanie powinno obsługiwać kodowanie *UTF-8*.

## SimpleExcelStreamWriter

Dlatego też konieczne było zapoznanie się z [dokumentacją](http://download.microsoft.com/download/2/4/8/24862317-78F0-4C4B-B355-C7B2C1D997DB/%5BMS-XLS%5D.pdf) formatu *XLS* a dokładniej <abbr title="Binary Interchange File Format">BIFF<abbr>. Efektem weekendu spędzonego na studiowaniu tej dokumentacji jest moja wersja klasy umożliwiającej zapis wiersz po wierszu do arkusza MS Excel - [SimpleExcelStreamWriter](https://github.com/tswiackiewicz/SimpleExcelStreamWriter). Przykładowe użycie:

``` php
$objExcelStream = new SimpleExcelStreamWriter('test.xls');
$objExcelStream->addNextRow($headers);
$objExcelStream->addNextRow($row_1);
$objExcelStream->addNextRow($row_2);
$objExcelStream->addNextRow($row_3);
$objExcelStream->addNextRow($row_n);
$objExcelStream->close();
```

Podsumowując, ***SimpleExcelStreamWriter*** to przykład prostego i dość wydajnego rozwiązania umożliwiającego zapis wiersz po wierszu tabelarycznych danych w formacie .xls. Co prawda posiada szereg ograniczeń, np. tylko pojedynczy arkusz, ale do eksportu raportów powinno to w zupełności wystarczyć. Na koniec jeszcze kilka przydatnych linków:

* [https://github.com/tswiackiewicz/SimpleExcelStreamWriter](https://github.com/tswiackiewicz/SimpleExcelStreamWriter)
* [http://stackoverflow.com/questions/3930975/alternative-for-php-excel](http://stackoverflow.com/questions/3930975/alternative-for-php-excel)
* [https://github.com/PHPOffice/PHPExcel](https://github.com/PHPOffice/PHPExcel)
* [http://pear.php.net/package/Spreadsheet_Excel_Writer](http://pear.php.net/package/Spreadsheet_Excel_Writer)
* [http://www.bettina-attack.de/jonny/view.php/projects/php_writeexcel/](http://www.bettina-attack.de/jonny/view.php/projects/php_writeexcel/)
* [http://code.google.com/p/hexcel/](http://code.google.com/p/hexcel/)
* [http://www.phpclasses.org/package/1919-PHP-Stream-wrapper-to-read-and-write-MS-Excel-files.html](http://www.phpclasses.org/package/1919-PHP-Stream-wrapper-to-read-and-write-MS-Excel-files.html)
* [https://github.com/PHPOffice/PHPExcel/wiki/File-Format-References](https://github.com/PHPOffice/PHPExcel/wiki/File-Format-References)
* [http://www.openoffice.org/sc/excelfileformat.odt](http://www.openoffice.org/sc/excelfileformat.odt)
* [http://download.microsoft.com/download/2/4/8/24862317-78F0-4C4B-B355-C7B2C1D997DB/[MS-XLS].pdf](http://download.microsoft.com/download/2/4/8/24862317-78F0-4C4B-B355-C7B2C1D997DB/%5BMS-XLS%5D.pdf)
* [https://github.com/PHPOffice/PHPExcel/wiki/File-Format-References](https://github.com/PHPOffice/PHPExcel/wiki/File-Format-References)
* [http://www.joelonsoftware.com/items/2008/02/19.html](http://www.joelonsoftware.com/items/2008/02/19.html)
* [http://blog.mayflower.de/561-Import-and-export-data-using-PHPExcel.html](http://blog.mayflower.de/561-Import-and-export-data-using-PHPExcel.html)
* [https://fclaweb.fcla.edu/uploads/Lydia Motyka/FDA_documentation/Action_Plans/BIFF8_apb.pdf](https://fclaweb.fcla.edu/uploads/Lydia Motyka/FDA_documentation/Action_Plans/BIFF8_apb.pdf)


