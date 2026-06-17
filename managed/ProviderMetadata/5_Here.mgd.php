<?php
/**
 * User: olivier
 * Date: 1/6/19
 * Time: 3:09 PM
 */
return [
  [
    'name' => 'here',
    'entity' => 'Geocoder',
    'update' => 'never',
    'params' => [
      'version' => 3,
      'name' => 'here',
      'title' => 'Here',
      'class' => 'Here\Here',
      'retained_response_fields' => ['geo_code_1', 'geo_code_2'],
      'is_active' => FALSE,
      'weight' => 6,
    ],
    'help_text' => ts('app_id and app_code required in the api_key - ie your key
    should look like:
      {"app_id" : "xyz", "app_code" : "klm"}
    '),
    'metadata' => [
      'argument' => ['api_key.app_id', 'api_key.app_code'],
      'required_config_fields' => ['api_key'],
      'required_api_key_subkeys' => ['app_id', 'app_code'],
      'is_enabled_on_install' => FALSE,
    ],
  ]
];
