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

use Civi\Api4\StateProvince;
use Geocoder\Query\GeocodeQuery;
use Geocoder\Model\AddressCollection;
use CRM_Geocoder_ExtensionUtil as E;
use Http\Discovery\Psr18Client as Client;
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
   * @var \Http\Discovery\Psr18Client
   */
  protected static $client;

  /**
   * Get client.
   *
   * @return Http\Discovery\Psr18Client
   */
  public static function getClient() {
    return self::$client;
  }

  /**
   * Set client.
   *
   * @param Http\Discovery\Psr18Client
   */
  public static function setClient($client) {
    self::$client = $client;
  }

  protected static $geoCoders;

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
   * @throws \CRM_Core_Exception
   * @throws \Geocoder\Exception\Exception
   */
  public static function format(&$values, $stateName = FALSE) {
    if (!self::getClient()) {
      self::setClient(new Client());
    }
    self::setGeocoders();
    // AFAIK only 2 char string accepted - from the examples.
    $locale = substr(CRM_Utils_System::getUFLocale() ?? '', 0, 2);
    $messageOnFail = NULL;

    foreach (self::$geoCoders as $geocoder) {
      if (!self::isUsable($geocoder)) {
        continue;
      }
      try {
        self::fillMissingAddressData($values, $geocoder);
        self::padPostalCodeIfRequired($values);
        if (!self::hasRequiredFieldsForGeocoder($values, $geocoder)) {
          continue;
        }
        if (!empty($geocoder['valid_countries']) && !empty($values['country_id'])) {
          if (!in_array($values['country_id'], $geocoder['valid_countries'])) {
            continue;
          }
        }
        $geocodableAddress = self::getGeocodableAddress($values, $geocoder);
        if (empty($geocodableAddress)) {
          continue;
        }

        $provider = self::getProviderClass($geocoder);

        $geocoderObj = new \Geocoder\StatefulGeocoder($provider, $locale);
        $result = $geocoderObj->geocodeQuery(GeocodeQuery::create($geocodableAddress));

        foreach (json_decode($geocoder['retained_response_fields'], TRUE) as $fieldName) {
          $values[$fieldName] = self::getValueFromResult($fieldName, $result, $values);
        }
        if (!empty($geocoder['datafill_response_fields'])) {
          foreach (json_decode($geocoder['datafill_response_fields'], TRUE) as $fieldName) {
            if (empty($values[$fieldName]) || $values[$fieldName] === 'null') {
              $filledValue = self::getValueFromResult($fieldName, $result, $values);
              // Do not overwrite fill fields if value not found.
              if ($filledValue) {
                $values[$fieldName] = $filledValue;
              }
            }
          }
        }

      return TRUE;
    }
    catch (Geocoder\Exception\CollectionIsEmpty $e) {
        $values['geo_code_1'] = 'null';
        $values['geo_code_2'] = 'null';
        $messageOnFail = ts('Failed to geocode address, no co-ordinates saved');
        continue;
      }
      catch (Geocoder\Exception\QuotaExceeded $e) {

        if (CRM_Core_Permission::check('access CiviCRM')) {
          CRM_Core_Session::setStatus(ts('Geocoder quota exceeded. No further geocoding attempts will be made for %1 seconds', array(
            $geocoder['threshold_standdown'],
            'int'
          )));
        }
        civicrm_api3('Geocoder', 'create', [
          'id' => $geocoder['id'],
          'threshold_last_hit' => 'now'
        ]);
        // Unset it so we reload next instance & recheck properly.
        self::$geoCoders = NULL;
        continue;
      }
      catch (Exception $e) {
        $messageOnFail = ts('Unknown geocoding error on :') . $geocoder['title'] . ":" . $e->getMessage();
        continue;
      }
    }

    // We went threw all the geocoders & couldn't geocode the address.
    // A message might be a bit aggressive if only geocoding some countries!
    if ($messageOnFail) {
      self::setMessage($messageOnFail);
    }
    if (!empty($values['id']) && empty($values['manual_geocode'])
    ) {
      // Could not geocode edited address, set to null.
      // An argument could be made to check whether 'material' fields are
      // changed, but that is kinda hard to define & adds extra lookups.
      $values['geo_code_1'] = 'null';
      $values['geo_code_2'] = 'null';
      $values['timezone'] = 'null';
    }
    return FALSE;
  }

  /**
   * Check if the geocoder is usable.
   *
   * @param array $geocoder
   *
   * @return bool
   */
  public static function isUsable($geocoder) {
    if (empty($geocoder['threshold_last_hit']) || $geocoder['threshold_last_hit'] === '0000-00-00 00:00:00' || empty($geocoder['threshold_standdown'])) {
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
  public static function setMessage($message) {
    if (CRM_Core_Permission::check('access CiviCRM')) {
      CRM_Core_Session::setStatus($message);
    }
  }

  /**
   * Get address values with additional fields fetched & irrelevant filtered.
   *
   * @param array $addressValues
   * @param array $geocoder
   *
   * @return array
   */
  protected static function getAddressValuesArray($addressValues, $geocoder) {
    $addressFields = array_keys(self::getAddressFields());

    // This merge will do an ordering for us.
    $addressValues = array_merge(array_fill_keys($addressFields, NULL), $addressValues);
    // filter out unrelated keys
    $keysToRetain = array_fill_keys($addressFields, 1);
    unset($keysToRetain['country_id'], $keysToRetain['state_province_id'], $keysToRetain['county_id']);
    $keysToRetain['country'] = $keysToRetain['state_province_id'] = $keysToRetain['county'];
    $addressValues = array_intersect_key($addressValues, $keysToRetain);

    return $addressValues;
  }

  /**
   * Retrieve additional data if we are dealing with an update that may be incomplete.
   *
   * @param array $inputValues
   * @param array $geocoder
   *
   * @throws \CRM_Core_Exception
   */
  public static function fillMissingAddressData(&$inputValues, $geocoder) {
    if (empty($inputValues['id'])) {
      return;
    }
    foreach (['county', 'state_province', 'country'] as $locationField) {
      if (empty($inputValues[$locationField]) && !empty($inputValues[$locationField . '_id'])) {
        $inputValues[$locationField] = CRM_Core_PseudoConstant::getLabel(
          'CRM_Core_BAO_Address',
          $locationField . '_id',
          $inputValues[$locationField . '_id']
        );
      }
    }
    $addressFields = array_keys(self::getAddressFields());

    if (isset($geocoder['required_fields'])) {
      $requiredFields = array_fill_keys(json_decode($geocoder['required_fields'], TRUE), 1);
    }
    else {
      $requiredFields = array_fill_keys($addressFields, 1);
    }

    $missingFields = array_diff_key($requiredFields, $inputValues);
    if (empty($missingFields)) {
      return;
    }

    $existingAddress = civicrm_api3('Address', 'getsingle', [
      'id' => $inputValues['id'],
      'return' => array_keys($missingFields)
    ]);
    $inputValues = array_merge($existingAddress, $inputValues);

    // clear an orphaned county
    if (empty($inputValues['county']) && !empty($inputValues['state_province'])) {
      unset($inputValues['county'], $inputValues['county_id']);
    }
  }

  /**
   * Is there sufficient information to pass this address to the geocoder.
   *
   * @param array $inputValues
   * @param array $geocoder
   *
   * @return bool
   */
  public static function hasRequiredFieldsForGeocoder($inputValues, $geocoder) {
    if (empty($geocoder['required_fields'])) {
      return TRUE;
    }
    $requiredFields = array_fill_keys(json_decode($geocoder['required_fields'], TRUE), 1);
    $missingFields = array_diff_key($requiredFields, $inputValues);
    return empty($missingFields) ? TRUE : FALSE;
  }

  /**
   * Get the list of fields that are used for addresses.
   *
   * @return array
   */
  public static function getAddressFields() {
    return [
      'street_address' => E::ts('Street Address'),
      'city' => E::ts('City'),
      'postal_code' => ts('Postal code'),
      'county_id' => E::ts('County'),
      'state_province_id' => E::ts('State / Province'),
      'country_id' => E::ts('Country'),
    ];
  }

  public static function getSendableFields($geocoder) {
    if (empty($geocoder['required_fields'])) {
      $keysToRetain = self::getAddressFields();
      unset($keysToRetain['country_id'], $keysToRetain['state_province_id'], $keysToRetain['county_id']);
      $keysToRetain['country'] = $keysToRetain['state_province_id'] = $keysToRetain['county'] = 1;
      return array_keys($keysToRetain);

    }
    return json_decode($geocoder['required_fields'], TRUE);
  }

  /**
   * Get a geocodable address.
   *
   * This is an address string.
   *
   * @param array $addressValues
   * @param $geocoder
   *
   * @return string
   * @throws \CRM_Core_Exception
   */
  protected static function getGeocodableAddress($addressValues, $geocoder) {
    $addressFields = self::getSendableFields($geocoder);
    // This merge will do an ordering for us.
    $addressValues = array_merge(array_fill_keys($addressFields, NULL), $addressValues);

    // filter out unrelated keys

    $addressValues = array_intersect_key($addressValues, array_fill_keys($addressFields, 1));
    // Convert the state id to name
    if (!empty($addressValues['state_province_id'])) {
      $addressValues['state_province_id'] = self::getStateName($addressValues['state_province_id']);
    }
    $geocodableAddress = implode(',', array_filter($addressValues, function($k) {
      return (!empty($k) && $k !== 'null');
    }));
    return $geocodableAddress;
  }

  /**
   * Convert the state id to name
   *
   * @param int|string $state the id
   *
   * @return string the state name
   * @throws \CRM_Core_Exception
   */
  protected static function getStateName($state) {
    if (!is_numeric($state)) {
      return $state;
    }
    $result = civicrm_api3('StateProvince', 'getsingle', [
      'id' => $state,
    ]);
    return $result['name'];
  }

  /**
   * Get the value for the specified field.
   *
   * @param string $fieldName
   * @param AddressCollection $result
   * @param array $values
   *   Address values to be saved
   *
   * @return string
   */
  protected static function getValueFromResult($fieldName, AddressCollection $result, $values) {
    $firstResult = $result->first();

    switch ($fieldName) {
      case 'geo_code_1':
        return $firstResult->getCoordinates() ? $firstResult->getCoordinates()->getLatitude() : NULL;

      case 'geo_code_2':
        return $firstResult->getCoordinates() ? $firstResult->getCoordinates()->getLongitude() : NULL;

      case 'timezone':
        return $firstResult->getTimezone();

      case 'city':
        return $firstResult->getLocality();

      case 'postal_code':
        return $firstResult->getPostalCode();

      case 'state_province_id':
        if (empty($values['country_id'])) {
          // not possible to determine state without the country.
          return '';
        }
        // Some providers return the code (abbreviation), others (eg nominatim/osm) return the name
        $stateAbbreviation = $firstResult->getAdminLevels()->get(1)->getCode();
        if (empty($stateAbbreviation) && !empty($firstResult->getAdminLevels()->get(1)->getName())) {
          // Try to get Abbreviation from State Name.
          $stateName = $firstResult->getAdminLevels()->get(1)->getName();
          $stateProvince = StateProvince::get(FALSE)
            ->addSelect('abbreviation', 'id')
            ->addWhere('name', '=', $stateName)
            ->addWhere('country_id', '=', $values['country_id'])
            ->addWhere('is_active', '=', TRUE)
            ->execute()
            ->first();
          if (empty($stateProvince['abbreviation'])) {
            // We need the abbreviation to get the ID.
            return '';
          }
          $stateAbbreviation = $stateProvince['abbreviation'];
          $stateProvinceID = $stateProvince['id'];
        }
        // Now we have the state Abbreviation we can check the cache (and fill it if required)
        if (!isset(\Civi::$statics[__CLASS__]['country_id'][$stateAbbreviation])) {
          if (empty($stateProvinceID)) {
            // We didn't already look up the ID, get it now
            $stateProvince = StateProvince::get(FALSE)
              ->addSelect('id')
              ->addWhere('abbreviation', '=', $stateAbbreviation)
              ->addWhere('country_id', '=', $values['country_id'])
              ->addWhere('is_active', '=', TRUE)
              ->execute()
              ->first();
            if (empty($stateProvince['id'])) {
              // Not found. Abbreviation doesn't exist for this Country.
              return '';
            }
            $stateProvinceID = $stateProvince['id'];
          }
          // Build our own static array as the core pseudoconstant does country limiting in a weird way.
          \Civi::$statics[__CLASS__]['country_id'][$stateAbbreviation] = $stateProvinceID;
        }
        return \Civi::$statics[__CLASS__]['country_id'][$stateAbbreviation];

      case 'county_id':
        $stateAbbreviation = self::getValueFromResult('state_province_id', $result, $values);
        $county = self::getAdminLevelByType($firstResult, 'county', 2);

        if ($stateAbbreviation && $county) {
          $id = CRM_Core_DAO::singleValueQuery('SELECT id FROM civicrm_county WHERE state_province_id = %1 AND name = %2 AND is_active=1', [
            1 => [$stateAbbreviation, 'Integer'],
            2 => [$county, 'String']
          ]);
          return $id ?: NULL;
        }
        return NULL;
    }
  }

  /**
   * Get an admin level name by type.
   * The name will end with the $type, e.g. "Lake County"...
   * ...and the $type will be removed on return, e.g. "Lake".
   * Can optionally return a name for a $defaultLevel if $type is not found in this way.
   *
   * @param Address $address Address to get admin level from.
   * @param string $type The type of admin level to get, e.g. "County".
   * @param integer $defaultLevel If $type is not found, use this level. 0 for no default level.
   * @return mixed The requested value, or NULL.
   */
  protected static function getAdminLevelByType($address, $type, $defaultLevel = 0) {
    $levels = $address->getAdminLevels();
    $ew = strtolower(" $type");

    foreach ($levels->all() as $level) {
      $name = $level->getName();

      if (str_ends_with(strtolower($name), $ew)) {
        return substr($name, 0, -strlen($ew));
      }
    }
    if ($defaultLevel > 0 && $levels->has($defaultLevel)) {
      return $levels->get($defaultLevel)->getName();
    }
    return NULL;
  }


  /**
   * Get metadata about entities.
   *
   * @return array
   */
  protected static function getEntitiesMetadata() {
    $entities = array();
    geocoder_civicrm_geo_managed($entities);
    $rekeyed = [];
    foreach ($entities as $entity) {
      $rekeyed[$entity['name']] = CRM_Utils_Array::value('metadata', $entity, []);
    }
    return $rekeyed;
  }

  /**
   * Get the argument for the provider.
   *
   * Sadly not all geocoders take the same argument so we need to set it up in our metadata.
   *
   * @param $geocoder
   *
   * @return string|array
   */
  protected static function getProviderArgument($geocoder) {
    return CRM_Utils_Array::value('argument', $geocoder);
  }

  /**
   * Is the geocoder configured with any required fields.
   *
   * @param array $metadata
   * @param array $geocoder
   *
   * @return bool
   */
  protected static function isGeocoderConfigured($metadata, $geocoder) {
    if (!empty($metadata['required_config_fields'])) {
      foreach ($metadata['required_config_fields'] as $fieldName) {
        if (empty($geocoder[$fieldName])) {
          return FALSE;
        }
      }
    }
    if (!empty($metadata['required_api_key_subkeys'])) {
      $key = json_decode($geocoder['api_key'], TRUE);
      foreach ($metadata['required_api_key_subkeys'] as $subkey) {
        if (empty($key[$subkey])) {
          return FALSE;
        }
      }

    }
    return TRUE;
  }

  /**
   * Pad the postal code if required.
   *
   * Currently just using the 2 known countries. Will think about how to extend.
   *
   * @param $values
   */
  protected static function padPostalCodeIfRequired(&$values) {
    if (empty($values['postal_code']) || !is_numeric($values['postal_code'])) {
      return;
    }
    if (empty($values['country_id'])) {
      return;
    }
    $postalCodeLengths = array('NZ' => 4, 'US' => 5);
    $countryCode = CRM_Core_PseudoConstant::countryIsoCode($values['country_id']);
    if (!isset($postalCodeLengths[$countryCode])) {
      return;
    }

    if (strlen($values['postal_code']) < $postalCodeLengths[$countryCode]) {
      $values['postal_code'] = str_pad($values['postal_code'], $postalCodeLengths[$countryCode], 0, STR_PAD_LEFT);
    }
  }

  /**
   * Set geocoders if not set.
   */
  protected static function setGeocoders() {
    if (!is_array(self::$geoCoders)) {
      self::$geoCoders = [];
      $geocoders = civicrm_api3('Geocoder', 'get', [
        'sequential' => 1,
        'is_active' => 1,
        'options' => ['sort' => 'weight'],
      ]);
      $metadata = self::getEntitiesMetadata();
      foreach ($geocoders['values'] as $geocoder) {
        if (self::isGeocoderConfigured($metadata[$geocoder['name']], $geocoder)) {
          // There is some historical mix up with iso vs id values
          // This is likely in part because apiv3 can be a bit fast & loose
          // & people adapted...
          if (!empty($geocoder['valid_countries'])) {
            $countries = json_decode($geocoder['valid_countries'], FALSE, 512, JSON_THROW_ON_ERROR);
            $isoCodeMap = CRM_Core_PseudoConstant::countryIsoCode();
            foreach ($countries as $country) {
              if (isset($isoCodeMap[$country])) {
                $countries[] = $isoCodeMap[$country];
              }
              elseif (in_array($country, $isoCodeMap, TRUE)) {
                $countries[] = array_search($country, $isoCodeMap, TRUE);
              }
            }
            $geocoder['valid_countries'] = $countries;
          }

          self::$geoCoders[$geocoder['name']] = array_merge($geocoder, $metadata[$geocoder['name']]);
        }
      }
    }
  }

  /**
   * Reset the cached geocoders.
   */
  public static function resetGeoCoders() {
    self::$geoCoders = NULL;
  }

  /**
   * Load the provider class.
   *
   * @param array $geocoder
   *
   * @return \Geocoder\Provider\Provider
   */
  protected static function getProviderClass($geocoder) {
    $classString = '\\Geocoder\\Provider\\' . $geocoder['class'];
    $arguments = (array) self::getProviderArgument($geocoder);
    $parameters = [];
    foreach ($arguments as $index => $argument) {
      if (strpos($index, 'pass_through') === 0) {
        $parameters[] = $argument;
        continue;
      }
      if (is_null($argument)) {
        $parameters[] = NULL;
        continue;
      }
      $parts = explode('.', $argument);
      if ($parts[0] === 'geocoder') {
        $parameters[] = $geocoder[$parts[1]];
      }
      elseif ($parts[0] === 'server') {
        $serverParts = explode(':', $parts[1]);
        $default = $serverParts[1] ?? '';
        $parameters[] = $_SERVER[$serverParts[0]] ?? $default;
      }
      if ($parts[0] === 'api_key') {
        $keyFields = json_decode($geocoder['api_key'], TRUE);
        $parameters[] = $keyFields[$parts[1]];
      }
    }
    return new $classString(self::$client, ...$parameters);
  }

  /**
   * @param string $geocodableAddress
   *
   * @return array
   * @throws \CRM_Core_Exception
   * @throws \Geocoder\Exception\Exception
   */
  public static function getCoordinates(string $geocodableAddress): array {
    if (!self::getClient()) {
      self::setClient(new Client());
    }
    self::setGeocoders();
    // AFAIK only 2 char string accepted - from the examples.
    $locale = substr(CRM_Utils_System::getUFLocale(), 0, 2);
    $messageOnFail = NULL;

    foreach (self::$geoCoders as $geocoder) {
      if (!self::isUsable($geocoder)) {
        continue;
      }

      try {
        $provider = self::getProviderClass($geocoder);
        $geocoderObj = new \Geocoder\StatefulGeocoder($provider, $locale);
        // Not do not url_encode as it breaks OpenStreetMap
        // https://github.com/eileenmcnaughton/org.wikimedia.geocoder/issues/61
        $result = $geocoderObj->geocodeQuery(GeocodeQuery::create($geocodableAddress));
        return [
          'geo_code_1' => $result->first()->getCoordinates()->getLatitude(),
          'geo_code_2' => $result->first()->getCoordinates()->getLongitude(),
        ];
      }
      catch (Geocoder\Exception\CollectionIsEmpty $e) {
        $messageOnFail = ts('Failed to geocode address, no co-ordinates saved');
        continue;
      }
      catch (Geocoder\Exception\QuotaExceeded $e) {
        if (CRM_Core_Permission::check('access CiviCRM')) {
          CRM_Core_Session::setStatus(ts('Geocoder quota exceeded. No further geocoding attempts will be made for %1 seconds', [
            $geocoder['threshold_standdown'],
            'int',
          ]));
        }
        civicrm_api3('Geocoder', 'create', [
          'id' => $geocoder['id'],
          'threshold_last_hit' => 'now',
        ]);
        // Unset it so we reload next instance & recheck properly.
        self::$geoCoders = NULL;
        continue;
      }
      catch (Exception $e) {
        $messageOnFail = ts('Unknown geocoding error on :') . $geocoder['title'] . ":" . $e->getMessage();
        continue;
      }
    }
    return ['geo_code_error' => $messageOnFail];
  }

}
