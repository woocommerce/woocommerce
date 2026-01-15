<?php
/**
 * Class WC_Gateway_Cheque file.
 *
 * @package WooCommerce\Gateways
 */

use Automattic\WooCommerce\Enums\OrderStatus;
use Automattic\WooCommerce\Internal\Admin\Settings\Utils as SettingsUtils;

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

/**
 * Cheque Payment Gateway.
 *
 * Provides a Cheque Payment Gateway, mainly for testing purposes.
 *
 * @class       WC_Gateway_Cheque
 * @extends     WC_Payment_Gateway
 * @version     2.1.0
 * @package     WooCommerce\Classes\Payment
 */
class WC_Gateway_Cheque extends WC_Payment_Gateway {

	/**
	 * Unique ID for this gateway.
	 *
	 * @var string
	 */
	const ID = 'cheque';

	/**
	 * Gateway instructions that will be added to the thank you page and emails.
	 *
	 * @var string
	 */
	public $instructions;

	/**
	 * Constructor for the gateway.
	 */
	public function __construct() {
		$this->id                 = self::ID;
		$this->icon               = apply_filters( 'woocommerce_cheque_icon', '' );
		$this->has_fields         = false;
		$this->method_title       = _x( 'Check payments', 'Check payment method', 'woocommerce' );
		$this->method_description = __( 'Take payments in person via checks. This offline gateway can also be useful to test purchases.', 'woocommerce' );

		// Load the settings.
		$this->init_form_fields();
		$this->init_settings();

		// Define user set variables.
		$this->title        = $this->get_option( 'title' );
		$this->description  = $this->get_option( 'description' );
		$this->instructions = $this->get_option( 'instructions' );

		// Actions.
		add_action( 'woocommerce_update_options_payment_gateways_' . $this->id, array( $this, 'process_admin_options' ) );
		add_action( 'woocommerce_thankyou_cheque', array( $this, 'thankyou_page' ) );

		// Customer Emails.
		add_action( 'woocommerce_email_before_order_table', array( $this, 'email_instructions' ), 10, 3 );
	}

	/**
	 * Initialise Gateway Settings Form Fields.
	 */
	public function init_form_fields() {

		$this->form_fields = array(
			'enabled'      => array(
				'title'   => __( 'Enable/Disable', 'woocommerce' ),
				'type'    => 'checkbox',
				'label'   => __( 'Enable check payments', 'woocommerce' ),
				'default' => 'no',
			),
			'title'        => array(
				'title'       => __( 'Title', 'woocommerce' ),
				'type'        => 'safe_text',
				'description' => __( 'This controls the title which the user sees during checkout.', 'woocommerce' ),
				'default'     => _x( 'Check payments', 'Check payment method', 'woocommerce' ),
				'desc_tip'    => true,
			),
			'description'  => array(
				'title'       => __( 'Description', 'woocommerce' ),
				'type'        => 'textarea',
				'description' => __( 'Payment method description that the customer will see on your checkout.', 'woocommerce' ),
				'default'     => __( 'Please send a check to Store Name, Store Street, Store Town, Store State / County, Store Postcode.', 'woocommerce' ),
				'desc_tip'    => true,
			),
			'instructions' => array(
				'title'       => __( 'Instructions', 'woocommerce' ),
				'type'        => 'textarea',
				'description' => __( 'Instructions that will be added to the thank you page and emails.', 'woocommerce' ),
				'default'     => '',
				'desc_tip'    => true,
			),
		);
	}

	/**
	 * Output for the order received page.
	 */
	public function thankyou_page() {
		if ( $this->instructions ) {
			echo wp_kses_post( wpautop( wptexturize( $this->instructions ) ) );
		}
	}

	/**
	 * Add content to the WC emails.
	 *
	 * @access public
	 * @param WC_Order $order Order object.
	 * @param bool     $sent_to_admin Sent to admin.
	 * @param bool     $plain_text Email format: plain text or HTML.
	 */
	public function email_instructions( $order, $sent_to_admin, $plain_text = false ) {
		if ( $this->instructions && ! $sent_to_admin && self::ID === $order->get_payment_method() ) {
			/**
			 * Filter the email instructions order status.
			 *
			 * @since 7.4
			 *
			 * @param string $status The default status.
			 * @param object $order  The order object.
			 */
			$instructions_order_status = apply_filters( 'woocommerce_cheque_email_instructions_order_status', OrderStatus::ON_HOLD, $order );
			if ( $order->has_status( $instructions_order_status ) ) {
				echo wp_kses_post( wpautop( wptexturize( $this->instructions ) ) . PHP_EOL );
			}
		}
	}

	/**
	 * Process the payment and return the result.
	 *
	 * @param int $order_id Order ID.
	 * @return array
	 */
	public function process_payment( $order_id ) {

		$order = wc_get_order( $order_id );

		if ( $order->get_total() > 0 ) {
			/**
			 * Filter the order status for cheque payment.
			 *
			 * @since 3.6.0
			 *
			 * @param string $status The default status.
			 * @param object $order  The order object.
			 */
			$process_payment_status = apply_filters( 'woocommerce_cheque_process_payment_order_status', OrderStatus::ON_HOLD, $order );
			// Mark as on-hold (we're awaiting the cheque).
			$order->update_status( $process_payment_status, _x( 'Awaiting check payment.', 'Check payment method', 'woocommerce' ) );
		} else {
			$order->payment_complete();
		}

		// Remove cart.
		WC()->cart->empty_cart();

		// Return thankyou redirect.
		return array(
			'result'   => 'success',
			'redirect' => $this->get_return_url( $order ),
		);
	}

	/**
	 * Get the settings URL for the gateway.
	 *
	 * @return string The settings page URL for the gateway.
	 */
	public function get_settings_url() {
		// Search for a WC_Settings_Payment_Gateways instance in the settings pages.
		$payments_settings_page = null;
		foreach ( WC_Admin_Settings::get_settings_pages() as $settings_page ) {
			if ( $settings_page instanceof WC_Settings_Payment_Gateways ) {
				$payments_settings_page = $settings_page;
				break;
			}
		}
		// If no instance found, return the default settings URL (the Reactified page).
		if ( empty( $payments_settings_page ) ) {
			return SettingsUtils::wc_payments_settings_url( '/' . WC_Settings_Payment_Gateways::OFFLINE_SECTION_NAME . '/' . $this->id );
		}

		$should_use_react_settings_page = $payments_settings_page->should_render_react_section( WC_Settings_Payment_Gateways::CHEQUE_SECTION_NAME );

		// We must not include both the path and the section query parameter, as this can cause weird behavior.
		return SettingsUtils::wc_payments_settings_url(
			$should_use_react_settings_page ? '/' . WC_Settings_Payment_Gateways::OFFLINE_SECTION_NAME . '/' . $this->id : null,
			$should_use_react_settings_page ? array() : array( 'section' => $this->id )
		);
	}
}
