<?php
use CRM_Geocoder_ExtensionUtil as E;

$params = [
  'version' => 4,
  'values' => [
    'label' => E::ts('Geocoders'),
    'name' => 'afsearchGeocoders',
    'url' => 'civicrm/admin/geocoders',
    'icon' => 'crm-i fa-list-alt',
    'permission' => [
      'administer CiviCRM system',
    ],
    'permission_operator' => 'AND',
    'parent_id.name' => 'Localization',
    'weight' => 1,
  ],
  'match' => ['name', 'domain_id'],
];
$navigationItems = [
  [
    'name' => 'Navigation_afsearchGeocoders',
    'entity' => 'Navigation',
    'cleanup' => 'unused',
    'update' => 'unmodified',
    'params' => $params,
  ],
];
$params['values']['parent_id.name'] = 'System Settings';
$params['values']['weight'] = 10;
$params['values']['name'] .= '_system_settings';

$navigationItems = [
  [
    'name' => 'Navigation_afsearchGeocoders_system_settings',
    'entity' => 'Navigation',
    'cleanup' => 'unused',
    'update' => 'unmodified',
    'params' => $params,
  ],
];
return $navigationItems;
