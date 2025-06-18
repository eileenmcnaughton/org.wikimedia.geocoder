<?php

namespace Civi\Geocoder;

use Civi\Test;
use Civi\Test\CiviEnvBuilder;
use Civi\Test\HeadlessInterface;
use Civi\Test\HookInterface;
use Civi\Test\TransactionalInterface;
use PHPUnit\Framework\TestCase;

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
class BaseTestClass extends TestCase implements HeadlessInterface, HookInterface {

  protected array $tablesToDrop = [];

  /**
   * @throws \CRM_Extension_Exception_ParseException
   */
  public function setUpHeadless(): CiviEnvBuilder {
    // Civi\Test has many helpers, like install(), uninstall(), sql(), and sqlFile().
    // See: https://github.com/civicrm/org.civicrm.testapalooza/blob/master/civi-test.md
    return Test::headless()
      ->installMe(__DIR__)
      ->apply();
  }

  public function setUp(): void {
    foreach (['civicrm_geonames_lookup', 'civicrm_open_postcode_geo_uk'] as $dataTable) {
      if (!\CRM_Core_DAO::singleValueQuery("SHOW TABLES LIKE %1", [1 => [$dataTable, 'String']])) {
        $this->tablesToDrop[] = $dataTable;
      }
    }
    parent::setUp();
  }

  public function tearDown(): void {
    foreach ($this->tablesToDrop as $dataTable) {
      \CRM_Core_DAO::singleValueQuery("DROP TABLE IF EXISTS " . $dataTable);
    }
    parent::tearDown();
  }

  /**
   * @return string
   */
  protected function getSqlFolder(): string {
    return __DIR__ . DIRECTORY_SEPARATOR . '..' . DIRECTORY_SEPARATOR . '..'
      . DIRECTORY_SEPARATOR . ".." . DIRECTORY_SEPARATOR . ".." . DIRECTORY_SEPARATOR . 'sql' . DIRECTORY_SEPARATOR;
  }

}
