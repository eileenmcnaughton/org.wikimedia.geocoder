<?php
/*
  +--------------------------------------------------------------------+
  | CiviCRM version 4.7                                                |
  +--------------------------------------------------------------------+
  | Copyright CiviCRM LLC (c) 2004-2017                                |
  +--------------------------------------------------------------------+
  | This file is a part of CiviCRM.                                    |
  |                                                                    |
  | CiviCRM is free software; you can copy, modify, and distribute it  |
  | under the terms of the GNU Affero General Public License           |
  | Version 3, 19 November 2007 and the CiviCRM Licensing Exception.   |
  |                                                                    |
  | CiviCRM is distributed in the hope that it will be useful, but     |
  | WITHOUT ANY WARRANTY; without even the implied warranty of         |
  | MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.               |
  | See the GNU Affero General Public License for more details.        |
  |                                                                    |
  | You should have received a copy of the GNU Affero General Public   |
  | License and the CiviCRM Licensing Exception along                  |
  | with this program; if not, contact CiviCRM LLC                     |
  | at info[AT]civicrm[DOT]org. If you have questions about the        |
  | GNU Affero General Public License or the licensing of CiviCRM,     |
  | see the CiviCRM license FAQ at http://civicrm.org/licensing        |
  +--------------------------------------------------------------------+
 */

use Geocoder\Query\GeocodeQuery;
use Geocoder\Query\ReverseQuery;

/**
 *
 * @package CRM
 * @copyright CiviCRM LLC (c) 2004-2017
 */

/**
 * Geocoder class.
 */
class CRM_Utils_Geocode_Geocoder {

  /**
   * @var \Http\Adapter\Guzzle6\Client
   */
  protected static $client;

  /**
   * Function that takes an address object and gets the latitude / longitude for this
   * address. Note that at a later stage, we could make this function also clean up
   * the address into a more valid format
   *
   * @param array $values
   * @param bool $stateName
   *
   * @return bool
   *   true if we modified the address, false otherwise
   */
  public static function format(&$values, $stateName = FALSE) {
    if (!self::$client) {
      self::$client = new \Http\Adapter\Guzzle6\Client();
    }

    $geocoders = civicrm_api3('Geocoder', 'get', [
      'sequential' => 1,
      'is_active' => 1,
      'options' => ['sort' => 'weight']
    ]);
    // @todo GeoCoder library permits a fallback cascade - do that.
    // (based on is_active + weight)
    foreach ($geocoders['values'] as $geocoder) {
      if (self::isUsable($geocoder)) {
        break;
      }
      unset($geocoder);
    }
    if (!$geocoder) {
      self::setMessage(ts('No usable geocoding providers'));
      return;
    }

    $classString = '\\Geocoder\\Provider\\' .  $geocoder['class'];
    try {
      // @todo just guessing what to pass - define in the metadata!
      $provider = new $classString(self::$client, CRM_Utils_Array::value('url', $geocoder, CRM_Utils_Array::value('api_key', $geocoder)));
      //$provider = new Geocoder\Provider\Mapquest\Mapquest()


      $geocoderObj = new \Geocoder\StatefulGeocoder($provider, 'en');

      $addressValues  = self::getAddressValuesArray($values, $geocoder);

      foreach (['county', 'state_province', 'country'] as $locationField) {
        if (empty($addressValues[$locationField]) && !empty($addressValues[$locationField . '_id'])) {
          $addressValues[$locationField] = CRM_Core_PseudoConstant::getLabel(
            'CRM_Core_BAO_Address',
            $locationField . '_id',
            $values[$locationField . '_id']
          );
          unset($addressValues[$locationField . '_id']);
        }
      }

      $result = $geocoderObj->geocodeQuery(GeocodeQuery::create(implode(',', $addressValues)));
      $values['geo_code_1'] = $result->first()->getCoordinates()->getLatitude();
      $values['geo_code_2'] = $result->first()
        ->getCoordinates()
        ->getLongitude();
    }
    catch (Geocoder\Exception\CollectionIsEmpty $e) {
      $values['geo_code_1'] = 'null';
      $values['geo_code_2'] = 'null';
      if (CRM_Core_Permission::check('access CiviCRM')) {
        CRM_Core_Session::setStatus(ts('Failed to geocode address, no co-ordinates saved'));
      }
    }
    catch (Geocoder\Exception\QuotaExceeded $e) {

      if (CRM_Core_Permission::check('access CiviCRM')) {
        CRM_Core_Session::setStatus(ts('Geocoder quota exceeded. No further geocoding attempts will be made for %1 seconds', array($geocoder['threshold_standdown'], 'int')));
      }
      civicrm_api3('Geocoder', 'create', ['id' => $geocoder['id'], 'threshold_last_hit' => 'now']);
    }
    catch (Exception $e) {
      self::setMessage(ts('Unknown geocoding error :') . $e->getMessage());
    }
  }

  /**
   * Check if the geocoder is usable.
   *
   * @param string $geocoder
   *
   * @return bool
   */
  public static function isUsable($geocoder) {
    if ($geocoder['threshold_last_hit'] === '0000-00-00 00:00:00' || empty($geocoder['threshold_standdown'])) {
      return TRUE;
    }
    $standDownEnds = strtotime('+ ' . $geocoder['threshold_standdown'] . ' seconds', strtotime($geocoder['threshold_last_hit']));
    if ($standDownEnds <= strtotime('now')) {
      return TRUE;
    }

    return FALSE;
  }

  /**
   * Set a message if the geocoding failed.
   *
   * @param string $message
   */
  public static function  setMessage($message) {
    if (CRM_Core_Permission::check('access CiviCRM')) {
      CRM_Core_Session::setStatus($message);
    }
  }

  /**
   * Get address values with additional fields fetched & irrelevant filtered.
   *
   * @param array $values
   * @param array $geocoder
   *
   * @return array
   */
  protected static function getAddressValuesArray($values, $geocoder) {
    $addressFields = [
      'street_address',
      'supplemental_address_1',
      'supplemental_address_2',
      'supplemental_address_2',
      'city',
      'postal_code',
      'county_id',
      'state_province_id',
      'country_id',
    ];

    if (!empty($values['id'])) {
      if (isset($geocoder['required_fields'])) {
        $requiredFields = array_fill_keys(json_decode($geocoder['required_fields'], TRUE), 1);
      }
      else {
        $requiredFields = array_fill_keys($addressFields, 1);
      }
      $missingFields = array_diff_key($requiredFields, $values);
      if (!empty($missingFields)) {
        $existingAddress = civicrm_api3('Address', 'getsingle', [
          'id' => $values['id'],
          'return' => array_keys($missingFields)
        ]);
      }
      $addressValues = array_merge($existingAddress, $values);
    }

    // This merge will do an ordering for us.
    $addressValues = array_merge(array_fill_keys($addressFields, NULL), $addressValues);
    // filter out unrelated keys
    $keysToRetain = array_fill_keys($addressFields, 1);
    unset($keysToRetain['country_id'], $keysToRetain['state_province_id'], $keysToRetain['county_id']);
    $keysToRetain['country'] = $keysToRetain['state_province_id'] = $keysToRetain['county'];
    $addressValues = array_intersect_key($addressValues, $keysToRetain);
    // filter out empty values.
    $addressValues = array_filter($addressValues);

    return $addressValues;
  }

}
