<?php

namespace Civi\Geocoder;

use PHPUnit\Framework\TestCase;

class UpgraderTest extends TestCase {

  public function managedEntityHasNameDataProvider(): array {
    return [
      [NULL, FALSE],
      ["", FALSE],
      [["name" => "abc"], TRUE],

    ];
  }

  /**
   * @dataProvider managedEntityHasNameDataProvider
   *
   * @param $entity
   * @param $expected
   *
   * @return void
   * @throws \ReflectionException
   */
  public function testManagedEntityHasName($entity, $expected): void {
    $method = new \ReflectionMethod("CRM_Geocoder_Upgrader::managedEntityHasName");
    $method->setAccessible(TRUE);
    $computed = $method->invoke(NULL, $entity);
    $this->assertEquals($expected, $computed);
  }

  public function managedEntityIsEnabledOnInstallDataProvider(): array {
    return [
      [NULL, FALSE],
      [[], FALSE],
      [["metadata" => NULL], FALSE],
      [["metadata" => []], FALSE],
      [["metadata" => ["is_enabled_on_install" => FALSE]], FALSE],
      [["metadata" => ["is_enabled_on_install" => TRUE]], TRUE],
    ];
  }

  /**
   * @dataProvider managedEntityIsEnabledOnInstallDataProvider
   *
   * @param null|array $entity
   * @param bool $expected
   *
   * @return void
   * @throws \ReflectionException
   */
  public function testManagedEntityIsEnabledOnInstall(?array $entity, bool $expected): void {
    $method = new \ReflectionMethod("CRM_Geocoder_Upgrader::managedEntityIsEnabledOnInstall");
    $method->setAccessible(TRUE);
    $computed = $method->invoke(NULL, $entity);
    $this->assertEquals($expected, $computed);
  }

  public function managedEntityIsNotActiveDataProvider(): array {
    return [
      [NULL, FALSE],
      [[], FALSE],
      [["params" => NULL], FALSE],
      [["params" => []], FALSE],
      [["params" => ["is_active" => FALSE]], TRUE],
      [["metadata" => ["is_active" => TRUE]], FALSE],
    ];
  }

  /**
   * @dataProvider managedEntityIsNotActiveDataProvider
   *
   * @param array|null $entity
   * @param bool $expected
   *
   * @return void
   * @throws \ReflectionException
   */
  public function testManagedEntityIsNotActive(?array $entity, bool $expected): void {
    $method = new \ReflectionMethod("CRM_Geocoder_Upgrader::managedEntityIsNotActive");
    $method->setAccessible(TRUE);
    $computed = $method->invoke(NULL, $entity);
    $this->assertEquals($expected, $computed);
  }

}
