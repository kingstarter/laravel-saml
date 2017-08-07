<?php

/**
 * Filesystem config update
 */

return [

    /*
    |--------------------------------------------------------------------------
    | Filesystem Disks
    |--------------------------------------------------------------------------
    |
    | Add an entry for the saml storage path to the disks array. The config 
    | entry will be added using the mergeConfigForm method.
    |
    */

    'disks' => [
        
        'saml' => [
            'driver' => 'local',
            'root' => storage_path().'/saml',
        ],

    ],

];
