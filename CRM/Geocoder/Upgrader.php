<?php
use CRM_Geocoder_ExtensionUtil as E;

/**
 * Collection of upgrade steps.
 */
class CRM_Geocoder_Upgrader extends CRM_Extension_Upgrader_Base {

  // By convention, functions that look like "function upgrade_NNNN()" are
  // upgrade tasks. They are executed in order (like Drupal's hook_update_N).

  /**
   * Example: Run an external SQL script when the module is installed.
   */
  public function install() {
    $this->executeSqlFile('sql/install.sql');
    $this->executeSqlFile('sql/install_zip_data_set.sql');
    civicrm_api3('Setting', 'create', ['geoProvider' => 'Geocoder']);
  }

  /**
   *  Add parameter column in DB
   *
   * @throws \CiviCRM_API3_Exception
   */
  public function upgrade_1100() {
    $this->ctx->log->info('Applying update 1100');
    $this->update_providers();
    return TRUE;
  }

	/**
	 *  Update the URL for Open Street Map end point
	 *
	 * @throws \CiviCRM_API3_Exception
	 */
	public function upgrade_1200() {
		$this->ctx->log->info( 'Applying update 1200: Update the URL for Open Street Map end point' );
		CRM_Core_DAO::executeQuery( "UPDATE `civicrm_geocoder` SET `url` = 'https://nominatim.openstreetmap.org' WHERE `url` LIKE '%nominatim.openstreetmap.org%'" );

		return TRUE;
	}

  /**
   * Add new providers.
   *
   * @return bool
   *
   * @throws \CiviCRM_API3_Exception
   */
  private function update_providers() {
    $geoCoders = [];
    geocoder_civicrm_geo_managed($geoCoders);

    // get already defined providers
    $defined = civicrm_api3('Geocoder', 'get', ['sequential' => 1, 'return' => ['name', 'weight'], 'options' => ['sort' => 'weight']])['values'];

    // get max weight value
    $max_weight = (int) $defined[count($defined) - 1]['weight'];
    foreach ($geoCoders as $geoCoder) {
      $is_defined = array_search($geoCoder['name'], array_column($defined, 'name'), TRUE);
      if (($is_defined === FALSE)) {
        $params = ['is_active' => $geoCoder['metadata']['is_enabled_on_install'], 'weight' => ++$max_weight];
        civicrm_api3('Geocoder', 'create', array_merge($params, $geoCoder['params']));
      }

    }

    return TRUE;
  }

  /**
   * Example: Run an external SQL script when the module is uninstalled.
   */
  public function uninstall() {
   $this->executeSqlFile('sql/uninstall.sql');
    civicrm_api3('Setting', 'create', ['geoProvider' => 'null']);
  }

}
