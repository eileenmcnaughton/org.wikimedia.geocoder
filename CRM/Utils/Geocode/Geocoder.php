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

    // @todo these 2 vars need to be retrieved from settings
    $url = 'https://nominatim.openstreetmap.org/search';
    $provider = new Geocoder\Provider\Nominatim\Nominatim(self::$client, $url);


    $geocoder = new \Geocoder\StatefulGeocoder($provider, 'en');
    foreach (['county', 'state_province', 'country'] as $locationField) {
      if (empty($values[$locationField]) && !empty($values[$locationField . '_id'])) {
        $values[$locationField] = CRM_Core_PseudoConstant::getLabel(
          'CRM_Core_BAO_Address',
          $locationField . '_id',
          $values[$locationField . '_id']
        );
      }
    }
    $addressFields = [
      'street_address',
      'supplemental_address_1',
      'supplemental_address_2',
      'supplemental_address_2',
      'city',
      'postal_code',
      'county',
      'state_province',
      'country',
    ];
    $addressValues = array_intersect_key($values, array_fill_keys($addressFields, 1));
    $addressValues = array_filter($addressValues);
    try {
      $result = $geocoder->geocodeQuery(GeocodeQuery::create(implode(',', $addressValues)));
      $values['geo_code_1'] = $result->first()->getCoordinates()->getLatitude();
      $values['geo_code_2'] = $result->first()
        ->getCoordinates()
        ->getLongitude();
    }
    catch (Geocoder\Exception\CollectionIsEmpty $e) {
      if (CRM_Core_Permission::check('access CiviCRM')) {
        CRM_Core_Session::setStatus(ts('Failed to geocode address, no co-ordinates saved'));
      }
    }
  }

}
