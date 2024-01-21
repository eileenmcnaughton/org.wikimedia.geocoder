<?php
return [
  [
    'name' => 'geocoder_dot_ca',
    'entity' => 'Geocoder',
    'update' => 'never',
    'params' => [
      'version' => 3,
      'name' => 'geocoder_dot_ca',
      'title' => 'Geocoder.ca',
      'class' => 'Geocoder.ca\Geocoder.ca',
      'api_key' =>  \Civi::settings()->get('geoAPIKey'),
      'url' => 'https://geocoder.ca',
      'is_active' => FALSE,
      'weight' => 8,
    ],
    'help_text' => ts('api key required - sign up https://geocoder.ca/?register=1'),
    'user_editable_fields' => ['api_key', 'threshold_standdown'],
    'metadata' => [
      'argument' => ['geocoder.api_key', 'geocoder.url'],
      'required_config_fields' => ['api_key'],
      'is_enabled_on_install' => FALSE,
    ],
  ]
];
