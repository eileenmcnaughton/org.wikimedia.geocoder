<?php
/**
 * Created by IntelliJ IDEA.
 * User: emcnaughton
 * Date: 1/17/18
 * Time: 2:32 PM
 */


function civicrm_api3_geocoder_geocode($params) {
  $httpClient = new \Http\Adapter\Guzzle6\Client();
  $provider = new \Geocoder\Provider\FreeGeoIp\FreeGeoIp;
  $geocoder = new \Geocoder\StatefulGeocoder($provider, 'en');
  $result = $geocoder->geocodeQuery(GeocodeQuery::create('Buckingham Palace, London'));
  return civicrm_api3_create_success($result);
}