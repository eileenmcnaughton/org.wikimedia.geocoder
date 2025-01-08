<?php
use CRM_Geocoder_ExtensionUtil as E;
return [
  [
    'name' => 'Navigation_afsearchGeocoders',
    'entity' => 'Navigation',
    'cleanup' => 'unused',
    'update' => 'unmodified',
    'params' => [
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
    ],
  ],
];
