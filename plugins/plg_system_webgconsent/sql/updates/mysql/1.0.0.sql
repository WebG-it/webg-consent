-- Schema iniziale: la tabella viene creata da sql/install.mysql.utf8.sql.
-- Questo file esiste per tenere traccia della versione dello schema (1.0.0).
CREATE TABLE IF NOT EXISTS `#__webgconsent_log` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `created` datetime NOT NULL,
  `visitor_id` char(32) NOT NULL,
  `text_version` varchar(32) NOT NULL,
  `outcome` varchar(8) NOT NULL,
  `ip_hash` char(64) NOT NULL,
  `user_agent` varchar(160) NOT NULL DEFAULT '',
  `entry_url` varchar(255) NOT NULL DEFAULT '',
  PRIMARY KEY (`id`),
  KEY `idx_created` (`created`),
  KEY `idx_visitor` (`visitor_id`),
  KEY `idx_ip_created` (`ip_hash`, `created`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 DEFAULT COLLATE=utf8mb4_unicode_ci;
