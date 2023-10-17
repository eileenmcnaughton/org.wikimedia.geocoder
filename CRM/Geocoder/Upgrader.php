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
  * This function should be called by CRM_Extension_Upgrader_Base::onPostInstall
  * in order to be able to work on managed entities
  * only during install (and not during upgrade)
  */
  public function postInstall() {
  $this->metadataEnableOnPostInstall();
  
  }
  /**
  * Some providers are not enabled through the managed entities,
  * but uses metadata to require activation on install.
  */
  protected function metadataEnableOnPostInstall()
  {
    $geoCoders = [];
    geocoder_civicrm_geo_managed($geoCoders); //defined in geocoder.php
    /* we only need to enable geocoders that are not marked as active but that are enabled on install*/

    $geoCodersToEnableOnPostInstall=array_filter($geoCoders,function($geocoder){
      return
      static::managedEntityHasName($geocoder)
      && static::managedEntityIsEnabledOnInstall($geocoder)
      && static::managedEntityIsNotActive($geocoder)
      ;
    
    });

    $geoCodersNames=array_map(function($geocoder){
    return $geocoder['name'];
    },$geoCodersToEnableOnPostInstall);
    array_walk($geoCodersNames,function($geoCoderName){
    static::activateGeocoder($geoCoderName);
    });
      
  }
  

  /**
  * Activate a geocoder by its name
  * @param $geoCoderName string name of the GeoCoder to activate
  * @return void
  * @throws \CiviCRM_API3_Exception
  */
  protected static function activateGeocoder($geoCoderName)
  {
  /*api3 does error checking itself, so there is less need to do checking*/
     $geoCoderId=civicrm_api3('Geocoder','getvalue',['return'=>"id",'name'=>$geoCoderName]);
     civicrm_api3('Geocoder','create',['id'=>$geoCoderId,'is_active'=>1]);
  }

  /**
  * Check whether a geocoder has a name
  * according to a managed entity definition
  * @param $geocoder array Array corresponding to a geocoder managed entity (as per geocoder_civicrm_geo_managed)
  * @return bool
  */
  
  protected static function managedEntityHasName($geocoder)
  {
    return
      is_array($geocoder) && array_key_exists('name',$geocoder)
      && is_string($geocoder['name']);
  }
  
  /**
  * Check whether a geocoder should be enabled on install or not,
  * according to a managed entity definition
  * @param $geocoder array Array corresponding to a geocoder managed entity (as per geocoder_civicrm_geo_managed)
  * @return bool
  */
  protected static function managedEntityIsEnabledOnInstall($geocoder)
  {
     return
      is_array($geocoder) && array_key_exists('metadata',$geocoder)
      && is_array($geocoder['metadata']) && array_key_exists('is_enabled_on_install',$geocoder['metadata'])
      && $geocoder['metadata']['is_enabled_on_install']===true;
  }
  
  /**
  * Check whether a geocoder should be active or not,
  * according to a manged entity definition
  * @param $geocoder array Array corresponding to a geocoder managed entity (as per geocoder_civicrm_geo_managed)
  * @return bool : return true if the geocoder should not be active ; false otherwise
  */
  protected static function managedEntityIsNotActive($geocoder)
  {
     return
     is_array($geocoder) && array_key_exists('params',$geocoder)
     && is_array($geocoder['params']) && array_key_exists('is_active',$geocoder['params'])
     && $geocoder['params']['is_active']===false;
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

  /**
   * Example: Run a simple query when a module is enabled.
   *
  public function enable() {
    CRM_Core_DAO::executeQuery('UPDATE foo SET is_active = 1 WHERE bar = "whiz"');
  }

  /**
   * Example: Run a simple query when a module is disabled.
   *
  public function disable() {
    CRM_Core_DAO::executeQuery('UPDATE foo SET is_active = 0 WHERE bar = "whiz"');
  }

  /**
   * Example: Run a couple simple queries.
   *
   * @return TRUE on success
   * @throws Exception
   *
  public function upgrade_4200() {
    $this->ctx->log->info('Applying update 4200');
    CRM_Core_DAO::executeQuery('UPDATE foo SET bar = "whiz"');
    CRM_Core_DAO::executeQuery('DELETE FROM bang WHERE willy = wonka(2)');
    return TRUE;
  } // */


  /**
   * Example: Run an external SQL script.
   *
   * @return TRUE on success
   * @throws Exception
  public function upgrade_4201() {
    $this->ctx->log->info('Applying update 4201');
    // this path is relative to the extension base dir
    $this->executeSqlFile('sql/upgrade_4201.sql');
    return TRUE;
  } // */


  /**
   * Example: Run a slow upgrade process by breaking it up into smaller chunk.
   *
   * @return TRUE on success
   * @throws Exception
  public function upgrade_4202() {
    $this->ctx->log->info('Planning update 4202'); // PEAR Log interface

    $this->addTask(E::ts('Process first step'), 'processPart1', $arg1, $arg2);
    $this->addTask(E::ts('Process second step'), 'processPart2', $arg3, $arg4);
    $this->addTask(E::ts('Process second step'), 'processPart3', $arg5);
    return TRUE;
  }
  public function processPart1($arg1, $arg2) { sleep(10); return TRUE; }
  public function processPart2($arg3, $arg4) { sleep(10); return TRUE; }
  public function processPart3($arg5) { sleep(10); return TRUE; }
  // */


  /**
   * Example: Run an upgrade with a query that touches many (potentially
   * millions) of records by breaking it up into smaller chunks.
   *
   * @return TRUE on success
   * @throws Exception
  public function upgrade_4203() {
    $this->ctx->log->info('Planning update 4203'); // PEAR Log interface

    $minId = CRM_Core_DAO::singleValueQuery('SELECT coalesce(min(id),0) FROM civicrm_contribution');
    $maxId = CRM_Core_DAO::singleValueQuery('SELECT coalesce(max(id),0) FROM civicrm_contribution');
    for ($startId = $minId; $startId <= $maxId; $startId += self::BATCH_SIZE) {
      $endId = $startId + self::BATCH_SIZE - 1;
      $title = E::ts('Upgrade Batch (%1 => %2)', array(
        1 => $startId,
        2 => $endId,
      ));
      $sql = '
        UPDATE civicrm_contribution SET foobar = whiz(wonky()+wanker)
        WHERE id BETWEEN %1 and %2
      ';
      $params = array(
        1 => array($startId, 'Integer'),
        2 => array($endId, 'Integer'),
      );
      $this->addTask($title, 'executeSql', $sql, $params);
    }
    return TRUE;
  } // */

}
