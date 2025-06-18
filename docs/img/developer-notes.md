As of the 1.4 release there are some metadata & field use changes. These are
best illustrated by examples.

- the arguments that can be defined in the metadata (to instantiate the provider class) are expanded
  e.g in OpenStreetMap the following
```
    'metadata' => [
      'argument' => ['geocoder.url', 'server.User-Agent:CiviCRM', 'server.Referrer'],
  ],
```

means to pass
1) geocoder metadata parameter (in this case from the field) 'url'
2) $_SERVER parameter 'User-Agent' with a default of 'CiviCRM'
3) $_SERVER parameter 'Referrer'

For the US ZIP Geocoder
```
      'argument' => ['pass_through' => [
        'tableName' => 'civicrm_geocoder_zip_dataset',
        'columns' => ['city', 'state_code', 'latitude', 'longitude', 'timezone'],
      ]],
```
means to pass
1) The value under the key 'pass_through' (or any key starting with that string) directly.

For the Here provider
```
    'metadata' => [
      'argument' => ['api_key.app_id', 'api_key.app_code'],
```
means to pass
1) app_id from the json_encoded array stored in the DB field api_key.
2) app_code from the json_encoded array stored in the DB field api_key.

Also from 1.4 the api_key field is used for a flat parameter - ie
api_key = xyz
or multipart user data - ie
api_key = {'app_id' : 'xy', 'app_code' : 'z'}
