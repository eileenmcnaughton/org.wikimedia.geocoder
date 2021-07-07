<?php
return [
  [
    'name' => 'de_plz',
    'entity' => 'Geocoder',
    'params' => [
      'version' => 3,
      'name' => 'de_plz',
      'title' => 'DE PLZ based geocoding',
      'class' => 'DEPlzProvider',
      'valid_countries' => ['DE'],
      'required_fields' => ['postal_code'],
      'retained_response_fields' => '["geo_code_1","geo_code_2", "postal_code"]',
      'datafill_response_fields' => ["city"],
    ],
    'metadata' => [
      'is_enabled_on_install' => FALSE,
    ]
  ]
];

