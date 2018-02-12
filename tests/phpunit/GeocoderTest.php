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
    $mock = new MockHandler([]);
    $handler = HandlerStack::create($mock);
    CRM_Utils_Geocode_Geocoder::setClient(new Client());
    $this->callAPISuccess('Address', 'create', [
      'postal_code' => 90210,
      'location_type_id' => 'Home',
      'contact_id' => $this->ids['contact'][0],
      'country_id' => 'US',
    ]);
  }

  /**
   * Example: Test that a version is returned.
   */
  public function testWellFormedVersion() {
    $this->assertRegExp('/^([0-9\.]|alpha|beta)*$/', \CRM_Utils_System::version());
  }

  /**
   * Example: Test that we're using a fake CMS.
   */
  public function testWellFormedUF() {
    $this->assertEquals('UnitTests', CIVICRM_UF);
  }

}
