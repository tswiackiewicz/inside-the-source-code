---
layout: post
title: "SphinxSearch - instalacja pluginu SphinxSE"
description: "W artykule zatytułowanym SphinxSE - Sphinx Storage Engine przedstawiony został prosty i szybki sposób rozszerzenia funkcjonalności naszej aplikacji o możliwość wyszukiwania pełnotekstowego (ang. full-text search). Zostało to osiągnięte dzięki wykorzystaniu SphinxSE czyli opcjonalnemu sposobowi..."
headline: 
modified: 2015-02-22
category: sphinxsearch
tags: [ha_sphinx.so, mariadb, mysql, percona, plugin, sphinxse, sphinx, sphinxsearch]
comments: true
featured: false
---

W artykule zatytułowanym [SphinxSE - Sphinx Storage Engine]({{ site.url}}/sphinxsearch/sphinxse-sphinx-storage-engine/) przedstawiony został prosty i szybki sposób rozszerzenia funkcjonalności naszej aplikacji o możliwość wyszukiwania pełnotekstowego (*ang. full-text search*). Zostało to osiągnięte dzięki wykorzystaniu **SphinxSE** czyli opcjonalnemu sposobowi przechowywania wyników wyszukiwania w wirtualnej tabeli, na której można wykonywać operacje typu *JOIN* z istniejącymi w naszej bazie danych tabelami. Wspomniany na początku artykuł skupia się wyłącznie na składni oraz możliwości zapytań *Sphinx Storage Engine*, pomijając zupełnie krok włączenie obsługi *ENGINE=SPHINX* przez bazę danych.

### INSTALL PLUGIN sphinx

Dodatkowy engine (jak choćby *MyISAM*, *InnoDB*, *Memory* itd. ) dla baz danych z rodziny MySQL (*MySQL*, *MariaDB*, *Percona Server*) dostarczany jest w postaci pluginu:

{% highlight bash %}
[mysql]> INSTALL PLUGIN sphinx SONAME 'ha_sphinx.so';
{% endhighlight %}

Jeśli instalacja pluginu przebiegła pomyślnie, na liście dostępnych silników powinien pojawić się SPHINX:

{% highlight sql %}
[mysql]> SHOW ENGINES;
+--------------------+---------+----------------------------------------------------------------------------+--------------+------+------------+
| Engine             | Support | Comment                                                                    | Transactions | XA   | Savepoints |
+--------------------+---------+----------------------------------------------------------------------------+--------------+------+------------+
| MyISAM             | YES     | MyISAM storage engine                                                      | NO           | NO   | NO         |
| MEMORY             | YES     | Hash based, stored in memory, useful for temporary tables                  | NO           | NO   | NO         |
| InnoDB             | DEFAULT | Supports transactions, row-level locking, and foreign keys                 | YES          | YES  | YES        |
| :                  | YES     | :                                                                          | NO           | NO   | NO         |
| :                  | YES     | :                                                                          | NO           | NO   | NO         |
| SPHINX             | YES     | Sphinx storage engine                                                      | NO           | NO   | NO         |
| :                  | YES     | :                                                                          | NO           | NO   | NO         |
| :                  | YES     | :                                                                          | NO           | NO   | NO         |
| PERFORMANCE_SCHEMA | YES     | Performance Schema                                                         | NO           | NO   | NO         |
+--------------------+---------+----------------------------------------------------------------------------+--------------+------+------------+  
{% endhighlight %}

Następnie możemy przystąpić do utworzenia tabeli z ***ENGINE=SPHINX*** i rozpocząć *full-text* search zadając klasyczne zapytania SQL. Niestety w przypadku Percona-Server, nie wszystko jest takie proste...

{% highlight bash %}
[Percona]> INSTALL PLUGIN sphinx SONAME 'ha_sphinx.so';
ERROR 1126 (HY000): Can't open shared library '/usr/lib/mysql/plugin/ha_sphinx.so' (errno: 2 /usr/lib/mysql/plugin/ha_sphinx.so: cannot open shared object file: No such file or directory) 
{% endhighlight %}

### *ha_sphinx.so*

Powyższy błąd spowodowany jest tym, że plugin do obsługi *SphinxSE* nie jest dostarczany razem z **Percona Server** (posiadają własną obsługę [FTS](http://www.percona.com/blog/2013/02/26/myisam-vs-innodb-full-text-search-in-mysql-5-6-part-1/])), zatem konieczne jest przygotowanie takiej biblioteki we własnym zakresie.

Procedura kompilacji pluginu wygląda następująco:

&#49;. przygotowanie katalogu roboczego

{% highlight bash %}
[tswiackiewicz@localhost:~]$ cd ~; mkdir percona_sphinx; cd percona_sphinx
{% endhighlight %}

&#50;. pobranie źródeł Percona-Server (wersja zgodną z tą z której korzystamy) oraz ich rozpakowanie

{% highlight bash %}
[tswiackiewicz@localhost:~/percona_sphinx]$ wget http://www.percona.com/downloads/Percona-Server-5.6/Percona-Server-5.6.22-72.0/source/tarball/percona-server-5.6.22-72.0.tar.gz
[tswiackiewicz@localhost:~/percona_sphinx]$ tar -xzf percona-server-5.6.22-72.0.tar.gz
{% endhighlight %}

&#51;. pobranie źródeł SphinxSearch oraz ich rozpakowanie

{% highlight bash %}
[tswiackiewicz@localhost:~/percona_sphinx]$ wget http://sphinxsearch.com/files/sphinx-2.2.6-release.tar.gz
[tswiackiewicz@localhost:~/percona_sphinx]$ tar -xzf sphinx-2.2.6-release.tar.gz
{% endhighlight %}

&#52;. kopiowanie źródeł pluginu do obsługi SphinxSE do Percona-Server

{% highlight bash %}
[tswiackiewicz@localhost:~/percona_sphinx]$ mkdir percona-server-5.6.22-72.0/storage/sphinx
[tswiackiewicz@localhost:~/percona_sphinx]$ cp sphinx-2.2.6-release/mysqlse/* percona-server-5.6.22-72.0/storage/sphinx/
{% endhighlight %}

&#53;. kompilacja źródeł Percony i wygenerowanie biblioteki *ha_sphinx.so*

{% highlight bash %}
[tswiackiewicz@localhost:~/percona_sphinx]$ cd percona-server-5.6.22-72.0; sh BUILD/autorun.sh; ./configure --with-plugins=sphinx
[tswiackiewicz@localhost:~/percona_sphinx]$ cd storage/sphinx; make
{% endhighlight %}

&#54;. kopiowanie biblioteki do docelowej lokalizacji oraz instalacja pluginu

{% highlight bash %}
[tswiackiewicz@localhost:~/percona_sphinx]$ sudo cp ~/percona_sphinx/percona-server-5.6.22-72.0/storage/sphinx/ha_sphinx.so /usr/lib/mysql/plugin
[tswiackiewicz@localhost:~]$ sudo service mysql restart
mysql stop/waiting
mysql start/running, process 6662
{% endhighlight %}

{% highlight bash %}
[Percona]> INSTALL PLUGIN sphinx SONAME 'ha_sphinx.so';
{% endhighlight %}

### Ubuntu 14.04

Przedstawiona powyżej procedura bardzo dobrze sprawdziła się w przypadku środowiska *Mac OS X Yosemite*, ale niestety problemy wystąpiły w *Ubuntu 14.04* (*Percona-Server* zainstalowany za pomocą apt-get):

{% highlight bash %}
[Percona]> INSTALL PLUGIN sphinx SONAME 'ha_sphinx.so';
ERROR 1126 (HY000): Can't open shared library '/usr/lib/mysql/plugin/ha_sphinx.so' (errno: 2 /usr/lib/mysql/plugin/ha_sphinx.so: undefined symbol: _ZTI7handler)
{% endhighlight %}

Analiza tego błędu i fachowych porad doprowadziła mnie do przyczyny - plugin trzeba skompilować z dokładnie takimi flagami z jakimi przygotowana została dostarczona dystrybucja naszej wersji bazy danych. Aby móc podejrzeć parametry kompilacji konieczne jest pobranie źródeł

{% highlight bash %}
[tswiackiewicz@localhost:~/percona_sphinx]$ mkdir percona-server-source; cd percona-server-source; sudo apt-get source percona-server-5.6
{% endhighlight %}

oraz wyszukiwanie parametrów kompilacji (cmake) w pliku percona-server-{$VERSION}/debian/rules

Wracając do naszej procedury kompilacji, kroki 1-4 są identyczne, natomiast w kroku 5 wykonujemy polecenie cmake z takimi parametrami z jakimi została skompilowana nasza baza danych, np.

{% highlight bash %}
[tswiackiewicz@localhost:~/percona_sphinx]$ cd percona-server-5.6.22-72.0; cmake -DCMAKE_INSTALL_PREFIX=/usr -DBUILD_CONFIG=mysql_release -DCMAKE_CXX_FLAGS="-O3 -g -felide-constructors -fno-exceptions -fno-rtti -fno-strict-aliasing" -DMYSQL_UNIX_ADDR=/var/run/mysqld/mysqld.sock -DCMAKE_BUILD_TYPE=RelWithDebInfo -DWITH_LIBWRAP=ON -DWITH_ZLIB=system -DWITH_SSL=system -DCOMPILATION_COMMENT="Percona Server (GPL), Release 72.0, Revision 738" -DSYSTEM_TYPE="debian-linux-gnu" -DINSTALL_LAYOUT=RPM -DINSTALL_LIBDIR=lib/i386-linux-gnu -DINSTALL_PLUGINDIR=lib/mysql/plugin -DWITH_EMBEDDED_SERVER=OFF -DWITH_INNODB_MEMCACHED=ON -DWITH_ARCHIVE_STORAGE_ENGINE=ON -DWITH_BLACKHOLE_STORAGE_ENGINE=ON -DWITH_FEDERATED_STORAGE_ENGINE=ON -DWITH_PAM=ON -DWITH_EXTRA_CHARSETS=all
[tswiackiewicz@localhost:~/percona_sphinx]$ cd storage/sphinx; make
{% endhighlight %}

Krok 6 jest identyczny tj. kopiowanie biblioteki a następnie instalacja pluginu.

Generalnie, włączenie obsługi **SphinxSE** jest bardzo proste. Jednak, w szczególnych przypadkach, może wymagać nieco więcej wysiłku (przygotowanie pluginu we własnym zakresie). Niemniej uważam, że i tak to jest stosunkowo niewielki koszt biorąc pod uwagę bardzo duże możliwości oferowane przez *Sphinx Storage Engine* oraz niski próg wejścia i mały zakres wprowadzonych zmian w bazowej wersji naszej aplikacji.

Przydatne linki:

* [https://mariadb.com/kb/en/mariadb/about-sphinxse/](https://mariadb.com/kb/en/mariadb/about-sphinxse/)
* [http://sysmagazine.com/posts/161461/](http://sysmagazine.com/posts/161461/)
* [https://trac.macports.org/ticket/45136](https://trac.macports.org/ticket/45136)
* [http://www.percona.com/doc/percona-server/5.6/installation/apt_repo.html](http://www.percona.com/doc/percona-server/5.6/installation/apt_repo.html)
* [http://www.digincore.org/index.php/blogi-razrabotchikov/entry/sphinxse-percona-mysql-ubuntu-debian](http://www.digincore.org/index.php/blogi-razrabotchikov/entry/sphinxse-percona-mysql-ubuntu-debian)
* [https://github.com/dragolabs/dpkg-sphinx-se](https://github.com/dragolabs/dpkg-sphinx-se)
* [https://github.com/ruhllatio/dpkg-sphinx-se](https://github.com/ruhllatio/dpkg-sphinx-se)
* [https://github.com/johnmarkg/percona-cluster-with-sphinx](https://github.com/johnmarkg/percona-cluster-with-sphinx)
* [https://github.com/nZEDb/nZEDb/tree/master/misc/sphinxsearch](https://github.com/nZEDb/nZEDb/tree/master/misc/sphinxsearch)
* [http://forums.nzedb.com/index.php?topic=1755.0](http://forums.nzedb.com/index.php?topic=1755.0)
* [http://www.codefromaway.net/2012/02/setting-up-percona-galera-and-sphinxse.html](http://www.codefromaway.net/2012/02/setting-up-percona-galera-and-sphinxse.html)
* [http://www.nicovs.be/install-mysql-with-sphinx-search-engine-support/](http://www.nicovs.be/install-mysql-with-sphinx-search-engine-support/)
* [http://forums.mysql.com/read.php?94,260436,260436](http://forums.mysql.com/read.php?94,260436,260436)
* [https://mariadb.com/kb/en/mariadb/sphinx-status-variables/](https://mariadb.com/kb/en/mariadb/sphinx-status-variables/)
* [http://sphinxsearch.com/forum/view.html?id=10238](http://sphinxsearch.com/forum/view.html?id=10238)
* [http://sphinxsearch.com/forum/view.html?id=10017](http://sphinxsearch.com/forum/view.html?id=10017)
* [http://sphinxsearch.com/forum/view.html?id=6862](http://sphinxsearch.com/forum/view.html?id=6862)
* [http://www.percona.com/blog/2013/02/26/myisam-vs-innodb-full-text-search-in-mysql-5-6-part-1/](http://www.percona.com/blog/2013/02/26/myisam-vs-innodb-full-text-search-in-mysql-5-6-part-1/)

