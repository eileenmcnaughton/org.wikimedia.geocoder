<?php

/**
 * DAOs provide an OOP-style facade for reading and writing database records.
 *
 * DAOs are a primary source for metadata in older versions of CiviCRM (<5.74)
 * and are required for some subsystems (such as APIv3).
 *
 * This stub provides compatibility. It is not intended to be modified in a
 * substantive way. Property annotations may be added, but are not required.
 * @property string $id
 * @property string $name
 * @property string $title
 * @property string $class
 * @property bool|string $is_active
 * @property string $weight
 * @property string $api_key
 * @property string $url
 * @property string $required_fields
 * @property string $retained_response_fields
 * @property string $datafill_response_fields
 * @property int|string $threshold_standdown
 * @property string $threshold_last_hit
 * @property string $valid_countries
 */
class CRM_Geocoder_DAO_Geocoder extends CRM_Geocoder_DAO_Base {

  /**
   * Required by older versions of CiviCRM (<5.74).
   * @var string
   */
  public static $_tableName = 'civicrm_geocoder';

}
