ALTER TABLE civicrm_geocoder ADD COLUMN IF NOT EXISTS parameters VARCHAR(255) COLLATE utf8_unicode_ci DEFAULT NULL COMMENT 'parameters (json format) to pass to provider';
