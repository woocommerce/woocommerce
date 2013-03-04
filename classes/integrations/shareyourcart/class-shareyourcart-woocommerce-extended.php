<?php

require_once("class.shareyourcart-wp-woocommerce.php");

class ShareYourCartWooCommerceEx extends ShareYourCartWooCommerce {

	public $settings;

	/**
	 * Constructor
	 * @param null
	 */
	function __construct( $settings ) {

		$this->settings = $settings;
		$this->SDK_ANALYTICS = false; //disable analytics for the woocommerce integration

		parent::__construct();
	}

	public function isCartActive() {
		return true; //since this class has loaded, the WooCommerce plugin is active
	}

	public function getSecretKey() {
		return '2cfd496d-7812-44ba-91ce-e43c59f6c680';
	}

	public function showAdminMenu() {
		//since we have already integrated this in our own settings page,
		//leave this function empty
	}

	/**
	 *
	 * Set the field value
	 *
	 */
	protected function setConfigValue($field, $value) {

		$this->settings[$field] = $value;

		//make sure to update the enabled field as well, based on the account_status
		switch($field){
			case 'account_status':
				$this->settings['enabled'] = ( $value == 'active' ? 'yes' : 'no' );
				break;
			case "plugin_current_version":
				//this setting needs to be set globally as well, in order to be recognized by other ShareYourCart integrations,
				//and to not interfere with one-another
				parent::setConfigValue($field, $value);
				break;
		}

		//save the config in the DB
		update_option( 'woocommerce_shareyourcart_settings', $this->settings );
	}

	/**
	 *
	 * Get the field value
	 *
	 */
	protected function getConfigValue( $field ) {

		$value = ( isset( $this->settings[$field] ) ) ? $this->settings[$field] : '';

		//search for the global value of this field
		//as it might have been changed by an external ShareYourCart integration
		if($field == "plugin_current_version"){
			$val = parent::getConfigValue($field);

			if(!empty($val)) $value = $val;
		}

		return $value;
	}
}