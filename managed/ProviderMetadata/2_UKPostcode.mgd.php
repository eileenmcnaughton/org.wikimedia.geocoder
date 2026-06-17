<?php
return [
  [
    'name' => 'uk_postcode',
    'entity' => 'Geocoder',
    'update' => 'never',
    'params' => [
      'version' => 3,
      'name' => 'uk_postcode',
      'title' => 'UK postcode based geocoding',
      'class' => 'UKPostcodeProvider',
      'valid_countries' => ['GB'],
      'required_fields' => ['postal_code'],
      'retained_response_fields' => '["geo_code_1","geo_code_2", "postal_code"]',
      'datafill_response_fields' => [],
      'is_active' => FALSE,
      'weight' => 2,
    ],
    'metadata' => [
      'is_enabled_on_install' => FALSE,
    ]
  ]
];

