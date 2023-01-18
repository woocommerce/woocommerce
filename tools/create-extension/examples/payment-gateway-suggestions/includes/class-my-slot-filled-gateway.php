<?php
/**
 * Class My_Slot_Filled_Gateway
 *
 * @package WooCommerce\Admin
 */

/**
 * Class My_Simple_Gateway
 */
class My_Slot_Filled_Gateway extends WC_Payment_Gateway {
	/**
	 * Constructor for the gateway.
	 */
	public function __construct() {
		$this->id                 = 'my-slot-filled-gateway';
		$this->has_fields         = false;
		$this->method_title       = __( 'Slot filled gateway', 'woocommerce-admin' );
		$this->method_description = __( 'A gateway demonstrating the use of slot fill for custom configuration screens.', 'woocommerce-admin' );

		// Load the settings.
		$this->init_form_fields();
		$this->init_settings();

		add_action( 'woocommerce_update_options_payment_gateways_' . $this->id, array( $this, 'process_admin_options' ) );
	}

	/**
	 * Initialise Gateway Settings Form Fields.
	 */
	public function init_form_fields() {
		$this->form_fields = array(
			'enabled'    => array(
				'title'   => __( 'Enabled', 'woocommerce-admin' ),
				'type'    => 'checkbox',
				'label'   => '',
				'default' => 'no',
			),
			'my_setting' => array(
				'title'   => __( 'My setting', 'woocommerce-admin' ),
				'type'    => 'text',
				'default' => '',
			),
		);
	}

	/**
	 * Determine if the gateway requires further setup.
	 */
	public function needs_setup() {
		$settings = get_option( 'woocommerce_my-slot-filled-gateway_settings', array() );
		return ! isset( $settings['my_setting'] ) || empty( $settings['my_setting'] );
	}

	/**
	 * Get post install script handles.
	 *
	 * @return array
	 */
	public function get_post_install_script_handles() {
		$asset_file = require __DIR__ . '/../build/index.asset.php';
		wp_register_script(
			'payment-gateway-suggestion-slot-fill',
			plugins_url( '/build/index.js', __FILE__ ),
			$asset_file['dependencies'],
			$asset_file['version'],
			true
		);

		return array(
			'payment-gateway-suggestion-slot-fill',
		);
	}
}

