<?php
/**
*	CLASS: Share Your Cart Wordpress Plugin
*	AUTHOR: Barandi Solutions
*	COUNTRY: Romania
*	EMAIL: catalin.paun@barandisolutions.ro
*	VERSION : 1.0
*	DESCRIPTION: This class is used as a base class for every wordpress shopping cart plugin we create.
* *    Copyright (C) 2011 Barandi Solutions
*/

require_once("sdk/class.shareyourcart-base.php");

if(!class_exists('ShareYourCartWordpressPlugin',false)){

	abstract class ShareYourCartWordpressPlugin extends ShareYourCartBase {

		protected static $_INSTANCES = array();
		protected static $_VERSION = 6;
		protected $_PLUGIN_PATH;

		/**
	* Constructor
	* @param null
	*/
		function __construct() {

			parent::__construct();

			//if there is installed another plugin with a never version
			//do not load this one further
			if(!$this->canLoad()) return;

			//make sure we add this instance
			self::$_INSTANCES []= $this;
			$this->_PLUGIN_PATH  = WP_PLUGIN_URL.'/'.str_replace(basename( __FILE__),"",plugin_basename(__FILE__));

			//setup the hooks
			register_activation_hook(self::getPluginFile(),            array(&$this,'activateHook'));
			register_deactivation_hook(self::getPluginFile(),          array(&$this,'deactivateHook'));

			//perform the rest of the loading AFTER all plugins have been loaded
			add_action('plugins_loaded',                               array(&$this,'pluginsLoadedHook'));
		}

		/**
	* Abstract isCartActive
	* @param null
	* @return boolean
	*/
		protected abstract function isCartActive();

		/**
	*
	* Get the plugin version.
	* @return an integer
	*
	*/
		protected function getPluginVersion(){

			return self::$_VERSION;
		}

		/**
	*
	* Return the project's URL
	*
	*/
		protected function getDomain(){

			return get_bloginfo('url');
		}

		/**
	*
	* Return the admin's email
	*
	*/
		protected function getAdminEmail(){

			return get_option('admin_email');
		}

		/**
	*
	* Set the field value
	*
	*/
		protected function setConfigValue($field,$value){
			update_option('_shareyourcart_'.$field,$value);
		}

		/**
	*
	* Get the field value
	*
	*/
		protected function getConfigValue($field){
			return get_option('_shareyourcart_'.$field);
		}

		/**
	*
	* Get the path to the main file of the plugin
	*
	*/
		public static function getPluginFile(){
			global $plugin;

			return $plugin;
		}

	/**
	*
	* Get the upload directory
	*
	*/
	public function getUploadDir(){
		$dir = wp_upload_dir();

      return (!empty($dir['path']) ? $dir['path'] : parent::getUploadDir());
    }

		/**
	*
	* Execute the SQL statement
	*
	*/
		protected function executeNonQuery($sql){

			if(substr($sql,0,12) == "CREATE TABLE"){

				require_once(ABSPATH . 'wp-admin/includes/upgrade.php');

				//if this is a create table command, use the special function which compares tables
				dbDelta($sql);

			} else {

				global $wpdb;

				//use the normal query
				$wpdb->query($sql);
			}
		}

		/**
	*
	* Get the row returned from the SQL
	*
	* @return an associative array containing the data of the row OR NULL
	*         if there is none
	*/
		protected function getRow($sql){

			global $wpdb;

			//get the row as an associative array
			return $wpdb->get_row($sql,ARRAY_A);
		}

		/**
	*
	* Get the table name based on the key
	*
	*/
		protected function getTableName($key){
			global $wpdb;

			return $wpdb->base_prefix.$key;
		}

		/**
	*
	* Insert the row into the specified table
	*
	*/
		protected function insertRow($tableName,$data){
			global $wpdb;

			$wpdb->insert($tableName,$data);
		}

		/**
	*
	* Create url for the specified file. The file must be specified in relative path
	* to the base of the plugin
	*/
		protected function createUrl($file){
			//get the real file path
			$file = realpath($file);

			//calculate the relative path from this file
			$file = SyC::relativepath(dirname(__FILE__),$file);

			//append the relative path to the current file's URL
			return WP_PLUGIN_URL.'/'.str_replace(basename( __FILE__),"",plugin_basename(__FILE__)).$file;
		}

		/**
	*
	* Part of the initialization process, this is used to check if the cart is
	* really active
	*
	*/
		public function pluginsLoadedHook() {

			//if the cart is not loaded, do not load this plugin further
			if(!$this->isCartActive()) return;

			add_action('wp_head',                                   array(&$this,'showPageHeader'));
			add_action('admin_menu',                                array(&$this,'showAdminMenu'));

			//add the shortcodes ONLY if they where not added before, by another plugin
			global $shortcode_tags;
			if(!array_key_exists('shareyourcart',$shortcode_tags)){
				add_shortcode('shareyourcart',                          array(&$this,'getButton'));
				add_shortcode('shareyourcart_button',                   array(&$this,'getButton'));
			}
		}

		/**
	*
	* This is the activate hook called by WordPress. It is used for both install and activate
	*
	*/
		public function activateHook() {

			if(!$this->isCartActive()) return;

			$message = '';

			//the db version is old, so install it again
			if(version_compare($this->getConfigValue('db_version'),self::$_DB_VERSION)!=0){

				$this->install($message);
			} else { //if the version are equal, simply activate the plugin

				$this->activate($message);
			}

			//TODO: perhaps we can show the message somehow to the user. For now, just log it
			error_log($message);
		}

		/**
	*
	* This is the deactivate hook called by WordPress.
	*
	*/
		public function deactivateHook() {

			if(!$this->isCartActive()) return;

			$message = '';

			$this->deactivate($message);

			//TODO: perhaps we can show the message somehow to the user. For now, just log it
			error_log($message);
		}

		/**
	*
	* When installing the new wordpress plugin, make sure to move the old data
	*
	*/
		public function install(&$message = null) {

			global $wpdb;

			$old_table_name = $this->getTableName('shareyourcart_settings');
			if($wpdb->get_var("show tables like '$old_table_name'") == $old_table_name) {

				//get the old app_key and client_id
				$settings = $wpdb->get_row("SELECT app_key, client_id FROM ".$wpdb->base_prefix."shareyourcart_settings LIMIT 1");
				if($settings) {

					$this->setConfigValue('appKey', $settings->app_key);
					$this->setConfigValue('clientId', $settings->client_id);
				}

				//remove the old table
				$wpdb->query("DROP TABLE $old_table_name");
			}

			parent::install($message);
		}

		/**
	*
	* This is the deactivate hook called by WordPress.
	*
	*/
		public static function uninstallHook() {

			//this hook is required by WordPress to be static
			//so call uninstall on the first instance
			if(!empty(self::$_INSTANCES)){

				self::$_INSTANCES[0]->uninstall();
			} else {

				//log the error
				error_log("There is no instance created when calling uninstall hook");
			}
		}

		/**
	* show the admin menu
	* @param null
	* @return boolean
	*/
		public function showAdminMenu() {

			//this is a single call function
			if(!$this->isFirstCall(__FUNCTION__)) return;

			//first, check the SDK status
			$this->checkSDKStatus();

			//see if we need to show the update notification
			$notification = null;
			if($this->hasNewerVersion())
			$notification = "<span class='update-plugins count-$warning_count'><span class='update-count'>" . number_format_i18n(1) . "</span></span>";

			$page = add_menu_page(
			__('Share your cart settings'),
			__('ShareYourCart').$notification,
			1,
			basename(__FILE__),
			array(&$this, 'showAdminPage'),
			$this->_PLUGIN_PATH .'sdk/img/shareyourcart.png'
			);
			add_action( 'admin_head-'.$page, array(&$this, 'showAdminHeader'));

			if($this->isActive())
			{
				//show the rest of the menu ONLY if the plugin is active
				$page = add_submenu_page(
				basename(__FILE__),
				__('Button'),
				__('Customize Button'),
				1,
				basename(__FILE__).'-button',
				array(&$this, 'showButtonCustomizationPage'));
				add_action( 'admin_head-'. $page, array(&$this, 'showAdminHeader'));

				$page = add_submenu_page(
				basename(__FILE__),
				__('Documentation'),
				__('Documentation'),
				1,
				basename(__FILE__).'-documentation',
				array(&$this, 'showDocumentationPage'));
				add_action( 'admin_head-'. $page, array(&$this, 'showAdminHeader'));
			}
		}

		/*
	*
	* Called when a new coupon is generated
	*
	*/
		public function couponCallback(){

			//since there are a lot of plugins for wordpress, first make sure this plugin
			//is active
			if(!$this->isCartActive()) {

				throw new Exception(SyC::t('sdk','Shopping Cart is not active'));
			}

			parent::couponCallback();

			//since this is actually an API, exit
			exit;
		}
	}

} //END IF
?>