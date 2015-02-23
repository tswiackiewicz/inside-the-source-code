
DROP DATABASE IF EXISTS `sphinx_test`;
CREATE DATABASE `sphinx_test`;
USE `sphinx_test`;

DROP TABLE IF EXISTS `docs`;
CREATE TABLE `docs` (
  `id` bigint(20) NOT NULL AUTO_INCREMENT,
  `attr1` bigint(20) NOT NULL,
  `attr2` bigint(20) NOT NULL,
  `attr3` bigint(20) NOT NULL,
  `title` varchar(100) NOT NULL,
  `content` text,
  `json_content` text,
  `created_on` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=18 DEFAULT CHARSET=utf8;

DROP TABLE IF EXISTS `sphinx_results`;
CREATE TABLE `sphinx_results` (
  `id` bigint(20) NOT NULL,
  `weight` int(11) NOT NULL,
  `query` varchar(3072) NOT NULL,
  `attr1` bigint(20) DEFAULT '0',
  `attr2` bigint(20) DEFAULT '0',
  `attr3` bigint(20) DEFAULT '0',
  `title` varchar(100) NOT NULL,
  `content` varchar(1000) DEFAULT NULL,
  `json_content` varchar(1000) DEFAULT NULL,
  `created_on` timestamp NOT NULL DEFAULT '0000-00-00 00:00:00',
  `_sph_groupby` int(11) DEFAULT '0',
  `_sph_count` int(11) DEFAULT '0',
  `_sph_distinct` int(11) DEFAULT '0',
  KEY `query` (`query`(1024))
) ENGINE=SPHINX DEFAULT CHARSET=utf8;

