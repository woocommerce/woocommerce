<?php
/**
 * WooCommerce Payment Gateways
 *
 * Loads payment gateways via hooks for use in the store.
 *
 * @version 2.2.0
 * @package WooCommerce\Classes\Payment
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
		);

		if ( $this->should_load_paypal_standard() ) {
			$load_gateways[] = 'WC_Gateway_Paypal';
		}

		// Filter.
		$load_gateways = apply_filters( 'woocommerce_payment_gateways', $load_gateways );

		// Get sort order option.
		$ordering  = (array) get_option( 'woocommerce_gateway_order' );
		$order_end = 999;

		// Load gateways in order.
		foreach ( $load_gateways as $gateway ) {
			if ( is_string( $gateway ) && class_exists( $gateway ) ) {
				$gateway = new $gateway();
			}

			// Gateways need to be valid and extend WC_Payment_Gateway.
			if ( ! is_a( $gateway, 'WC_Payment_Gateway' ) ) {
				continue;
			}

			if ( isset( $ordering[ $gateway->id ] ) && is_numeric( $ordering[ $gateway->id ] ) ) {
				// Add in position.
				$this->payment_gateways[ $ordering[ $gateway->id ] ] = $gateway;
			} else {
				// Add to end of the array.
				$this->payment_gateways[ $order_end ] = $gateway;
				$order_end++;
			}
		}

		ksort( $this->payment_gateways );

		// Listen for gateways being enabled so as to notify the admin.
		add_action( 'add_option', [ $this, 'payment_gateway_settings_add_option' ], 10, 2 );
		add_action( 'update_option', [ $this, 'payment_gateway_settings_update_option' ], 10, 3 );
	}

	/**
	 * Email the site admin when a payment gateway is enabled.
	 *
	 * @param string $option Option name.
	 * @param array  $old_value Old value.
	 * @param array  $value New value.
	 * @since 8.4.0
	 */
	public function payment_gateway_settings_update_option( $option, $old_value, $value ) {
		if ( ! $this->gateway_settings_enabled( $value, $old_value ) ) {
			return;
		}
		if ( ! $this->option_is_gateway_settings( $option ) ) {
			return;
		}

		// This is a change to a payment gateway's settings and it was just enabled. Let's send an email to the admin.
		$this->notify_admin_payment_gateway_enabled( $value['title'] );
	}

	public function payment_gateway_settings_add_option( $option, $value ) {
		if ( ! $this->gateway_settings_enabled( $value ) ) {
			return;
		}
		if ( ! $this->option_is_gateway_settings( $option ) ) {
			return;
		}

		// This is a change to a payment gateway's settings and it was just enabled. Let's send an email to the admin.
		$this->notify_admin_payment_gateway_enabled( $value['title'] );
	}

	private function notify_admin_payment_gateway_enabled( $gateway_title ) {
		$admin_email          = get_option( 'admin_email' );
		$user                 = get_user_by( 'email', $admin_email );
		$username             = $user ? $user->user_login : $admin_email;
		$gateway_settings_url = self_admin_url( 'admin.php?page=wc-settings&tab=checkout' );
		$site_name            = wp_specialchars_decode( get_option( 'blogname' ), ENT_QUOTES );
		$site_url             = home_url();
	
		/* translators: Payment gateway enabled notification email. 1: Username, 2: Gateway Title, 3: Site URL, 4: Gateway Settings URL, 5: Admin Email, 6: Site Name, 7: Site URL. */
		$email_text = sprintf(
			__(
				'Howdy %1$s,

The payment gateway "%2$s" was just enabled on this site:
%3$s

If this was intentional you can safely ignore and delete this email. 

If you did not enable this payment gateway, please log in to your site and consider disabling it here:
%4$s

This email has been sent to %5$s

Regards,
All at %6$s
%7$s',
				'woocommerce'
			),
			$username,
			$gateway_title,
			$site_url,
			$gateway_settings_url,
			$admin_email,
			$site_name,
			$site_url
		);
	
		if ( '' !== get_option( 'blogname' ) ) {
			$site_title = wp_specialchars_decode( get_option( 'blogname' ), ENT_QUOTES );
		} else {
			$site_title = wp_parse_url( home_url(), PHP_URL_HOST );
		}
	
		return wp_mail(
			$admin_email,
			sprintf(
				/* translators: Payment gateway enabled notification email subject. %s1: Site title, $s2: Gateway title. */
				__( '[%1$s] Payment gateway %2$s enabled', 'woocommerce' ),
				$site_title,
				$value['title']
			),
			$email_text
		);
	}
	
	private function option_is_gateway_settings( $option ) {
		foreach ( WC_Payment_Gateways::instance()->payment_gateways() as $gateway ) {
			if ( $option === $gateway->get_option_key() ) {
				return true;
			}
		}
		return false;
	}
	
	private function gateway_settings_enabled( $value, $old_value = null ) {
		if ( $old_value === null ) {
			// There was no old value, so this is a new option.
			if ( ! empty( $value) && is_array( $value ) && isset( $value['enabled'] ) && $value['enabled'] === 'yes' && isset( $value['title'] ) ) {
				return true;
			}
			return false;
		}
		// There was an old value, so this is an update.
		if ( ! empty( $value) && ! empty( $old_value) && is_array( $value ) && is_array( $old_value ) && isset( $value['enabled'] ) && isset( $old_value['enabled'] ) && $value['enabled'] === 'yes' && $old_value['enabled'] !== 'yes' && isset( $value['title'] ) ) {
			return true;
		}
		return false;
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

		return array_filter( (array) apply_filters( 'woocommerce_available_payment_gateways', $_available_gateways ), array( $this, 'filter_valid_gateway_class' ) );
	}

	/**
	 * Callback for array filter. Returns true if gateway is of correct type.
	 *
	 * @since 3.6.0
	 * @param object $gateway Gateway to check.
	 * @return bool
	 */
	protected function filter_valid_gateway_class( $gateway ) {
		return $gateway && is_a( $gateway, 'WC_Payment_Gateway' );
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

		$current_gateway = false;

		if ( WC()->session ) {
			$current = WC()->session->get( 'chosen_payment_method' );

			if ( $current && isset( $gateways[ $current ] ) ) {
				$current_gateway = $gateways[ $current ];
			}
		}

		if ( ! $current_gateway ) {
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

	/**
	 * Determines if PayPal Standard should be loaded.
	 *
	 * @since 5.5.0
	 * @return bool Whether PayPal Standard should be loaded or not.
	 */
	protected function should_load_paypal_standard() {
		$paypal = new WC_Gateway_Paypal();
		return $paypal->should_load();
	}
}
