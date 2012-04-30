<?php
/**
 *	CLASS: Share Your Cart API
 *	AUTHOR: Barandi Solutions
 *	COUNTRY: Romania
 *	EMAIL: catalin.paun@barandisolutions.ro
 *	VERSION : 1.0
 *	DESCRIPTION: This class is used as a wrapper to communicate to the ShareYourCart API.
 * *    Copyright (C) 2011 Barandi Solutions
 */

if(!class_exists('ShareYourCartAPI',false)){
 
class ShareYourCartAPI {
    
    protected     $SHAREYOURCART_URL = "www.shareyourcart.com";
	protected	  $SHAREYOURCART_SANDBOX_URL = "sandbox.shareyourcart.com";
    protected     $SHAREYOURCART_API;
    protected     $SHAREYOURCART_API_REGISTER;
    protected     $SHAREYOURCART_API_RECOVER;
    protected     $SHAREYOURCART_API_ACTIVATE;
    protected     $SHAREYOURCART_API_DEACTIVATE;
    protected     $SHAREYOURCART_API_CREATE;
    protected     $SHAREYOURCART_API_VALIDATE;
	protected     $SHAREYOURCART_API_STATUS;
    protected     $SHAREYOURCART_CONFIGURE;
    protected     $SHAREYOURCART_BUTTON_JS;
	protected     $SHAREYOURCART_BUTTON_URL;
    
    /**
    * Constructor
    * @param null
    */
    function __construct() {
        
		$is_live = true;
		
		//check if the current object has a secret key function
		//this is for backward compatibility
		if(method_exists ($this,'getSecretKey')){
			$secretKey = $this->getSecretKey();
			if(empty($secretKey)){
				throw new Exception("You must specify a valid secret key");
			}
			
			//check if the secret key is a sandbox one
			if(strpos($secretKey,"sndbx_") === 0){
				$this->SHAREYOURCART_URL = $this->SHAREYOURCART_SANDBOX_URL;
				$is_live = false;
			}
		}
		
        $this->SHAREYOURCART_API            = (isset($_SERVER['HTTPS']) && !strcasecmp($_SERVER['HTTPS'],'on') ? 'https://' : 'http://') . $this->SHAREYOURCART_URL;
        $this->SHAREYOURCART_REGISTER       = $this->SHAREYOURCART_API.'/account/create';
        $this->SHAREYOURCART_API_REGISTER   = $this->SHAREYOURCART_API.'/account/create';
        $this->SHAREYOURCART_API_RECOVER    = $this->SHAREYOURCART_API.'/account/recover';
        $this->SHAREYOURCART_API_ACTIVATE   = $this->SHAREYOURCART_API.'/account/activate';
        $this->SHAREYOURCART_API_DEACTIVATE = $this->SHAREYOURCART_API.'/account/deactivate';
        $this->SHAREYOURCART_API_CREATE     = $this->SHAREYOURCART_API.'/session/create';
        $this->SHAREYOURCART_API_VALIDATE   = $this->SHAREYOURCART_API.'/session/validate';
        $this->SHAREYOURCART_API_STATUS     = $this->SHAREYOURCART_API.'/sdk';
        $this->SHAREYOURCART_CONFIGURE      = $this->SHAREYOURCART_API.'/configure';   
		$this->SHAREYOURCART_BUTTON_JS      = $this->SHAREYOURCART_API.'/js/'.($is_live ? 'button.js' : 'button_sandbox.js');
		$this->SHAREYOURCART_BUTTON_URL     = $this->SHAREYOURCART_API.'/button';
    }
    
    /**
    * startSession
    * @param array $params
    * @return array $data  
    */
    public function startSession($params) {
	//make sure the session is started
	if(session_id() == '')
            session_start();
        
        $session = curl_init($this->SHAREYOURCART_API_CREATE);

        // Tell curl to use HTTP POST
        curl_setopt($session, CURLOPT_POST, true);
        // Tell curl that this is the body of the POST
        curl_setopt($session, CURLOPT_POSTFIELDS, http_build_query($params,'','&'));
        // Tell curl not to return headers, but do return the response
        curl_setopt($session, CURLOPT_HEADER, false);
        curl_setopt($session, CURLOPT_RETURNTRANSFER, true);

        $response = curl_exec($session);
        $httpCode = curl_getinfo($session, CURLINFO_HTTP_CODE);
        curl_close($session);
        
        // If the operation was not succesfull, print the error
        if($httpCode != 200) 
            throw new Exception($response);
        
        // Decode the result
        $results = json_decode($response, true);
        
        // Find the token
        if(isset($results['token'])) {
            
            // Link the token with the current cart ( held in session id )
            $data = array(
                'token' => $results['token'],
                'session_id' => session_id(),
            );
                
            // A token was obtained, so redirect the browser
            header("Location: $results[session_url]", true, 302);
            return $data;
                
        }
        
        //show the raw response received ( for debug purposes )
        throw new Exception($response);
    }
    
    /**
    * make sure the coupon is valid
    * @param null 
    */
    public function assertCouponIsValid($token, $coupon_code, $coupon_value, $coupon_type) {
        
        // Verifies POST information
        if(!isset($token, $coupon_code, $coupon_value, $coupon_type)) {
               
            throw new Exception(SyC::t('sdk',"At least one of the parameters is missing."));
        }
        
        // Urlencode and concatenate the POST arguments
        $params = array(
            'token' => $token,
            'coupon_code' => $coupon_code,
            'coupon_value' => $coupon_value,
            'coupon_type'=> $coupon_type,
        );

        // Make the API call
        $session = curl_init($this->SHAREYOURCART_API_VALIDATE);

        // Tell curl to use HTTP POST
        curl_setopt($session, CURLOPT_POST, true);
        // Tell curl that this is the body of the POST
        curl_setopt($session, CURLOPT_POSTFIELDS, http_build_query($params,'','&'));
        // Tell curl not to return headers, but do return the response
        curl_setopt($session, CURLOPT_HEADER, false);
        curl_setopt($session, CURLOPT_RETURNTRANSFER, true);

        $response = curl_exec($session);
        $httpCode = curl_getinfo($session, CURLINFO_HTTP_CODE);
        curl_close($session);
        
        //if the operation was not succesfull, print the error
		if($httpCode != 200) 
            throw new Exception(SyC::t('sdk',"Coupon Invalid: {coupon}", array('{coupon}' => $response)));
        
        $results = json_decode($response, true);    

        //if the result is not valid, print it
        if(!isset($results['valid']) || !($results['valid'] === true)) 
            throw new Exception(SyC::t('sdk',"Coupon Invalid: {coupon}", array('{coupon}' => $response)));
    }
    
    /**
    * register
    * @param string $secretKey
    * @param string $domain
    * @param string $email
    * @param string $message  
    * @return array json_decode  
    */
    public function register($secretKey, $domain, $email, &$message = null) {
        
        // Urlencode and concatenate the POST arguments
        $params = array(
                'secret_key' => $secretKey,
                'domain' => $domain,
                'email' => $email,
        );

        // Make the API call
        $session = curl_init($this->SHAREYOURCART_API_REGISTER);

        // Tell curl to use HTTP POST
        curl_setopt($session, CURLOPT_POST, true);
        // Tell curl that this is the body of the POST
        curl_setopt($session, CURLOPT_POSTFIELDS, http_build_query($params,'','&'));
        // Tell curl not to return headers, but do return the response
        curl_setopt($session, CURLOPT_HEADER, false);
        curl_setopt($session, CURLOPT_RETURNTRANSFER, true);

        $response = curl_exec($session);
        $httpCode = curl_getinfo($session, CURLINFO_HTTP_CODE);
        curl_close($session);
        
        // If the operation was not succesfull, return FALSE
        if($httpCode != 200) {
            if(isset($message)) $message = $response;
			
			return false;     
        }
		
		//if the caller is expecting a message
		//let him know what happened
		if(isset($message)){
		
			$message = SyC::t('sdk','The account has been registered');
		}
        
        // Return the response after decoding it
        return json_decode($response, true);    
        
    }
    
    /**
    * recover
    * @param string $secretKey
    * @param string $domain
    * @param string $email
    * @param string $message  
    * @return boolean
    */
    public function recover($secretKey, $domain, $email, &$message = null) {
        
        // Urlencode and concatenate the POST arguments
        $params = array(
            'secret_key' => $secretKey,
            'domain' => $domain,
            'email' => $email,
        );

        // Make the API call
        $session = curl_init($this->SHAREYOURCART_API_RECOVER);

        // Tell curl to use HTTP POST
        curl_setopt($session, CURLOPT_POST, true);
        // Tell curl that this is the body of the POST
        curl_setopt($session, CURLOPT_POSTFIELDS, http_build_query($params,'','&'));
        // Tell curl not to return headers, but do return the response
        curl_setopt($session, CURLOPT_HEADER, false);
        curl_setopt($session, CURLOPT_RETURNTRANSFER, true);

        $response = curl_exec($session);
        $httpCode = curl_getinfo($session, CURLINFO_HTTP_CODE);
        curl_close($session);
        
        //if the operation was not succesfull, return FALSE
        if($httpCode != 200) {
            if(isset($message)) $message = $response;
			
			return false;
        }
		
		//if the caller is expecting a message
		//let him know what happened
		if(isset($message)){
		
			$message = (!empty($response) ? $response : SyC::t('sdk',"An email has been sent with your credentials at {email}",array('{email}' => $email)));
		}
        
        return true;
        
    }
    
    /**
    * setAccountStatus
    * @param string $secretKey
    * @param string $clientId
    * @param string $appKey
    * @param string $activate
    * @param string $message  
    * @return boolean
    */
    public function setAccountStatus($secretKey, $clientId, $appKey, $activate = true, &$message = null) {
        
        // Urlencode and concatenate the POST arguments
        $params = array(
            'secret_key' => $secretKey,
            'client_id' => $clientId,
            'app_key' => $appKey,
        );

        //make the API call
        $session = curl_init($activate ? $this->SHAREYOURCART_API_ACTIVATE : $this->SHAREYOURCART_API_DEACTIVATE);

        // Tell curl to use HTTP POST
        curl_setopt($session, CURLOPT_POST, true);
        // Tell curl that this is the body of the POST
        curl_setopt($session, CURLOPT_POSTFIELDS, http_build_query($params,'','&'));
        // Tell curl not to return headers, but do return the response
        curl_setopt($session, CURLOPT_HEADER, false);
        curl_setopt($session, CURLOPT_RETURNTRANSFER, true);

        $response = curl_exec($session);
        $httpCode = curl_getinfo($session, CURLINFO_HTTP_CODE);
        curl_close($session);
        
        // Notify the caller
        if($httpCode != 200) {
            if(isset($message)) $message = $response;
			
			return false;
        }
		
		//if the caller is expecting a message
		//let him know what happened
		if(isset($message)){
		
			$message = (!empty($response) ? $response : ($activate ? SyC::t('sdk','The account has been enabled') : SyC::t('sdk','The account has been disabled')));
		}
        
        return true;
    }
	
	/**
    * setAccountStatus
    * @param string $secretKey
    * @param string $clientId
    * @param string $appKey
	* @param string $message  
    * @return array or FALSE
    */
    public function getSDKStatus($secretKey=null, $clientId=null, $appKey=null, &$message = null)
	{
		$params = array();
		
		if(isset($secretKey)) $params['secret_key'] = $secretKey;
        if(isset($clientId))  $params['client_id'] = $clientId;
        if(isset($appKey))    $params['app_key'] = $appKey;
		
		//make the API call
        $session = curl_init($this->SHAREYOURCART_API_STATUS);

        // Tell curl to use HTTP POST
        curl_setopt($session, CURLOPT_POST, true);
        // Tell curl that this is the body of the POST
        curl_setopt($session, CURLOPT_POSTFIELDS, http_build_query($params,'','&'));
        // Tell curl not to return headers, but do return the response
        curl_setopt($session, CURLOPT_HEADER, false);
        curl_setopt($session, CURLOPT_RETURNTRANSFER, true);

        $response = curl_exec($session);
        $httpCode = curl_getinfo($session, CURLINFO_HTTP_CODE);
        curl_close($session);
        
        // Notify the caller
        if($httpCode != 200) {
            if(isset($message)) $message = $response;
			
			return false;
        }
		
		 // Decode the result
        return json_decode($response, true);
	}
}


/**
*
* This class should contain all the static methods in the SDK
*
*/
class SyC
{
	static $_messages;
	static $_language = 'en';
	
	/**
	*
	* Change the language the SDK should be displayed in
	*
	*/
	public static function setLanguage($newLanguage = null){
		self::$_language = $newLanguage;
		
		//reset the old messages, so that they are reloaded
		self::$_messages = null;	
	}
	
	/*
	* Translate the specified message
	* 
	*/
	public static function t($category,$message,$params=array())
	{
		//load the translation from file if not done so
		if(!isset(self::$_messages)){
			
			//The language is the folder name, and the category is the name of the file
			$messageFile = dirname(__FILE__).DIRECTORY_SEPARATOR.'messages'.DIRECTORY_SEPARATOR.self::$_language.DIRECTORY_SEPARATOR.$category.'.php';
			
			if(is_file($messageFile))
			{
				self::$_messages=include($messageFile);
			}
			
			//make sure we have an array for this variable
			if(!is_array(self::$_messages)) self::$_messages=array();
		}
		
		//check if the text has a valid translation
		if(isset(self::$_messages[$message]) && !empty(self::$_messages[$message])){
			$message = self::$_messages[$message];
		}
		
		//return the translated message, with the parameters replaced
		return $params!==array() ? strtr($message,$params) : $message;
	}
	
	
	function relativepath($from, $to, $ps = '/' ,$ds = DIRECTORY_SEPARATOR)
	{	
		$arFrom = explode($ds, rtrim($from, $ds));
		$arTo = explode($ds, rtrim($to, $ds));
		
		while(count($arFrom) && count($arTo) && ($arFrom[0] == $arTo[0]))
		{
			array_shift($arFrom);
			array_shift($arTo);
		}
		return str_pad("", count($arFrom) * 3, '..'.$ps).implode($ps, $arTo);
	}

	/**
* rel2Abs
* @param string $src
* @return string
*/
	function rel2Abs($rel, $base) {

		/* return if already absolute URL */
		if (parse_url($rel, PHP_URL_SCHEME) != '')
		return $rel;

		/* queries and anchors */
		if ($rel[0] == '#' || $rel[0] == '?')
		return $base . $rel;

		/* parse base URL and convert to local variables:
	$scheme, $host, $path */
		extract(parse_url($base));

		/* remove non-directory element from path */
		$path = preg_replace('#/[^/]*$#', '', @$path);

		/* destroy path if relative url points to root */
		if ($rel[0] == '/')
		$path = '';

		/* dirty absolute URL */
		$abs = "$host$path/$rel";

		/* replace '//' or '/./' or '/foo/../' with '/' */
		$re = array('#(/\.?/)#', '#/(?!\.\.)[^/]+/\.\./#');
		for ($n = 1; $n > 0; $abs = preg_replace($re, '/', $abs, -1, $n)) {

		}

		/* absolute URL is ready! */
		return $scheme . '://' . $abs;
	}

	/**
* htmlIndent
* @param string $src
* @return string
*/
	function htmlIndent($src) {

		//replace all leading spaces with &nbsp;
		//Attention: this will render wrong html if you split a tag on more lines!
		return preg_replace_callback('/(^|\n)( +)/', create_function('$match', 'return str_repeat("&nbsp;", strlen($match[0]));'
		), $src);
	}

}

} //END IF
?>
