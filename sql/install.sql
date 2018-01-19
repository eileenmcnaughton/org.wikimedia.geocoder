CREATE TABLE `civicrm_geocoder` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT COMMENT 'Unique Geocoder ID',
  `name` varchar(32) COLLATE utf8_unicode_ci NOT NULL COMMENT 'Provider name',
  `title` varchar(32) COLLATE utf8_unicode_ci NOT NULL COMMENT 'Provider Title',
  `class` varchar(32) COLLATE utf8_unicode_ci NOT NULL DEFAULT '' COMMENT 'Non generic part of the class name - after Geocoder\\Provider\\. See mgd files for examples',
  `is_active` tinyint(1) DEFAULT '0' COMMENT 'Enabled?',
  `weight` int(10) unsigned DEFAULT NULL COMMENT 'Weight',
  `api_key` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL COMMENT 'API Key',
  `url` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL COMMENT 'URL (if required)',
  `required_fields` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL COMMENT 'json array of fields required for this to parse',
  `retained_response_fields` varchar(255) COLLATE utf8_unicode_ci DEFAULT '["geo_code_1","geo_code_2"]' COMMENT 'fields to be retained from the response',
  `additional_metadata` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL COMMENT 'json array of any additional provider specific data',
  `threshold` int(11) DEFAULT NULL COMMENT 'Threshold of queries in period',
  `threshold_unit` varchar(12) COLLATE utf8_unicode_ci DEFAULT 'day' COMMENT 'Unit in which threshold is measured',
  `threshold_number` int(11) DEFAULT '1' COMMENT 'Number of units in threshold',
  `counter_start` timestamp NULL DEFAULT NULL COMMENT 'When the counter was last reset',
  `counter` int(11) NOT NULL DEFAULT 0 COMMENT 'Number of queries since counter',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;


