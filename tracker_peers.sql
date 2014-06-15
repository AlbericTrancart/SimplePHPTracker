CREATE TABLE IF NOT EXISTS `tracker_peers` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `peer_id` blob NOT NULL,
  `ip_address` int(11) unsigned NOT NULL,
  `port` int(11) unsigned NOT NULL,
  `info_hash` blob NOT NULL,
  `bytes_uploaded` int(11) unsigned DEFAULT NULL,
  `bytes_downloaded` int(11) unsigned DEFAULT NULL,
  `bytes_left` int(11) unsigned DEFAULT NULL,
  `status` varchar(10) NOT NULL DEFAULT 'incomplete',
  `expires` datetime DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8;

