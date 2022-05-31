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
 * Implements hook_civicrm_postInstall().
 *
 * @link http://wiki.civicrm.org/confluence/display/CRMDOC/hook_civicrm_postInstall
 */
function geocoder_civicrm_postInstall() {
  _geocoder_civix_civicrm_postInstall();
}

/**
 * Implements hook_civicrm_uninstall().
 *
 * @link http://wiki.civicrm.org/confluence/display/CRMDOC/hook_civicrm_uninstall
 */
function geocoder_civicrm_uninstall() {
  _geocoder_civix_civicrm_uninstall();
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
 * Implements hook_civicrm_disable().
 *
 * @link http://wiki.civicrm.org/confluence/display/CRMDOC/hook_civicrm_disable
 */
function geocoder_civicrm_disable() {
  _geocoder_civix_civicrm_disable();
}

/**
 * Implements hook_civicrm_upgrade().
 *
 * @link http://wiki.civicrm.org/confluence/display/CRMDOC/hook_civicrm_upgrade
 */
function geocoder_civicrm_upgrade($op, CRM_Queue_Queue $queue = NULL) {
  return _geocoder_civix_civicrm_upgrade($op, $queue);
}

/**
 * Generate a list of geocoders to manage.
 *
 * In order to avoid having them installed when we do not wish we alter the
 * file names & manage ourselves.
 *
 * @link http://wiki.civicrm.org/confluence/display/CRMDOC/hook_civicrm_managed
 */
function geocoder_civicrm_geo_managed(&$entities) {
  $mgdFiles = _geocoder_civix_find_files(__DIR__, '*.mgd.geo.php');
  foreach ($mgdFiles as $file) {
    $es = include $file;
    foreach ($es as $e) {
      if (empty($e['module'])) {
        $e['module'] = E::LONG_NAME;
      }
      if (empty($e['params']['version'])) {
        $e['params']['version'] = '3';
      }
      $entities[] = $e;
    }
  }
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

// --- Functions below this ship commented out. Uncomment as required. ---

/**
 * Implements hook_civicrm_preProcess().
 *
 * @link http://wiki.civicrm.org/confluence/display/CRMDOC/hook_civicrm_preProcess
 *

 // */

/**
 * Implements hook_civicrm_navigationMenu().
 *
 * @link http://wiki.civicrm.org/confluence/display/CRMDOC/hook_civicrm_navigationMenu
 *
function geocoder_civicrm_navigationMenu(&$menu) {
  _geocoder_civix_insert_navigation_menu($menu, NULL, array(
    'label' => E::ts('The Page'),
    'name' => 'the_page',
    'url' => 'civicrm/the-page',
    'permission' => 'access CiviReport,access CiviContribute',
    'operator' => 'OR',
    'separator' => 0,
  ));
  _geocoder_civix_navigationMenu($menu);
} // */
