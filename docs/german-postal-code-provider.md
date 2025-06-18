## German Postal Code geocoder

- Germany’s postal code(s) are known as ‘Postleitzahl(en)’, acronym PLZ.

- This provider derives coordinates from German PLZs *only*, so it is not very precise, but good enough for many purposes. It installs the required data as a local SQL table, so there are no online service, no fees, limits or latency.

- It can handle *and correct* postal codes with spaces missing/in wrong places.

- If the address contains a valid postal code, the geocodes, the city, and the federal state is filled (if empty)

- This Geocoding provider is not installed by default.

### Installation for German dataset.

1. The postcode-geo data is located in: org.wikimedia.geocoder/sql/PLZ.de.sql
2. Import that file with the data into your CiviCRM database. This will create a new table named "civicrm_geocoder_plzde_dataset". If that table existed before, it will be dropped.
3. Enable the UK Postcode geocoder by executing an SQL statement like this:
   ```sql
   INSERT INTO civicrm_geocoder SET
   name = 'de_plz',
   title = 'DE Postleitzahlen',
   class = 'DEPlzProvider',
   is_active = 1,
   weight = (SELECT MAX(weight) + 1 FROM civicrm_geocoder g),
   api_key = NULL,
   url = NULL,
   required_fields = '["postal_code"]',
   retained_response_fields = '["geo_code_1","geo_code_2"]',
   datafill_response_fields = '["city"]',
   threshold_standdown = 60,
   threshold_last_hit = NULL,
   valid_countries = '["DE"]';
   ```
   You may want to reassign weights for all geocoders registered in the `civicrm_geocoder` table.
