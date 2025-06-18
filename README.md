# Geocoding for CiviCRM based on geocoder library

Documentation is moving to https://docs.civicrm.org/geocoder/en/latest/

**You will not see all the documentation if you are viewing the readme page directly**

Implementation of geocoder library (which itself supports multiple providers) https://github.com/geocoder-php/mapquest-provider. Only those that have been tested are enabled so far.

When an address is edited CiviCRM will obtain additional data from the geocoding provider
and save it to the `civicrm_address` database with the address. It will also geocode addresses
to be used as the focal point of proximity searches or for event maps.

Note that the terms of data use by geocoding providers variesand it is your responsibility
to understand and adhere to them.

## Available geocoders

- OpenStreetMap - this is zero-config & is enabled as the default (lowest weight) on install if you have no existing mapping provider
- USZipGeocoder - this is enabled on install & has no config. It will work as a fallback for US addresses only.
- UK Postcodes - see below
- MapQuest - requires an API key to be used
- GoogleMaps - requires an API key to be used - this is enabled on install as the default if you
already have google configured as your provider. However, the Terms of service suggest it may not be a good choose https://support.google.com/code/answer/55180?hl=en
- GeoName DB geocoder - this requires that you get a sample dataset from geonames. I will require a developer or similar to tweak the download into an sql table. There is a sample dataset for New Zealand in the install directory & if loaded it will work for New Zealand.
- Here (not enabled by default)
- Addok (not enabled by default)
- German Postal code - see below

### Requires
  - CiviCRM 6.3
  - php 8.1
  - Smarty 5

### Features

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

### Installation

After installing the extension Geocoding will be enabled with the OpenStreetMap geocoder.
You can enable or disable geocoders and undertake minor edits to the geocoders
under Administer->Localization->Geocoders. Depending on the geocoder
this might involve editing a url or api key but several of the geocoders
require you to load data directly in mysql, which you may not be able to do
without server admin help.

### Future development

1) consider caching - for https providers - https://github.com/geocoder-php/Geocoder/blob/master/docs/cookbook/cache.md
For DB providers we could cache the result of each query. The downside is potential
large memory usage for little query gain if there is a big spread of postal codes.
2) consider the library support of ip address geocoding
