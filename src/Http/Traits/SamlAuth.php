<?php

namespace KingStarter\LaravelSaml\Http\Traits;

use Storage;
use Illuminate\Http\Request;
use LightSaml\Model\Protocol\Response as Response;
use LightSaml\Credential\X509Certificate;

// For debug purposes, include the Log facade
use Illuminate\Support\Facades\Log;

trait SamlAuth
{
    /*
    |--------------------------------------------------------------------------
    | File handling (metadata, certificates)
    |--------------------------------------------------------------------------
    */
    
    /**
     * Get either the url or the content of a given file.
     */    
    protected function getSamlFile($configPath, $url) {
        if ($url)
            return Storage::disk('saml')->url($configPath);
        return Storage::disk('saml')->get($configPath);
    }    
    
    /**
     * Get either the url or the content of the saml metadata file.
     *
     * @param boolean url   Set to true to get the metadata url, otherwise the
     *                      file content will be returned. Defaults to false.   
     * @return String with either the url or the content
     */
    protected function metadata($url = false) {
        return $this->getSamlFile(config('saml.idp.metadata'), $url);
    }
    
    /**
     * Get either the url or the content of the certificate file.
     *
     * @param boolean url   Set to true to get the certificate url, otherwise the
     *                      file content will be returned. Defaults to false.   
     * @return String with either the url or the content
     */
    protected function certfile($url = false) {
        return $this->getSamlFile(config('saml.idp.cert'), $url);
    }

    /**
     * Get either the url or the content of the certificate keyfile.
     *
     * @param boolean url   Set to true to get the certificate key url, otherwise
     *                      the file content will be returned. Defaults to false.   
     * @return String with either the url or the content
     */
    protected function keyfile($url = false) {
        return $this->getSamlFile(config('saml.idp.key'), $url);
    }
    
    /*
    |--------------------------------------------------------------------------
    | Saml authentication
    |--------------------------------------------------------------------------
    */    

    /**
     * Handle an http request as saml authentication request. Note that the method
     * should only be called in case a saml request is also included. 
     *
     * @param  \Illuminate\Http\Request  $request
     * @return void
     */    
    public function handleSamlLoginRequest($request) {
        if (isset($request->SAMLRequest)) {
            // Get and decode the SAML request
            $SAML = $request->SAMLRequest;
            $decoded = base64_decode($SAML);
            $xml = gzinflate($decoded);
            // Initiate context and authentication request object
            $deserializationContext = new \LightSaml\Model\Context\DeserializationContext();
            $deserializationContext->getDocument()->loadXML($xml);
            $authnRequest = new \LightSaml\Model\Protocol\AuthnRequest();
            $authnRequest->deserialize($deserializationContext->getDocument()->firstChild, $deserializationContext);
            // Generate the saml response (saml authentication attempt)
            $this->buildSamlResponse($authnRequest, $request);
        }
    }

    /**
     * Make a saml authentication attempt by building the saml response.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\RedirectResponse
     * @see https://www.lightsaml.com/LightSAML-Core/Cookbook/How-to-make-Response/
     * @see https://imbringingsyntaxback.com/implementing-a-saml-idp-with-laravel/
     */
    protected function buildSamlResponse($authnRequest, $request)
    {
        // Get corresponding destination and issuer configuration from SAML config file for assertion URL
        // Note: Simplest way to determine the correct assertion URL is a short debug output on first run
        $destination = config('saml.sp.'.base64_encode($authnRequest->getAssertionConsumerServiceURL()).'.destination');
        $issuer = config('saml.sp.'.base64_encode($authnRequest->getAssertionConsumerServiceURL()).'.issuer');

        // Load in both certificate and keyfile
        // The files are stored within a private storage path, this prevents from
        // making them accessible from outside  
        $x509 = new X509Certificate();
        $certificate = $x509->loadPem($this->certfile());
        // Load in keyfile content (last parameter determines of the first one is a file or its content)
        $privateKey = \LightSaml\Credential\KeyHelper::createPrivateKey($this->keyfile(), '', false);

        if (config('saml.debug_saml_request')) {
            Log::debug('<SamlAuth::buildSAMLResponse>');
            Log::debug('Assertion URL: ' . $authnRequest->getAssertionConsumerServiceURL());
            Log::debug('Assertion URL: ' . base64_encode($authnRequest->getAssertionConsumerServiceURL()));
            Log::debug('Destination: ' . $destination);
            Log::debug('Issuer: ' . $issuer);
            Log::debug('Certificate: ' . $this->certfile());
        }

        // Generate the response object
        $response = new \LightSaml\Model\Protocol\Response();
        $response
           ->addAssertion($assertion = new \LightSaml\Model\Assertion\Assertion())
            ->setID(\LightSaml\Helper::generateID())
            ->setIssueInstant(new \DateTime())
            ->setDestination($destination)
            ->setIssuer(new \LightSaml\Model\Assertion\Issuer($issuer))
            ->setStatus(new \LightSaml\Model\Protocol\Status(new \LightSaml\Model\Protocol\StatusCode(\LightSaml\SamlConstants::STATUS_SUCCESS)))
            ->setSignature(new \LightSaml\Model\XmlDSig\SignatureWriter($certificate, $privateKey))
        ;

        // We are responding with both the email and the username as attributes
        // TODO: Add here other attributes, e.g. groups / roles / permissions
        if(\Auth::check()){
            $email = \Auth::user()->email;
            $name  = \Auth::user()->name;
        }else {
            $email = $request['email'];
            $name  = 'Place Holder';
        }        
        
        // Generate the SAML assertion for the response xml object
        $assertion
            ->setId(\LightSaml\Helper::generateID())
            ->setIssueInstant(new \DateTime())
            ->setIssuer(new \LightSaml\Model\Assertion\Issuer($issuer))
            
            ->setSubject(
                    (new \LightSaml\Model\Assertion\Subject())
                        ->setNameID(new \LightSaml\Model\Assertion\NameID(
                            $email,
                            \LightSaml\SamlConstants::NAME_ID_FORMAT_EMAIL
                        ))
                    ->addSubjectConfirmation(
                            (new \LightSaml\Model\Assertion\SubjectConfirmation())
                           ->setMethod(\LightSaml\SamlConstants::CONFIRMATION_METHOD_BEARER)
                           ->setSubjectConfirmationData(
                                    (new \LightSaml\Model\Assertion\SubjectConfirmationData())
                                   ->setInResponseTo($authnRequest->getId())
                                   ->setNotOnOrAfter(new \DateTime('+1 MINUTE'))
                                   ->setRecipient($authnRequest->getAssertionConsumerServiceURL())
                                )
                        )
                )            
                ->setConditions(
                    (new \LightSaml\Model\Assertion\Conditions())
                        ->setNotBefore(new \DateTime())
                        ->setNotOnOrAfter(new \DateTime('+1 MINUTE'))
                        ->addItem(
                            new \LightSaml\Model\Assertion\AudienceRestriction([
                                config('saml.sp.'.base64_encode($authnRequest->getAssertionConsumerServiceURL()).'.audience_restriction', 
                                    $authnRequest->getAssertionConsumerServiceURL())])
                        )
                )
            ->addItem(
                    (new \LightSaml\Model\Assertion\AttributeStatement())
                    ->addAttribute(new \LightSaml\Model\Assertion\Attribute(
                            \LightSaml\ClaimTypes::EMAIL_ADDRESS,
                            $email
                        ))
                    ->addAttribute(new \LightSaml\Model\Assertion\Attribute(
                            \LightSaml\ClaimTypes::COMMON_NAME,
                            $name
                        ))
                )
            ->addItem(
                    (new \LightSaml\Model\Assertion\AuthnStatement())
                    ->setAuthnInstant(new \DateTime('-10 MINUTE'))
                    ->setSessionIndex('_some_session_index')
                    ->setAuthnContext(
                            (new \LightSaml\Model\Assertion\AuthnContext())
                           ->setAuthnContextClassRef(\LightSaml\SamlConstants::AUTHN_CONTEXT_PASSWORD_PROTECTED_TRANSPORT)
                        )
                )
            ;
            
            // Send out the saml response
            $this->sendSamlResponse($response);
    }

    /**
     * Send saml response object (print out)
     *
     * @param  \LightSaml\Model\Protocol\Response  $response
     * @return \Illuminate\Http\RedirectResponse
     */
    protected function sendSamlResponse(Response $response)
    {
        $bindingFactory = new \LightSaml\Binding\BindingFactory();
        $postBinding = $bindingFactory->create(\LightSaml\SamlConstants::BINDING_SAML2_HTTP_POST);
        $messageContext = new \LightSaml\Context\Profile\MessageContext();
        $messageContext->setMessage($response)->asResponse();
        /** @var \Symfony\Component\HttpFoundation\Response $httpResponse */
        $httpResponse = $postBinding->send($messageContext);
        print $httpResponse->getContent()."\n\n";
    }
}
