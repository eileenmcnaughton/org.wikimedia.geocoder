<?php
/**
 * Created by IntelliJ IDEA.
 * User: emcnaughton
 * Date: 1/19/18
 * Time: 3:09 PM
 */
return [
  [
    'name' => 'google_maps',
    'entity' => 'Geocoder',
    'update' => 'never',
    'params' => [
      'version' => 3,
      'name' => 'google_maps',
      'title' => 'Google Maps',
      'class' => 'GoogleMaps\GoogleMaps',
      'datafill_response_fields' => ["city", "state_province_id", "county_id"],
      'api_key' =>  \Civi::settings()->get('geoAPIKey'),
      'is_active' => FALSE,
      'weight' => 5,
    ],
    'help_text' => ts('Adhering to Terms of service is your responsibility - https://support.google.com/code/answer/55180?hl=en'),
    'user_editable_fields' => ['api_key', 'threshold_standdown'],
    'metadata' => [
      'argument' => [NULL, 'geocoder.api_key'],
      'required_config_fields' => ['api_key'],
      // Not enabled by default, but special handling will enable if api key is already configured.
      'is_enabled_on_install' => FALSE,
    ],
  ]
];
