<?php

class UpgraderTest extends \PHPUnit\Framework\TestCase
{
    public function managedEntityHasNameDataProvider()
    {
        return [
            [null, false],
            ["", false],
            [["name" => "abc"], true]

        ];

    }

    /**
     * @dataProvider managedEntityHasNameDataProvider
     * @param $entity
     * @param $expected
     * @return void
     * @throws ReflectionException
     */
    public function testManagedEntityHasName($entity, $expected)
    {
        $method = new ReflectionMethod("CRM_Geocoder_Upgrader::managedEntityHasName");
        $method->setAccessible(true);
        $computed = $method->invoke(null, $entity);
        $this->assertEquals($expected, $computed);
    }

    public function managedEntityIsEnabledOnInstallDataProvider()
    {
        return [
            [null, false],
            [[], false],
            [["metadata" => null], false],
            [["metadata" => []], false],
            [["metadata" => ["is_enabled_on_install" => false]], false],
            [["metadata" => ["is_enabled_on_install" => true]], true]
        ];
    }

    /**
     * @dataProvider managedEntityIsEnabledOnInstallDataProvider
     * @param $entity
     * @param $expected
     * @return void
     * @throws ReflectionException
     */
    public function testManagedEntityIsEnabledOnInstall($entity, $expected)
    {
        $method = new ReflectionMethod("CRM_Geocoder_Upgrader::managedEntityIsEnabledOnInstall");
        $method->setAccessible(true);
        $computed = $method->invoke(null, $entity);
        $this->assertEquals($expected, $computed);
    }

    public function managedEntityIsNotActiveDataProvider()
    {
        return [
            [null, false],
            [[], false],
            [["params" => null], false],
            [["params" => []], false],
            [["params" => ["is_active" => false]], true],
            [["metadata" => ["is_active" => true]], false]
        ];
    }

    /**
     * @dataProvider managedEntityIsNotActiveDataProvider
     * @param $entity
     * @param $expected
     * @return void
     * @throws ReflectionException
     */
    public function testManagedEntityIsNotActive($entity,$expected)
    {
        $method = new ReflectionMethod("CRM_Geocoder_Upgrader::managedEntityIsNotActive");
        $method->setAccessible(true);
        $computed = $method->invoke(null, $entity);
        $this->assertEquals($expected, $computed);
    }

}
