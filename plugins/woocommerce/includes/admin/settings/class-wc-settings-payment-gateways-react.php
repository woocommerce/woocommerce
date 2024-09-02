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
	 * Get the whitelist of sections to render using React.
	 *
	 * @return array List of section identifiers.
	 */
	private function get_reactify_render_sections() {
		$sections = array(
			'offline',
			'woocommerce_payments',
			'main',
		);

		/**
		 * Filters the list of payment settings sections to be rendered using React.
		 *
		 * @since 9.3.0
		 *
		 * @param array $sections List of section identifiers.
		 */
		return apply_filters( 'experimental_woocommerce_admin_payment_reactify_render_sections', $sections );
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
		global $current_section;

		// We don't want to output anything from the action for now. So we buffer it and discard it.
		ob_start();
		/**
		 * Fires before the payment gateways settings fields are rendered.
		 *
		 * @since 1.5.7
		 */
		do_action( 'woocommerce_admin_field_payment_gateways' );
		ob_end_clean();

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

	/**
	 * Check if the given section should be rendered using React.
	 *
	 * @param string $section The section to check.
	 * @return bool Whether the section should be rendered using React.
	 */
	private function should_render_react_section( $section ) {
		return in_array( $section, $this->get_reactify_render_sections(), true );
	}

	/**
	 * Render the React section.
	 *
	 * @param string $section The section to render.
	 */
	private function render_react_section( $section ) {
		global $hide_save_button;
		$hide_save_button = true;
		echo '<div id="experimental_wc_settings_payments_' . esc_attr( $section ) . '"></div>';

		// Output the gateways data to the page so the React app can use it.
		$controller = new WC_REST_Payment_Gateways_Controller();
		$response   = $controller->get_items( new WP_REST_Request( 'GET', '/wc/v3/payment_gateways' ) );
		echo '<script type="application/json" id="experimental_wc_settings_payments_gateways">' . wp_json_encode( $response->data ) . '</script>';
	}

	/**
	 * Render the classic gateway settings page.
	 *
	 * @param array  $payment_gateways The payment gateways.
	 * @param string $current_section The current section.
	 */
	private function render_classic_gateway_settings_page( $payment_gateways, $current_section ) {
		foreach ( $payment_gateways as $gateway ) {
			if ( in_array( $current_section, array( $gateway->id, sanitize_title( get_class( $gateway ) ) ), true ) ) {
				if ( isset( $_GET['toggle_enabled'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended
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

	/**
	 * Save settings.
	 */
	public function save() {
		global $current_section;

		$wc_payment_gateways = WC_Payment_Gateways::instance();

		$this->save_settings_for_current_section();

		if ( ! $current_section ) {
			// If section is empty, we're on the main settings page. This makes sure 'gateway ordering' is saved.
			$wc_payment_gateways->process_admin_options();
			$wc_payment_gateways->init();
		} else {
			// There is a section - this may be a gateway or custom section.
			foreach ( $wc_payment_gateways->payment_gateways() as $gateway ) {
				if ( in_array( $current_section, array( $gateway->id, sanitize_title( get_class( $gateway ) ) ), true ) ) {
					/**
					 * Fires update actions for payment gateways.
					 *
					 * @since 3.4.0
					 *
					 * @param int $gateway->id Gateway ID.
					 */
					do_action( 'woocommerce_update_options_payment_gateways_' . $gateway->id );
					$wc_payment_gateways->init();
				}
			}

			$this->do_update_options_action();
		}
	}
}

return new WC_Settings_Payment_Gateways_React();
