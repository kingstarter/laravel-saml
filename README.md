# Laravel SAML

Laravel-SAML adds SAML support to make a laravel application to both a SAML identity provider (IDP) and a SAML service provider (SP). The package is designed to work with Laravel 5.4.

The package is mostly designed to function according to following guide:
https://imbringingsyntaxback.com/implementing-a-saml-idp-with-laravel/

To get a better basic understanding for SAML in general, read https://github.com/jch/saml

## Installation

### Basic package installation

Using ```composer```: 

``` 
composer require "kingstarter/laravel-saml":"dev-master"
```
#### Laravel 5.4
Add the service provider to ```config/app.php```

```
    KingStarter\LaravelSaml\LaravelSamlServiceProvider::class,
```
#### Laravel 5.5
This package supports Laravel's Package Auto Discovery and should be automatically loaded when required using composer. If the package is not auto discovered run

```bash
    php artisan package:discover
```
#### Configuration
There is one configuration file to publish and the config/filesystem.php file that needs to be extended. The command
```
php artisan vendor:publish --tag="saml_config"
```

will publish the config/saml.php file. 

#### SAML SP entries

Within the saml.php config file the SAML Service Provider array needs to be filled. Subsequently an example from the config/saml.php file:

```
'sp' => [        
    
    /**
     * Sample SP entry
     * The entry is identified by the base64 encoded URL. This example shows a possible entry for
     * a SimpleSamlPhp service provider running on localhost:
     * 
     * Sample URL:         https://localhost/samlsp/module.php/saml/sp/saml2-acs.php/default-sp
     * Base64 encoded URL: aHR0cHM6Ly9sb2NhbGhvc3Qvc2FtbHNwL21vZHVsZS5waHAvc2FtbC9zcC9zYW1sMi1hY3MucGhwL2RlZmF1bHQtc3A=
     */
    'aHR0cHM6Ly9sb2NhbGhvc3Qvc2FtbHNwL21vZHVsZS5waHAvc2FtbC9zcC9zYW1sMi1hY3MucGhwL2RlZmF1bHQtc3A=' => [
    
        // The destination is the consuming SAML URL. This might be a SamlAuthController receiving the SAML response.  
        'destination' => 'https://localhost/samlsp/module.php/saml/sp/saml2-acs.php/default-sp',
        // Issuer could be anything, mostly it makes sense to pass the metadata URL
        'issuer' => 'https://localhost',
        
        // OPTIONAL: Use a specific audience restriction value when creating the SAMLRequest object.
        //           Default value is the assertion consumer service URL (the base64 encoded SP url). 
        //           This is a bugfix for Nextcloud as SP and can be removed for normal SPs.
        'audience_restriction' => 'http://localhost',
    ],
    
],
```

You can generate the base_64 encoded AssertionURL by using the following artisan command.
 
```bash
   $ php artisan laravel-saml:encodeurl https://sp.webapp.com/saml/login
   --
   URL Given: https://sp.webapp.com/saml/login
   Encoded AssertionURL:aHR0cHM6Ly9zcC53ZWJhcHAuY29tL3NhbWwvbG9naW4=
```

config/saml.php:
```
'sp' => [        
    
     ...

    /**
     * New entry
     * 
     * Sample URL:         https://sp.webapp.com/saml/login
     * Base64 encoded URL: aHR0cHM6Ly9zcC53ZWJhcHAuY29tL3NhbWwvY29uc3VtZQ==
     */
    'aHR0cHM6Ly9zcC53ZWJhcHAuY29tL3NhbWwvY29uc3VtZQ==' => [
        'destination' => 'https://sp.webapp.com/saml/consume',
        'issuer'      => 'https://sp.webapp.com',
    ],
],
```

#### FileSystem configuration 

Within ```config/filesystem.php``` following entry needs to be added:
```
    'disks' => [

        ...
        
        'saml' => [
            'driver' => 'local',
            'root' => storage_path().'/saml',
        ],

    ],
```

The package controllers are using the ```storage/saml``` path for retrieving both certificates and the metadata file. Create first the storage path, then either add or link the certificates. Add also a metadata file for the SAML IDP. For help generating an IDP metadata.xml file, see https://www.samltool.com/idp_metadata.php.

```
mkdir -p storage/saml/idp
touch storage/saml/{metadata.xml,cert.pem,key.pem}
```

Add the contents to the metadata.xml, cert.pem and key.pem files for the IDP. 

### Using the SAML package

To use the SAML package, some files need to be modified. Within your login view, problably ```resources/views/auth/login.blade.php``` add a SAMLRequest field beneath the CSRF field (this is actually a good place for it):
```
    {{-- The hidden CSRF field for secure authentication --}}
    {{ csrf_field() }}
    {{-- Add a hidden SAML Request field for SAML authentication --}}
    @if(isset($_GET['SAMLRequest']))
        <input type="hidden" id="SAMLRequest" name="SAMLRequest" value="{{ $_GET['SAMLRequest'] }}">
    @endif
```

The SAMLRequest field will be filled automatically when a SAMLRequest is sent by a http request and therefore initiate a SAML authentication attempt. To initiate the SAML auth, the login and redirect functions need to be modified. Within ```app/Http/Middleware/AuthenticatesUsers.php``` add following lines to both the top and the authenticated function: 
(NOTE: you might need to copy it out from vendor/laravel/framework/src/Illuminate/Foundation/Auth/ to your Middleware directory) 

```
<?php

namespace App\Http\Middleware;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Foundation\Auth\RedirectsUsers;
use Illuminate\Foundation\Auth\ThrottlesLogins;

use KingStarter\LaravelSaml\Http\Traits\SamlAuth;

trait AuthenticatesUsers
{
    use RedirectsUsers, ThrottlesLogins, SamlAuth;
    
    ...

    protected function authenticated(Request $request, $user)
    {
        if(Auth::check() && isset($request['SAMLRequest'])) {
            $this->handleSamlLoginRequest($request);
        }
    }
    
    ...
```

To allow later direct redirection when somebody is already logged in, we need to add also some lines to ```app/Http/Middleware/RedirectIfAuthenticated.php```:
```
<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Support\Facades\Auth;

use KingStarter\LaravelSaml\Http\Traits\SamlAuth;

class RedirectIfAuthenticated
{
    use SamlAuth;
    
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @param  string|null  $guard
     * @return mixed
     */
    public function handle($request, Closure $next, $guard = null)
    {
        if(Auth::check() && isset($request['SAMLRequest'])){  
            $this->handleSamlLoginRequest($request);
        }
        if (Auth::guard($guard)->check()) {
            return redirect('/home');
        }
        return $next($request);
    }
}
```

## SAML Service Providers

To add one or more service providers, go to the ```config/saml.php``` configuration file and scroll down to the 'sp' array. Having the Login-Address of the SAML-SP, add another entry. For reasons of internal interpretation, the URL needs to be Base64 encoded. In case that there are some problems receiving the Base64 string, it is always possible to use the debugger setting the ```saml.debug_saml_request``` flag within the config file. Make sure that the environmental logging variable ```APP_LOG_LEVEL``` is set to debug.

