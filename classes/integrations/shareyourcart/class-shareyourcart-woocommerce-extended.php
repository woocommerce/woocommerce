<?php

require_once( "class.shareyourcart-wp-woocommerce.php" );

/**
 * ShareYourCartWooCommerceEx class.
 *
 * @extends ShareYourCartWooCommerce
 */
class ShareYourCartWooCommerceEx extends ShareYourCartWooCommerce {

	public $settings;

	/**
	 * __construct function.
	 *
	 * @access public
	 * @param mixed $settings
	 * @return void
	 */
	function __construct( $settings ) {
		$this->settings = $settings;

		// disable analytics for the woocommerce integration
		$this->SDK_ANALYTICS = false;

		parent::__construct();
	}

	/**
	 * Since this class has loaded, the WooCommerce plugin is active.
	 *
	 * @access public
	 * @return bool
	 */
	public function isCartActive() {
		return true;
	}

	/**
	 * getSecretKey function.
	 *
	 * @access public
	 * @return string
	 */
	public function getSecretKey() {
		return '2cfd496d-7812-44ba-91ce-e43c59f6c680';
	}

	/**
	 * Since we have already integrated this in our own settings page, leave this function empty.
	 *
	 * @access public
	 * @return void
	 */
	public function showAdminMenu() {}

	/**
	 * Set the field value
	 *
	 * @access protected
	 * @param mixed $field
	 * @param mixed $value
	 * @return void
	 */
	protected function setConfigValue( $field, $value ) {

		$this->settings[ $field ] = $value;

		//make sure to update the enabled field as well, based on the account_status
		switch( $field ){
			case 'account_status':
				$this->settings['enabled'] = ( $value == 'active' ? 'yes' : 'no' );
			break;
			case "plugin_current_version":
				//this setting needs to be set globally as well, in order to be recognized by other ShareYourCart integrations,
				//and to not interfere with one-another
				parent::setConfigValue( $field, $value );
			break;
		}

		//save the config in the DB
		update_option( 'woocommerce_shareyourcart_settings', $this->settings );
	}

	/**
	 * Get the field value
	 *
	 * @access protected
	 * @param mixed $field
	 * @return string
	 */
	protected function getConfigValue( $field ) {

		$value = ( isset( $this->settings[ $field ] ) ) ? $this->settings[ $field ] : '';

		// search for the global value of this field
		// as it might have been changed by an external ShareYourCart integration
		if ( $field == "plugin_current_version" ) {
			$val = parent::getConfigValue( $field );

			if ( ! empty( $val ) )
				$value = $val;
		}

		return $value;
	}
}