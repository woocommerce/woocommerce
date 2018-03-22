<?php
/**
 * WooCommerce Payment Gateways
 *
 * Loads payment gateways via hooks for use in the store.
 *
 * @version 2.2.0
 * @package WooCommerce/Classes/Payment
 */

defined( 'ABSPATH' ) || exit;

/**
 * Payment gateways class.
 */
class WC_Payment_Gateways {

	/**
	 * Payment gateway classes.
	 *
	 * @var array
	 */
	public $payment_gateways = array();

	/**
	 * The single instance of the class.
	 *
	 * @var WC_Payment_Gateways
	 * @since 2.1.0
	 */
	protected static $_instance = null;

	/**
	 * Main WC_Payment_Gateways Instance.
	 *
	 * Ensures only one instance of WC_Payment_Gateways is loaded or can be loaded.
	 *
	 * @since 2.1
	 * @return WC_Payment_Gateways Main instance
	 */
	public static function instance() {
		if ( is_null( self::$_instance ) ) {
			self::$_instance = new self();
		}
		return self::$_instance;
	}

	/**
	 * Cloning is forbidden.
	 *
	 * @since 2.1
	 */
	public function __clone() {
		wc_doing_it_wrong( __FUNCTION__, __( 'Cloning is forbidden.', 'woocommerce' ), '2.1' );
	}

	/**
	 * Unserializing instances of this class is forbidden.
	 *
	 * @since 2.1
	 */
	public function __wakeup() {
		wc_doing_it_wrong( __FUNCTION__, __( 'Unserializing instances of this class is forbidden.', 'woocommerce' ), '2.1' );
	}

	/**
	 * Initialize payment gateways.
	 */
	public function __construct() {
		$this->init();
	}

	/**
	 * Load gateways and hook in functions.
	 */
	public function init() {
		$load_gateways = array(
			'WC_Gateway_BACS',
			'WC_Gateway_Cheque',
			'WC_Gateway_COD',
			'WC_Gateway_Paypal',
		);

		/**
		 * Simplify Commerce is @deprecated in 2.6.0. Only load when enabled.
		 */
		if ( ! class_exists( 'WC_Gateway_Simplify_Commerce_Loader' ) && in_array( WC()->countries->get_base_country(), apply_filters( 'woocommerce_gateway_simplify_commerce_supported_countries', array( 'US', 'IE' ) ), true ) ) {
			$simplify_options = get_option( 'woocommerce_simplify_commerce_settings', array() );

			if ( ! empty( $simplify_options['enabled'] ) && 'yes' === $simplify_options['enabled'] ) {
				if ( function_exists( 'wcs_create_renewal_order' ) ) {
					$load_gateways[] = 'WC_Addons_Gateway_Simplify_Commerce';
				} else {
					$load_gateways[] = 'WC_Gateway_Simplify_Commerce';
				}
			}
		}

		// Filter.
		$load_gateways = apply_filters( 'woocommerce_payment_gateways', $load_gateways );

		// Get sort order option.
		$ordering  = (array) get_option( 'woocommerce_gateway_order' );
		$order_end = 999;

		// Load gateways in order.
		foreach ( $load_gateways as $gateway ) {
			$load_gateway = is_string( $gateway ) ? new $gateway() : $gateway;

			if ( isset( $ordering[ $load_gateway->id ] ) && is_numeric( $ordering[ $load_gateway->id ] ) ) {
				// Add in position.
				$this->payment_gateways[ $ordering[ $load_gateway->id ] ] = $load_gateway;
			} else {
				// Add to end of the array.
				$this->payment_gateways[ $order_end ] = $load_gateway;
				$order_end++;
			}
		}

		ksort( $this->payment_gateways );
	}

	/**
	 * Get gateways.
	 *
	 * @return array
	 */
	public function payment_gateways() {
		$_available_gateways = array();

		if ( count( $this->payment_gateways ) > 0 ) {
			foreach ( $this->payment_gateways as $gateway ) {
				$_available_gateways[ $gateway->id ] = $gateway;
			}
		}

		return $_available_gateways;
	}

	/**
	 * Get array of registered gateway ids
	 *
	 * @since 2.6.0
	 * @return array of strings
	 */
	public function get_payment_gateway_ids() {
		return wp_list_pluck( $this->payment_gateways, 'id' );
	}

	/**
	 * Get available gateways.
	 *
	 * @return array
	 */
	public function get_available_payment_gateways() {
		$_available_gateways = array();

		foreach ( $this->payment_gateways as $gateway ) {
			if ( $gateway->is_available() ) {
				if ( ! is_add_payment_method_page() ) {
					$_available_gateways[ $gateway->id ] = $gateway;
				} elseif ( $gateway->supports( 'add_payment_method' ) || $gateway->supports( 'tokenization' ) ) {
					$_available_gateways[ $gateway->id ] = $gateway;
				}
			}
		}

		return apply_filters( 'woocommerce_available_payment_gateways', $_available_gateways );
	}

	/**
	 * Set the current, active gateway.
	 *
	 * @param array $gateways Available payment gateways.
	 */
	public function set_current_gateway( $gateways ) {
		// Be on the defensive.
		if ( ! is_array( $gateways ) || empty( $gateways ) ) {
			return;
		}

		if ( is_user_logged_in() ) {
			$default_token = WC_Payment_Tokens::get_customer_default_token( get_current_user_id() );
			if ( ! is_null( $default_token ) ) {
				$default_token_gateway = $default_token->get_gateway_id();
			}
		}

		$current = ( isset( $default_token_gateway ) ? $default_token_gateway : WC()->session->get( 'chosen_payment_method' ) );

		if ( $current && isset( $gateways[ $current ] ) ) {
			$current_gateway = $gateways[ $current ];

		} else {
			$current_gateway = current( $gateways );
		}

		// Ensure we can make a call to set_current() without triggering an error.
		if ( $current_gateway && is_callable( array( $current_gateway, 'set_current' ) ) ) {
			$current_gateway->set_current();
		}
	}

	/**
	 * Save options in admin.
	 */
	public function process_admin_options() {
		$gateway_order = isset( $_POST['gateway_order'] ) ? wc_clean( wp_unslash( $_POST['gateway_order'] ) ) : ''; // WPCS: input var ok, CSRF ok.
		$order         = array();

		if ( is_array( $gateway_order ) && count( $gateway_order ) > 0 ) {
			$loop = 0;
			foreach ( $gateway_order as $gateway_id ) {
				$order[ esc_attr( $gateway_id ) ] = $loop;
				$loop++;
			}
		}

		update_option( 'woocommerce_gateway_order', $order );
	}
}
