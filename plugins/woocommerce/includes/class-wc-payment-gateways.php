<?php
/**
 * WooCommerce Payment Gateways
 *
 * Loads payment gateways via hooks for use in the store.
 *
 * @version 2.2.0
 * @package WooCommerce\Classes\Payment
 */

use Automattic\WooCommerce\Proxies\LegacyProxy;
use Automattic\WooCommerce\Utilities\ArrayUtil;

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
				++$order_end;
			}
		}

		ksort( $this->payment_gateways );

		add_action( 'wc_payment_gateways_initialized', array( $this, 'on_payment_gateways_initialized' ) );
		/**
		 * Hook that is called when the payment gateways have been initialized.
		 *
		 * @param WC_Payment_Gateways $wc_payment_gateways The payment gateways instance.
		 * @since 8.5.0
		 */
		do_action( 'wc_payment_gateways_initialized', $this );
	}

	// phpcs:disable Generic.CodeAnalysis.UnusedFunctionParameter.Found

	/**
	 * Hook into payment gateway settings changes.
	 *
	 * @param WC_Payment_Gateways $wc_payment_gateways The WC_Payment_Gateways instance.
	 * @since 8.5.0
	 *
	 * @internal For exclusive usage of WooCommerce core, backwards compatibility not guaranteed.
	 */
	public function on_payment_gateways_initialized( WC_Payment_Gateways $wc_payment_gateways ) {
		foreach ( $this->payment_gateways as $gateway ) {
			$option_key = $gateway->get_option_key();
			add_action(
				'add_option_' . $option_key,
				function ( $option, $value ) use ( $gateway ) {
					$this->payment_gateway_settings_option_changed( $gateway, $value, $option );
				},
				10,
				2
			);
			add_action(
				'update_option_' . $option_key,
				function ( $old_value, $value, $option ) use ( $gateway ) {
					$this->payment_gateway_settings_option_changed( $gateway, $value, $option, $old_value );
				},
				10,
				3
			);
		}
	}

	// phpcs:enable Generic.CodeAnalysis.UnusedFunctionParameter.Found

	/**
	 * Callback for when a gateway settings option was added or updated.
	 *
	 * @param WC_Payment_Gateway $gateway   The gateway for which the option was added or updated.
	 * @param mixed              $value     New value.
	 * @param string             $option    Option name.
	 * @param mixed              $old_value Old value. `null` when called via add_option_ hook.
	 * @since 8.5.0
	 */
	private function payment_gateway_settings_option_changed( $gateway, $value, $option, $old_value = null ) {
		if ( ! $this->was_gateway_enabled( $value, $old_value ) ) {
			return;
		}

		// This is a change to a payment gateway's settings and it was just enabled. Let's send an email to the admin.
		// "untitled" shouldn't happen, but just in case.
		$this->notify_admin_payment_gateway_enabled( $gateway );
	}

	/**
	 * Email the site admin when a payment gateway has been enabled.
	 *
	 * @param WC_Payment_Gateway $gateway The gateway that was enabled.
	 * @return bool Whether the email was sent or not.
	 * @since 8.5.0
	 */
	private function notify_admin_payment_gateway_enabled( $gateway ) {
		$admin_email          = get_option( 'admin_email' );
		$user                 = get_user_by( 'email', $admin_email );
		$username             = $user ? $user->user_login : $admin_email;
		$gateway_title        = $gateway->get_method_title();
		$gateway_settings_url = esc_url_raw( self_admin_url( 'admin.php?page=wc-settings&tab=checkout&section=' . $gateway->id ) );
		$site_name            = wp_specialchars_decode( get_option( 'blogname' ), ENT_QUOTES );
		$site_url             = home_url();
		/**
		 * Allows adding to the addresses that receive payment gateway enabled notifications.
		 *
		 * @param array              $email_addresses The array of email addresses to notify.
		 * @param WC_Payment_Gateway $gateway The gateway that was enabled.
		 * @return array             The augmented array of email addresses to notify.
		 * @since 8.5.0
		 */
		$email_addresses   = apply_filters( 'wc_payment_gateway_enabled_notification_email_addresses', array(), $gateway );
		$email_addresses[] = $admin_email;
		$email_addresses   = array_unique(
			array_filter(
				$email_addresses,
				function ( $email_address ) {
					return filter_var( $email_address, FILTER_VALIDATE_EMAIL );
				}
			)
		);

		$logger = wc_get_container()->get( LegacyProxy::class )->call_function( 'wc_get_logger' );
		$logger->info( sprintf( 'Payment gateway enabled: "%s"', $gateway_title ) );

		$email_text = sprintf(
			/* translators: Payment gateway enabled notification email. 1: Username, 2: Gateway Title, 3: Site URL, 4: Gateway Settings URL, 5: Admin Email, 6: Site Name, 7: Site URL. */
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
			$email_addresses,
			sprintf(
				/* translators: Payment gateway enabled notification email subject. %s1: Site title, $s2: Gateway title. */
				__( '[%1$s] Payment gateway "%2$s" enabled', 'woocommerce' ),
				$site_title,
				$gateway_title
			),
			$email_text
		);
	}

	/**
	 * Determines from changes in settings if a gateway was enabled.
	 *
	 * @param array $value New value.
	 * @param array $old_value Old value.
	 * @return bool Whether the gateway was enabled or not.
	 */
	private function was_gateway_enabled( $value, $old_value = null ) {
		if ( null === $old_value ) {
			// There was no old value, so this is a new option.
			if ( ! empty( $value ) && is_array( $value ) && isset( $value['enabled'] ) && 'yes' === $value['enabled'] && isset( $value['title'] ) ) {
				return true;
			}
			return false;
		}
		// There was an old value, so this is an update.
		if (
			ArrayUtil::get_value_or_default( $value, 'enabled' ) === 'yes' &&
			ArrayUtil::get_value_or_default( $old_value, 'enabled' ) !== 'yes' ) {
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
				++$loop;
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
		// Tech debt: This class needs to be initialized to make sure any existing subscriptions gets processed as expected, even if the gateway is not enabled for new orders.
		// Eventually, we want to load this via a singleton pattern to avoid unnecessary instantiation.
		$paypal = new WC_Gateway_Paypal();
		return $paypal->should_load();
	}
}
