<?php

/**
 * 	CLASS: Share Your Cart Base
 * 	AUTHOR: Barandi Solutions
 * 	COUNTRY: Romania
 * 	EMAIL: catalin.paun@barandisolutions.ro
 * 	VERSION : 1.0
 * 	DESCRIPTION: This class is used as a base class for every PHP plugin we create.
 * *    Copyright (C) 2012 Barandi Solutions
 */
require_once(dirname(__FILE__) ."/class.shareyourcart-api.php");

if(!class_exists('ShareYourCartBase',false)){

abstract class ShareYourCartBase extends ShareYourCartAPI {

	//this array is used to hold function calls between different instances of this class
	private static $_SINGLE_FUNCTIONS_CALLS = array();
	private static $_SDK_VERSION = '1.6';  //the first one is the SDK main version, while the second one is it's revision
	protected static $_DB_VERSION = '1.1';
	
	/**
    * Constructor
    * @param null
    */
    function __construct() {
	
		//set exception handler
		if(set_exception_handler(array(&$this,'UncaughtExceptionHandler')) !== null)
			restore_exception_handler(); //if there allready was an exception handler, revert back to it
	
		parent::__construct();
		
		//now, add the api version to the button_js, in order to force users to download the latest
		//JS file
		$this->SHAREYOURCART_BUTTON_JS .= '?v='. $this->getConfigValue('api_version');
		$this->SHAREYOURCART_BUTTON_URL .= '?client_id='. $this->getClientId();
		
		//set the language
		SyC::setLanguage($this->getConfigValue('lang'));
	}
	
	/**
	 * Execute NonQuery SQL
	 * @param string action
	 * @param string extra
	 * @return boolean
	 */
	protected abstract function executeNonQuery($sql);

	/**
	 *
	 * Get the row returned from the SQL
	 *
	 * @return an associative array containing the data of the row OR NULL
	 *         if there is none
	 */
	protected abstract function getRow($sql);

	/**
	 * Abstract getTableName
	 * @param string key
	 *
	 */
	protected abstract function getTableName($key);

	/**
	 * Abstract setConfigValue
	 * @param string option
	 * @param string value
	 * @return boolean
	 */
	protected abstract function setConfigValue($field, $value);

	/**
	 * Abstract setConfigValue
	 * @param string option
	 * @param string value
	 * @return string
	 */
	protected abstract function getConfigValue($field);

	/**
	 * 	Description: This returns an array with the details of the product
	 *               displayed on this page. If it is not a product, it
	 *               will return FALSE
	 * @remarks  this will be mostly used by LITE integrations, so no need to make it mandatory
	 * @param null
	 * @return boolean / array
	 */
	protected function getCurrentProductDetails(){
		
		return FALSE;
	}
	
	/**
	*
	* Return TRUE if this page describes a single product, otherwise FALSE
	*
	*/
	protected abstract function isSingleProduct();

	/**
	 *
	 * Return the URL to be called when the button is pressed
	 *
	 */
	protected abstract function getButtonCallbackURL();

	/*
	 *
	 * Create url for the specified file. The file must be specified in relative path
	 * to the base of the plugin
	 */
	protected abstract function createUrl($file);

	/**
	 *
	 * Called after the session has been reloaded
	 *
	 */
	protected abstract function loadSessionData();

	/**
	 *
	 * Returns the plugin's secret key. This should be overwritten only by the actual plugin
	 *
	 */
	protected abstract function getSecretKey();

	/**
	 *
	 * Insert a row in the table
	 * @param string tableName
	 * @param array data
	 *
	 */
	protected abstract function insertRow($tableName, $data);

	/**
	 *
	 * Apply the coupon code
	 *
	 */
	protected abstract function applyCoupon($coupon_code);
	
	/**
	*
	* Get the plugin version.
	* @return an integer
	*
	*/
	protected abstract function getPluginVersion();

	/**
	*
	* Get the entire Version, including the SDK one
	*
	*/
	public function getVersion()
	{
		//make sure a developer enters only a number as a version
		if(!is_int($minor_version = $this->getPluginVersion()))
			throw new Exception(SyC::t('sdk','The Plugin Version must be an integer'));
		
		return self::$_SDK_VERSION.'.'.$minor_version;
	}
	
	/**
	*
	* Return TRUE if there is a newer version of the plugin
	*
	*/
	protected function hasNewerVersion()
	{
		return version_compare($this->getVersion(),$this->getConfigValue('latest_version'),'<');
	}
	
	/**
	*
	* Check if this instance can load, or not!
	* This is ment so that at ALL times, only the latest plugin version will work
	*
	*/
	protected function canLoad()
	{
		$v = version_compare($this->getVersion(),$this->getConfigValue('plugin_current_version')); 
		//first, check if this instance is the latest one, or not
		if($v < 0) return false;
			 
		//save the latest version, for later reference
		if($v > 0 )
			$this->setConfigValue('plugin_current_version',$this->getVersion());
			
		return true;
	}

	/**
	 * install the plugin
	 * @param null
	 * @return boolean
	 */
	public function install(&$message = null) {

		//this is a single call function
		if (!$this->isFirstCall(__FUNCTION__))
		return;

		//create the tokens table
		$this->createTable($this->getTableName('shareyourcart_tokens'), array(
            'id' => 'int(11)',
            'token' => 'varchar(255)',
            'session_id' => 'varchar(255)',
		), 'id');

		//create the coupon log table
		$this->createTable($this->getTableName('shareyourcart_coupons'), array(
            'id' => 'int(11)',
            'token' => 'varchar(255)',
            'coupon_id' => 'varchar(255)',
		), 'id');

		//save the DB version, for later use
		$this->setConfigValue('db_version', self::$_DB_VERSION);

		//if we have credentials in the DB, try to activate the plugin
		//with them
		$activated = false;
		$appKey = $this->getAppKey();
		$clientId = $this->getClientId();
		if(!empty($appKey) && !empty($clientId)){
		
			$activated = $this->activate($message);
		}

		return true;
	}

	/**
	 * uninstall the plugin
	 * @param null
	 * @return boolean
	 */
	public function uninstall(&$message = null) {

		//this is a single call function
		if (!$this->isFirstCall(__FUNCTION__))
		return;

		//first, make sure we deactivate the plugin
		$this->deactivate($message);

		//remove the tables
		$this->dropTable($this->getTableName('shareyourcart_tokens'));
		$this->dropTable($this->getTableName('shareyourcart_coupons'));

		//remove the db version
		$this->setConfigValue('db_version', null);

		return true;
	}

	/**
	 * activate the plugin
	 * @param null
	 * @return boolean
	 */
	public function activate(&$message = null) {

		//this is a single call function
		if (!$this->isFirstCall(__FUNCTION__))
		return;

		//active the API
		if ($this->setAccountStatus($this->getSecretKey(), $this->getClientID(), $this->getAppKey(), true, $message) === TRUE) {

			$this->setConfigValue("account_status", "active");
			return true;
		} else {

			$this->setConfigValue("account_status", "inactive");
			return false;
		}
	}

	/**
	 * deactivate the plugin
	 * @param null
	 * @return boolean
	 */
	public function deactivate(&$message = null) {

		//send the notification to the API
		$this->setAccountStatus($this->getSecretKey(), $this->getClientID(), $this->getAppKey(), false, $message);

		//no matter what the API says, disable this plugin
		$this->setConfigValue("account_status", "inactive");

		return true;
	}

	/**
	 * getAppKey
	 * @param null
	 * @return appKey
	 */
	public function getAppKey() {

		return $this->getConfigValue('appKey');
	}

	/**
	 * getClientID
	 * @param null
	 * @return clientID
	 */
	public function getClientId() {
		return $this->getConfigValue('clientId');
	}

	/**
	 * Check if the plugin is active, or not
	 * @param null
	 * @return boolean
	 */
	public function isActive() {

		return ($this->getConfigValue('account_status') == "active");
	}

	/**
	 * startSession
	 * @param string $params
	 * @return boolean
	 */
	public function startSession($params) {

		//make sure the params contain the required entries
		if (!isset($params['app_key']))
		$params['app_key'] = $this->getAppKey();

		if (!isset($params['client_id']))
		$params['client_id'] = $this->getClientId();

		//create a new session
		$data = parent::startSession($params);

		//save session details
		$this->insertRow($this->getTableName('shareyourcart_tokens'), $data);
		
		//we can't rely on the fact that the row has been inserted, so check!
		if($this->getSessionId($data['token']) === null)
			throw new Exception(SyC::t('sdk','Token cannot be saved. Check your "{table_name}" table permissions.', array('{table_name}' => $this->getTableName('shareyourcart_tokens'))));

		return true;
	}

	/**
	 * ensureCouponIsValid
	 * @param string $params
	 * @return boolean
	 */
	public function assertCouponIsValid($token, $coupon_code, $coupon_value, $coupon_type) {

		//first call the parent function
		parent::assertCouponIsValid($token, $coupon_code, $coupon_value, $coupon_type);

		//get the session_id associated with the token
		$session_id = $this->getSessionId($token);

		//make sure the session is valid
		if ($session_id === null) {
			throw new Exception(SyC::t('sdk','Token not found'));
		}

		//resume the session
		session_destroy();
		session_id($session_id);
		session_start();

		$this->loadSessionData();
	}

	/**
	 * simply show the button
	 * @param null
	 * @return boolean
	 */
	public function showButton() {

		echo $this->getButton();
	}

	/**
	 *
	 * get the button code
	 *
	 */
	public function getButton() {

		//make sure the API is active
		if(!$this->isActive()) return;

		return $this->renderButton($this->getButtonCallbackURL());
	}

	/**
	 * renderButton
	 * @param null
	 * @return boolean
	 */
	protected function renderButton($callback_url) {
		ob_start();

		$current_button_type = $this->getConfigValue("button_type");
		$button_html = $this->getConfigValue("button_html");
		
		$button_img = $this->getConfigValue("btn-img");
		$button_img_width = $this->getConfigValue("btn-img-width");
		$button_img_height = $this->getConfigValue("btn-img-height");
		
		$button_img_hover = $this->getConfigValue("btn-img-h");
		$button_img_hover_width = $this->getConfigValue("btn-img-h-width");
		$button_img_hover_height = $this->getConfigValue("btn-img-h-height");

		switch ($current_button_type)
		{
			case '1':
				include(dirname(__FILE__) . '/views/button.php');
				break;
			case '2':
				include(dirname(__FILE__) . '/views/button-img.php');
				break;
			case '3':
				include(dirname(__FILE__) . '/views/button-custom.php');
				break;
			default:
				include(dirname(__FILE__) . '/views/button.php');
				break;
		}


		return ob_get_clean();
	}

	/**
	 *
	 * show the button on a product page
	 *
	 */
	public function showProductButton() {

		echo $this->getProductButton();
	}

	/**
	 *
	 * get the button for a product page
	 *
	 */
	public function getProductButton() {

		if($this->isSingleProduct() && !$this->getConfigValue('hide_on_product')){

			return	$this->getButton();
		}

		//else return nothing
		return null;
	}

	/**
	 *
	 * show the button on a cart page
	 *
	 */
	public function showCartButton() {
		
		echo $this->getCartButton();
	}

	/**
	 *
	 * get the button for the cart page
	 *
	 */
	public function getCartButton() {

		if(!$this->getConfigValue('hide_on_checkout')){

			return $this->getButton();
		}

		//return nothing
		return null;
	}

	/**
	 *
	 * Simply show the page header
	 *
	 */
	public function showPageHeader() {

		echo $this->getPageHeader();
	}

	/**
	 * Get the page header code
	 * @param null
	 * @return boolean
	 */
	public function getPageHeader() {

		//this is a single call function
		if (!$this->isFirstCall(__FUNCTION__))
		return;

		$data = $this->getCurrentProductDetails();

		ob_start();
		include(dirname(__FILE__) . '/views/page-header.php');
		return ob_get_clean();
	}

	/**
	 * Show the admin header code
	 * @param null
	 * @return boolean
	 */
	public function showAdminHeader() {

		echo $this->getAdminHeader();
	}

	/**
	 * Get the admin header code
	 * @param null
	 * @return boolean
	 */
	public function getAdminHeader() {
			
		//this is a single call function
		if (!$this->isFirstCall(__FUNCTION__))
		return;
		
		//check the SDK status
		$this->checkSDKStatus(true); //for a check, as the admin might have changed the language in the configure page, so we need to sync with it

		ob_start();
		include(dirname(__FILE__) . '/views/admin-header.php');
		return ob_get_clean();
	}

	/**
	 * show the admin page
	 * @param null
	 * @return boolean
	 */
	public function showAdminPage($html='',$show_header=TRUE,$show_footer=TRUE) {

		echo $this->getAdminPage($html,$show_header,$show_footer);
	}

	/**
	 *
	 * get the admin page
	 *
	 */
	public function getAdminPage($html='',$show_header=TRUE,$show_footer=TRUE) {

		//this is a single call function
		if (!$this->isFirstCall(__FUNCTION__))
		return;

		$status_message = '';
		$refresh = false;
		
		//check if this is a post for this particular page
		if ($_SERVER['REQUEST_METHOD'] == 'POST' &&
		!empty($_POST['syc-account-form'])) {

			$this->setConfigValue('appKey', $_POST['app_key']);
			$this->setConfigValue('clientId', $_POST['client_id']);

			//it is vital that we call the activation API here, to make sure, that the account is ACTIVE
			//call the account status function
			$message = '';
			if ($this->activate($message) == true) {

				$status_message = SyC::t('sdk','Account settings successfully saved');
			} else {

				//the account did not activate, so show the error
				$status_message = $message;
			}
			
			//since we might have changed the status, REFRESH
			$refresh = true;
		}
		//the user decided to disable the API
		else if ($_SERVER['REQUEST_METHOD'] == 'POST' &&
		!empty($_POST['disable-API'])){
			
			$this->deactivate($status_message);
			
			//since we might have changed the status, REFRESH
			$refresh = true;
		}
		//the user decided to activate the API
		else if ($_SERVER['REQUEST_METHOD'] == 'POST' &&
		!empty($_POST['enable-API'])){
			
			$this->activate($status_message);
			
			//since we might have changed the status, REFRESH
			$refresh = true;
		}  
		//check if the user want's to recover his account
		else if (@$_REQUEST['syc-account'] === 'recover'){

			//by default, show the form if we are here
			$show_form = true;
			if($_SERVER['REQUEST_METHOD'] == 'POST' &&
			!empty($_POST['syc-recover-account']))
			{
				//try to recover if the user posted the form
				$show_form = !$this->recover($this->getSecretKey(), @$_REQUEST['domain'], @$_REQUEST['email'], $status_message);
			}
			
			//if we need to show the form
			if($show_form)
			{
				//if there is a message, put the form on a new line
				if(!empty($status_message))
					$status_message .= "<br /><br />";
				
				ob_start();
				include(dirname(__FILE__) . '/views/account-recover-partial.php');
				$status_message .= ob_get_clean();
			} 
			else 
			{
				//Refresh in order to get rid of the GET parameter
				$refresh = true;
			}
		}
		else if (@$_REQUEST['syc-account'] === 'create'){
			
			//by default, show the form if we are here
			$show_form = true;
			if($_SERVER['REQUEST_METHOD'] == 'POST' &&
			!empty($_POST['syc-create-account']))
			{
				//first, check if the user has agreed to the terms and conditions
				if(isset($_POST['syc-terms-agreement']))
				{
					//try to create the account if the user posted the form
					if (!(($register = $this->register($this->getSecretKey(), @$_REQUEST['domain'], @$_REQUEST['email'], $status_message)) === false)) 
					{
						$this->setConfigValue('appKey', @$register['app_key']);
						$this->setConfigValue('clientId', @$register['client_id']);
                        
						$this->setConfigValue("account_status", "active");
						$show_form = false; //no need to show the register form anymore
						
						//since we might have changed the status, REFRESH
						$refresh = true;
					}
				}
				else
				{
					$status_message = SyC::t('sdk',"Error. You must agree with the terms and conditions bellow");
				}
			}
			
			//if we need to show the form
			if($show_form)
			{
				//if there is a message, put the form on a new line
				if(!empty($status_message))
					$status_message .= "<br /><br />";
				
				ob_start();
				include(dirname(__FILE__) . '/views/account-create-partial.php');
				$status_message .= ob_get_clean();
			}
		}
		
		//make sure there is a session variable setup
		session_start();
		
		//since switching the API status has a great impact on how the UI looks, refresh the page
		//just to make sure the UI is using the latest value
		if($refresh)
		{
			//first, save the status message
			$_SESSION['_syc_status_message'] = $status_message;
			
			//recreate the url ( but before that make sure there is no syc-account parameter in it )
			unset($_GET['syc-account']);
			$url = '?'.http_build_query($_GET,'','&');
			
			@header("HTTP/1.1 302 Found");
			@header("Location: $url");
			echo "<meta http-equiv=\"refresh\" content=\"0; url=$url\">"; //it can happen that the headers have allready been sent, so use the html version as well
			exit;
		}
		
		//if there is a status message
		if(!empty($_SESSION['_syc_status_message']))
		{
			$status_message = $_SESSION['_syc_status_message'];
			unset($_SESSION['_syc_status_message']);
		}

		// Display the view
		ob_start();
		include(dirname(__FILE__) . '/views/admin-page.php');
		return ob_get_clean();
	}

	/**
	 *
	 * show page to customize the button
	 *
	 */
	public function showButtonCustomizationPage($html='',$show_header=TRUE,$show_footer=TRUE) {

		echo $this->getButtonCustomizationPage($html,$show_header,$show_footer);
	}

	/**
	 *
	 * get the page to customize the button
	 *
	 */
	public function getButtonCustomizationPage($html='',$show_header=TRUE,$show_footer=TRUE){
		//if visual settings are submitted
		if ($_SERVER['REQUEST_METHOD'] == 'POST' &&
		!empty($_POST['syc-visual-form'])) {

			//set the button declaration
			$this->setConfigValue("button_type", $_POST['button_type']);

			//set the button skin
			$this->setConfigValue("button_skin", $_POST['button_skin']);

			//set the button position
			$this->setConfigValue("button_position", $_POST['button_position']);
			
			//set the button height
			$this->setConfigValue("dont_set_height", empty($_POST['show_on_single_row']));

			//set the button html
			$this->setConfigValue("button_html", rawurldecode($_POST['button_html']));

			//set the show
			$this->setConfigValue("hide_on_product", empty($_POST['show_on_product']));

			//set the show'
			$this->setConfigValue("hide_on_checkout", empty($_POST['show_on_checkout']));

			if($_FILES["button-img"]["name"]!='') {

				$target_path = dirname(__FILE__). "/img/";

				$target_path = $target_path . 'button-img.png';

				if(file_exists($target_path)) unlink($target_path);
				
				list($width, $height, $type, $attr) = getimagesize($_FILES['button-img']['tmp_name']);
				
				if (move_uploaded_file($_FILES['button-img']['tmp_name'], $target_path))
				{
					//set the button img
					$this->setConfigValue("btn-img", $this->createUrl($target_path));
					$this->setConfigValue("btn-img-width", $width);
					$this->setConfigValue("btn-img-height", $height);
				}
			}

			if($_FILES["button-img-hover"]["name"]!='') {
				$target_path = dirname(__FILE__). "/img/";

				$target_path = $target_path . 'btn-img-hover.png';

				if(file_exists($target_path)) unlink($target_path);
				
				list($width, $height, $type, $attr) = getimagesize($_FILES['button-img-hover']['tmp_name']);

				if(move_uploaded_file($_FILES['button-img-hover']['tmp_name'], $target_path))
				{
					//set the show'
					$this->setConfigValue("btn-img-h", $this->createUrl($target_path));
					$this->setConfigValue("btn-img-h-width", $width);
					$this->setConfigValue("btn-img-h-height", $height);
				}
			}

			$status_message = SyC::t('sdk','Button settings successfully updated.');
		}

		$current_button_type = $this->getConfigValue("button_type");
		$current_skin = $this->getConfigValue("button_skin");
		$current_position = $this->getConfigValue("button_position");
		$show_on_checkout = !$this->getConfigValue("hide_on_checkout");
		$show_on_product = !$this->getConfigValue("hide_on_product");
		$show_on_single_row = !$this->getConfigValue("dont_set_height");

		$button_html = $this->getConfigValue("button_html");
		$button_img = $this->getConfigValue("btn-img");
		$button_img_hover = $this->getConfigValue("btn-img-h");

		//render the view
		ob_start();
		include(dirname(__FILE__) . '/views/button-settings-page.php');
		return ob_get_clean();
	}

	/**
	 * showDocumentation
	 * @param null
	 * @return boolean
	 */
	public function showDocumentationPage($html='',$show_header=TRUE,$show_footer=TRUE) {

		echo $this->getDocumentationPage($html,$show_header,$show_footer);
	}

	/**
	 * get the documentation page
	 * @param null
	 * @return boolean
	 */
	public function getDocumentationPage($html='',$show_header=TRUE,$show_footer=TRUE) {

		//this is a single call function
		if (!$this->isFirstCall(__FUNCTION__))
		return;
			
		$action_url = $this->getButtonCallbackURL();

		//render the view
		ob_start();
		include(dirname(__FILE__) . '/views/documentation.php');
		return ob_get_clean();
	}
	
	/**
	 * show the update notification
	 * @param null
	 */
	public function showUpdateNotification(){
		echo $this->getUpdateNotification();
	}
	
	/**
	 * get the update notification
	 * @param null
	 */
	public function getUpdateNotification(){
		//render the view
		ob_start();
		include(dirname(__FILE__) . '/views/update-notification-partial.php');
		return ob_get_clean();
	}

	/*
	 *
	 * Called when a new coupon is generated
	 *
	 */

	public function couponCallback() {

		try {
			/*             * ********* Check input parameters ******************************* */
			if (!isset($_POST['token'], $_POST['coupon_code'], $_POST['coupon_value'], $_POST['coupon_type'])) {
				throw new Exception(SyC::t('sdk','At least one of the parameters is missing. Received: {data}', array('{data}' => print_r($_POST, true))));
			}

			//make sure the coupon is valid
			$this->assertCouponIsValid($_POST['token'], $_POST['coupon_code'], $_POST['coupon_value'], $_POST['coupon_type']);

			//save the coupon
			$this->saveCoupon($_POST['token'], $_POST['coupon_code'], $_POST['coupon_value'], $_POST['coupon_type']);

			//check if the coupon is intended to be applied to the current cart
			if (empty($_POST['save_only'])) {
				$this->applyCoupon($_POST['coupon_code']);
			}
		} catch (Exception $e) {

			header("HTTP/1.0 403");
			echo $e->getMessage();
		}
	}

	/**
	 * Save the coupon
	 * @param null
	 * @return boolean
	 */
	protected function saveCoupon($token, $coupon_code, $coupon_value, $coupon_type) {

		//add the coupon id in shareyourcart coupons table
		$data = array(
            'token' => $token,
            'coupon_id' => $coupon_code,
		);

		$this->insertRow($this->getTableName('shareyourcart_coupons'), $data);
	}

	/**
	 *
	 * Based on the token, get the session_id
	 *
	 */
	protected function getSessionId($token) {

		$result = $this->getRow("SELECT session_id FROM " . $this->getTableName('shareyourcart_tokens') . " WHERE token='$token'");

		return isset($result) ? $result['session_id'] : null;
	}

	/**
	 *
	 * createTable
	 * @param table
	 * @param array columns
	 * @param string @option
	 */
	protected function createTable($tableName, $columns, $primaryKey, $options=NULL) {

		$sql = "CREATE TABLE IF NOT EXISTS $tableName (\n ";

		foreach ($columns as $name => $type) {

			$sql .= "$name $type";

			if ($name == $primaryKey) {
				$sql .= " NOT NULL AUTO_INCREMENT";
			}

			$sql .= ",\n ";
		}

		$sql .= "PRIMARY KEY ($primaryKey));";

		$this->executeNonQuery($sql);
		
		//we can't relly on the fact that the table has been properly created, so check it!
		if(!$this->existsTable($tableName))
			throw new Exception(SyC::t('sdk','Cannot create table "{table_name}". Check your database permissions.', array('{table_name}' => $tableName)));
	}
	
	/**
	*
	* existsTable
	* @return TRUE if the table exists, otherwise false
	*/
	protected function existsTable($tableName){
	
		$table_details = $this->getRow("show tables like '$tableName'");
		
		//if there are table details, it means the table exists
		return !empty($table_details);
	}

	/**
	 * Drop the specified table
	 *
	 * @param string tableName
	 */
	protected function dropTable($tableName) {

		$this->executeNonQuery("DROP TABLE $tableName");
		
		//we can't relly on the fact that the table has been properly droped, so check it!
		if(!$this->existsTable($tableName))
			throw new Exception(SyC::t('sdk','Cannot drop table "{table_name}". Check your database permissions.', array('{table_name}' => $tableName)));
	}

	/**
	*
	* Check the SDK status
	* @param $force. TRUE to check, no matter how soon the previous check was. This can have a great impact on the experience of the admin
	*
	*/
	protected function checkSDKStatus($force = false) {
		
		//call the API at most only once every 5 minutes, not sooner
		if(!$force && (time()-$this->getConfigValue('api_last_check')) < 5*60) 
			return;
		
		//set the latest check time
		$this->setConfigValue('api_last_check',time());
		
		//get an update from the API
		$message = '';
		if(is_array($result = $this->getSDKStatus($this->getSecretKey(), $this->getClientId(), $this->getAppKey(), $message)))
		{
			//save the data
			$this->setConfigValue('api_version', @$result['api_version']);
			$this->setConfigValue('latest_version', @$result['plugin_latest_version']);
			$this->setConfigValue('download_url', @$result['plugin_download_url']);
			
			//set the current language the SDK should be displayed in
			if(isset($result['lang'])){
				$this->setConfigValue('lang', $result['lang']);
				SyC::setLanguage($result['lang']);
			}
		}else{
			//simply log the error, for now!
			error_log(print_r($message,true));
		}	
	}
	
	/*
	 *
	 * Call to see if the function was called once, or not
	 *
	 */
	protected static function isFirstCall($functionName) {

		if (array_key_exists($functionName, self::$_SINGLE_FUNCTIONS_CALLS))
		return false;

		self::$_SINGLE_FUNCTIONS_CALLS[$functionName] = true;
		return true;
	}
	
	/**
	*
	* User to catch any unhandled exceptions and print them nicelly
	*
	*/
	public function UncaughtExceptionHandler(Exception $e) {
		//@header("HTTP/1.0 403");
		echo $e->getMessage();
	}
}

} //END IF
?>