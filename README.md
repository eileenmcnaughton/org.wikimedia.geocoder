Geocoding for CiviCRM based on geocoder library

Requires - CiviCRM 5.x, php 5.6

Implementation of geocoder library (which itself supports multiple providers) https://github.com/geocoder-php/mapquest-provider. Only those that have been tested are enabled so far.

When an address is edited CiviCRM will obtain additional data from the geocoding provider
and save it to the civicrm_address database with the address. It will also geocode addresses
to be used as the focal point of proximity searches or for event maps.

Note that the terms of data use by geocoding providers varies and it is your responsibility
to understand and adhere to them.

Currently enabled geocoders are
- Open Street Maps - this is zero-config & is enabled as the default (lowest weight)on install if you have no existing mapping provider
- USZipGeocoder - this is enabled on install & has no config. It will work as a fallback for US addresses only.
- MapQuest - requires an API key to be used
- GoogleMaps - requires an API key to be used - this is enabled on install as the default if you 
already have google configured as your provider. However the Terms of service suggest it may not be a good choose https://support.google.com/code/answer/55180?hl=en
- GeoName DB geocoder - this requires that you get a sample dataset from geonames. I will require a developer or similar to tweak the download into an sql table. There is a sample dataset for New Zealand in the install directory & if loaded it will work for New Zealand.


Features
- Threshold standdown period. If the geocoding quota is hit for a provider it is not used
again until the standdown has expired. By default the standdown is 1 minute but it is configurable per geocoder instance.
- Provider fall over. If a provider is not valid (e.g because the quota was hit or it only does a
 particular country or it's required fields are not present) then the next geocoder (by weight) 
 will be used.
- Database table based geocoding. If you do not wish to interact with an external site then
a US zip table lookup is available (from CiviSpace). It is possible to download other datasets (e.g from geonames) & upload & use them but that requires some more config.
- Datafill fields. Each geocoder is configured with 2 sets of fields to retain - 'retained_response_fields' - these overwrite the existing fields for the address - usually latitude & longitude
  'data fill fields' - these are added to the existing fields if the existing field is not set.
- other providers from https://github.com/geocoder-php/Geocoder#providers can be added easily  



Next steps
1) make geocoders configurable - the form at /civicrm/a/#/geocoders
currently only gives view access. I'm committed to extending the form based on the metadata rather than hard-coding & have added 'help_text' & 'user_editable_fields' to the entity specs. The plan is to expose these via getfields & then use them to drive the form. I'd like a cool way to manage weight.
2) consider caching - for https providers - https://github.com/geocoder-php/Geocoder/blob/master/docs/cookbook/cache.md
For DB providers we could cache the result of each query. The downside is potential
large memory usage for little query gain if there is a big spread of postal codes.


Also of interest
- library supports ip address geocoding
