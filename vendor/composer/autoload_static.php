<?php

// autoload_static.php @generated by Composer

namespace Composer\Autoload;

class ComposerStaticInit028dbade44ba9a9c4c7eb74c2f9f757a
{
    public static $files = array (
        '9c67151ae59aff4788964ce8eb2a0f43' => __DIR__ . '/..' . '/clue/stream-filter/src/functions_include.php',
        '8cff32064859f4559445b89279f3199c' => __DIR__ . '/..' . '/php-http/message/src/filters.php',
    );

    public static $prefixLengthsPsr4 = array (
        'H' => 
        array (
            'Http\\Promise\\' => 13,
            'Http\\Message\\' => 13,
            'Http\\Discovery\\' => 15,
            'Http\\Client\\' => 12,
            'Http\\Adapter\\Guzzle6\\' => 21,
        ),
        'G' => 
        array (
            'Geocoder\\Provider\\Nominatim\\' => 28,
            'Geocoder\\Provider\\MapQuest\\' => 27,
            'Geocoder\\Provider\\Here\\' => 23,
            'Geocoder\\Provider\\GoogleMaps\\' => 29,
            'Geocoder\\Provider\\FreeGeoIp\\' => 28,
            'Geocoder\\Provider\\DataTable\\' => 28,
            'Geocoder\\Provider\\Addok\\' => 24,
            'Geocoder\\Http\\' => 14,
            'Geocoder\\' => 9,
        ),
        'C' => 
        array (
            'Clue\\StreamFilter\\' => 18,
        ),
    );

    public static $prefixDirsPsr4 = array (
        'Http\\Promise\\' => 
        array (
            0 => __DIR__ . '/..' . '/php-http/promise/src',
        ),
        'Http\\Message\\' => 
        array (
            0 => __DIR__ . '/..' . '/php-http/message/src',
            1 => __DIR__ . '/..' . '/php-http/message-factory/src',
        ),
        'Http\\Discovery\\' => 
        array (
            0 => __DIR__ . '/..' . '/php-http/discovery/src',
        ),
        'Http\\Client\\' => 
        array (
            0 => __DIR__ . '/..' . '/php-http/httplug/src',
        ),
        'Http\\Adapter\\Guzzle6\\' => 
        array (
            0 => __DIR__ . '/..' . '/php-http/guzzle6-adapter/src',
        ),
        'Geocoder\\Provider\\Nominatim\\' => 
        array (
            0 => __DIR__ . '/..' . '/geocoder-php/nominatim-provider',
        ),
        'Geocoder\\Provider\\MapQuest\\' => 
        array (
            0 => __DIR__ . '/..' . '/geocoder-php/mapquest-provider',
        ),
        'Geocoder\\Provider\\Here\\' => 
        array (
            0 => __DIR__ . '/..' . '/geocoder-php/here-provider',
        ),
        'Geocoder\\Provider\\GoogleMaps\\' => 
        array (
            0 => __DIR__ . '/..' . '/geocoder-php/google-maps-provider',
        ),
        'Geocoder\\Provider\\FreeGeoIp\\' => 
        array (
            0 => __DIR__ . '/..' . '/geocoder-php/free-geoip-provider',
        ),
        'Geocoder\\Provider\\DataTable\\' => 
        array (
            0 => __DIR__ . '/..' . '/wikimedia/civicrm-data-table-provider',
        ),
        'Geocoder\\Provider\\Addok\\' => 
        array (
            0 => __DIR__ . '/..' . '/geo6/geocoder-php-addok-provider',
        ),
        'Geocoder\\Http\\' => 
        array (
            0 => __DIR__ . '/..' . '/geocoder-php/common-http',
        ),
        'Geocoder\\' => 
        array (
            0 => __DIR__ . '/..' . '/willdurand/geocoder',
        ),
        'Clue\\StreamFilter\\' => 
        array (
            0 => __DIR__ . '/..' . '/clue/stream-filter/src',
        ),
    );

    public static $classMap = array (
        'Composer\\InstalledVersions' => __DIR__ . '/..' . '/composer/InstalledVersions.php',
    );

    public static function getInitializer(ClassLoader $loader)
    {
        return \Closure::bind(function () use ($loader) {
            $loader->prefixLengthsPsr4 = ComposerStaticInit028dbade44ba9a9c4c7eb74c2f9f757a::$prefixLengthsPsr4;
            $loader->prefixDirsPsr4 = ComposerStaticInit028dbade44ba9a9c4c7eb74c2f9f757a::$prefixDirsPsr4;
            $loader->classMap = ComposerStaticInit028dbade44ba9a9c4c7eb74c2f9f757a::$classMap;

        }, null, ClassLoader::class);
    }
}
