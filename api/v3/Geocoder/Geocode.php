<?php
/**
 * Created by IntelliJ IDEA.
 * User: emcnaughton
 * Date: 1/17/18
 * Time: 2:32 PM
 */
use Geocoder\Query\GeocodeQuery;

function civicrm_api3_geocoder_geocode($params) {
  $httpClient = new \Http\Adapter\Guzzle6\Client();
  $provider = new \Geocoder\Provider\FreeGeoIp\FreeGeoIp($httpClient);
  $geocoder = new \Geocoder\StatefulGeocoder($provider, 'en');
  $result = $geocoder->geocodeQuery(GeocodeQuery::create('125.236.242.137'));
  return civicrm_api3_create_success($result);
}