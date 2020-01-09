<?php
/**
 * MaxMind Geolocation Integration
 *
 * @version 3.9.0
 * @package WooCommerce/Integrations
 */

defined( 'ABSPATH' ) || exit;

require_once 'class-wc-maxmind-geolocation-database.php';

/**
 * WC MaxMind Geolocation Integration
 *
 * @version 3.9.0
 */
class WC_MaxMind_Geolocation_Integration extends WC_Integration {

	/**
	 * Initialize the integration.
	 */
	public function __construct() {
		$this->id                 = 'woocommerce_maxmind_geolocation';
		$this->method_title       = __( 'WooCommerce MaxMind Geolocation', 'woocommerce' );
		$this->method_description = __( 'An integration for utilizing MaxMind to do Geolocation lookups.', 'woocommerce' );

		$this->init_form_fields();
		$this->init_settings();

		// Bind to the save action for the settings.
		add_action( 'woocommerce_update_options_integration_' . $this->id, array( $this, 'process_admin_options' ) );
	}

	/**
	 * Initializes the settings fields.
	 */
	public function init_form_fields() {
		$this->form_fields = array(
			'license_key' => array(
				'title'       => __( 'MaxMind License Key', 'woocommerce' ),
				'type'        => 'password',
				'description' => __( 'The key that will be used when dealing with MaxMind Geolocation services. You can generate a key in your account after creating an account on MaxMind\'s website.', 'woocommerce' ),
				'desc_tip'    => false,
				'default'     => '',
			),
		);
	}

	/**
	 * Checks to make sure that the license key is valid.
	 *
	 * @param string $key The key of the field.
	 * @param mixed  $value The value of the field.
	 * @return mixed
	 * @throws Exception When the license key is invalid.
	 */
	public function validate_license_key_field( $key, $value ) {
		// Empty license keys have no need to validate the data.
		if ( empty( $value ) ) {
			return $value;
		}

		return $value;
	}
}
