<?php
/*
 * Copyright (c) 2013, 2014 MasterCard International Incorporated
 * All rights reserved.
 * 
 * Redistribution and use in source and binary forms, with or without modification, are 
 * permitted provided that the following conditions are met:
 * 
 * Redistributions of source code must retain the above copyright notice, this list of 
 * conditions and the following disclaimer.
 * Redistributions in binary form must reproduce the above copyright notice, this list of 
 * conditions and the following disclaimer in the documentation and/or other materials 
 * provided with the distribution.
 * Neither the name of the MasterCard International Incorporated nor the names of its 
 * contributors may be used to endorse or promote products derived from this software 
 * without specific prior written permission.
 * THIS SOFTWARE IS PROVIDED BY THE COPYRIGHT HOLDERS AND CONTRIBUTORS "AS IS" AND ANY 
 * EXPRESS OR IMPLIED WARRANTIES, INCLUDING, BUT NOT LIMITED TO, THE IMPLIED WARRANTIES 
 * OF MERCHANTABILITY AND FITNESS FOR A PARTICULAR PURPOSE ARE DISCLAIMED. IN NO EVENT 
 * SHALL THE COPYRIGHT HOLDER OR CONTRIBUTORS BE LIABLE FOR ANY DIRECT, INDIRECT, 
 * INCIDENTAL, SPECIAL, EXEMPLARY, OR CONSEQUENTIAL DAMAGES (INCLUDING, BUT NOT LIMITED
 * TO, PROCUREMENT OF SUBSTITUTE GOODS OR SERVICES; LOSS OF USE, DATA, OR PROFITS; 
 * OR BUSINESS INTERRUPTION) HOWEVER CAUSED AND ON ANY THEORY OF LIABILITY, WHETHER 
 * IN CONTRACT, STRICT LIABILITY, OR TORT (INCLUDING NEGLIGENCE OR OTHERWISE) ARISING 
 * IN ANY WAY OUT OF THE USE OF THIS SOFTWARE, EVEN IF ADVISED OF THE POSSIBILITY OF 
 * SUCH DAMAGE.
 */


class Simplify_HTTP
{
    const DELETE = "DELETE";
    const GET = "GET";
    const POST = "POST";
    const PUT = "PUT";

    const HTTP_SUCCESS = 200;
    const HTTP_REDIRECTED = 302;
    const HTTP_UNAUTHORIZED = 401;
    const HTTP_NOT_FOUND = 404;
    const HTTP_NOT_ALLOWED = 405;
    const HTTP_BAD_REQUEST = 400;


    const JWS_NUM_HEADERS         = 7;
    const JWS_ALGORITHM           = 'HS256';
    const JWS_TYPE                = 'JWS';
    const JWS_HDR_UNAME           = 'uname';
    const JWS_HDR_URI             = 'api.simplifycommerce.com/uri';
    const JWS_HDR_TIMESTAMP       = 'api.simplifycommerce.com/timestamp';
    const JWS_HDR_NONCE           = 'api.simplifycommerce.com/nonce';
    const JWS_HDR_TOKEN           = 'api.simplifycommerce.com/token';
    const JWS_MAX_TIMESTAMP_DIFF  = 300; // 5 minutes in seconds

    static private $_validMethods = array(
        "post" => self::POST,
        "put" => self::PUT,
        "get" => self::GET,
        "delete" => self::DELETE);

    /**
     * @param string $url
     */
    private function request($url, $method, $authentication, $payload = '')
    {
        if ($authentication->publicKey == null) {
            throw new InvalidArgumentException('Must have a valid public key to connect to the API');
        }

        if ($authentication->privateKey == null) {
            throw new InvalidArgumentException('Must have a valid API key to connect to the API');
        }

        if (!array_key_exists(strtolower($method), self::$_validMethods)) {
            throw new InvalidArgumentException('Invalid method: '.strtolower($method));
        }

        $method = self::$_validMethods[strtolower($method)];

        $curl = curl_init();

        $options = array();

        $options[CURLOPT_URL] = $url;
        $options[CURLOPT_CUSTOMREQUEST] = $method;
        $options[CURLOPT_RETURNTRANSFER] = true;
        $options[CURLOPT_FAILONERROR] = false;

        $signature = $this->jwsEncode($authentication, $url, $payload, $method == self::POST || $method == self::PUT);

        if ($method == self::POST || $method == self::PUT) {
            $headers = array(
                 'Content-type: application/json'
            );
            $options[CURLOPT_POSTFIELDS] = $signature;
        } else {
            $headers = array(
                 'Authorization: JWS ' . $signature
            );
        }

        array_push($headers, 'Accept: application/json');
        $user_agent = 'PHP-SDK/' . Simplify_Constants::VERSION;
        if (Simplify::$userAgent != null) {
            $user_agent = $user_agent . ' ' . Simplify::$userAgent;
        }
        array_push($headers, 'User-Agent: ' . $user_agent);

        $options[CURLOPT_HTTPHEADER] = $headers;

        curl_setopt_array($curl, $options);

        $data = curl_exec($curl);
        $errno = curl_errno($curl);
        $status = curl_getinfo($curl, CURLINFO_HTTP_CODE);

        if ($data == false || $errno != CURLE_OK) {
            throw new Simplify_ApiConnectionException(curl_error($curl));
        }

        $object = json_decode($data, true);
                                     //'typ' => self::JWS_TYPE,
        $response = array('status' => $status, 'object' => $object);

        return $response;
        curl_close($curl);
    }

    /**
     * Handles Simplify API requests
     *
     * @param string $url
     * @param $method
     * @param $authentication
     * @param string $payload
     * @return mixed
     * @throws Simplify_AuthenticationException
     * @throws Simplify_ObjectNotFoundException
     * @throws Simplify_BadRequestException
     * @throws Simplify_NotAllowedException
     * @throws Simplify_SystemException
     */
    public function apiRequest($url, $method, $authentication, $payload = ''){

        $response = $this->request($url, $method, $authentication, $payload);

        $status = $response['status'];
        $object = $response['object'];

        if ($status == self::HTTP_SUCCESS) {
            return $object;
        }

        if ($status == self::HTTP_REDIRECTED) {
            throw new Simplify_BadRequestException("Unexpected response code returned from the API, have you got the correct URL?", $status, $object);
        } else if ($status == self::HTTP_BAD_REQUEST) {
            throw new Simplify_BadRequestException("Bad request", $status, $object);
        } else if ($status == self::HTTP_UNAUTHORIZED) {
            throw new Simplify_AuthenticationException("You are not authorized to make this request.  Are you using the correct API keys?", $status, $object);
        } else if ($status == self::HTTP_NOT_FOUND) {
            throw new Simplify_ObjectNotFoundException("Object not found", $status, $object);
        } else if ($status == self::HTTP_NOT_ALLOWED) {
            throw new Simplify_NotAllowedException("Operation not allowed", $status, $object);
        } else if ($status < 500) {
            throw new Simplify_BadRequestException("Bad request", $status, $object);
        }
        throw new Simplify_SystemException("An unexpected error has been raised.  Looks like there's something wrong at our end." , $status, $object);
    }

    /**
     * Handles Simplify OAuth requests
     *
     * @param string $url
     * @param string $payload
     * @param Simplify_Authentication $authentication
     * @return mixed
     * @throws Simplify_AuthenticationException
     * @throws Simplify_ObjectNotFoundException
     * @throws Simplify_BadRequestException
     * @throws Simplify_NotAllowedException
     * @throws Simplify_SystemException
     */
    public function oauthRequest($url, $payload, $authentication){

        $response = $this->request($url, Simplify_HTTP::POST, $authentication, $payload);

        $status = $response['status'];
        $object = $response['object'];

        if ($status == self::HTTP_SUCCESS) {
            return $object;
        }

        $error = $object['error'];
        $error_description = $object['error_description'];

        if ($status == self::HTTP_REDIRECTED) {
            throw new Simplify_BadRequestException("Unexpected response code returned from the API, have you got the correct URL?", $status, $object);
        } else if ($status == self::HTTP_BAD_REQUEST) {

            if ( $error == 'invalid_request'){
                throw new Simplify_BadRequestException("", $status, $this->buildOauthError('Error during OAuth request', $error, $error_description));
            }else if ( $error == 'unsupported_grant_type'){
                throw new Simplify_BadRequestException("", $status, $this->buildOauthError('Unsupported grant type in OAuth request', $error, $error_description));
            }else if ( $error == 'invalid_scope'){
                throw new Simplify_BadRequestException("", $status, $this->buildOauthError('Invalid scope in OAuth request', $error, $error_description));
            }else{
                throw new Simplify_BadRequestException("", $status, $this->buildOauthError('Unknown OAuth error', $error, $error_description));
            }

            //TODO:  build BadRequestException error JSON

        } else if ($status == self::HTTP_UNAUTHORIZED){

            if ( $error == 'access_denied'){
                throw new Simplify_AuthenticationException("", $status, $this->buildOauthError('Access denied for OAuth request', $error, $error_description));
            }else if ( $error == 'invalid_client'){
                throw new Simplify_AuthenticationException("", $status, $this->buildOauthError('Invalid client ID in OAuth request', $error, $error_description));
            }else if ( $error == 'unauthorized_client'){
                throw new Simplify_AuthenticationException("", $status, $this->buildOauthError('Unauthorized client in OAuth request', $error, $error_description));
            }else{
                throw new Simplify_AuthenticationException("", $status, $this->buildOauthError('Unknown authentication error', $error, $error_description));
            }

        } else if ($status < 500) {
            throw new Simplify_BadRequestException("Bad request", $status, $object);
        }
        throw new Simplify_SystemException("An unexpected error has been raised.  Looks like there's something wrong at our end." , $status, $object);
    }

    /**
     * @param Simplify_Authentication $authentication
     */
    public function jwsDecode($authentication, $hash)
    {
        if ($authentication->publicKey == null) {
            throw new InvalidArgumentException('Must have a valid public key to connect to the API');
        }

        if ($authentication->privateKey == null) {
            throw new InvalidArgumentException('Must have a valid API key to connect to the API');
        }

        if (!isset($hash['payload'])) {
            throw new InvalidArgumentException('Event data is Missing payload');
        }
        $payload = trim($hash['payload']);


        try {
            $parts = explode('.', $payload);
            if (count($parts) != 3) {
                $this->jwsAuthError("Incorrectly formatted JWS message");
            }

            $headerStr = $this->jwsUrlSafeDecode64($parts[0]);
            $bodyStr = $this->jwsUrlSafeDecode64($parts[1]);
            $sigStr = $parts[2];

            $url = null;
            if (isset($hash['url'])) {
                $url = $hash['url'];
            }
            $this->jwsVerifyHeader($headerStr, $url, $authentication->publicKey);

            $msg = $parts[0] . "." . $parts[1];
            if (!$this->jwsVerifySignature($authentication->privateKey, $msg, $sigStr)) {
                $this->jwsAuthError("JWS signature does not match");
            }

            return $bodyStr;

        } catch (ApiException $e) {
            throw $e;
        } catch (Exception $e) {
            $this->jwsAuthError("Exception during JWS decoding: " . $e);
        }
    }

    /**
     * @param string $payload
     * @param boolean $hasPayload
     */
    private function jwsEncode($authentication, $url, $payload, $hasPayload)
    {
        // TODO - better seeding of RNG
        $jws_hdr = array('typ' => self::JWS_TYPE,
                          'alg' => self::JWS_ALGORITHM,
                          'kid' => $authentication->publicKey,
                          self::JWS_HDR_URI => $url,
                          self::JWS_HDR_TIMESTAMP => sprintf("%u000", round(microtime(true))),
                          self::JWS_HDR_NONCE => sprintf("%u", mt_rand()),
    	);

        // add oauth token if provided
        if ( !empty($authentication->accessToken) ){
            $jws_hdr[self::JWS_HDR_TOKEN] = $authentication->accessToken;
        }

        $header = $this->jwsUrlSafeEncode64(json_encode($jws_hdr));

        if ($hasPayload) {
            $payload = $this->jwsUrlSafeEncode64($payload);
        } else {
            $payload = '';
        }

        $msg = $header . "." . $payload;
        return $msg . "." . $this->jwsSign($authentication->privateKey, $msg);
    }

    /**
     * @param string $msg
     */
    private function jwsSign($privateKey, $msg) {
        $decodedPrivateKey = $this->jwsUrlSafeDecode64($privateKey);
        $sig = hash_hmac('sha256', $msg, $decodedPrivateKey, true);

        return $this->jwsUrlSafeEncode64($sig);
    }

    /**
     * @param string $header
     */
    private function jwsVerifyHeader($header, $url, $publicKey) {

	    $hdr = json_decode($header, true);

	    if (count($hdr) != self::JWS_NUM_HEADERS) {
		    $this->jwsAuthError("Incorrect number of JWS header parameters - found " . count($hdr) . " required " . self::JWS_NUM_HEADERS);
        }

	    if ($hdr['alg'] != self::JWS_ALGORITHM) {
		    $this->jwsAuthError("Incorrect algorithm - found " . $hdr['alg'] . " required " . self::WS_ALGORITHM);
        }

	    if ($hdr['typ'] != self::JWS_TYPE) {
		    $this->jwsAuthError("Incorrect type - found " . $hdr['typ'] . " required " . self::JWS_TYPE);
        }

	    if ($hdr['kid'] == null) {
		    $this->jwsAuthError("Missing Key ID");
	    }

	    if ($hdr['kid'] != $publicKey) {
	        if ($this->isLiveKey($publicKey)) {
	            $this->jwsAuthError("Invalid Key ID");
            }
        }

	    if ($hdr[self::JWS_HDR_URI] == null) {
		    $this->jwsAuthError("Missing URI");
        }

	    if ($url != null && $hdr[self::JWS_HDR_URI] != $url) {
	        $this->jwsAuthError("Incorrect URL - found " . $hdr[self::JWS_HDR_URI] . " required " . $url);
        }


	    if ($hdr[self::JWS_HDR_TIMESTAMP] == null) {
		    $this->jwsAuthError("Missing timestamp");
        }

	    if (!$this->jwsVerifyTimestamp($hdr[self::JWS_HDR_TIMESTAMP])) {
		    $this->jwsAuthError("Invalid timestamp");
	    }

	    if ($hdr[self::JWS_HDR_NONCE] == null) {
		    $this->jwsAuthError("Missing nonce");
        }

	    if ($hdr[self::JWS_HDR_UNAME] == null) {
		    $this->jwsAuthError("Missing username");
        }
    }


    /**
     * @param string $msg
     */
    private function jwsVerifySignature($privateKey, $msg, $expectedSig) {
        return $this->jwsSign($privateKey, $msg) == $expectedSig;
    }

    /**
     * @param string $reason
     */
    private function jwsAuthError($reason) {
        throw new Simplify_AuthenticationException("JWS authentication failure: " . $reason);
    }

    private function jwsVerifyTimestamp($ts) {
        $now = round(microtime(true)); // Seconds
        return abs($now - $ts / 1000) < self::JWS_MAX_TIMESTAMP_DIFF;
    }

    private function isLiveKey($k) {
        return strpos($k, "lvpb") === 0;
    }

    /**
     * @param string $s
     */
    private function jwsUrlSafeEncode64($s) {
        return str_replace(array('+', '/', '='),
                           array('-', '_', ''),
                           base64_encode($s));
    }

    private function jwsUrlSafeDecode64($s) {

        switch (strlen($s) % 4) {
            case 0: break;
            case 2: $s = $s . "==";
                    break;
            case 3: $s = $s . "=";
                    break;
            default: throw new InvalidArgumentException('incorrecly formatted JWS payload');
        }
        return base64_decode(str_replace(array('-', '_'), array('+', '/'), $s));
    }

    /**
     * @param string $msg
     */
    private function buildOauthError($msg, $error, $error_description){

        return array(
            'error' => array(
                'code' => 'oauth_error',
                'message' => $msg.', error code: '.$error.', description: '.$error_description.''
            )
        );
    }
}
