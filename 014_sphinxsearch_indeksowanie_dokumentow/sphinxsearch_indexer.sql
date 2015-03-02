sphinxQL> CALL KEYWORDS('The quick brown fox jumped over the lazy dog', 'test_indexer_stemmer');
+------+-----------+------------+
| qpos | tokenized | normalized |
+------+-----------+------------+
| 2    | quick     | quick      |
| 3    | brown     | brown      |
| 4    | fox       | fox        |
| 5    | jumped    | jump       |
| 6    | over      | over       |
| 8    | lazy      | lazy       |
| 9    | dog       | dog        |
+------+-----------+------------+
7 rows in set (0.00 sec)

sphinxQL> CALL KEYWORDS('Quick <i>brown</i> foxes leap over <b>lazy</b> dogs in summer', 'test_indexer_stemmer');
+------+-----------+------------+
| qpos | tokenized | normalized |
+------+-----------+------------+
| 1    | quick     | quick      |
| 2    | brown     | brown      |
| 3    | foxes     | fox        |
| 4    | leap      | jump       |
| 5    | over      | over       |
| 6    | lazy      | lazy       |
| 7    | dogs      | dog        |
| 8    | in        | in         |
| 9    | summer    | summer     |
+------+-----------+------------+
9 rows in set (0.00 sec)

sphinxQL> CALL KEYWORDS('@sphinxsearch! AT&T user@sphinxsearch.com', 'test_blend_chars');
+------+-----------------------+-----------------------+
| qpos | tokenized             | normalized            |
+------+-----------------------+-----------------------+
| 1    | @sphinxsearch!        | @sphinxsearch!        |
| 1    | sphinxsearch!         | sphinxsearch!         |
| 1    | @sphinxsearch         | @sphinxsearch         |
| 1    | sphinxsearch          | sphinxsearch          |
| 2    | at&t                  | at&t                  |
| 2    | at                    | at                    |
| 3    | t                     | t                     |
| 4    | user@sphinxsearch.com | user@sphinxsearch.com |
| 4    | user                  | user                  |
| 5    | sphinxsearch.com      | sphinxsearch.com      |
+------+-----------------------+-----------------------+
10 rows in set (0.00 sec)
