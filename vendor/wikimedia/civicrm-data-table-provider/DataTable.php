<?php

/*
 * This file is part of the Geocoder package.
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *
 * @license    MIT License
 */

namespace Geocoder\Provider\DataTable;

use Geocoder\Collection;
use Geocoder\Exception\UnsupportedOperation;
use Geocoder\Model\AddressBuilder;
use Geocoder\Model\AddressCollection;
use Geocoder\Model\AdminLevel;
use Geocoder\Query\GeocodeQuery;
use Geocoder\Query\ReverseQuery;
use Geocoder\Http\Provider\AbstractHttpProvider;
use Geocoder\Provider\Provider;
use Http\Client\HttpClient;
use Geocoder\Exception\CollectionIsEmpty;

/**
 * @author Eileen McNaughton <emcnaughton@wikimedia.org>
 */
final class DataTable extends AbstractHttpProvider implements Provider
{
    /**
     * @var string
     */
    private $tableName;

  /**
   * @param HttpClient $client
   * @param string $tableName
   *
   * @throws \Exception
   */
    public function __construct(HttpClient $client, $tableName)
    {
        parent::__construct($client);

        if (!\CRM_Utils_Rule::mysqlColumnNameOrAlias($tableName)
          || !\CRM_Core_DAO::executeQuery("SHOW CREATE TABLE $tableName")) {
          throw new \Exception('Invalid table');
        }

        $this->tableName = $tableName;
    }

    /**
     * {@inheritdoc}
     */
    public function geocodeQuery(GeocodeQuery $query)
    {

        $postalCode = substr(trim($query->getText()), 0, 5);

        $sql = "
         SELECT city, state_code, latitude, longitude, timezone
         FROM {$this->tableName} g
         WHERE postal_code = %2";

        $result = \CRM_Core_DAO::executeQuery(
          $sql,
          [1 => [$this->tableName, 'String'], 2 => [$postalCode, 'String']]
        );

        $builder = new AddressBuilder($this->getName());
        if ($result->fetch()) {
          $builder->setCoordinates($result->latitude, $result->longitude);
          $builder->setLocality($result->city);
          $builder->setTimezone($result->timezone);
          $builder->setAdminLevels([new AdminLevel(1, $result->state_code, $result->state_code)]);
          return new AddressCollection([$builder->build()]);
        }

        throw new CollectionIsEmpty();
    }

    /**
     * {@inheritdoc}
     */
    public function reverseQuery(ReverseQuery $query)
    {
        throw new UnsupportedOperation('The data table provider is not able to do reverse geocoding yet.');
    }

    /**
     * {@inheritdoc}
     */
    public function getName()
    {
        return 'data_table';
    }
}
