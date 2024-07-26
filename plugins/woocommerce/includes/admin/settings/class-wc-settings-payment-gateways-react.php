<?php
declare( strict_types = 1);

// @codingStandardsIgnoreLine.
/**
 * WooCommerce Checkout Settings
 *
 * @package WooCommerce\Admin
 */

defined( 'ABSPATH' ) || exit;

if ( class_exists( 'WC_Settings_Payment_Gateways_React', false ) ) {
	return new WC_Settings_Payment_Gateways_React();
}

/**
 * WC_Settings_Payment_Gateways_React.
 */
class WC_Settings_Payment_Gateways_React extends WC_Settings_Page {

	/**
	 * Whitelist of sections to render using React.
	 *
	 * @return array
	 */
	private function get_reactify_render_sections() {
		$sections = [
			'offline',
			'woocommerce_payments',
			'main',
		];

		return apply_filters( 'woocommerce_admin_payment_reactify_render_sections', $sections );
	}

	/**
	 * Constructor.
	 */
	public function __construct() {
		$this->id    = 'checkout';
		$this->label = _x( 'Payments', 'Settings tab label', 'woocommerce' );
		parent::__construct();
	}

	/**
	 * Output the settings.
	 */
	public function output() {
		//phpcs:disable WordPress.Security.NonceVerification.Recommended
		global $current_section, $hide_save_button;
		$hide_save_button = true;

		// Load gateways so we can show any global options they may have.
		$payment_gateways = WC()->payment_gateways->payment_gateways();

		if ( $this->should_render_react_section( $current_section ) ) {
			$this->render_react_section( $current_section );
		} elseif ( $current_section ) {
			$this->render_classic_gateway_settings_page( $payment_gateways, $current_section );
		} else {
			$this->render_react_section( 'main' );
		}

		parent::output();
		//phpcs:enable
	}

	private function should_render_react_section( $section ) {
		return in_array( $section, $this->get_reactify_render_sections(), true );
	}

	private function render_react_section( $section ) {
		echo '<div id="experimental_wc_settings_payments_' . esc_attr( $section ) . '"></div>';
	}

	private function render_classic_gateway_settings_page( $payment_gateways, $current_section ) {
		foreach ( $payment_gateways as $gateway ) {
			if ( in_array( $current_section, array( $gateway->id, sanitize_title( get_class( $gateway ) ) ), true ) ) {
				if ( isset( $_GET['toggle_enabled'] ) ) {
					$enabled = $gateway->get_option( 'enabled' );

					if ( $enabled ) {
						$gateway->settings['enabled'] = wc_string_to_bool( $enabled ) ? 'no' : 'yes';
					}
				}
				$this->run_gateway_admin_options( $gateway );
				break;
			}
		}
	}

	/**
	 * Run the 'admin_options' method on a given gateway.
	 * This method exists to easy unit testing.
	 *
	 * @param object $gateway The gateway object to run the method on.
	 */
	protected function run_gateway_admin_options( $gateway ) {
		$gateway->admin_options();
	}

	/**
	 * Don't show any section links.
	 *
	 * @return array
	 */
	public function get_sections() {
		return array();
	}
}

return new WC_Settings_Payment_Gateways_React();
