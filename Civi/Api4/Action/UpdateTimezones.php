<?php

namespace Civi\Api4\Action;

use Civi\Api4\Generic\AbstractAction;
use Civi\Api4\Generic\Result;
use CRM_Core_DAO;

/**
 * Adds missing time zone information to the geocoder zip code dataset from a web service
 * See https://askgeo.com/#web-api
 * TODO: determine DST, adjust result when InDstNow is true. Currently running for APO/FPO
 * (overseas military) zip codes, so assuming all are without DST.
 *
 * @method $this setAccountNumber(int $accountNumber) Set the AskGeo account number
 * @method $this setApiKey(string $apiKey) Set the AskGeo API key
 */
class UpdateTimezones extends AbstractAction {

  /**
   * @var int AskGeo account number
   * @required
   */
  protected int $accountNumber;

  /**
   * @var string AskGeo API Key
   * @required
   */
  protected string $apiKey;

  public function _run(Result $result) {
    $alreadyFound = [];
    $query = CRM_Core_DAO::executeQuery(
      'SELECT postal_code, latitude, longitude FROM civicrm_geocoder_zip_dataset WHERE timezone IS NULL'
    );
    while ($query->fetch()) {
      $lat = $query->latitude;
      $long = $query->longitude;
      $pointString = $lat . ',' . $long;
      if (!array_key_exists($pointString, $alreadyFound)) {
        $url = "https://api.askgeo.com/v1/$this->accountNumber/$this->apiKey/query.json?databases=TimeZone&points=$pointString";
        $fetched = file_get_contents($url);
        $decoded = json_decode($fetched, true);
        $msOffset = (int)$decoded['data'][0]['TimeZone']['CurrentOffsetMs'];
        $hourOffset = $msOffset / 3600000;
        $tz = 'UTC' . ($hourOffset >= 0 ? '+' : '') . $hourOffset;
        $alreadyFound[$pointString] = $tz;
      }
      CRM_Core_DAO::executeQuery(
        'UPDATE civicrm_geocoder_zip_dataset SET timezone = %1, dst = 0 WHERE postal_code = %2',
        [
          1 => [$alreadyFound[$pointString], 'String'],
          2 => [$query->postal_code, 'String']
        ]
      );
      $result[$query->postal_code] = $alreadyFound[$pointString];
    }
  }
}
