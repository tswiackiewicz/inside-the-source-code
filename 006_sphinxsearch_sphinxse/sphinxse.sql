CREATE TABLE `sphinx_results` (
  `id` bigint(20) NOT NULL,
  `weight` int(11) NOT NULL,
  `query` varchar(3072) NOT NULL,
  `attr1` bigint(20) DEFAULT '0',
  `attr2` bigint(20) DEFAULT '0',
  `attr3` bigint(20) DEFAULT '0',
  `_sph_groupby` int(11) DEFAULT '0',
  `_sph_count` int(11) DEFAULT '0',
  `_sph_distinct` int(11) DEFAULT '0',
  KEY `query` (`query`(1024))
) ENGINE=SPHINX DEFAULT CHARSET=utf8;


SELECT 
    d.`id`, 
    d.`title`, 
    d.`content`, 
    d.`created_on` 
FROM 
    `sphinx_results` AS sphx 
JOIN 
    `docs` AS d 
ON 
    d.`id` = sphx.`id` 
WHERE 
    sphx.`query` = "@content Lorem ipsum;mode=extended;index=test_sphinxse1;host=127.0.0.1;port=9312;" 


SELECT 
    `id`, 
    `title`, 
    `content`, 
    `created_on` 
FROM 
    `sphinx_results` 
WHERE 
    `query` = "@content Lorem ipsum;mode=extended;index=test_sphinxse2;host=127.0.0.1;port=9312;";


SELECT 
    d.`id`, 
    d.`title`, 
    d.`content`, 
    sphx.`attr1` 
FROM 
    `sphinx_results` AS sphx 
JOIN 
    `docs` AS d 
ON 
    d.`id` = sphx.`id` 
WHERE 
    sphx.`query` = "@content Lorem ipsum;mode=extended;index=test_sphinxse1;filter=attr1,1;host=127.0.0.1;port=9312;";


SELECT 
    `id`, 
    `content`, 
    `_sph_count` AS count 
FROM 
    `sphinx_results` 
WHERE 
    `query` = "@title sample;mode=extended;index=test_sphinxse2;groupby=attr:content;groupsort=@count desc;host=127.0.0.1;port=9312;";


SELECT 
    `id`, 
    `title`, 
    `content` 
FROM 
    `sphinx_results` 
WHERE 
    `query` = "@content \"\\\\\\\"test\\\\\\\" \\\'test\\\' test\\\% \\\@\\\;\\\,\\\=\";mode=extended;index=test_sphinxse2;host=127.0.0.1;port=9312;";
