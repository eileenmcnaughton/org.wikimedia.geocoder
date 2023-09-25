<?php

require_once 'geocoder.civix.php';

// checking if the file exists allows compilation elsewhere if desired.
if (file_exists(__DIR__ . '/vendor/autoload.php')) {
  require_once __DIR__ . '/vendor/autoload.php';
}

use CRM_Geocoder_ExtensionUtil as E;

/**
 * Implements hook_civicrm_config().
 *
 * @link http://wiki.civicrm.org/confluence/display/CRMDOC/hook_civicrm_config
 */
function geocoder_civicrm_config(&$config) {
  _geocoder_civix_civicrm_config($config);
}

/**
 * Implements hook_civicrm_install().
 *
 * @link http://wiki.civicrm.org/confluence/display/CRMDOC/hook_civicrm_install
 */
function geocoder_civicrm_install() {
  _geocoder_civix_civicrm_install();
}

/**
 * Implements hook_civicrm_enable().
 *
 * @link http://wiki.civicrm.org/confluence/display/CRMDOC/hook_civicrm_enable
 */
function geocoder_civicrm_enable() {
  _geocoder_civix_civicrm_enable();
}

/**
 * Load our metadata files (we have extra metadata in them that we use).
 *
 * @param array $entities
 */
function geocoder_civicrm_geo_managed(&$entities) {
  if (!isset(Civi::$statics['geocoder_civicrm_geo_managed']['entities'])) {
    $managedFiles = CRM_Utils_File::findFiles(__DIR__, '*.mgd.php');
    $geocoders = [];
    foreach ($managedFiles as $file) {
      $es = include $file;
      foreach ($es as $e) {
        if (empty($e['module'])) {
          $e['module'] = E::LONG_NAME;
        }
        if (empty($e['params']['version'])) {
          $e['params']['version'] = '3';
        }
        $geocoders[] = $e;
      }
    }
    Civi::$statics['geocoder_civicrm_geo_managed']['entities'] = $geocoders;
  }
  $entities = Civi::$statics['geocoder_civicrm_geo_managed']['entities'];
}

/**
 * Make sure all our geocoders are in managed.
 *
 * Historically they weren't because the managed system didn't
 * support 'update' => 'never' and kept enabling / reverting them.
 * But now, for a few releases, we will hold a routine to help convert.
 *
 * @link http://wiki.civicrm.org/confluence/display/CRMDOC/hook_civicrm_managed
 */
function geocoder_civicrm_managed(&$entities) {
  if (!empty(Civi::$statics['geocoder_civicrm_managed']['check'])) {
    return;
  }
  $geocoders = CRM_Core_DAO::executeQuery('SELECT id, name FROM civicrm_geocoder')->fetchAll();
  $ids = [];
  foreach ($geocoders as $geocoder) {
    $ids[$geocoder['name']] = $geocoder['id'];
  }
  if (!empty($ids)) {
    $managedEntities = CRM_Core_DAO::executeQuery("SELECT name FROM civicrm_managed WHERE module = 'org.wikimedia.geocoder' AND entity_type = 'Geocoder' AND entity_id IN (" . implode(',', $ids) . ')')->fetchAll();
    foreach ($managedEntities as $managedEntity) {
      if (isset($ids[$managedEntity['name']])) {
        unset($ids[$managedEntity['name']]);
      }
    }
    foreach ($ids as $name => $id) {
      CRM_Core_DAO::executeQuery("INSERT INTO civicrm_managed (module, name, entity_type, entity_id) VALUES('org.wikimedia.geocoder', '$name', 'Geocoder', $id)");
    }
  }
  Civi::$statics['geocoder_civicrm_managed']['check'] = TRUE;

}

/**
 * Implements hook_civicrm_entityTypes.
 *
 * @link https://docs.civicrm.org/dev/en/latest/hooks/hook_civicrm_entityTypes
 *
 * @param array $entityTypes
 *   Registered entity types.
 */
function geocoder_civicrm_entityTypes(&$entityTypes) {
  $entityTypes['CRM_Geocoder_DAO_Geocoder'] = [
    'name' => 'Geocoder',
    'class' => 'CRM_Geocoder_DAO_Geocoder',
    'table' => 'civicrm_geocoder',
  ];
}

/**
 * Implements hook_alterLogTables().
 *
 * @param array $logTableSpec
 */
function geocoder_civicrm_alterLogTables(&$logTableSpec) {
  $staticDataTables = ['civicrm_geocoder_zip_dataset', '`civicrm_geonames_lookup'];
  foreach ($staticDataTables as $staticDataTable) {
    if (isset($logTableSpec[$staticDataTable])) {
      unset($logTableSpec[$staticDataTable]);
    }
  }
}
