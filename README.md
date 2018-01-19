Geocoding for CiviCRM based on geocoder library

Implementation of geocoder library (which itself supports multiple providers) https://github.com/geocoder-php/mapquest-provider

Out of the box Open Street Maps is enabled and Mapquest is available but 
needs the entry in civicrm_geocoder table updated with an api key (free)
and the weights altered so it has the lowest weight

Next steps
1) get the zip_code based geocoding working
2) enable some more geocoders (around 20 exist - https://github.com/geocoder-php/Geocoder#providers)
3) implement optional threshold management
4) Look at implementing the provider failover per https://github.com/geocoder-php/Geocoder#the-chain-provider
5) ohhh caching https://github.com/geocoder-php/Geocoder/blob/master/docs/cookbook/cache.md

Also of interest
- library supports ip address geocoding