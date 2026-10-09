<?php
/**
 * Minimal gateway for the payment settings screen example.
 *
 * @package WooCommerce\E2E
 */

declare( strict_types=1 );

defined( 'ABSPATH' ) || exit;

/**
 * A gateway with classic settings, so its section can redirect to the example screen.
 */
class Payment_Settings_Example_Gateway extends WC_Payment_Gateway {

	/**
	 * Set up the gateway.
	 */
	public function __construct() {
		$this->id                 = 'payment_settings_example';
		$this->method_title       = 'Example payments';
		$this->method_description = 'Example gateway for the payment settings screen.';
		$this->has_fields         = false;
		$this->init_form_fields();
		$this->init_settings();
		$this->title = $this->get_option( 'title' );
		add_action( 'woocommerce_update_options_payment_gateways_' . $this->id, array( $this, 'process_admin_options' ) );
	}

	/**
	 * Classic settings fields.
	 */
	public function init_form_fields() {
		$this->form_fields = array(
			'enabled' => array(
				'title'   => 'Enable/Disable',
				'type'    => 'checkbox',
				'label'   => 'Enable example payments',
				'default' => 'no',
			),
			'title'   => array(
				'title'   => 'Title',
				'type'    => 'text',
				'default' => 'Example payments',
			),
		);
	}
}
