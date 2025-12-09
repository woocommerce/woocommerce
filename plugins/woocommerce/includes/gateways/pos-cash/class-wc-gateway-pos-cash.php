<?php
/**
 * Class WC_Gateway_POS_Cash file.
 *
 * @package WooCommerce\Gateways
 */

use Automattic\WooCommerce\Enums\OrderStatus;

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

/**
 * POS Cash Payment Gateway.
 *
 * A payment gateway for Point of Sale cash transactions. This gateway is only
 * available during POS checkout sessions and immediately marks orders as paid.
 *
 * @class       WC_Gateway_POS_Cash
 * @extends     WC_Payment_Gateway
 * @package     WooCommerce\Classes\Payment
 */
class WC_Gateway_POS_Cash extends WC_Payment_Gateway {

	/**
	 * Unique ID for this gateway.
	 *
	 * @var string
	 */
	const ID = 'pos_cash';

	/**
	 * Constructor for the gateway.
	 */
	public function __construct() {
		$this->id                 = self::ID;
		$this->icon               = '';
		$this->has_fields         = false;
		$this->method_title       = __( 'POS Cash', 'woocommerce' );
		$this->method_description = __( 'Accept cash payments at Point of Sale. This gateway is only available for POS checkout sessions.', 'woocommerce' );

		// Load the settings.
		$this->init_form_fields();
		$this->init_settings();

		$this->title       = $this->get_option( 'title', __( 'Cash', 'woocommerce' ) );
		$this->description = $this->get_option( 'description', '' );

		// This gateway doesn't need to be enabled in settings - it's auto-available for POS.
		$this->enabled = 'yes';

		add_action( 'woocommerce_update_options_payment_gateways_' . $this->id, array( $this, 'process_admin_options' ) );
	}

	/**
	 * Initialise Gateway Settings Form Fields.
	 */
	public function init_form_fields() {
		$this->form_fields = array(
			'title'       => array(
				'title'       => __( 'Title', 'woocommerce' ),
				'type'        => 'safe_text',
				'description' => __( 'Payment method title shown during POS checkout.', 'woocommerce' ),
				'default'     => __( 'Cash', 'woocommerce' ),
				'desc_tip'    => true,
			),
			'description' => array(
				'title'       => __( 'Description', 'woocommerce' ),
				'type'        => 'textarea',
				'description' => __( 'Payment method description shown during POS checkout.', 'woocommerce' ),
				'default'     => '',
				'desc_tip'    => true,
			),
		);
	}

	/**
	 * Check if the gateway is available for use.
	 *
	 * This gateway is ONLY available for POS sessions.
	 *
	 * @return bool
	 */
	public function is_available() {
		return wc_is_pos_session();
	}

	/**
	 * Process the payment and return the result.
	 *
	 * For POS cash payments, we trust that the cash has been received
	 * and immediately mark the order as complete.
	 *
	 * @param int $order_id Order ID.
	 * @return array
	 */
	public function process_payment( $order_id ) {
		$order = wc_get_order( $order_id );

		// Mark the order as paid - cash has been received at the point of sale.
		$order->payment_complete();

		// Add order note indicating POS cash payment.
		$order->add_order_note( __( 'Payment received via POS cash transaction.', 'woocommerce' ) );

		// Start a fresh transaction for the next POS customer.
		$this->start_new_pos_transaction();

		return array(
			'result'   => 'success',
			'redirect' => $this->get_return_url( $order ),
		);
	}

	/**
	 * Start a new POS transaction by clearing the transaction ID.
	 *
	 * This ensures that customer data from one transaction doesn't carry over
	 * to the next. In POS, each transaction serves a different customer.
	 *
	 * The next POS request will get a fresh session with a new transaction ID
	 * because POSSessionHandler checks if the cart is empty when initializing.
	 */
	private function start_new_pos_transaction() {
		// Simply empty the cart. The POSSessionHandler will detect an empty cart
		// on the next request and create a fresh session with a new transaction ID.
		WC()->cart->empty_cart();
	}
}
