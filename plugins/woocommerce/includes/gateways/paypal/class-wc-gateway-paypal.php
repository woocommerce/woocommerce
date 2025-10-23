<?php
/**
 * PayPal Standard Payment Gateway.
 *
 * Provides a PayPal Standard Payment Gateway.
 *
 * @class       WC_Gateway_Paypal
 * @extends     WC_Payment_Gateway
 * @version     2.3.0
 * @package     WooCommerce\Classes\Payment
 */

use Automattic\Jetpack\Constants;
use Automattic\WooCommerce\Enums\PaymentGatewayFeature;
use Automattic\Jetpack\Connection\Manager as Jetpack_Connection_Manager;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! class_exists( 'WC_Gateway_Paypal_Constants' ) ) {
	require_once __DIR__ . '/includes/class-wc-gateway-paypal-constants.php';
}

if ( ! class_exists( 'WC_Gateway_Paypal_Helper' ) ) {
	require_once __DIR__ . '/includes/class-wc-gateway-paypal-helper.php';
}

if ( ! class_exists( 'WC_Gateway_Paypal_Buttons' ) ) {
	require_once __DIR__ . '/class-wc-gateway-paypal-buttons.php';
}

/**
 * WC_Gateway_Paypal Class.
 */
class WC_Gateway_Paypal extends WC_Payment_Gateway {

	/**
	 * Unique ID for this gateway.
	 *
	 * @var string
	 */
	const ID = 'paypal';

	/**
	 * Whether or not logging is enabled
	 *
	 * @var bool
	 */
	public static $log_enabled = null;

	/**
	 * Logger instance
	 *
	 * @var WC_Logger
	 */
	public static $log = false;

	/**
	 * Whether the test mode is enabled.
	 *
	 * @var bool
	 */
	public $testmode;

	/**
	 * Whether the debug mode is enabled.
	 *
	 * @var bool
	 */
	public $debug;

	/**
	 * The intent of the payment (capture or authorize).
	 *
	 * @var string
	 */
	public $intent;

	/**
	 * Email address to send payments to.
	 *
	 * @var string
	 */
	public $email;

	/**
	 * Receiver email.
	 *
	 * @var string
	 */
	public $receiver_email;

	/**
	 * Identity token.
	 *
	 * @var string
	 */
	public $identity_token;

	/**
	 * Jetpack connection manager.
	 *
	 * @var Jetpack_Connection_Manager
	 */
	private $jetpack_connection_manager;

	/**
	 * Whether the Transact onboarding is complete.
	 *
	 * @var bool
	 */
	private $transact_onboarding_complete;

	/**
	 * The *Singleton* instance of this class
	 *
	 * @var WC_Gateway_Paypal
	 */
	private static $instance;

	/**
	 * Returns the *Singleton* instance of this class.
	 *
	 * @return WC_Gateway_Paypal The *Singleton* instance.
	 */
	public static function get_instance() {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	/**
	 * Set the instance of the gateway.
	 *
	 * @param WC_Gateway_Paypal $instance The instance of the gateway.
	 * @return void
	 */
	public static function set_instance( $instance ) {
		self::$instance = $instance;
	}

	/**
	 * Constructor for the gateway.
	 */
	public function __construct() {
		$this->id                = self::ID;
		$this->has_fields        = false;
		$this->order_button_text = __( 'Proceed to PayPal', 'woocommerce' );
		$this->method_title      = __( 'PayPal Standard', 'woocommerce' );
		/* translators: %s: Link to WC system status page */
		$this->method_description = __( 'PayPal Standard redirects customers to PayPal to enter their payment information.', 'woocommerce' );
		$this->supports           = array(
			PaymentGatewayFeature::PRODUCTS,
			PaymentGatewayFeature::REFUNDS,
		);

		// Load the settings.
		$this->init_form_fields();
		$this->init_settings();

		// Define user set variables.
		$this->title                        = $this->get_option( 'title' );
		$this->description                  = $this->get_option( 'description' );
		$this->testmode                     = 'yes' === $this->get_option( 'testmode', 'no' );
		$this->intent                       = 'sale' === $this->get_option( 'paymentaction', 'sale' ) ? 'capture' : 'authorize';
		$this->debug                        = 'yes' === $this->get_option( 'debug', 'no' );
		$this->email                        = $this->get_option( 'email' );
		$this->receiver_email               = $this->get_option( 'receiver_email', $this->email );
		$this->identity_token               = $this->get_option( 'identity_token' );
		$this->transact_onboarding_complete = 'yes' === $this->get_option( 'transact_onboarding_complete', 'no' );
		self::$log_enabled                  = $this->debug;

		if ( $this->testmode ) {
			/* translators: %s: Link to PayPal sandbox testing guide page */
			$this->description .= ' ' . sprintf( __( 'SANDBOX ENABLED. You can use sandbox testing accounts only. See the <a href="%s">PayPal Sandbox Testing Guide</a> for more details.', 'woocommerce' ), 'https://developer.paypal.com/tools/sandbox/' );
			$this->description  = trim( $this->description );
		}

		// Actions.
		add_action( 'woocommerce_update_options_payment_gateways_' . $this->id, array( $this, 'process_admin_options' ) );
		add_action( 'woocommerce_order_status_processing', array( $this, 'capture_payment' ) );
		add_action( 'woocommerce_order_status_completed', array( $this, 'capture_payment' ) );
		add_action( 'admin_enqueue_scripts', array( $this, 'admin_scripts' ) );

		if ( ! $this->is_valid_for_use() ) {
			$this->enabled = 'no';
		} else {
			include_once __DIR__ . '/includes/class-wc-gateway-paypal-ipn-handler.php';
			new WC_Gateway_Paypal_IPN_Handler( $this->testmode, $this->receiver_email );

			if ( $this->identity_token ) {
				include_once __DIR__ . '/includes/class-wc-gateway-paypal-pdt-handler.php';
				$pdt_handler = new WC_Gateway_Paypal_PDT_Handler( $this->testmode, $this->identity_token );
				$pdt_handler->set_receiver_email( $this->receiver_email );
			}
		}

		if ( 'yes' === $this->enabled ) {
			add_filter( 'woocommerce_thankyou_order_received_text', array( $this, 'order_received_text' ), 10, 2 );
			// Hide action buttons for pending orders as they take a while to be captured with orders v2.
			add_filter( 'woocommerce_my_account_my_orders_actions', array( $this, 'hide_action_buttons' ), 10, 2 );

			add_filter( 'woocommerce_settings_api_form_fields_paypal', array( $this, 'maybe_remove_fields' ), 15 );

			// Hook for plugin upgrades.
			add_action( 'woocommerce_updated', array( $this, 'maybe_onboard_with_transact' ) );

			if ( $this->should_use_orders_v2() ) {
				// Hook for updating the shipping information on order approval (Orders v2).
				add_action( 'woocommerce_before_thankyou', array( $this, 'update_addresses_in_order' ), 10 );

				$buttons = new WC_Gateway_Paypal_Buttons( $this );
				if ( $buttons->is_enabled() ) {
					add_action( 'wp_enqueue_scripts', array( $this, 'enqueue_scripts' ) );
					add_filter( 'wp_script_attributes', array( $this, 'add_paypal_sdk_attributes' ) );

					// Render the buttons container to load the buttons via PayPal JS SDK.
					// Classic checkout page.
					add_action( 'woocommerce_checkout_before_customer_details', array( $this, 'render_buttons_container' ) );
					// Classic cart page.
					add_action( 'woocommerce_after_cart_totals', array( $this, 'render_buttons_container' ) );
					// Product page.
					add_action( 'woocommerce_after_add_to_cart_form', array( $this, 'render_buttons_container' ) );
				}
			}
		}
	}

	/**
	 * Update the shipping and billing information for the order.
	 * Hooked on 'woocommerce_before_thankyou'.
	 *
	 * @param int $order_id The order ID.
	 * @return void
	 */
	public function update_addresses_in_order( $order_id ) {
		$order = wc_get_order( $order_id );

		// Bail early if the order is not a PayPal order.
		if ( ! $order || $order->get_payment_method() !== $this->id ) {
			return;
		}

		// Bail early if not on Orders v2.
		if ( ! $this->should_use_orders_v2() ) {
			return;
		}

		$paypal_order_id = $order->get_meta( '_paypal_order_id' );
		if ( empty( $paypal_order_id ) ) {
			return;
		}

		try {
			include_once WC_ABSPATH . 'includes/gateways/paypal/includes/class-wc-gateway-paypal-request.php';
			$paypal_request       = new WC_Gateway_Paypal_Request( $this );
			$paypal_order_details = $paypal_request->get_paypal_order_details( $paypal_order_id );

			// Update the shipping information.
			$full_name = $paypal_order_details['purchase_units'][0]['shipping']['name']['full_name'] ?? '';
			if ( ! empty( $full_name ) ) {
				$approximate_first_name = explode( ' ', $full_name )[0] ?? '';
				$approximate_last_name  = explode( ' ', $full_name )[1] ?? '';
				$order->set_shipping_first_name( $approximate_first_name );
				$order->set_shipping_last_name( $approximate_last_name );
			}

			$shipping_address = $paypal_order_details['purchase_units'][0]['shipping']['address'] ?? array();
			if ( ! empty( $shipping_address ) ) {
				$order->set_shipping_country( $shipping_address['country_code'] ?? '' );
				$order->set_shipping_postcode( $shipping_address['postal_code'] ?? '' );
				$order->set_shipping_state( $shipping_address['admin_area_1'] ?? '' );
				$order->set_shipping_city( $shipping_address['admin_area_2'] ?? '' );
				$order->set_shipping_address_1( $shipping_address['address_line_1'] ?? '' );
				$order->set_shipping_address_2( $shipping_address['address_line_2'] ?? '' );
			}

			// Update the billing information.
			$full_name = $paypal_order_details['payer']['name'] ?? array();
			$email     = $paypal_order_details['payer']['email_address'] ?? '';
			if ( ! empty( $full_name ) ) {
				$order->set_billing_first_name( $full_name['given_name'] ?? '' );
				$order->set_billing_last_name( $full_name['surname'] ?? '' );
				$order->set_billing_email( $email );
			}

			$billing_address = $paypal_order_details['payer']['address'] ?? array();
			if ( ! empty( $billing_address ) ) {
				$order->set_billing_country( $billing_address['country_code'] ?? '' );
				$order->set_billing_postcode( $billing_address['postal_code'] ?? '' );
				$order->set_billing_state( $billing_address['admin_area_1'] ?? '' );
				$order->set_billing_city( $billing_address['admin_area_2'] ?? '' );
				$order->set_billing_address_1( $billing_address['address_line_1'] ?? '' );
				$order->set_billing_address_2( $billing_address['address_line_2'] ?? '' );
			}

			$order->save();
		} catch ( Exception $e ) {
			self::log( 'Error updating addresses for order #' . $order_id . ': ' . $e->getMessage(), 'error' );
		}
	}

	/**
	 * Onboard the merchant with the Transact platform.
	 *
	 * @return void
	 */
	public function maybe_onboard_with_transact() {
		if ( ! is_admin() || ! current_user_can( 'manage_woocommerce' ) ) {
			return;
		}

		// Do not run if PayPal Standard is not enabled.
		if ( 'yes' !== $this->enabled ) {
			return;
		}

		/**
		 * Filters whether the gateway should use Orders v2 API.
		 *
		 * @param bool $use_orders_v2 Whether the gateway should use Orders v2 API.
		 *
		 * @since 10.2.0
		 */
		$use_orders_v2 = apply_filters(
			'woocommerce_paypal_use_orders_v2',
			WC_Gateway_Paypal_Helper::is_orders_v2_migration_eligible()
		);

		// If the conditions are met, but there is an override to not use Orders v2,
		// respect the override. Bail early -- we don't need to onboard if not using Orders v2.
		if ( ! $use_orders_v2 ) {
			return;
		}

		include_once __DIR__ . '/includes/class-wc-gateway-paypal-transact-account-manager.php';
		$transact_account_manager = new WC_Gateway_Paypal_Transact_Account_Manager( $this );
		$transact_account_manager->do_onboarding();
	}

	/**
	 * Return whether or not this gateway still requires setup to function.
	 *
	 * When this gateway is toggled on via AJAX, if this returns true a
	 * redirect will occur to the settings page instead.
	 *
	 * @since 3.4.0
	 * @return bool
	 */
	public function needs_setup() {
		return ! is_email( $this->email );
	}

	/**
	 * Logging method.
	 *
	 * @param string $message Log message.
	 * @param string $level Optional. Default 'info'. Possible values:
	 *                      emergency|alert|critical|error|warning|notice|info|debug.
	 */
	public static function log( $message, $level = 'info' ) {
		if ( is_null( self::$log_enabled ) ) {
			$settings          = get_option( 'woocommerce_paypal_settings' );
			self::$log_enabled = 'yes' === ( $settings['debug'] ?? 'no' );
		}

		if ( self::$log_enabled ) {
			if ( empty( self::$log ) ) {
				self::$log = wc_get_logger();
			}
			self::$log->log( $level, $message, array( 'source' => self::ID ) );
		}
	}

	/**
	 * Processes and saves options.
	 * If there is an error thrown, will continue to save and validate fields, but will leave the erroring field out.
	 *
	 * @return bool was anything saved?
	 */
	public function process_admin_options() {
		$saved = parent::process_admin_options();

		// Maybe clear logs.
		if ( 'yes' !== $this->get_option( 'debug', 'no' ) ) {
			if ( empty( self::$log ) ) {
				self::$log = wc_get_logger();
			}
			self::$log->clear( self::ID );
		}

		// Trigger Transact onboarding when settings are saved.
		if ( $saved ) {
			$this->maybe_onboard_with_transact();
		}

		return $saved;
	}

	/**
	 * Get gateway icon.
	 *
	 * @return string
	 */
	public function get_icon() {
		// We need a base country for the link to work, bail if in the unlikely event no country is set.
		$base_country = WC()->countries->get_base_country();
		if ( empty( $base_country ) ) {
			return '';
		}
		$icon_html = '';
		$icon      = (array) $this->get_icon_image( $base_country );

		foreach ( $icon as $i ) {
			$icon_html .= '<img src="' . esc_attr( $i ) . '" alt="' . esc_attr__( 'PayPal acceptance mark', 'woocommerce' ) . '" />';
		}

		$icon_html .= sprintf( '<a href="%1$s" class="about_paypal" onclick="javascript:window.open(\'%1$s\',\'WIPaypal\',\'toolbar=no, location=no, directories=no, status=no, menubar=no, scrollbars=yes, resizable=yes, width=1060, height=700\'); return false;">' . esc_attr__( 'What is PayPal?', 'woocommerce' ) . '</a>', esc_url( $this->get_icon_url( $base_country ) ) );

		return apply_filters( 'woocommerce_gateway_icon', $icon_html, $this->id );
	}

	/**
	 * Get the link for an icon based on country.
	 *
	 * @param  string $country Country two letter code.
	 * @return string
	 */
	protected function get_icon_url( $country ) {
		$url           = 'https://www.paypal.com/' . strtolower( $country );
		$home_counties = array( 'BE', 'CZ', 'DK', 'HU', 'IT', 'JP', 'NL', 'NO', 'ES', 'SE', 'TR', 'IN' );
		$countries     = array( 'DZ', 'AU', 'BH', 'BQ', 'BW', 'CA', 'CN', 'CW', 'FI', 'FR', 'DE', 'GR', 'HK', 'ID', 'JO', 'KE', 'KW', 'LU', 'MY', 'MA', 'OM', 'PH', 'PL', 'PT', 'QA', 'IE', 'RU', 'BL', 'SX', 'MF', 'SA', 'SG', 'SK', 'KR', 'SS', 'TW', 'TH', 'AE', 'GB', 'US', 'VN' );

		if ( in_array( $country, $home_counties, true ) ) {
			return $url . '/webapps/mpp/home';
		} elseif ( in_array( $country, $countries, true ) ) {
			return $url . '/webapps/mpp/paypal-popup';
		} else {
			return $url . '/cgi-bin/webscr?cmd=xpt/Marketing/general/WIPaypal-outside';
		}
	}

	/**
	 * Get PayPal images for a country.
	 *
	 * @param string $country Country code.
	 * @return array of image URLs
	 */
	protected function get_icon_image( $country ) {
		switch ( $country ) {
			case 'US':
			case 'NZ':
			case 'CZ':
			case 'HU':
			case 'MY':
				$icon = 'https://www.paypalobjects.com/webstatic/mktg/logo/AM_mc_vs_dc_ae.jpg';
				break;
			case 'TR':
				$icon = 'https://www.paypalobjects.com/webstatic/mktg/logo-center/logo_paypal_odeme_secenekleri.jpg';
				break;
			case 'GB':
				$icon = 'https://www.paypalobjects.com/webstatic/mktg/Logo/AM_mc_vs_ms_ae_UK.png';
				break;
			case 'MX':
				$icon = array(
					'https://www.paypal.com/es_XC/Marketing/i/banner/paypal_visa_mastercard_amex.png',
					'https://www.paypal.com/es_XC/Marketing/i/banner/paypal_debit_card_275x60.gif',
				);
				break;
			case 'FR':
				$icon = 'https://www.paypalobjects.com/webstatic/mktg/logo-center/logo_paypal_moyens_paiement_fr.jpg';
				break;
			case 'AU':
				$icon = 'https://www.paypalobjects.com/webstatic/en_AU/mktg/logo/Solutions-graphics-1-184x80.jpg';
				break;
			case 'DK':
				$icon = 'https://www.paypalobjects.com/webstatic/mktg/logo-center/logo_PayPal_betalingsmuligheder_dk.jpg';
				break;
			case 'RU':
				$icon = 'https://www.paypalobjects.com/webstatic/ru_RU/mktg/business/pages/logo-center/AM_mc_vs_dc_ae.jpg';
				break;
			case 'NO':
				$icon = 'https://www.paypalobjects.com/webstatic/mktg/logo-center/banner_pl_just_pp_319x110.jpg';
				break;
			case 'CA':
				$icon = 'https://www.paypalobjects.com/webstatic/en_CA/mktg/logo-image/AM_mc_vs_dc_ae.jpg';
				break;
			case 'HK':
				$icon = 'https://www.paypalobjects.com/webstatic/en_HK/mktg/logo/AM_mc_vs_dc_ae.jpg';
				break;
			case 'SG':
				$icon = 'https://www.paypalobjects.com/webstatic/en_SG/mktg/Logos/AM_mc_vs_dc_ae.jpg';
				break;
			case 'TW':
				$icon = 'https://www.paypalobjects.com/webstatic/en_TW/mktg/logos/AM_mc_vs_dc_ae.jpg';
				break;
			case 'TH':
				$icon = 'https://www.paypalobjects.com/webstatic/en_TH/mktg/Logos/AM_mc_vs_dc_ae.jpg';
				break;
			case 'JP':
				$icon = 'https://www.paypal.com/ja_JP/JP/i/bnr/horizontal_solution_4_jcb.gif';
				break;
			case 'IN':
				$icon = 'https://www.paypalobjects.com/webstatic/mktg/logo/AM_mc_vs_dc_ae.jpg';
				break;
			default:
				$icon = WC_HTTPS::force_https_url( WC()->plugin_url() . '/includes/gateways/paypal/assets/images/paypal.png' );
				break;
		}
		return apply_filters( 'woocommerce_paypal_icon', $icon );
	}

	/**
	 * Check if this gateway is available in the user's country based on currency.
	 *
	 * @return bool
	 */
	public function is_valid_for_use() {
		return in_array(
			get_woocommerce_currency(),
			apply_filters(
				'woocommerce_paypal_supported_currencies',
				array( 'AUD', 'BRL', 'CAD', 'MXN', 'NZD', 'HKD', 'SGD', 'USD', 'EUR', 'JPY', 'TRY', 'NOK', 'CZK', 'DKK', 'HUF', 'ILS', 'MYR', 'PHP', 'PLN', 'SEK', 'CHF', 'TWD', 'THB', 'GBP', 'RMB', 'RUB', 'INR' )
			),
			true
		);
	}

	/**
	 * Admin Panel Options.
	 * - Options for bits like 'title' and availability on a country-by-country basis.
	 *
	 * @since 1.0.0
	 */
	public function admin_options() {
		if ( $this->is_valid_for_use() ) {
			parent::admin_options();
		} else {
			?>
			<div class="inline error">
				<p>
					<strong><?php esc_html_e( 'Gateway disabled', 'woocommerce' ); ?></strong>: <?php esc_html_e( 'PayPal Standard does not support your store currency.', 'woocommerce' ); ?>
				</p>
			</div>
			<?php
		}
	}

	/**
	 * Initialise Gateway Settings Form Fields.
	 */
	public function init_form_fields() {
		$this->form_fields = include __DIR__ . '/includes/settings-paypal.php';
	}

	/**
	 * Filter to remove fields for Orders v2.
	 *
	 * @param array $form_fields Form fields.
	 * @return array
	 */
	public function maybe_remove_fields( $form_fields ) {
		// Remove legacy setting fiels when using Orders v2.
		if ( $this->should_use_orders_v2() ) {
			foreach ( $form_fields as $key => $field ) {
				if ( isset( $field['is_legacy'] ) && $field['is_legacy'] ) {
					unset( $form_fields[ $key ] );
				}
			}
		}

		if ( ! $this->should_use_orders_v2() ) {
			unset( $form_fields['paypal_buttons'] );
		}
		return $form_fields;
	}

	/**
	 * Get the transaction URL.
	 *
	 * @param  WC_Order $order Order object.
	 * @return string
	 */
	public function get_transaction_url( $order ) {
		if ( $this->testmode ) {
			$this->view_transaction_url = 'https://www.sandbox.paypal.com/cgi-bin/webscr?cmd=_view-a-trans&id=%s';
		} else {
			$this->view_transaction_url = 'https://www.paypal.com/cgi-bin/webscr?cmd=_view-a-trans&id=%s';
		}
		return parent::get_transaction_url( $order );
	}

	/**
	 * Process the payment and return the result.
	 *
	 * @param  int $order_id Order ID.
	 * @return array
	 * @throws Exception If the PayPal order creation fails.
	 */
	public function process_payment( $order_id ) {
		include_once __DIR__ . '/includes/class-wc-gateway-paypal-request.php';

		$order          = wc_get_order( $order_id );
		$paypal_request = new WC_Gateway_Paypal_Request( $this );

		if ( $this->should_use_orders_v2() ) {
			$paypal_order = $paypal_request->create_paypal_order( $order );
			if ( ! $paypal_order || empty( $paypal_order['id'] ) || empty( $paypal_order['redirect_url'] ) ) {
				throw new Exception(
					esc_html__( 'We are unable to process your PayPal payment at this time. Please try again or use a different payment method.', 'woocommerce' )
				);
			}

			$redirect_url = $paypal_order['redirect_url'];
		} else {
			$redirect_url = $paypal_request->get_request_url( $order, $this->testmode );
		}

		return array(
			'result'   => 'success',
			'redirect' => $redirect_url,
		);
	}

	/**
	 * Can the order be refunded via PayPal?
	 *
	 * @param  WC_Order $order Order object.
	 * @return bool
	 */
	public function can_refund_order( $order ) {
		$has_api_creds = false;

		if ( $this->testmode ) {
			$has_api_creds = $this->get_option( 'sandbox_api_username' ) && $this->get_option( 'sandbox_api_password' ) && $this->get_option( 'sandbox_api_signature' );
		} else {
			$has_api_creds = $this->get_option( 'api_username' ) && $this->get_option( 'api_password' ) && $this->get_option( 'api_signature' );
		}

		return $order && $order->get_transaction_id() && $has_api_creds;
	}

	/**
	 * Init the API class and set the username/password etc.
	 */
	protected function init_api() {
		include_once __DIR__ . '/includes/class-wc-gateway-paypal-api-handler.php';

		WC_Gateway_Paypal_API_Handler::$api_username  = $this->testmode ? $this->get_option( 'sandbox_api_username' ) : $this->get_option( 'api_username' );
		WC_Gateway_Paypal_API_Handler::$api_password  = $this->testmode ? $this->get_option( 'sandbox_api_password' ) : $this->get_option( 'api_password' );
		WC_Gateway_Paypal_API_Handler::$api_signature = $this->testmode ? $this->get_option( 'sandbox_api_signature' ) : $this->get_option( 'api_signature' );
		WC_Gateway_Paypal_API_Handler::$sandbox       = $this->testmode;
	}

	/**
	 * Process a refund if supported.
	 *
	 * @param  int    $order_id Order ID.
	 * @param  float  $amount Refund amount.
	 * @param  string $reason Refund reason.
	 * @return bool|WP_Error
	 */
	public function process_refund( $order_id, $amount = null, $reason = '' ) {
		$order = wc_get_order( $order_id );

		if ( ! $this->can_refund_order( $order ) ) {
			return new WP_Error( 'error', __( 'Refund failed.', 'woocommerce' ) );
		}

		$this->init_api();

		$result = WC_Gateway_Paypal_API_Handler::refund_transaction( $order, $amount, $reason );

		if ( is_wp_error( $result ) ) {
			static::log( 'Refund Failed: ' . $result->get_error_message(), 'error' );
			return new WP_Error( 'error', $result->get_error_message() );
		}

		static::log( 'Refund Result: ' . wc_print_r( $result, true ) );

		switch ( strtolower( $result->ACK ) ) { // phpcs:ignore WordPress.NamingConventions.ValidVariableName.UsedPropertyNotSnakeCase
			case 'success':
			case 'successwithwarning':
				$order->add_order_note(
					/* translators: 1: Refund amount, 2: Refund ID */
					sprintf( __( 'Refunded %1$s - Refund ID: %2$s', 'woocommerce' ), $result->GROSSREFUNDAMT, $result->REFUNDTRANSACTIONID ) // phpcs:ignore WordPress.NamingConventions.ValidVariableName.UsedPropertyNotSnakeCase
				);
				return true;
		}

		return isset( $result->L_LONGMESSAGE0 ) ? new WP_Error( 'error', $result->L_LONGMESSAGE0 ) : false; // phpcs:ignore WordPress.NamingConventions.ValidVariableName.UsedPropertyNotSnakeCase
	}

	/**
	 * Capture payment when the order is changed from on-hold to complete or processing
	 *
	 * @param  int $order_id Order ID.
	 */
	public function capture_payment( $order_id ) {
		$order = wc_get_order( $order_id );

		// Bail if the order is not a PayPal order.
		if ( self::ID !== $order->get_payment_method() ) {
			return;
		}

		// If the order is authorized via legacy API, the '_paypal_status' meta will be 'pending'.
		$is_authorized_via_legacy_api = 'pending' === $order->get_meta( '_paypal_status', true );

		if ( $this->should_use_orders_v2() && ! $is_authorized_via_legacy_api ) {
			include_once __DIR__ . '/includes/class-wc-gateway-paypal-request.php';

			$paypal_request = new WC_Gateway_Paypal_Request( $this );
			$paypal_request->capture_authorized_payment( $order );
			return;
		}

		if ( 'pending' === $order->get_meta( '_paypal_status', true ) && $order->get_transaction_id() ) {
			$this->init_api();
			$result = WC_Gateway_Paypal_API_Handler::do_capture( $order );

			if ( is_wp_error( $result ) ) {
				static::log( 'Capture Failed: ' . $result->get_error_message(), 'error' );
				/* translators: %s: Paypal gateway error message */
				$order->add_order_note( sprintf( __( 'Payment could not be captured: %s', 'woocommerce' ), $result->get_error_message() ) );
				return;
			}

			static::log( 'Capture Result: ' . wc_print_r( $result, true ) );

			// phpcs:disable WordPress.NamingConventions.ValidVariableName.UsedPropertyNotSnakeCase
			if ( ! empty( $result->PAYMENTSTATUS ) ) {
				switch ( $result->PAYMENTSTATUS ) {
					case 'Completed':
						/* translators: 1: Amount, 2: Authorization ID, 3: Transaction ID */
						$order->add_order_note( sprintf( __( 'Payment of %1$s was captured - Auth ID: %2$s, Transaction ID: %3$s', 'woocommerce' ), $result->AMT, $result->AUTHORIZATIONID, $result->TRANSACTIONID ) );
						$order->update_meta_data( '_paypal_status', $result->PAYMENTSTATUS );
						$order->set_transaction_id( $result->TRANSACTIONID );
						$order->save();
						break;
					default:
						/* translators: 1: Authorization ID, 2: Payment status */
						$order->add_order_note( sprintf( __( 'Payment could not be captured - Auth ID: %1$s, Status: %2$s', 'woocommerce' ), $result->AUTHORIZATIONID, $result->PAYMENTSTATUS ) );
						break;
				}
			}
			// phpcs:enable
		}
	}

	/**
	 * Load admin scripts.
	 *
	 * @since 3.3.0
	 */
	public function admin_scripts() {
		$screen    = get_current_screen();
		$screen_id = $screen ? $screen->id : '';

		if ( 'woocommerce_page_wc-settings' !== $screen_id ) {
			return;
		}

		$suffix  = Constants::is_true( 'SCRIPT_DEBUG' ) ? '' : '.min';
		$version = Constants::get_constant( 'WC_VERSION' );

		wp_enqueue_script( 'woocommerce_paypal_admin', WC()->plugin_url() . '/includes/gateways/paypal/assets/js/paypal-admin' . $suffix . '.js', array(), $version, true );
	}

	/**
	 * Enqueue scripts.
	 */
	public function enqueue_scripts() {
		if ( 'no' === $this->enabled ) {
			return;
		}

		$version           = Constants::get_constant( 'WC_VERSION' );
		$is_page_supported = is_checkout() || is_cart() || is_product();
		$buttons           = new WC_Gateway_Paypal_Buttons( $this );
		$options           = $buttons->get_common_options();

		if ( empty( $options['client-id'] ) || ! $is_page_supported ) {
			return;
		}

		$sdk_host = $this->testmode ? 'https://www.sandbox.paypal.com/sdk/js' : 'https://www.paypal.com/sdk/js';

		// Add PayPal JS SDK script.
		// phpcs:ignore WordPress.WP.EnqueuedResourceParameters.MissingVersion
		wp_register_script( 'paypal-standard-sdk', add_query_arg( $options, $sdk_host ), array(), null, false );
		wp_enqueue_script( 'paypal-standard-sdk' );

		wp_register_script( 'wc-paypal-frontend', WC()->plugin_url() . '/client/legacy/js/gateways/paypal.js', array( 'jquery', 'wp-api-fetch' ), $version, true );

		wp_localize_script(
			'wc-paypal-frontend',
			'paypal_standard',
			array(
				'gateway_id'                => $this->id,
				'is_product_page'           => is_product(),
				'app_switch_request_origin' => $buttons->get_current_page_for_app_switch(),
				'wc_store_api_nonce'        => wp_create_nonce( 'wc_store_api' ),
				'create_order_nonce'        => wp_create_nonce( 'wc_gateway_paypal_standard_create_order' ),
				'cancel_payment_nonce'      => wp_create_nonce( 'wc_gateway_paypal_standard_cancel_payment' ),
				'generic_error_message'     => __( 'An unknown error occurred', 'woocommerce' ),
			)
		);

		wp_enqueue_script( 'wc-paypal-frontend' );
	}

	/**
	 * Add PayPal SDK attributes to the script.
	 *
	 * @param array $attrs Attributes.
	 * @return array
	 */
	public function add_paypal_sdk_attributes( $attrs ) {
		if ( 'paypal-standard-sdk-js' === $attrs['id'] ) {
			$buttons   = new WC_Gateway_Paypal_Buttons( $this );
			$page_type = $buttons->get_page_type();

			$attrs['data-page-type']              = $page_type;
			$attrs['data-partner-attribution-id'] = 'Woo_Cart_CoreUpgrade';
		}

		return $attrs;
	}

	/**
	 * Builds the PayPal payment fields area.
	 *
	 * @since 10.3.0
	 */
	public function render_buttons_container() {
		echo '<div id="paypal-standard-container"></div>';
	}

	/**
	 * Custom PayPal order received text.
	 *
	 * @since 3.9.0
	 * @param string   $text Default text.
	 * @param WC_Order $order Order data.
	 * @return string
	 */
	public function order_received_text( $text, $order ) {
		if ( $order && $this->id === $order->get_payment_method() ) {
			return esc_html__( 'Thank you for your payment. Your transaction has been completed, and a receipt for your purchase has been emailed to you. Log into your PayPal account to view transaction details.', 'woocommerce' );
		}

		return $text;
	}

	/**
	 * Hide "Pay" and "Cancel" action buttons for pending orders as orders v2 takes a while to be captured.
	 *
	 * @param array    $actions An array with the default actions.
	 * @param WC_Order $order The order.
	 * @return array
	 */
	public function hide_action_buttons( $actions, $order ) {
		if ( $this->should_use_orders_v2() && $this->id === $order->get_payment_method() ) {
			unset( $actions['pay'], $actions['cancel'] );
		}
		return $actions;
	}

	/**
	 * Determines whether PayPal Standard should be loaded or not.
	 *
	 * By default PayPal Standard isn't loaded on new installs or on existing sites which haven't set up the gateway.
	 *
	 * @since 5.5.0
	 *
	 * @return bool Whether PayPal Standard should be loaded.
	 */
	public function should_load() {
		$option_key  = '_should_load';
		$should_load = $this->get_option( $option_key );

		if ( '' === $should_load ) {
			// Set default `_should_load` to 'yes' on existing stores with PayPal Standard enabled or with existing PayPal Standard orders.
			$should_load = 'yes' === $this->enabled || $this->has_paypal_orders();

			$this->update_option( $option_key, wc_bool_to_string( $should_load ) );
		} else {
			// Enabled always takes precedence over the option.
			$should_load = wc_string_to_bool( $this->enabled ) || wc_string_to_bool( $should_load );
		}

		return $should_load;
	}

	/**
	 * Checks if the store has at least one PayPal Standand order.
	 *
	 * @return bool
	 */
	public function has_paypal_orders() {
		$paypal_orders = wc_get_orders(
			array(
				'limit'          => 1,
				'return'         => 'ids',
				'payment_method' => self::ID,
			)
		);

		return is_countable( $paypal_orders ) ? 1 === count( $paypal_orders ) : false;
	}

	/**
	 * Check if the gateway should use Orders v2 API.
	 *
	 * @return bool
	 */
	public function should_use_orders_v2() {
		/**
		 * Filters whether the gateway should use Orders v2 API.
		 *
		 * @param bool $use_orders_v2 Whether the gateway should use Orders v2 API.
		 *
		 * @since 10.2.0
		 */
		$use_orders_v2 = apply_filters(
			'woocommerce_paypal_use_orders_v2',
			WC_Gateway_Paypal_Helper::is_orders_v2_migration_eligible()
		);

		// If the conditions are met, but there is an override to not use Orders v2,
		// respect the override.
		if ( ! $use_orders_v2 ) {
			return false;
		}

		// If the gateway is not onboarded, bail early.
		if ( ! $this->is_transact_onboarding_complete() ) {
			return false;
		}

		// We need a Jetpack connection to be able to send authenticated requests to the proxy.
		$jetpack_connection_manager = $this->get_jetpack_connection_manager();
		if ( ! $jetpack_connection_manager || ! $jetpack_connection_manager->is_connected() ) {
			return false;
		}

		// We need merchant and provider accounts with Transact to be able to use the proxy.
		include_once __DIR__ . '/includes/class-wc-gateway-paypal-transact-account-manager.php';
		$transact_account_manager = new WC_Gateway_Paypal_Transact_Account_Manager( $this );
		$merchant_account_data    = $transact_account_manager->get_transact_account_data( 'merchant' );
		if ( empty( $merchant_account_data ) ) {
			return false;
		}

		$provider_account_data = $transact_account_manager->get_transact_account_data( 'provider' );
		if ( empty( $provider_account_data ) ) {
			return false;
		}

		return true;
	}

	/**
	 * Get the Jetpack connection manager.
	 *
	 * @return Jetpack_Connection_Manager
	 */
	public function get_jetpack_connection_manager() {
		if ( ! $this->jetpack_connection_manager ) {
			$this->jetpack_connection_manager = new Jetpack_Connection_Manager( 'woocommerce' );
		}
		return $this->jetpack_connection_manager;
	}

	/**
	 * Whether the Transact onboarding is complete.
	 *
	 * @return bool
	 */
	public function is_transact_onboarding_complete() {
		return $this->transact_onboarding_complete;
	}

	/**
	 * Set the Transact onboarding as complete.
	 *
	 * @return void
	 */
	public function set_transact_onboarding_complete() {
		if ( $this->transact_onboarding_complete ) {
			return;
		}

		$this->update_option( 'transact_onboarding_complete', 'yes' );
		$this->transact_onboarding_complete = true;
	}
}

// Initialize PayPal admin notices handler on 'init' hook to ensure the class loads before admin_init and admin_notices hooks fire.
add_action(
	'init',
	function () {
		if ( ! is_admin() || ( defined( 'DOING_AJAX' ) && DOING_AJAX ) ) {
			return;
		}

		include_once __DIR__ . '/includes/class-wc-gateway-paypal-notices.php';
		new WC_Gateway_Paypal_Notices();
	}
);
