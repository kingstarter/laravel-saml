<?php

namespace KingStarter\LaravelSaml\Http\Controllers;

use Illuminate\Routing\Controller as Controller;
use Storage;
use KingStarter\LaravelSaml\Http\Traits\SamlAuth;

class SamlIdpController extends Controller 
{
    use SamlAuth;
    
    // This includes the controller routing points for 
    // - metadata
    // - certfile
    // - keyfile (this one should be used only for authenticated users)
}