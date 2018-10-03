CREATE TABLE IF NOT EXISTS `#__discussions_topics` (
	`id`          INT(11)          NOT NULL AUTO_INCREMENT,
	`context`     VARCHAR(255)     NOT NULL DEFAULT '',
	`item_id`     INT(11)          NOT NULL DEFAULT '0',
	`title`       VARCHAR(255)     NOT NULL DEFAULT '',
	`text`        LONGTEXT         NOT NULL DEFAULT '',
	`images`      LONGTEXT         NOT NULL DEFAULT '',
	`state`       TINYINT(3)       NOT NULL DEFAULT '0',
	`created`     DATETIME         NOT NULL DEFAULT '0000-00-00 00:00:00',
	`created_by`  INT(11)          NOT NULL DEFAULT '0',
	`attribs`     TEXT             NOT NULL DEFAULT '',
	`metakey`     MEDIUMTEXT       NOT NULL DEFAULT '',
	`metadesc`    MEDIUMTEXT       NOT NULL DEFAULT '',
	`access`      INT(10)          NOT NULL DEFAULT '0',
	`hits`        INT(10) UNSIGNED NOT NULL DEFAULT '0',
	`region`      CHAR(7)          NOT NULL DEFAULT '*',
	`metadata`    MEDIUMTEXT       NOT NULL DEFAULT '',
	`tags_search` MEDIUMTEXT       NOT NULL DEFAULT '',
	`tags_map`    MEDIUMTEXT       NOT NULL DEFAULT '',
	UNIQUE KEY `id` (`id`)
)
	ENGINE = MyISAM
	DEFAULT CHARSET = utf8
	AUTO_INCREMENT = 0;

CREATE TABLE IF NOT EXISTS `#__discussions_posts` (
	`id`         INT(11)    NOT NULL AUTO_INCREMENT,
	`topic_id`   INT(11)    NOT NULL DEFAULT '0',
	`text`       TEXT       NOT NULL DEFAULT '',
	`state`      TINYINT(3) NOT NULL DEFAULT '0',
	`created`    DATETIME   NOT NULL DEFAULT '0000-00-00 00:00:00',
	`created_by` INT(11)    NOT NULL DEFAULT '0',
	`access`     INT(10)    NOT NULL DEFAULT '0',
	UNIQUE KEY `id` (`id`)
)
	ENGINE = MyISAM
	DEFAULT CHARSET = utf8
	AUTO_INCREMENT = 0;


CREATE TABLE IF NOT EXISTS `#__discussions_tags` (
  `id`           INT(11)          NOT NULL AUTO_INCREMENT,
  `metakey`      MEDIUMTEXT       NOT NULL DEFAULT '',
  `metadesc`     MEDIUMTEXT       NOT NULL DEFAULT '',
  `metadata`     MEDIUMTEXT       NOT NULL DEFAULT '',
  UNIQUE KEY `id` (`id`)
)
  ENGINE = MyISAM
  DEFAULT CHARSET = utf8
AUTO_INCREMENT = 0;