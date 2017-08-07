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

Add the service provider to ```config/app.php```

```
    KingStarter\LaravelSaml\LaravelSamlServiceProvider::class,
```

There is one configuration file to publish and the config/filesystem.php file that needs to be extended. The command
```
php artisan vendor:publish --tag="saml_config"
```
will publish the config/saml.php file. Within the saml.php config file the SAML Service Provider array needs to be filled. An example for a local address is given.

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

The SAMLRequest field will be filled automatically when a SAMLRequest is send by an http request and therefore initiate a SAML authentication attempt. To initiate the SAML auth, the login and redirect functions need to be modified. Within ```app/Http/Middleware/AuthenticatesUsers.php``` add following lines to both the top and the authenticated function: 
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

