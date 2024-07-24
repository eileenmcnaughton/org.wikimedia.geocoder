<?php

namespace Civi\Geocoder;

use Civi\Api4\Address;
use Civi\Api4\Contact;
use Civi\Test\Api3TestTrait;
use Civi\Test\EntityTrait;
use CRM_Core_DAO;
use CRM_Utils_File;
use CRM_Utils_Geocode_Geocoder;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Psr7\Response;
use Http\Adapter\Guzzle6\Client;
use Civi\Test\GuzzleTestTrait;

/**
 * FIXME - Add test description.
 *
 * Tips:
 *  - With HookInterface, you may implement CiviCRM hooks directly in the test
 * class. Simply create corresponding functions (e.g. "hook_civicrm_post(...)"
 * or similar).
 *  - With TransactionalInterface, any data changes made by setUp() or
 * test****() functions will rollback automatically -- as long as you don't
 * manipulate schema or truncate tables. If this test needs to manipulate
 * schema or truncate tables, then either: a. Do all that using setupHeadless()
 * and Civi\Test. b. Disable TransactionalInterface, and handle all
 * setup/teardown yourself.
 *
 * @group headless
 */
class GeocoderTest extends BaseTestClass {
  use EntityTrait;

  use Api3TestTrait;
  use GuzzleTestTrait;

  protected $ids = [];

  protected $geocoders = [];

  /**
   * @throws \CRM_Core_Exception
   */
  public function setUp(): void {
    parent::setUp();
    $this->setHttpClientToEmptyMock();
    $geocoders = $this->callAPISuccess('Geocoder', 'get')['values'];
    foreach ($geocoders as $geocoder) {
      $this->geocoders[$geocoder['name']] = $geocoder;
    }

    $this->configureGeoCoders([
      'open_street_maps' => [
        'name' => 'open_street_maps',
        'is_active' => 1,
        'weight' => 1,
      ],
      'us_zip_geocoder' => [
        'name' => 'us_zip_geocoder',
        'is_active' => 1,
        'weight' => 2,
      ],
      'geonames_db_table' => [
        'name' => 'geonames_db_table',
        'is_active' => 1,
        'weight' => 3,
      ],
    ]);

    $this->createTestEntity('Contact', [
      'contact_type' => 'Individual',
      'first_name' => 'Brer',
      'last_name' => 'Rabbit',
    ]);
  }

  /**
   * Clean up after class.
   *
   */
  public function tearDown(): void {
    Contact::delete(FALSE)
      ->addWhere('id', 'IN', $this->ids['Contact'])
      ->setUseTrash(FALSE)
      ->execute();
    $this->configureGeoCoders($this->geocoders);
    parent::tearDown();
  }

  /**
   * Test OpenStreetMap geocodes address.
   *
   * @throws \Exception
   */
  public function testOpenStreetMap(): void {
    $responses = [file_get_contents(__DIR__ . '/Responses/OpenStreetMap.json')];
    $this->getClient($responses);
    $address = $this->callAPISuccess('Address', 'create', [
      'postal_code' => 90210,
      'location_type_id' => 'Home',
      'contact_id' => $this->ids['Contact']['default'],
      'country_id' => 'US',
    ]);
    $address = $this->callAPISuccessGetSingle('Address', ['id' => $address['id']]);
    // Different systems seem to vary in their precision so let's round.
    $this->assertEquals('34.0781172375', round($address['geo_code_1'], 10));
    $this->assertEquals('-118.35299997', round($address['geo_code_2'], 8));
  }

  /**
   * Test when OpenStreetMap fails we fall back on the next one
   * (USZipGeoCoder).
   *
   * Note the lat long are slightly different between the 2 providers & we get
   * timezone.
   *
   */
  public function testOpenStreetMapFailsFallsBackToUSLookup(): void {
    $address = $this->callAPISuccess('Address', 'create', [
      'postal_code' => 90210,
      'location_type_id' => 'Home',
      'contact_id' => $this->ids['Contact']['default'],
      'country_id' => 'US',
    ]);
    $address = $this->callAPISuccessGetSingle('Address', ['id' => $address['id']]);
    $this->assertEquals('34.088808', $address['geo_code_1']);
    $this->assertEquals('-118.40612', $address['geo_code_2']);
    $this->assertEquals('UTC-8', $address['timezone']);
    $this->assertEquals('Beverly Hills', $address['city']);
    $this->assertEquals(
      $this->callAPISuccessGetValue('StateProvince', [
        'return' => 'id',
        'name' => 'California',
      ]),
      $address['state_province_id']
    );
  }

  /**
   * Test that postal codes are suitably formatted for the locale
   * (ex. left-pad with 0s)
   *
   * This only applies to NZ & US at the moment but as we get validation for
   * more countries we can extend.
   *
   */
  public function testShortPostalCode(): void {
    $this->setHttpClientToEmptyMock();
    $address = $this->callAPISuccess('Address', 'create', [
      'postal_code' => 624,
      'location_type_id' => 'Home',
      'contact_id' => $this->ids['Contact']['default'],
      'country_id' => 'US',
    ]);
    $address = $this->callAPISuccessGetSingle('Address', ['id' => $address['id']]);
    $this->assertEquals('18.055399', $address['geo_code_1']);
  }

  /**
   * Test geoname table option.
   *
   * @throws \Exception
   */
  public function testGeoName(): void {
    $this->setHttpClientToEmptyMock();
    $drop = FALSE;
    if (!CRM_Core_DAO::singleValueQuery("SHOW TABLES LIKE 'civicrm_geonames_lookup'")) {
      // set up headless doesn't seem to be called in wmf tests ...but I haven't
      // double checked if we can drop if when running tests in isolation.
      CRM_Utils_File::sourceSQLFile(NULL, $this->getSqlFolder() . 'nz_sample_geoname_table.sql');
      $drop = TRUE;
    }
    $address = $this->callAPISuccess('Address', 'create', [
      'postal_code' => '0951',
      'location_type_id' => 'Home',
      'contact_id' => $this->ids['Contact']['default'],
      'country_id' => 'NZ',
    ]);
    $address = $this->callAPISuccessGetSingle('Address', ['id' => $address['id']]);
    $this->assertEquals('-36.5121', $address['geo_code_1']);
    $this->assertEquals('174.661', $address['geo_code_2']);
    $this->assertEquals('Puhoi', $address['city']);
    if ($drop) {
      CRM_Core_DAO::executeQuery("DROP TABLE civicrm_geonames_lookup");
    }
  }

  /**
   * @throws \CRM_Core_Exception
   * @throws \Civi\Core\Exception\DBQueryException
   */
  public function testUK(): void {
    // We need to enable the uk_postcode geocoder
    $id = (int) civicrm_api3('Geocoder', 'getvalue', [
      'name' => 'uk_postcode',
      'return' => 'id',
    ]);
    if (!$id) {
      throw new \CRM_Core_Exception("Failed to find uk_postcode geocoder");
    }
    $drop = FALSE;
    if (!CRM_Core_DAO::singleValueQuery("SHOW TABLES LIKE 'civicrm_open_postcode_geo_uk'")) {
      // set up headless doesn't seem to be called in wmf tests ...but I haven't
      // double checked if we can drop if when running tests in isolation.
      CRM_Utils_File::sourceSQLFile(NULL, $this->getSqlFolder(). 'open_postcode_geo-test.sql');
      $drop = TRUE;
    }
    civicrm_api3('Geocoder', 'create', ['is_active' => 1, 'id' => $id]);

    $this->configureGeoCoders([
      'uk_postcode' => [
        'name' => 'uk_postcode',
        'is_active' => 1,
        'weight' => 1,
      ],
    ]);

    // Check that passing in a valid, known postcode yields the correct latitude.
    $address = $this->callAPISuccess('Address', 'create', [
      'postal_code' => 'SW1A 0AA',
      'location_type_id' => 'Home',
      'contact_id' => $this->ids['Contact']['default'],
      'country_id' => 'GB',
    ]);
    $address = $this->callAPISuccessGetSingle('Address', ['id' => $address['id']]);
    $this->assertEquals('51.49984', $address['geo_code_1'] ?? NULL);
    $this->assertEquals('SW1A 0AA', $address['postal_code'] ?? NULL);
    Address::delete(FALSE)->addWhere('id', '=', $address['id']);

    // Check that passing in a malformed but correct postcode without spaces
    // (a) gets latitude and (b) gets corrected.
    $address = $this->callAPISuccess('Address', 'create', [
      'postal_code' => 'SW1A0AA',
      'location_type_id' => 'Home',
      'contact_id' => $this->ids['Contact']['default'],
      'country_id' => 'GB',
    ]);
    $address = $this->callAPISuccessGetSingle('Address', ['id' => $address['id']]);
    $this->assertEquals('51.49984', $address['geo_code_1'] ?? NULL);
    $this->assertEquals('SW1A 0AA', $address['postal_code'] ?? NULL);
    Address::delete(FALSE)->addWhere('id', '=', $address['id']);

    // Check that passing in bad postcode/one we don't know does no damage.
    $address = $this->callAPISuccess('Address', 'create', [
      'postal_code' => 'ZEBRA678',
      'location_type_id' => 'Home',
      'contact_id' => $this->ids['Contact']['default'],
      'country_id' => 'GB',
    ]);
    $address = $this->callAPISuccessGetSingle('Address', ['id' => $address['id']]);
    // Check the postcode wasn't changed.
    $this->assertEquals('ZEBRA678', $address['postal_code'] ?? NULL);
    Address::delete(FALSE)->addWhere('id', '=', $address['id']);
    if ($drop) {
      CRM_Core_DAO::executeQuery("DROP TABLE civicrm_open_postcode_geo_uk");
    }
  }

  /**
   * Configure geocoders for testing.
   *
   * @param array $coders
   *   Array of coders that should be enabled.
   */
  protected function configureGeoCoders(array $coders): void {
    foreach ($this->geocoders as $geoCoder) {
      if (isset($coders[$geoCoder['name']])) {
        $params = array_merge(['id' => $geoCoder['id']], $coders[$geoCoder['name']]);
      }
      else {
        $params = ['id' => $geoCoder['id'], 'is_active' => 0];
      }
      // @todo api should handle these but for now we will.
      $jsonFields = [
        'required_fields',
        'retained_response_fields',
        'datafill_response_fields',
        'valid_countries',
      ];
      foreach ($jsonFields as $jsonField) {
        if (!empty($params[$jsonField]) && is_string($jsonField)) {
          $params[$jsonField] = json_decode($params[$jsonField]);
        }
      }

      $this->callAPISuccess('Geocoder', 'create', $params);
    }
  }

  /**
   * @param array $responses
   */
  protected function getClient(array $responses): void {
    $this->createMockHandler($responses);
    $this->setUpClientWithHistoryContainer();
    CRM_Utils_Geocode_Geocoder::setClient($this->getGuzzleClient());
  }

  protected function setHttpClientToEmptyMock(): void {
    $responses = [];
    $this->getClient($responses);
  }

}
