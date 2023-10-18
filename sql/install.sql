CREATE TABLE IF NOT EXISTS `civicrm_geocoder` (
  `id` int unsigned NOT NULL AUTO_INCREMENT COMMENT 'Unique Geocoder ID',
  `name` varchar(32) NOT NULL COMMENT 'Provider name',
  `title` varchar(32) NOT NULL COMMENT 'Provider Title',
  `class` varchar(32) NOT NULL COMMENT 'Non generic part of the class name - after Geocoder_Provider. See mgd files for examples',
  `is_active` tinyint DEFAULT 0 COMMENT 'Enabled?',
  `weight` int unsigned COMMENT 'Weight',
  `api_key` varchar(255) COMMENT 'API Key or a json array of user customised values.',
  `url` varchar(255) COMMENT 'URL (if required)',
  `required_fields` varchar(255) COMMENT 'Array of fields required for this to parse',
  `retained_response_fields` varchar(255) DEFAULT '["geo_code_1","geo_code_2"]' COMMENT 'fields to be retained from the response',
  `datafill_response_fields` varchar(255) COMMENT 'fields retained to fill but not overwrite data',
  `threshold_standdown` int DEFAULT 60 COMMENT 'Number of seconds to wait before retrying after hitting threshold. Geocaching delayed in this time',
  `threshold_last_hit` timestamp NULL COMMENT 'Timestamp when the threshold was last hit.',
  `valid_countries` varchar(255) NULL COMMENT 'Countries this geocoder is valid for',
  PRIMARY KEY (`id`)
)
ENGINE=InnoDB;
