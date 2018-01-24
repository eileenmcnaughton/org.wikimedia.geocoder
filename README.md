Geocoding for CiviCRM based on geocoder library

Requires - CiviCRM 4.7.31, php 5.6

Implementation of geocoder library (which itself supports multiple providers) https://github.com/geocoder-php/mapquest-provider. Only those that have been tested are enabled so far.

When an address is edited CiviCRM will obtain additional data from the geocoding provider
and save it to the civicrm_address database with the address. It will also geocode addresses
to be used as the focal point of proximity searches or for event maps.

Note that the terms of data use by geocoding providers varies and it is your responsibility
to understand and adhere to them.

Currently enabled geocoders are
- Open Street Maps - this is zero-config & is enabled as the default (lowest weight)on install if you have no existing mapping provider
- MapQuest - requires an API key to be used
- GoogleMaps - requires an API key to be used - this is enabled on install as the default if you 
already have google configured as your provider. However the Terms of service suggest it may not be a good choose https://support.google.com/code/answer/55180?hl=en
- USZipGeocoder - this is enabled on install & has no config. It has the highest weight
so will work as a fallback only
- GeoName DB geocoder - this requires more config to use for any country other than NZ which is
used as the sample dataset.

Out of the box Open Street Maps is enabled and Mapquest is available but 
needs the entry in civicrm_geocoder table updated with an api key (free)
and the weights altered so it has the lowest weight

Features
- Threshold standdown period. If the geocoding quota is hit for a provider it is not used
again until the standdown has expired. By default the standdown is 1 minute but it is configurable per geocoder instance.
- Provider fall over. If a provider is not valid (e.g because the quota was hit or it only does a
 particular country or it's required fields are not present) then the next geocoder (by weight) 
 will be used.
- Database table based geocoding. If you do not wish to interact with an external site then
a US zip table lookup is available (from CiviSpace). It is possible to download other datasets (e.g from geonames) & upload & use them but that requires some more config.
- Datafill fields. Each geocoder is configured with 2 sets of fields to retain - 'retained_response_fields' - these overwrite the exisitng fields for the address - usually latitude & longitude
  'data fill fields' - these are added to the existing fields if the existing field is not set.
- other providers from https://github.com/geocoder-php/Geocoder#providers can be added easily  



Next steps
1) get the zip_code based geocoding working
4) Look at improving provider failover per https://github.com/geocoder-php/Geocoder#the-chain-provider
5) ohhh caching https://github.com/geocoder-php/Geocoder/blob/master/docs/cookbook/cache.md
6) make geocoders configurable - the form at /civicrm/a/#/geocoders
currently only gives view access. I'm committed to extending the form based on the metadata rather than hard-coding & have added 'help_text' & 'user_editable_fields' to the entity specs. The plan is to expose these via getfields & then use them to drive the form. I'd like a cool way to manage weight.

Also of interest
- library supports ip address geocoding