DROP TABLE IF EXISTS `users`;
CREATE TABLE `users` (
  `id` INT(11) UNSIGNED NOT NULL AUTO_INCREMENT,
  `email` VARCHAR(254) NOT NULL,
  `username` VARCHAR(32) NOT NULL DEFAULT '',
  `password` VARCHAR(64) NOT NULL,
  `logins` INT(10) UNSIGNED NOT NULL DEFAULT '0',
  `last_login` INT(10) UNSIGNED,
  `facebook_uid` VARCHAR(100),
  `twitter_uid` VARCHAR(100),
  `last_login_ip` VARCHAR(40),
  PRIMARY KEY  (`id`),
  UNIQUE KEY `uniq_username` (`username`),
  UNIQUE KEY `uniq_email` (`email`)
) ENGINE=INNODB  DEFAULT CHARSET=utf8;

DROP TABLE IF EXISTS `visitors`;
CREATE TABLE `visitors` (
  `id` INT(11) UNSIGNED NOT NULL AUTO_INCREMENT,
  `user_id` INT(10) UNSIGNED NULL,
  `purchase_id` INT(10) UNSIGNED NULL,
  `country_id` INT(10) UNSIGNED NULL,
  `token` VARCHAR(100),
  `ip` VARCHAR(100),
  `currency` VARCHAR(3) NOT NULL,
  PRIMARY KEY  (`id`)
) ENGINE=INNODB  DEFAULT CHARSET=utf8;


DROP TABLE IF EXISTS `locations`;
CREATE TABLE `locations` (
  `id` int(11) UNSIGNED NOT NULL AUTO_INCREMENT,
  `name` varchar(100) NOT NULL,
  `short_name` varchar(10) NOT NULL,
  `type` varchar(100) NOT NULL,
  PRIMARY KEY  (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

DROP TABLE IF EXISTS `locations_branches`;
CREATE TABLE `locations_branches` (
  `ansestor_id` int(11) UNSIGNED NOT NULL,
  `descendant_id` int(11) UNSIGNED NOT NULL,
  `depth` int(11) UNSIGNED NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

INSERT INTO `locations` (`id`, `name`, `short_name`, `type`)
VALUES
  (1,'Everywhere', NULL, 'region'),
  (2,'Europe', NULL, 'region'),
  (3,'France', 'FR', 'country'),
  (4,'Turkey', 'TR', 'country'),
  (5,'Germany', 'GR', 'country'),
  (6,'Australia', 'AU', 'country'),
  (7,'United Kingdom', 'GB', 'country'),
  (8,'Russia', 'RU', 'country'),
  (9,'London', NULL, 'city');

INSERT INTO `locations_branches` (`ansestor_id`, `descendant_id`, `depth`)
VALUES
  (1,1,0),
  (2,2,0),
  (3,3,0),
  (4,4,0),
  (5,5,0),
  (6,6,0),
  (7,7,0),
  (8,8,0),
  (9,9,0),
  (1,2,1),
  (1,4,1),
  (1,5,1),
  (1,6,1),
  (1,7,1),
  (1,8,1),
  (1,9,1),
  (1,3,2),
  (1,7,2),
  (1,9,3),
  (2,3,1),
  (2,5,1),
  (2,7,1),
  (2,9,2),
  (7,9,1);

# Dump of table users
# ------------------------------------------------------------

INSERT INTO `users` (`id`, `email`, `username`, `password`, `logins`, `last_login`, `facebook_uid`, `twitter_uid`, `last_login_ip`)
VALUES
  (1,'admin@example.com','admin','f02c9f1f724ebcf9db6784175cb6bd82663380a5f8bd78c57ad20d5dfd953f15',5,1374320224,'facebook-test','','10.20.10.1'),
  (2,'user@example.com','user','f02c9f1f724ebcf9db6784175cb6bd82663380a5f8bd78c57ad20d5dfd953f15',3,1374320224,'facebook-test2','','10.20.10.2');

INSERT INTO `visitors` (`id`, `user_id`, `country_id`, `token`, `ip`, `currency`)
VALUES
  (1,1,3,'f02c9f1f724ebcf9db6784175cb6bd82663380a5f8bd78c57ad20d5dfd953f15','10.20.10.1','EUR');
