<?php
use CRM_Geocoder_ExtensionUtil as E;
return [
  'type' => 'search',
  'title' => E::ts('Geocoders'),
  'icon' => 'fa-list-alt',
  'server_route' => 'civicrm/admin/geocoders',
  'permission' => [
    'administer CiviCRM system',
  ],
  'search_displays' => [
    'Geocoders.Geocoders_Table_1',
  ],
];
