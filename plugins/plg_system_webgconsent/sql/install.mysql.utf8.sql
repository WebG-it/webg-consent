CREATE TABLE IF NOT EXISTS `#__webgconsent_log` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `created` datetime NOT NULL COMMENT 'UTC',
  `visitor_id` char(32) NOT NULL COMMENT 'ID pseudonimo casuale, lo stesso del cookie wgc_ok',
  `text_version` varchar(32) NOT NULL,
  `outcome` varchar(8) NOT NULL COMMENT 'accepted | rejected',
  `ip_hash` char(64) NOT NULL COMMENT 'HMAC-SHA256 con sale, mai IP in chiaro',
  `user_agent` varchar(160) NOT NULL DEFAULT '',
  `entry_url` varchar(255) NOT NULL DEFAULT '' COMMENT 'solo percorso, senza query',
  PRIMARY KEY (`id`),
  KEY `idx_created` (`created`),
  KEY `idx_visitor` (`visitor_id`),
  KEY `idx_ip_created` (`ip_hash`, `created`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 DEFAULT COLLATE=utf8mb4_unicode_ci;
