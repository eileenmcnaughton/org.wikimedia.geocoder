
## UK Postcode geocoder

- This *only* goes on postcodes which it looks up from a database (so no online service, no fees, limits or latency).

- It can handle *and correct* postcodes with spaces missing/in wrong places.

- It is not installed by default because of the size of the data.

### Data License

Due to licensing, only GB's postcodes are included, not the UK's. (i.e. no
Northern Ireland postcodes are included.) You may be permitted to add NI
postcodes to your local database, if you can get them, but they can't be
distributed as part of this extension.

The data came from https://www.getthedata.com/open-postcode-geo

> Open Postcode Geo is derived from the ONS Postcode Directory.
>
> From the ONS:
>
> http://www.ons.gov.uk/methodology/geography/licences
>
> Our postcode products (derived from Code-Point(R) Open) are subject to the Open Government Licence and the Ordnance Survey OpenData Licence.
>
> - Contains OS data (c) Crown copyright and database right 2021
> - Contains Royal Mail data (c) Royal Mail copyright and database right 2021
> - Contains National Statistics data (c) Crown copyright and database right 2021

### Downloading the data

1. grab the .sql.gz file from https://www.getthedata.com/open-postcode-geo and unzip it.
2. Edit the table name to `civicrm_open_postcode_geo_uk` (you can do this with `sed -i 's/open_postcode_geo/civicrm_open_postcode_geo_uk/g' /path/to/open_postcode_geo.sql`)
3. Import the data into your CiviCRM database
4. Run SQL like this:
    ```sql
    ALTER TABLE civicrm_open_postcode_geo_uk
    DROP `status`,
    DROP usertype,
    DROP easting,
    DROP northing,
    DROP positional_quality_indicator,
    DROP country,
    DROP postcode_fixed_width_seven,
    DROP postcode_fixed_width_eight,
    DROP postcode_area,
    DROP postcode_district,
    DROP postcode_sector,
    DROP outcode,
    DROP incode,
    DROP KEY IF EXISTS postcode_no_space,
    ADD PRIMARY KEY (postcode_no_space);
    ```
5. Enable the UK Postcode geocoder. Not sure if there's a UI for this, but you can do it via the API (v3).
