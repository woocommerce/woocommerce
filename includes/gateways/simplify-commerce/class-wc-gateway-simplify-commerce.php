<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

/**
 * Simplify Commerce Gateway.
 *
 * @class 		WC_Gateway_Simplify_Commerce
 * @extends		WC_Payment_Gateway_CC
 * @since       2.2.0
 * @version		1.0.0
 * @package		WooCommerce/Classes/Payment
 * @author 		WooThemes
 */
class WC_Gateway_Simplify_Commerce extends WC_Payment_Gateway_CC {

	/**
	 * Constructor.
	 */
	public function __construct() {
		$this->id                 = 'simplify_commerce';
		$this->method_title       = __( 'Simplify Commerce', 'woocommerce' );
		$this->method_description = __( 'Take payments via Simplify Commerce - uses simplify.js to create card tokens and the Simplify Commerce SDK. Requires SSL when sandbox is disabled.', 'woocommerce' );
		$this->new_method_label   = __( 'Use a new card', 'woocommerce' );
		$this->has_fields         = true;
		$this->supports           = array(
			'subscriptions',
			'products',
			'subscription_cancellation',
			'subscription_reactivation',
			'subscription_suspension',
			'subscription_amount_changes',
			'subscription_payment_method_change', // Subscriptions 1.n compatibility
			'subscription_payment_method_change_customer',
			'subscription_payment_method_change_admin',
			'subscription_date_changes',
			'multiple_subscriptions',
			'default_credit_card_form',
			'tokenization',
			'refunds',
			'pre-orders',
		);
		$this->view_transaction_url = 'https://www.simplify.com/commerce/app#/payment/%s';

		// Load the form fields
		$this->init_form_fields();

		// Load the settings.
		$this->init_settings();

		// Get setting values
		$this->title           = $this->get_option( 'title' );
		$this->description     = $this->get_option( 'description' );
		$this->enabled         = $this->get_option( 'enabled' );
		$this->mode            = $this->get_option( 'mode', 'standard' );
		$this->modal_color     = $this->get_option( 'modal_color', '#a46497' );
		$this->sandbox         = $this->get_option( 'sandbox' );
		$this->public_key      = ( 'no' === $this->sandbox ) ? $this->get_option( 'public_key' ) : $this->get_option( 'sandbox_public_key' );
		$this->private_key     = ( 'no' === $this->sandbox ) ? $this->get_option( 'private_key' ) : $this->get_option( 'sandbox_private_key' );

		$this->init_simplify_sdk();

		// Hooks
		add_action( 'wp_enqueue_scripts', array( $this, 'payment_scripts' ) );
		add_action( 'woocommerce_update_options_payment_gateways_' . $this->id, array( $this, 'process_admin_options' ) );
		add_action( 'woocommerce_receipt_' . $this->id, array( $this, 'receipt_page' ) );
		add_action( 'woocommerce_api_wc_gateway_simplify_commerce', array( $this, 'return_handler' ) );
	}

	/**
	 * Init Simplify SDK.
	 */
	protected function init_simplify_sdk() {
		// Include lib
		require_once( dirname( __FILE__ ) . '/includes/Simplify.php' );

		Simplify::$publicKey  = $this->public_key;
		Simplify::$privateKey = $this->private_key;
		Simplify::$userAgent  = 'WooCommerce/' . WC()->version;
	}

	/**
	 * Admin Panel Options.
	 * - Options for bits like 'title' and availability on a country-by-country basis.
	 */
	public function admin_options() {
		?>
		<h3><?php _e( 'Simplify Commerce by MasterCard', 'woocommerce' ); ?></h3>

		<?php if ( empty( $this->public_key ) ) : ?>
			<div class="simplify-commerce-banner updated">
				<img src="<?php echo WC()->plugin_url() . '/includes/gateways/simplify-commerce/assets/images/logo.png'; ?>" />
				<p class="main"><strong><?php _e( 'Getting started', 'woocommerce' ); ?></strong></p>
				<p><?php _e( 'Simplify Commerce is your merchant account and payment gateway all rolled into one. Choose Simplify Commerce as your WooCommerce payment gateway to get access to your money quickly with a powerful, secure payment engine backed by MasterCard.', 'woocommerce' ); ?></p>

				<p><a href="https://www.simplify.com/commerce/partners/woocommerce#/signup" target="_blank" class="button button-primary"><?php _e( 'Sign up for Simplify Commerce', 'woocommerce' ); ?></a> <a href="https://www.simplify.com/commerce/partners/woocommerce#/" target="_blank" class="button"><?php _e( 'Learn more', 'woocommerce' ); ?></a></p>

			</div>
		<?php else : ?>
			<p><?php _e( 'Simplify Commerce is your merchant account and payment gateway all rolled into one. Choose Simplify Commerce as your WooCommerce payment gateway to get access to your money quickly with a powerful, secure payment engine backed by MasterCard.', 'woocommerce' ); ?></p>
		<?php endif; ?>

		<?php $this->checks(); ?>

		<table class="form-table">
			<?php $this->generate_settings_html(); ?>
			<script type="text/javascript">
				jQuery( '#woocommerce_simplify_commerce_sandbox' ).on( 'change', function() {
					var sandbox    = jQuery( '#woocommerce_simplify_commerce_sandbox_public_key, #woocommerce_simplify_commerce_sandbox_private_key' ).closest( 'tr' ),
						production = jQuery( '#woocommerce_simplify_commerce_public_key, #woocommerce_simplify_commerce_private_key' ).closest( 'tr' );

					if ( jQuery( this ).is( ':checked' ) ) {
						sandbox.show();
						production.hide();
					} else {
						sandbox.hide();
						production.show();
					}
				}).change();

				jQuery( '#woocommerce_simplify_commerce_mode' ).on( 'change', function() {
					var color = jQuery( '#woocommerce_simplify_commerce_modal_color' ).closest( 'tr' );

					if ( 'standard' === jQuery( this ).val() ) {
						color.hide();
					} else {
						color.show();
					}
				}).change();
			</script>
		</table>
		<?php
	}

	/**
	 * Check if SSL is enabled and notify the user.
	 */
	public function checks() {
		if ( 'no' == $this->enabled ) {
			return;
		}

		if ( version_compare( phpversion(), '5.3', '<' ) ) {

			// PHP Version
			echo '<div class="error"><p>' . sprintf( __( 'Simplify Commerce Error: Simplify commerce requires PHP 5.3 and above. You are using version %s.', 'woocommerce' ), phpversion() ) . '</p></div>';

		} elseif ( ! $this->public_key || ! $this->private_key ) {

			// Check required fields
			echo '<div class="error"><p>' . __( 'Simplify Commerce Error: Please enter your public and private keys', 'woocommerce' ) . '</p></div>';

		} elseif ( 'standard' == $this->mode && ! wc_checkout_is_https() ) {

			// Show message when using standard mode and no SSL on the checkout page
			echo '<div class="error"><p>' . sprintf( __( 'Simplify Commerce is enabled, but the <a href="%s">force SSL option</a> is disabled; your checkout may not be secure! Please enable SSL and ensure your server has a valid SSL certificate - Simplify Commerce will only work in sandbox mode.', 'woocommerce' ), admin_url( 'admin.php?page=wc-settings&tab=checkout' ) ) . '</p></div>';

		}
	}

	/**
	 * Check if this gateway is enabled.
	 *
	 * @return bool
	 */
	public function is_available() {
		if ( 'yes' !== $this->enabled ) {
			return false;
		}

		if ( 'standard' === $this->mode && 'yes' !== $this->sandbox && ! wc_checkout_is_https() ) {
			return false;
		}

		if ( ! $this->public_key || ! $this->private_key ) {
			return false;
		}

		return true;
	}

	/**
	 * Initialise Gateway Settings Form Fields.
	 */
	public function init_form_fields() {
		$this->form_fields = array(
			'enabled' => array(
				'title'       => __( 'Enable/Disable', 'woocommerce' ),
				'label'       => __( 'Enable Simplify Commerce', 'woocommerce' ),
				'type'        => 'checkbox',
				'description' => '',
				'default'     => 'no',
			),
			'title' => array(
				'title'       => __( 'Title', 'woocommerce' ),
				'type'        => 'text',
				'description' => __( 'This controls the title which the user sees during checkout.', 'woocommerce' ),
				'default'     => __( 'Credit card', 'woocommerce' ),
				'desc_tip'    => true,
			),
			'description' => array(
				'title'       => __( 'Description', 'woocommerce' ),
				'type'        => 'text',
				'description' => __( 'This controls the description which the user sees during checkout.', 'woocommerce' ),
				'default'     => 'Pay with your credit card via Simplify Commerce by MasterCard.',
				'desc_tip'    => true,
			),
			'mode' => array(
				'title'       => __( 'Payment mode', 'woocommerce' ),
				'label'       => __( 'Enable Hosted Payments', 'woocommerce' ),
				'type'        => 'select',
				'description' => sprintf( __( 'Standard will display the credit card fields on your store (SSL required). %1$s Hosted Payments will display a Simplify Commerce modal dialog on your store (if SSL) or will redirect the customer to Simplify Commerce hosted page (if not SSL). %1$s Note: Hosted Payments need a new API Key pair with the hosted payments flag selected. %2$sFor more details check the Simplify Commerce docs%3$s.', 'woocommerce' ), '<br />', '<a href="https://simplify.desk.com/customer/portal/articles/1792405-how-do-i-enable-hosted-payments" target="_blank">', '</a>' ),
				'default'     => 'standard',
				'options'     => array(
					'standard' => __( 'Standard', 'woocommerce' ),
					'hosted'   => __( 'Hosted Payments', 'woocommerce' ),
				),
			),
			'modal_color' => array(
				'title'       => __( 'Modal color', 'woocommerce' ),
				'type'        => 'color',
				'description' => __( 'Set the color of the buttons and titles on the modal dialog.', 'woocommerce' ),
				'default'     => '#a46497',
				'desc_tip'    => true,
			),
			'sandbox' => array(
				'title'       => __( 'Sandbox', 'woocommerce' ),
				'label'       => __( 'Enable sandbox mode', 'woocommerce' ),
				'type'        => 'checkbox',
				'description' => __( 'Place the payment gateway in sandbox mode using sandbox API keys (real payments will not be taken).', 'woocommerce' ),
				'default'     => 'yes',
			),
			'sandbox_public_key' => array(
				'title'       => __( 'Sandbox public key', 'woocommerce' ),
				'type'        => 'text',
				'description' => __( 'Get your API keys from your Simplify account: Settings > API Keys.', 'woocommerce' ),
				'default'     => '',
				'desc_tip'    => true,
			),
			'sandbox_private_key' => array(
				'title'       => __( 'Sandbox private key', 'woocommerce' ),
				'type'        => 'text',
				'description' => __( 'Get your API keys from your Simplify account: Settings > API Keys.', 'woocommerce' ),
				'default'     => '',
				'desc_tip'    => true,
			),
			'public_key' => array(
				'title'       => __( 'Public key', 'woocommerce' ),
				'type'        => 'text',
				'description' => __( 'Get your API keys from your Simplify account: Settings > API Keys.', 'woocommerce' ),
				'default'     => '',
				'desc_tip'    => true,
			),
			'private_key' => array(
				'title'       => __( 'Private key', 'woocommerce' ),
				'type'        => 'text',
				'description' => __( 'Get your API keys from your Simplify account: Settings > API Keys.', 'woocommerce' ),
				'default'     => '',
				'desc_tip'    => true,
			),
		);
	}

	/**
	 * Payment form on checkout page.
	 */
	public function payment_fields() {
		$description = $this->get_description();

		if ( 'yes' == $this->sandbox ) {
			$description .= ' ' . sprintf( __( 'TEST MODE ENABLED. Use a test card: %s', 'woocommerce' ), '<a href="https://www.simplify.com/commerce/docs/tutorial/index#testing">https://www.simplify.com/commerce/docs/tutorial/index#testing</a>' );
		}

		if ( $description ) {
			echo wpautop( wptexturize( trim( $description ) ) );
		}

		if ( 'standard' == $this->mode ) {
			parent::payment_fields();
		}
	}

	/**
	 * Outputs scripts used for simplify payment.
	 */
	public function payment_scripts() {
		$load_scripts = false;

		if ( is_checkout() ) {
			$load_scripts = true;
		}
		if ( $this->is_available() ) {
			$load_scripts = true;
		}

		if ( false === $load_scripts ) {
			return;
		}

		wp_enqueue_script( 'simplify-commerce', 'https://www.simplify.com/commerce/v1/simplify.js', array( 'jquery' ), WC_VERSION, true );
		wp_enqueue_script( 'wc-simplify-commerce', WC()->plugin_url() . '/includes/gateways/simplify-commerce/assets/js/simplify-commerce.js', array( 'simplify-commerce', 'wc-credit-card-form' ), WC_VERSION, true );
		wp_localize_script( 'wc-simplify-commerce', 'Simplify_commerce_params', array(
			'key'           => $this->public_key,
			'card.number'   => __( 'Card number', 'woocommerce' ),
			'card.expMonth' => __( 'Expiry month', 'woocommerce' ),
			'card.expYear'  => __( 'Expiry year', 'woocommerce' ),
			'is_invalid'    => __( 'is invalid', 'woocommerce' ),
			'mode'          => $this->mode,
			'is_ssl'        => is_ssl(),
		) );
	}

	public function add_payment_method() {
		if ( empty( $_POST['simplify_token'] ) ) {
			wc_add_notice( __( 'There was a problem adding this card.', 'woocommerce' ), 'error' );
			return;
		}

		$cart_token     = wc_clean( $_POST['simplify_token'] );
		$customer_token = $this->get_users_token();
		$current_user   = wp_get_current_user();
		$customer_info  = array(
			'email' => $current_user->user_email,
			'name'  => $current_user->display_name,
		);

		$token = $this->save_token( $customer_token, $cart_token, $customer_info );
		if ( is_null( $token ) ) {
			wc_add_notice( __( 'There was a problem adding this card.', 'woocommerce' ), 'error' );
			return;
		}

		return array(
			'result'   => 'success',
			'redirect' => wc_get_endpoint_url( 'payment-methods' ),
		);
	}

	/**
	 * Actually saves a customer token to the database.
	 *
	 * @param  WC_Payment_Token   $customer_token Payment Token
	 * @param  string             $cart_token     CC Token
	 * @param  array              $customer_info  'email', 'name'
	 */
	public function save_token( $customer_token, $cart_token, $customer_info ) {
		if ( ! is_null( $customer_token ) ) {
			$customer = Simplify_Customer::findCustomer( $customer_token->get_token() );
			$updates = array( 'token' => $cart_token );
			$customer->setAll( $updates );
			$customer->updateCustomer();
			$customer = Simplify_Customer::findCustomer( $customer_token->get_token() ); // get updated customer with new set card
			$token = $customer_token;
		} else {
			$customer = Simplify_Customer::createCustomer( array(
				'token'     => $cart_token,
				'email'     => $customer_info['email'],
				'name'      => $customer_info['name'],
			) );
			$token = new WC_Payment_Token_CC();
			$token->set_token( $customer->id );
		}

		// If we were able to create an save our card, save the data on our side too
		if ( is_object( $customer ) && '' != $customer->id ) {
			$customer_properties = $customer->getProperties();
			$card = $customer_properties['card'];
			$token->set_gateway_id( $this->id );
			$token->set_card_type( strtolower( $card->type ) );
			$token->set_last4( $card->last4 );
			$expiry_month = ( 1 === strlen( $card->expMonth ) ? '0' . $card->expMonth : $card->expMonth );
			$token->set_expiry_month( $expiry_month );
			$token->set_expiry_year( '20' . $card->expYear );
			if ( is_user_logged_in() ) {
				$token->set_user_id( get_current_user_id() );
			}
			$token->save();
			return $token;
		}

		return null;
	}

	/**
	 * Process customer: updating or creating a new customer/saved CC
	 *
	 * @param  WC_Order           $order          Order object
	 * @param  WC_Payment_Token   $customer_token Payment Token
	 * @param  string             $cart_token     CC Token
	 */
	protected function process_customer( $order, $customer_token = null, $cart_token = '' ) {
		// Are we saving a new payment method?
		if ( is_user_logged_in() && isset( $_POST['wc-simplify_commerce-new-payment-method'] ) && true === (bool) $_POST['wc-simplify_commerce-new-payment-method'] ) {
			$customer_info = array(
				'email' => $order->get_billing_email(),
				'name'  => trim( $order->get_formatted_billing_full_name() ),
			);
			$token = $this->save_token( $customer_token, $cart_token, $customer_info );
			if ( ! is_null( $token ) ) {
				$order->add_payment_token( $token );
			}
		}
	}

	/**
	 * Process standard payments.
	 *
	 * @param  WC_Order $order
	 * @param  string   $cart_token
	 * @uses   Simplify_ApiException
	 * @uses   Simplify_BadRequestException
	 * @return array
	 */
	protected function process_standard_payments( $order, $cart_token = '', $customer_token = '' ) {
		try {

			if ( empty( $cart_token ) && empty( $customer_token ) ) {
				$error_msg = __( 'Please make sure your card details have been entered correctly and that your browser supports JavaScript.', 'woocommerce' );

				if ( 'yes' == $this->sandbox ) {
					$error_msg .= ' ' . __( 'Developers: Please make sure that you\'re including jQuery and there are no JavaScript errors on the page.', 'woocommerce' );
				}

				throw new Simplify_ApiException( $error_msg );
			}

			// We need to figure out if we want to charge the card token (new unsaved token, no customer, etc)
			// or the customer token (just saved method, previously saved method)
			$pass_tokens = array();

			if ( ! empty( $cart_token ) ) {
				$pass_tokens['token'] = $cart_token;
			}

			if ( ! empty( $customer_token ) ) {
				$pass_tokens['customer'] = $customer_token;
				// Use the customer token only, since we already saved the (one time use) card token to the customer
				if ( isset( $_POST['wc-simplify_commerce-new-payment-method'] ) && true === (bool) $_POST['wc-simplify_commerce-new-payment-method'] ) {
					unset( $pass_tokens['token'] );
				}
			}

			// Did we create an account and save a payment method? We might need to use the customer token instead of the card token
			if ( isset( $_POST['createaccount'] ) && true === (bool) $_POST['createaccount'] && empty( $customer_token ) ) {
				$user_token = $this->get_users_token();
				if ( ! is_null( $user_token ) ) {
					$pass_tokens['customer'] = $user_token->get_token();
					unset( $pass_tokens['token'] );
				}
			}

			$payment_response = $this->do_payment( $order, $order->get_total(), $pass_tokens );

			if ( is_wp_error( $payment_response ) ) {
				throw new Simplify_ApiException( $payment_response->get_error_message() );
			} else {
				// Remove cart
				WC()->cart->empty_cart();

				// Return thank you page redirect
				return array(
					'result'   => 'success',
					'redirect' => $this->get_return_url( $order ),
				);
			}
		} catch ( Simplify_ApiException $e ) {
			if ( $e instanceof Simplify_BadRequestException && $e->hasFieldErrors() && $e->getFieldErrors() ) {
				foreach ( $e->getFieldErrors() as $error ) {
					wc_add_notice( $error->getFieldName() . ': "' . $error->getMessage() . '" (' . $error->getErrorCode() . ')', 'error' );
				}
			} else {
				wc_add_notice( $e->getMessage(), 'error' );
			}

			return array(
				'result'   => 'fail',
				'redirect' => '',
			);
		}
	}

 	/**
	 * do payment function.
	 *
	 * @param WC_order $order
	 * @param int $amount (default: 0)
	 * @uses  Simplify_BadRequestException
	 * @return bool|WP_Error
	 */
	public function do_payment( $order, $amount = 0, $token = array() ) {
		if ( $amount * 100 < 50 ) {
			return new WP_Error( 'simplify_error', __( 'Sorry, the minimum allowed order total is 0.50 to use this payment method.', 'woocommerce' ) );
		}

		try {
			// Charge the customer
			$data = array(
				'amount'              => $amount * 100, // In cents.
				'description'         => sprintf( __( '%1$s - Order #%2$s', 'woocommerce' ), esc_html( get_bloginfo( 'name', 'display' ) ), $order->get_order_number() ),
				'currency'            => strtoupper( get_woocommerce_currency() ),
				'reference'           => $order->get_id(),
			);

			$data = array_merge( $data, $token );
			$payment = Simplify_Payment::createPayment( $data );

		} catch ( Exception $e ) {

			$error_message = $e->getMessage();

			if ( $e instanceof Simplify_BadRequestException && $e->hasFieldErrors() && $e->getFieldErrors() ) {
				$error_message = '';
				foreach ( $e->getFieldErrors() as $error ) {
					$error_message .= ' ' . $error->getFieldName() . ': "' . $error->getMessage() . '" (' . $error->getErrorCode() . ')';
				}
			}

			$order->add_order_note( sprintf( __( 'Simplify payment error: %s.', 'woocommerce' ), $error_message ) );

			return new WP_Error( 'simplify_payment_declined', $e->getMessage(), array( 'status' => $e->getCode() ) );
		}

		if ( 'APPROVED' == $payment->paymentStatus ) {
			// Payment complete
			$order->payment_complete( $payment->id );

			// Add order note
			$order->add_order_note( sprintf( __( 'Simplify payment approved (ID: %1$s, Auth Code: %2$s)', 'woocommerce' ), $payment->id, $payment->authCode ) );

			return true;
		} else {
			$order->add_order_note( __( 'Simplify payment declined', 'woocommerce' ) );

			return new WP_Error( 'simplify_payment_declined', __( 'Payment was declined - please try another card.', 'woocommerce' ) );
		}
	}

	/**
	 * Process standard payments.
	 *
	 * @param WC_Order $order
	 * @return array
	 */
	protected function process_hosted_payments( $order ) {
		return array(
			'result'   => 'success',
			'redirect' => $order->get_checkout_payment_url( true ),
		);
	}

	protected function get_users_token() {
		$customer_token  = null;
		if ( is_user_logged_in() ) {
			$tokens = WC_Payment_Tokens::get_customer_tokens( get_current_user_id() );
			foreach ( $tokens as $token ) {
				if ( $token->get_gateway_id() === $this->id ) {
					$customer_token = $token;
					break;
				}
			}
		}
		return $customer_token;
	}

	/**
	 * Process the payment.
	 *
	 * @param int $order_id
	 */
	public function process_payment( $order_id ) {
		$order = wc_get_order( $order_id );

		// Payment/CC form is hosted on Simplify
		if ( 'hosted' === $this->mode ) {
			return $this->process_hosted_payments( $order );
		}

		// New CC info was entered
		if ( isset( $_POST['simplify_token'] ) ) {
			$cart_token           = wc_clean( $_POST['simplify_token'] );
			$customer_token       = $this->get_users_token();
			$customer_token_value = ( ! is_null( $customer_token ) ? $customer_token->get_token() : '' );
			$this->process_customer( $order, $customer_token, $cart_token );
			return $this->process_standard_payments( $order, $cart_token, $customer_token_value );
		}

		// Possibly Create (or update) customer/save payment token, use an existing token, and then process the payment
		if ( isset( $_POST['wc-simplify_commerce-payment-token'] ) && 'new' !== $_POST['wc-simplify_commerce-payment-token'] ) {
			$token_id = wc_clean( $_POST['wc-simplify_commerce-payment-token'] );
			$token    = WC_Payment_Tokens::get( $token_id );
			if ( $token->get_user_id() !== get_current_user_id() ) {
				wc_add_notice( __( 'Please make sure your card details have been entered correctly and that your browser supports JavaScript.', 'woocommerce' ), 'error' );
				return;
			}
			$this->process_customer( $order, $token );
			return $this->process_standard_payments( $order, '', $token->get_token() );
		}
	}

	/**
	 * Hosted payment args.
	 *
	 * @param  WC_Order $order
	 *
	 * @return array
	 */
	protected function get_hosted_payments_args( $order ) {
		$args = apply_filters( 'woocommerce_simplify_commerce_hosted_args', array(
			'sc-key'          => $this->public_key,
			'amount'          => $order->get_total() * 100,
			'reference'       => $order->get_id(),
			'name'            => esc_html( get_bloginfo( 'name', 'display' ) ),
			'description'     => sprintf( __( 'Order #%s', 'woocommerce' ), $order->get_order_number() ),
			'receipt'         => 'false',
			'color'           => $this->modal_color,
			'redirect-url'    => WC()->api_request_url( 'WC_Gateway_Simplify_Commerce' ),
			'address'         => $order->get_billing_address_1() . ' ' . $order->get_billing_address_2(),
			'address-city'    => $order->get_billing_city(),
			'address-state'   => $order->get_billing_state(),
			'address-zip'     => $order->get_billing_postcode(),
			'address-country' => $order->get_billing_country(),
			'operation'       => 'create.token',
		), $order->get_id() );

		return $args;
	}

	/**
	 * Receipt page.
	 *
	 * @param  int $order_id
	 */
	public function receipt_page( $order_id ) {
		$order = wc_get_order( $order_id );

		echo '<p>' . __( 'Thank you for your order, please click the button below to pay with credit card using Simplify Commerce by MasterCard.', 'woocommerce' ) . '</p>';

		$args        = $this->get_hosted_payments_args( $order );
		$button_args = array();
		foreach ( $args as $key => $value ) {
			$button_args[] = 'data-' . esc_attr( $key ) . '="' . esc_attr( $value ) . '"';
		}

		echo '<script type="text/javascript" src="https://www.simplify.com/commerce/simplify.pay.js"></script>
			<button class="button alt" id="simplify-payment-button" ' . implode( ' ', $button_args ) . '>' . __( 'Pay now', 'woocommerce' ) . '</button> <a class="button cancel" href="' . esc_url( $order->get_cancel_order_url() ) . '">' . __( 'Cancel order &amp; restore cart', 'woocommerce' ) . '</a>
			';
	}

	/**
	 * Return handler for Hosted Payments.
	 */
	public function return_handler() {
		@ob_clean();
		header( 'HTTP/1.1 200 OK' );

		if ( isset( $_REQUEST['reference'] ) && isset( $_REQUEST['paymentId'] ) && isset( $_REQUEST['signature'] ) ) {
			$signature = strtoupper( md5( $_REQUEST['amount'] . $_REQUEST['reference'] . $_REQUEST['paymentId'] . $_REQUEST['paymentDate'] . $_REQUEST['paymentStatus'] . $this->private_key ) );
			$order_id  = absint( $_REQUEST['reference'] );
			$order     = wc_get_order( $order_id );

			if ( hash_equals( $signature, $_REQUEST['signature'] ) ) {
				$order_complete = $this->process_order_status( $order, $_REQUEST['paymentId'], $_REQUEST['paymentStatus'], $_REQUEST['paymentDate'] );

				if ( ! $order_complete ) {
					$order->update_status( 'failed', __( 'Payment was declined by Simplify Commerce.', 'woocommerce' ) );
				}

				wp_redirect( $this->get_return_url( $order ) );
				exit();
			}
		}

		wp_redirect( wc_get_page_permalink( 'cart' ) );
		exit();
	}

	/**
	 * Process the order status.
	 *
	 * @param  WC_Order $order
	 * @param  string   $payment_id
	 * @param  string   $status
	 * @param  string   $auth_code
	 *
	 * @return bool
	 */
	public function process_order_status( $order, $payment_id, $status, $auth_code ) {
		if ( 'APPROVED' == $status ) {
			// Payment complete
			$order->payment_complete( $payment_id );

			// Add order note
			$order->add_order_note( sprintf( __( 'Simplify payment approved (ID: %1$s, Auth Code: %2$s)', 'woocommerce' ), $payment_id, $auth_code ) );

			// Remove cart
			WC()->cart->empty_cart();

			return true;
		}

		return false;
	}

	/**
	 * Process refunds.
	 * WooCommerce 2.2 or later.
	 *
	 * @param  int $order_id
	 * @param  float $amount
	 * @param  string $reason
	 * @uses   Simplify_ApiException
	 * @uses   Simplify_BadRequestException
	 * @return bool|WP_Error
	 */
	public function process_refund( $order_id, $amount = null, $reason = '' ) {
		try {
			$payment_id = get_post_meta( $order_id, '_transaction_id', true );

			$refund = Simplify_Refund::createRefund( array(
				'amount'    => $amount * 100, // In cents.
				'payment'   => $payment_id,
				'reason'    => $reason,
				'reference' => $order_id,
			) );

			if ( 'APPROVED' == $refund->paymentStatus ) {
				return true;
			} else {
				throw new Simplify_ApiException( __( 'Refund was declined.', 'woocommerce' ) );
			}
		} catch ( Simplify_ApiException $e ) {
			if ( $e instanceof Simplify_BadRequestException && $e->hasFieldErrors() && $e->getFieldErrors() ) {
				foreach ( $e->getFieldErrors() as $error ) {
					return new WP_Error( 'simplify_refund_error', $error->getFieldName() . ': "' . $error->getMessage() . '" (' . $error->getErrorCode() . ')' );
				}
			} else {
				return new WP_Error( 'simplify_refund_error', $e->getMessage() );
			}
		}

		return false;
	}

	/**
	 * Get gateway icon.
	 *
	 * @access public
	 * @return string
	 */
	public function get_icon() {
		$icon  = '<img src="' . WC_HTTPS::force_https_url( WC()->plugin_url() . '/assets/images/icons/credit-cards/visa.svg' ) . '" alt="Visa" width="32" />';
		$icon .= '<img src="' . WC_HTTPS::force_https_url( WC()->plugin_url() . '/assets/images/icons/credit-cards/mastercard.svg' ) . '" alt="MasterCard" width="32" />';
		$icon .= '<img src="' . WC_HTTPS::force_https_url( WC()->plugin_url() . '/assets/images/icons/credit-cards/discover.svg' ) . '" alt="Discover" width="32" />';
		$icon .= '<img src="' . WC_HTTPS::force_https_url( WC()->plugin_url() . '/assets/images/icons/credit-cards/amex.svg' ) . '" alt="Amex" width="32" />';
		$icon .= '<img src="' . WC_HTTPS::force_https_url( WC()->plugin_url() . '/assets/images/icons/credit-cards/jcb.svg' ) . '" alt="JCB" width="32" />';

		return apply_filters( 'woocommerce_gateway_icon', $icon, $this->id );
	}
}
