<?php

require_once __DIR__ . '/BaseTestClass.php';

use CRM_Geocoder_ExtensionUtil as E;
use Civi\Test\HeadlessInterface;
use Civi\Test\HookInterface;
use Civi\Test\TransactionalInterface;
use Http\Adapter\Guzzle6\Client;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Psr7\Response;

/**
 * FIXME - Add test description.
 *
 * Tips:
 *  - With HookInterface, you may implement CiviCRM hooks directly in the test class.
 *    Simply create corresponding functions (e.g. "hook_civicrm_post(...)" or similar).
 *  - With TransactionalInterface, any data changes made by setUp() or test****() functions will
 *    rollback automatically -- as long as you don't manipulate schema or truncate tables.
 *    If this test needs to manipulate schema or truncate tables, then either:
 *       a. Do all that using setupHeadless() and Civi\Test.
 *       b. Disable TransactionalInterface, and handle all setup/teardown yourself.
 *
 * @group headless
 */
class GeocoderTest extends BaseTestClass implements HeadlessInterface, HookInterface, TransactionalInterface {

  protected $ids = array();

  public function setUpHeadless() {
    // Civi\Test has many helpers, like install(), uninstall(), sql(), and sqlFile().
    // See: https://github.com/civicrm/org.civicrm.testapalooza/blob/master/civi-test.md
    return \Civi\Test::headless()
      ->installMe(__DIR__)
      ->apply();
  }

  public function setUp() {
    parent::setUp();
    $contact = $this->callAPISuccess('Contact', 'create', [
      'contact_type' => 'Individual',
      'first_name' => 'Brer',
      'last_name' => 'Rabbit',
    ]);
    $this->ids['contact'][] = $contact['id'];
  }

  public function tearDown() {
    foreach ($this->ids as $entity => $entityIDs) {
      foreach ($entityIDs as $id) {
        $this->callAPISuccess($entity, 'delete', ['id' => $id]);
      }
    }
    parent::tearDown();
  }

  public function testGeocoderStack() {

  }

  /**
   * Test open street maps geocodes address.
   */
  public function testOpenStreetMaps() {
    $responses = [new Response(200, [], file_get_contents(__DIR__ . '/Responses/OpenStreetMaps.xml'))];
    $this->getClient($responses);
    $address = $this->callAPISuccess('Address', 'create', [
      'postal_code' => 90210,
      'location_type_id' => 'Home',
      'contact_id' => $this->ids['contact'][0],
      'country_id' => 'US',
    ]);
    $this->callAPISuccessGetSingle('Address', ['id' => $address['id']]);
    $this->assertEquals('34.0781172375027', $address['geo_code_1']);
    $this->assertEquals('-118.352999970633', $address['geo_code_2']);
  }

  /**
   * Test when open street maps fail we fall back on the next one (USZipGeoCoder).
   *
   * Note the lat long are slightly different between the 2 providers & we get timezone.
   */
  public function testOpenStreetMapsFailsFallsback() {
    $responses = [];
    $this->getClient($responses);
    $address = $this->callAPISuccess('Address', 'create', [
      'postal_code' => 90210,
      'location_type_id' => 'Home',
      'contact_id' => $this->ids['contact'][0],
      'country_id' => 'US',
    ]);
    $address = $this->callAPISuccessGetSingle('Address', ['id' => $address['id']]);
    $this->assertEquals('34.088808', $address['geo_code_1']);
    $this->assertEquals('-118.40612', $address['geo_code_2']);
    $this->assertEquals('UTC-8', $address['timezone']);

  }

  /**
   * @param $responses
   */
  protected function getClient($responses) {
    $mock = new MockHandler($responses);
    $handler = HandlerStack::create($mock);
    CRM_Utils_Geocode_Geocoder::setClient(Client::createWithConfig(['handler' => $handler]));
  }

}
