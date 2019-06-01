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
    'params' => [
      'version' => 3,
      'name' => 'here',
      'title' => 'Here',
      'class' => 'Here\Here',
      'retained_response_fields' => ['geo_code_1', 'geo_code_2'],
      'parameters' => '{ "appId" : "Your appId", "appCode" : "Your appCode" }',
    ],
    'help_text' => ts('app_id and app_code required '),
    'metadata' => [
      'required_config_fields' => ['parameters'],
      'is_enabled_on_install' => FALSE,
    ],
  ]
];
