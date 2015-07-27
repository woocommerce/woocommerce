<?php
/**
 * WooCommerce Payment Gateways class
 *
 * Loads payment gateways via hooks for use in the store.
 *
 * @class 		WC_Payment_Gateways
 * @version		2.2.0
 * @package		WooCommerce/Classes/Payment
 * @category	Class
 * @author 		WooThemes
 */
class WC_Payment_Gateways {

	/** @var array Array of payment gateway classes. */
	public $payment_gateways;

	/**
	 * @var WC_Payment_Gateways The single instance of the class
	 * @since 2.1
	 */
	protected static $_instance = null;

	/**
	 * Main WC_Payment_Gateways Instance
	 *
	 * Ensures only one instance of WC_Payment_Gateways is loaded or can be loaded.
	 *
	 * @since 2.1
	 * @static
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
		_doing_it_wrong( __FUNCTION__, __( 'Cheatin&#8217; huh?', 'woocommerce' ), '2.1' );
	}

	/**
	 * Unserializing instances of this class is forbidden.
	 *
	 * @since 2.1
	 */
	public function __wakeup() {
		_doing_it_wrong( __FUNCTION__, __( 'Cheatin&#8217; huh?', 'woocommerce' ), '2.1' );
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

		if ( 'US' === WC()->countries->get_base_country() ) {
			if ( class_exists( 'WC_Subscriptions_Order' ) || class_exists( 'WC_Pre_Orders_Order' ) ) {
				if ( ! function_exists( 'wcs_create_renewal_order' ) ) { // Subscriptions < 2.0
					$load_gateways[] = 'WC_Addons_Gateway_Simplify_Commerce_Deprecated';
				} else {
					$load_gateways[] = 'WC_Addons_Gateway_Simplify_Commerce';
				}
			} else {
				$load_gateways[] = 'WC_Gateway_Simplify_Commerce';
			}
		}

		// Filter
		$load_gateways = apply_filters( 'woocommerce_payment_gateways', $load_gateways );

		// Get sort order option
		$ordering  = (array) get_option( 'woocommerce_gateway_order' );
		$order_end = 999;

		// Load gateways in order
		foreach ( $load_gateways as $gateway ) {
			$load_gateway = is_string( $gateway ) ? new $gateway() : $gateway;

			if ( isset( $ordering[ $load_gateway->id ] ) && is_numeric( $ordering[ $load_gateway->id ] ) ) {
				// Add in position
				$this->payment_gateways[ $ordering[ $load_gateway->id ] ] = $load_gateway;
			} else {
				// Add to end of the array
				$this->payment_gateways[ $order_end ] = $load_gateway;
				$order_end++;
			}
		}

		ksort( $this->payment_gateways );
	}

	/**
	 * Get gateways.
	 *
	 * @access public
	 * @return array
	 */
	public function payment_gateways() {
		$_available_gateways = array();

		if ( sizeof( $this->payment_gateways ) > 0 ) {
			foreach ( $this->payment_gateways as $gateway ) {
				$_available_gateways[ $gateway->id ] = $gateway;
			}
		}

		return $_available_gateways;
	}

	/**
	 * Get available gateways.
	 *
	 * @access public
	 * @return array
	 */
	public function get_available_payment_gateways() {
		$_available_gateways = array();

		foreach ( $this->payment_gateways as $gateway ) {
			if ( $gateway->is_available() ) {
				if ( ! is_add_payment_method_page() ) {
					$_available_gateways[ $gateway->id ] = $gateway;
				} elseif( $gateway->supports( 'add_payment_method' ) ) {
					$_available_gateways[ $gateway->id ] = $gateway;
				}
			}
		}

		return apply_filters( 'woocommerce_available_payment_gateways', $_available_gateways );
	}

	/**
	 * Set the current, active gateway
	 */
	public function set_current_gateway( $gateways ) {
		$default = get_option( 'woocommerce_default_gateway', current( array_keys( $gateways ) ) );
		$current = WC()->session->get( 'chosen_payment_method', $default );

		if ( isset( $gateways[ $current ] ) ) {
			$gateways[ $current ]->set_current();
		} elseif ( isset( $gateways[ $default ] ) ) {
			$gateways[ $default ]->set_current();
		}
	}

	/**
	 * Save options in admin.
	 */
	public function process_admin_options() {

		$default_gateway = ( isset( $_POST['default_gateway'] ) ) ? esc_attr( $_POST['default_gateway'] ) : '';
		$gateway_order = ( isset( $_POST['gateway_order'] ) ) ? $_POST['gateway_order'] : '';

		$order = array();

		if ( is_array( $gateway_order ) && sizeof( $gateway_order ) > 0 ) {
			$loop = 0;
			foreach ( $gateway_order as $gateway_id ) {
				$order[ esc_attr( $gateway_id ) ] = $loop;
				$loop++;
			}
		}

		update_option( 'woocommerce_default_gateway', $default_gateway );
		update_option( 'woocommerce_gateway_order', $order );
	}
}
