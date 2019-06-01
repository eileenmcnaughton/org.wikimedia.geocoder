ALTER TABLE civicrm_geocoder ADD COLUMN IF NOT EXISTS is_licensed TINYINT(1) DEFAULT '0' COMMENT 'Use licensed provider ?';
ALTER TABLE civicrm_geocoder ADD COLUMN IF NOT EXISTS parameters VARCHAR(255) COLLATE utf8_unicode_ci DEFAULT NULL COMMENT 'parameters to pass to provider';
