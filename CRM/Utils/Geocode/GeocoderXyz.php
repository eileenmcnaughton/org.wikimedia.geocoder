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
 * Class that uses Geocoder.xyz geocoder
 */
class CRM_Utils_Geocode_GeocoderXyz extends CRM_Utils_Geocode_GeocoderCa {

  /**
   * Server to retrieve the lat/long
   *
   * @var string
   */
  static protected $_server = 'geocode.xyz';

  public static function getMapper() {
   return [
      'region' => 'country',
      'cityname' => 'city',
      'postal' => 'postal_code',
      'streetname' => 'street_address',
    ];
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
      \Civi::log()->error('Geocoding failed.  Message from Geocode.xyz: ' . $string);
      $coords['geo_code_error'] = $string;
    }
    if (isset($xml->latt) && isset($xml->longt)) {
      $coords['geo_code_1'] = (float) $xml->latt;
      $coords['geo_code_2'] = (float) $xml->longt;
    }

    return $coords;
  }

}
