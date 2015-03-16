<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

/**
 * WooCommerce Payment Gateway class
 *
 * Extended by individual payment gateways to handle payments.
 *
 * @class       WC_Payment_Gateway
 * @extends     WC_Settings_API
 * @version     2.1.0
 * @package     WooCommerce/Abstracts
 * @category    Abstract Class
 * @author      WooThemes
 */
abstract class WC_Payment_Gateway extends WC_Settings_API {

	/**
	 * Set if the place order button should be renamed on selection
	 * @var string
	 */
	public $order_button_text;

	/**
	 * Payment method title
	 * @var string
	 */
	public $title;

	/**
	 * Chosen payment method id
	 * @var bool
	 */
	public $chosen;

	/**
	 * True if the gateway shows fields on the checkout
	 * @var bool
	 */
	public $has_fields;

	/**
	 * Countries this gateway is allowed for
	 * @var array
	 */
	public $countries;

	/**
	 * Available for all counties or specific
	 * @var string
	 */
	public $availability;

	/**
	 * Icon for the gateway
	 * @var string
	 */
	public $icon;

	/**
	 * Description for the gateway
	 * @var string
	 */
	public $description;

	/**
	 * Supported features such as 'default_credit_card_form', 'refunds'
	 * @var array
	 */
	public $supports = array( 'products' );

	/**
	 * Maximum transaction amount, zero does not define a maximum
	 * @var int
	 */
	public $max_amount = 0;

	/**
	 * Optional URL to view a transaction
	 * @var string
	 */
	public $view_transaction_url = '';

	/**
	 * Get the return url (thank you page)
	 *
	 * @param WC_Order $order
	 * @return string
	 */
	public function get_return_url( $order = null ) {

		if ( $order ) {
			$return_url = $order->get_checkout_order_received_url();
		} else {
			$return_url = wc_get_endpoint_url( 'order-received', '', wc_get_page_permalink( 'checkout' ) );
		}

		if ( is_ssl() || get_option('woocommerce_force_ssl_checkout') == 'yes' ) {
			$return_url = str_replace( 'http:', 'https:', $return_url );
		}

		return apply_filters( 'woocommerce_get_return_url', $return_url );
	}

	/**
	 * Get a link to the transaction on the 3rd party gateway size (if applicable)
	 *
	 * @param  WC_Order $order the order object
	 * @return string transaction URL, or empty string
	 */
	public function get_transaction_url( $order ) {

		$return_url = '';
		$transaction_id = $order->get_transaction_id();

		if ( ! empty( $this->view_transaction_url ) && ! empty( $transaction_id ) ) {
			$return_url = sprintf( $this->view_transaction_url, $transaction_id );
		}

		return apply_filters( 'woocommerce_get_transaction_url', $return_url, $order, $this );
	}

	/**
	 * Get the order total in checkout and pay_for_order.
	 *
	 * @return bool
	 */
	protected function get_order_total() {

		$total = 0;
		$order_id = absint( get_query_var( 'order-pay' ) );

		// Gets order total from "pay for order" page.
		if ( 0 < $order_id ) {
			$order = wc_get_order( $order_id );
			$total = (float) $order->get_total();

		// Gets order total from cart/checkout.
		} elseif ( 0 < WC()->cart->total ) {
			$total = (float) WC()->cart->total;
		}

		return $total;
	}

	/**
	 * Check if the gateway is available for use
	 *
	 * @return bool
	 */
	public function is_available() {

		$is_available = ( 'yes' === $this->enabled ) ? true : false;

		if ( WC()->cart && 0 < $this->get_order_total() && 0 < $this->max_amount && $this->max_amount < $this->get_order_total() ) {
			$is_available = false;
		}

		return $is_available;
	}

	/**
	 * has_fields function.
	 *
	 * @return bool
	 */
	public function has_fields() {
		return $this->has_fields ? true : false;
	}

	/**
	 * Return the gateway's title
	 *
	 * @return string
	 */
	public function get_title() {
		return apply_filters( 'woocommerce_gateway_title', $this->title, $this->id );
	}

	/**
	 * Return the gateway's description
	 *
	 * @return string
	 */
	public function get_description() {
		return apply_filters( 'woocommerce_gateway_description', $this->description, $this->id );
	}

	/**
	 * get_icon function.
	 *
	 * @return string
	 */
	public function get_icon() {

		$icon = $this->icon ? '<img src="' . WC_HTTPS::force_https_url( $this->icon ) . '" alt="' . esc_attr( $this->get_title() ) . '" />' : '';

		return apply_filters( 'woocommerce_gateway_icon', $icon, $this->id );
	}

	/**
	 * Set as current gateway.
	 *
	 * Set this as the current gateway.
	 *
	 * @access public
	 * @return void
	 */
	public function set_current() {
		$this->chosen = true;
	}

	/**
	 * Process Payment
	 *
	 * Process the payment. Override this in your gateway. When implemented, this should
	 * return the success and redirect in an array. e.g.
	 *
	 *        return array(
	 *            'result'   => 'success',
	 *            'redirect' => $this->get_return_url( $order )
	 *        );
	 *
	 * @param int $order_id
	 * @return array
	 */
	public function process_payment( $order_id ) {
		return array();
	}

	/**
	 * Process refund
	 *
	 * If the gateway declares 'refunds' support, this will allow it to refund
	 * a passed in amount.
	 *
	 * @param  int $order_id
	 * @param  float $amount
	 * @param  string $reason
	 * @return bool|WP_Error True or false based on success, or a WP_Error object
	 */
	public function process_refund( $order_id, $amount = null, $reason = '' ) {
		return false;
	}

	/**
	 * Validate frontend fields
	 *
	 * Validate payment fields on the frontend.
	 */
	public function validate_fields() { return true; }

	/**
	 * If There are no payment fields show the description if set.
	 * Override this in your gateway if you have some.
	 */
	public function payment_fields() {

		if ( $description = $this->get_description() ) {
			echo wpautop( wptexturize( $description ) );
		}

		if ( $this->supports( 'default_credit_card_form' ) ) {
			$this->credit_card_form();
		}
	}

	/**
	 * Check if a gateway supports a given feature.
	 *
	 * Gateways should override this to declare support (or lack of support) for a feature.
	 * For backward compatibility, gateways support 'products' by default, but nothing else.
	 *
	 * @param string $feature string The name of a feature to test support for.
	 * @return bool True if the gateway supports the feature, false otherwise.
	 * @since 1.5.7
	 */
	public function supports( $feature ) {
		return apply_filters( 'woocommerce_payment_gateway_supports', in_array( $feature, $this->supports ) ? true : false, $feature, $this );
	}

	/**
	 * Core credit card form which gateways can used if needed.
	 *
	 * @param  array $args
	 */
	public function credit_card_form( $args = array(), $fields = array() ) {

		wp_enqueue_script( 'wc-credit-card-form' );

		$default_args = array(
			'fields_have_names' => true, // Some gateways like stripe don't need names as the form is tokenized
		);

		$args = wp_parse_args( $args, apply_filters( 'woocommerce_credit_card_form_args', $default_args, $this->id ) );

		$default_fields = array(
			'card-number-field' => '<p class="form-row form-row-wide">
				<label for="' . esc_attr( $this->id ) . '-card-number">' . __( 'Card Number', 'woocommerce' ) . ' <span class="required">*</span></label>
				<input id="' . esc_attr( $this->id ) . '-card-number" class="input-text wc-credit-card-form-card-number" type="text" maxlength="20" autocomplete="off" placeholder="•••• •••• •••• ••••" name="' . ( $args['fields_have_names'] ? $this->id . '-card-number' : '' ) . '" />
			</p>',
			'card-expiry-field' => '<p class="form-row form-row-first">
				<label for="' . esc_attr( $this->id ) . '-card-expiry">' . __( 'Expiry (MM/YY)', 'woocommerce' ) . ' <span class="required">*</span></label>
				<input id="' . esc_attr( $this->id ) . '-card-expiry" class="input-text wc-credit-card-form-card-expiry" type="text" autocomplete="off" placeholder="' . __( 'MM / YY', 'woocommerce' ) . '" name="' . ( $args['fields_have_names'] ? $this->id . '-card-expiry' : '' ) . '" />
			</p>',
			'card-cvc-field' => '<p class="form-row form-row-last">
				<label for="' . esc_attr( $this->id ) . '-card-cvc">' . __( 'Card Code', 'woocommerce' ) . ' <span class="required">*</span></label>
				<input id="' . esc_attr( $this->id ) . '-card-cvc" class="input-text wc-credit-card-form-card-cvc" type="text" autocomplete="off" placeholder="' . __( 'CVC', 'woocommerce' ) . '" name="' . ( $args['fields_have_names'] ? $this->id . '-card-cvc' : '' ) . '" />
			</p>'
		);

		$fields = wp_parse_args( $fields, apply_filters( 'woocommerce_credit_card_form_fields', $default_fields, $this->id ) );
		?>
		<fieldset id="<?php echo $this->id; ?>-cc-form">
			<?php do_action( 'woocommerce_credit_card_form_start', $this->id ); ?>
			<?php
				foreach ( $fields as $field ) {
					echo $field;
				}
			?>
			<?php do_action( 'woocommerce_credit_card_form_end', $this->id ); ?>
			<div class="clear"></div>
		</fieldset>
		<?php
	}
}
