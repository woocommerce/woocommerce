<?php
/**
 * Class My_Simple_Gateway
 *
 * @package WooCommerce\Admin
 */

/**
 * Class My_Simple_Gateway
 */
class My_Simple_Gateway extends WC_Payment_Gateway {
	/**
	 * Constructor for the gateway.
	 */
	public function __construct() {
		$this->id                 = 'my-simple-gateway';
		$this->has_fields         = false;
		$this->method_title       = __( 'Simple gateway', 'woocommerce-admin' );
		$this->method_description = __( 'A simple gateway to show extension of gateway installation during onboarding.', 'woocommerce-admin' );

		// Load the settings.
		$this->init_form_fields();
		$this->init_settings();

		add_action( 'woocommerce_update_options_payment_gateways_' . $this->id, array( $this, 'process_admin_options' ) );
	}

	/**
	 * Init settings for gateways.
	 */
	public function init_settings() {
		parent::init_settings();
		$this->enabled = ! empty( $this->settings['enabled'] ) && 'yes' === $this->settings['enabled'] ? 'yes' : 'no';
	}


	/**
	 * Initialise Gateway Settings Form Fields.
	 */
	public function init_form_fields() {
		$this->form_fields = array(
			'enabled' => array(
				'title'   => __( 'Enabled', 'woocommerce-admin' ),
				'type'    => 'checkbox',
				'label'   => '',
				'default' => 'no',
			),
			'api_key' => array(
				'title'   => __( 'API Key', 'woocommerce-admin' ),
				'type'    => 'text',
				'default' => '',
			),
		);
	}

	/**
	 * Determine if the gateway requires further setup.
	 */
	public function needs_setup() {
		$settings = get_option( 'woocommerce_my-simple-gateway_settings', array() );
		return ! isset( $settings['api_key'] ) || empty( $settings['api_key'] );
	}

	/**
	 * Get setup help text.
	 *
	 * @return string
	 */
	public function get_setup_help_text() {
		return __( 'Help text displayed beneath the configuration form.', 'woocommerce-admin' );
	}

	/**
	 * Get required settings keys.
	 *
	 * @return array
	 */
	public function get_required_settings_keys() {
		return array( 'api_key' );
	}
}

