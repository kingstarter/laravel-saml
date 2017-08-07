<?php

/**
 * This file is part of laravel-saml,
 * a SAML IDP integration for laravel. 
 *
 * @license MIT
 * @package kingstarter/laravel-saml
 */

return [

    /*
    |--------------------------------------------------------------------------
    | Base settings
    |--------------------------------------------------------------------------
    |
    | General package settings
    |
    */

    // Include the pre-defined routes from package or not.
    'use_package_routes' => true,
    
    // Allow debugging within SamlAuth trait to get SP data  
    // during SAML authentication request
    'debug_saml_request' => false,

    /*
    |--------------------------------------------------------------------------
    | IDP (identification provider) settings
    |--------------------------------------------------------------------------
    |
    | Set overall configuration for laravel as idp server.
    |
    | All files are in storage/saml and referenced via Storage::disk('saml') 
    | as root directory. To have a valid storage configuration, add the root  
    | path to the config/filesystem.php file.
    |
    */
    
    'idp' => [
        'metadata'  => 'idp/metadata.xml',
        'cert'      => 'idp/cert.pem',
        'key'       => 'idp/key.pem',
    ],

    /*
    |--------------------------------------------------------------------------
    | SP (service provider) settings
    |--------------------------------------------------------------------------
    |
    | Array of service provider data. Add your list of SPs here.
    |
    | An SP is defined by its consumer service URL which is base64 encoded. 
    | It contains the destination, issuer, cert and cert-key. 
    |
    */

    'sp' => [        
        
        /**
         * Sample SP entry
         * The entry is identified by the base64 encoded URL. This example shows a possible entry for
         * a SimpleSamlPhp service provider running on localhost:
         * 
         * Sample URL:         https://localhost/samlsp/module.php/saml/sp/saml2-acs.php/default-sp
         * Base64 encoded URL: aHR0cHM6Ly9sb2NhbGhvc3Qvc2FtbHNwL21vZHVsZS5waHAvc2FtbC9zcC9zYW1sMi1hY3MucGhwL2RlZmF1bHQtc3A=
         *
         * Note: Make sure that the base64 encoding works correctly. The linux terminal base64 cmd seems either to add or 
         *       skip a char at the end, though the base64 string is incorrect. In case of doubt, use the debug method 
         *       within the laravel-saml/src/Http/Traits/SamlAuth.php file to obtain the correct encoding when trying to
         *       authenticate. The debug output will show nothing for destination and issuer if it cannot find any entry
         *       here. Another possibility is the page base64encode.org which seems to work correctly.
         */
        'aHR0cHM6Ly9sb2NhbGhvc3Qvc2FtbHNwL21vZHVsZS5waHAvc2FtbC9zcC9zYW1sMi1hY3MucGhwL2RlZmF1bHQtc3A=' => [
            // The destination is the consuming SAML URL. This might be a SamlAuthController receiving the SAML response.  
            'destination' => 'https://localhost/samlsp/module.php/saml/sp/saml2-acs.php/default-sp',
            // Issuer could be anything, mostly it makes sense to pass the metadata URL
            'issuer' => 'http://localhost/saml/idp/metadata',
        ],
        
    ],
    
];
