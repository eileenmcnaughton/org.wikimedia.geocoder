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
  $url = 'https://nominatim.openstreetmap.org/search';
  $provider = new Geocoder\Provider\Nominatim\Nominatim($httpClient, $url);
  $geocoder = new \Geocoder\StatefulGeocoder($provider, 'en');
  $result = $geocoder->geocodeQuery(GeocodeQuery::create('258 Crane Road, New Zealand'));
  return civicrm_api3_create_success($result);
}