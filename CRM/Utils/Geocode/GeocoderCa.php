<?php
/*
 +--------------------------------------------------------------------+
 | Copyright CiviCRM LLC. All rights reserved.                        |
 |                                                                    |
 | This work is published under the GNU AGPLv3 license with some      |
 | permitted exceptions and without any warranty. For full license    |
 | and copyright information, see https://civicrm.org/licensing       |
 +--------------------------------------------------------------------+
 */

/**
 *
 * @package CRM
 * @copyright CiviCRM LLC https://civicrm.org/licensing
 */

/**
 * Class that uses Geocoder.ca geocoder
 *
 * @deprecated - this is not a supported way to add geocoders to this extension.
 * * Add files to ProviderMetadata.
 */
class CRM_Utils_Geocode_GeocoderCa {

  /**
   * Server to retrieve the lat/long
   *
   * @var string
   */
  static protected $_server = 'geocoder.ca';

  public static function getMapper() {
    return [
      'country' => 'country',
      'city' => 'city',
      'postal' => 'postal_code',
      'staddress' => 'street_address',
    ];
  }

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
    // we need a valid country, else we ignore
    if (empty($values['country'])) {
      return FALSE;
    }

    $add = [];


    $city = $values['city'] ?? NULL;

    if (!empty($values['state_province']) || (!empty($values['state_province_id']) && $values['state_province_id'] != 'null')) {
      if (!empty($values['state_province_id'])) {
        $stateProvince = CRM_Core_DAO::getFieldValue('CRM_Core_DAO_StateProvince', $values['state_province_id']) ?? '';
      }
      else {
        if (!$stateName) {
          $stateProvince = CRM_Core_DAO::getFieldValue('CRM_Core_DAO_StateProvince',
            $values['state_province'],
            'name',
            'abbreviation'
          ) ?? '';
        }
        else {
          $stateProvince = $values['state_province'] ?? '';
        }
      }

      // dont add state twice if replicated in city (happens in NZ and other countries, CRM-2632)
      if ($stateProvince != $city) {
        $add[] = 'state=' . urlencode(str_replace('', '+', $stateProvince));
      }
    }
    foreach (self::getMapper() as $key => $addressFieldName) {
      if (!empty($values[$addressFieldName])) {
        $add[] = $key . '=' . urlencode(str_replace('', '+', $values[$addressFieldName]));
      }
    }

    $add = implode('&', $add);

    $coord = self::makeRequest($add);

    $values['geo_code_1'] = $coord['geo_code_1'] ?? 'null';
    $values['geo_code_2'] = $coord['geo_code_2'] ?? 'null';

    if (isset($coord['geo_code_error'])) {
      $values['geo_code_error'] = $coord['geo_code_error'];
    }

    $geoCoder = array_pop(explode('_', __CLASS__));
    CRM_Utils_Hook::geocoderFormat($geoCoder, $values, $coord['request_xml']);

    return isset($coord['geo_code_1'], $coord['geo_code_2']);
  }

  /**
   * @param string $address
   *   Plain text address
   * @return array
   * @throws \GuzzleHttp\Exception\GuzzleException
   */
  public static function getCoordinates($address) {
    return self::makeRequest(urlencode($address));
  }

  /**
   * @param string $add
   *   Url-encoded address
   *
   * @return array
   *   An array of values with the following possible keys:
   *     geo_code_error: String error message
   *     geo_code_1: Float latitude
   *     geo_code_2: Float longitude
   *     request_xml: SimpleXMLElement parsed xml from geocoding API
   *
   * @throws \GuzzleHttp\Exception\GuzzleException
   */
  private static function makeRequest($add) {
    $coords = [];
    $config = CRM_Core_Config::singleton();
    if (!empty($config->geoAPIKey)) {
      $add .= '?geoit=XML&auth=' . urlencode($config->geoAPIKey);
    }

    $query = 'https://' . self::$_server . '/' . $add;
    $client = new GuzzleHttp\Client();
    $request = $client->request('GET', $query, ['timeout' => \Civi::settings()->get('http_timeout')]);
    $string = $request->getBody();

    libxml_use_internal_errors(TRUE);
    $xml = @simplexml_load_string($string);
    $coords['request_xml'] = $xml;
    if (isset($xml->error)) {
      $string = sprintf('Error %s: %s', $xml->error->code, $xml->error->description);
      \Civi::log()->error('Geocoding failed.  Message from Geocoder.ca: ' . $string);
      $coords['geo_code_error'] = $string;
    }
    if (isset($xml->latt) && isset($xml->longt)) {
      $coords['geo_code_1'] = (float) $xml->latt;
      $coords['geo_code_2'] = (float) $xml->longt;
    }

    return $coords;
  }

}
