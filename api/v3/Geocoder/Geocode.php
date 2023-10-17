<?php
/**
 * Created by IntelliJ IDEA.
 * User: emcnaughton
 * Date: 1/17/18
 * Time: 2:32 PM
 */
use Geocoder\Query\GeocodeQuery;

function civicrm_api3_geocoder_geocode($params) {

  // currently not working
  $httpClient = new Http\Discovery\Psr18Client();
  $url = 'https://nominatim.openstreetmap.org';
  $provider = new Geocoder\Provider\Nominatim\Nominatim($httpClient, $url);
  $geocoder = new \Geocoder\StatefulGeocoder($provider, 'en');
  $result = $geocoder->geocodeQuery(GeocodeQuery::create('Disney Land, United States'));
  return civicrm_api3_create_success($result);
}
