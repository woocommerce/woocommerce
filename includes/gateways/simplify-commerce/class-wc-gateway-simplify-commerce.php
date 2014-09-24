<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

/**
 * Simplify Commerce Gateway
 *
 * @class 		WC_Gateway_Simplify_Commerce
 * @extends		WC_Payment_Gateway
 * @since       2.2.0
 * @version		1.0.0
 * @package		WooCommerce/Classes/Payment
 * @author 		WooThemes
 */
class WC_Gateway_Simplify_Commerce extends WC_Payment_Gateway {

	/**
	 * Constructor
	 */
	public function __construct() {
		$this->id                 = 'simplify_commerce';
		$this->method_title       = __( 'Simplify Commerce', 'woocommerce' );
		$this->method_description = __( 'Take payments via Simplify Commerce - uses simplify.js to create card tokens and the Simplify Commerce SDK. Requires SSL when sandbox is disabled.', 'woocommerce' );
		$this->has_fields         = true;
		$this->supports           = array(
			'subscriptions',
			'products',
			'subscription_cancellation',
			'subscription_reactivation',
			'subscription_suspension',
			'subscription_amount_changes',
			'subscription_payment_method_change',
			'subscription_date_changes',
			'default_credit_card_form',
			'refunds',
			'pre-orders'
		);
		$this->view_transaction_url = 'https://www.simplify.com/commerce/app#/payment/%s';

		// Load the form fields
		$this->init_form_fields();

		// Load the settings.
		$this->init_settings();

		// Get setting values
		$this->title       = $this->get_option( 'title' );
		$this->description = $this->get_option( 'description' );
		$this->enabled     = $this->get_option( 'enabled' );
		$this->sandbox     = $this->get_option( 'sandbox' );
		$this->private_key = $this->sandbox == 'no' ? $this->get_option( 'private_key' ) : $this->get_option( 'sandbox_private_key' );
		$this->public_key  = $this->sandbox == 'no' ? $this->get_option( 'public_key' ) : $this->get_option( 'sandbox_public_key' );

		$this->init_simplify_sdk();

		// Hooks
		add_action( 'wp_enqueue_scripts', array( $this, 'payment_scripts' ) );
		add_action( 'admin_notices', array( $this, 'checks' ) );
		add_action( 'woocommerce_update_options_payment_gateways_' . $this->id, array( $this, 'process_admin_options' ) );
	}

	/**
	 * Init Simplify SDK.
	 *
	 * @return void
	 */
	protected function init_simplify_sdk() {
		// Include lib
		require_once( 'includes/Simplify.php' );

		Simplify::$publicKey  = $this->public_key;
		Simplify::$privateKey = $this->private_key;
		Simplify::$userAgent  = 'WooCommerce/' . WC()->version;
	}

	/**
	 * Admin Panel Options
	 * - Options for bits like 'title' and availability on a country-by-country basis
	 *
	 * @access public
	 * @return void
	 */
  	public function admin_options() {
		?>
		<h3><?php _e( 'Simplify Commerce by Mastercard', 'woocommerce' ); ?></h3>

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

		<table class="form-table">
			<?php $this->generate_settings_html(); ?>
			<script type="text/javascript">
				jQuery( '#woocommerce_simplify_commerce_sandbox' ).change( function () {
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
			</script>
		</table>
		<?php
  	}

	/**
	 * Check if SSL is enabled and notify the user
	 */
	public function checks() {
		if ( $this->enabled == 'no' ) {
			return;
		}

		// PHP Version
		if ( version_compare( phpversion(), '5.3', '<' ) ) {
			echo '<div class="error"><p>' . sprintf( __( 'Simplify Commerce Error: Simplify commerce requires PHP 5.3 and above. You are using version %s.', 'woocommerce' ), phpversion() ) . '</p></div>';
		}

		// Check required fields
		elseif ( ! $this->public_key || ! $this->private_key ) {
			echo '<div class="error"><p>' . __( 'Simplify Commerce Error: Please enter your public and private keys', 'woocommerce' ) . '</p></div>';
		}

		// Show message if enabled and FORCE SSL is disabled and WordpressHTTPS plugin is not detected
		elseif ( 'no' == get_option( 'woocommerce_force_ssl_checkout' ) && ! class_exists( 'WordPressHTTPS' ) ) {
			echo '<div class="error"><p>' . sprintf( __( 'Simplify Commerce is enabled, but the <a href="%s">force SSL option</a> is disabled; your checkout may not be secure! Please enable SSL and ensure your server has a valid SSL certificate - Simplify Commerce will only work in sandbox mode.', 'woocommerce'), admin_url( 'admin.php?page=wc-settings&tab=checkout' ) ) . '</p></div>';
		}
	}

	/**
	 * Check if this gateway is enabled
	 */
	public function is_available() {
		if ( 'yes' != $this->enabled ) {
			return false;
		}

		if ( ! is_ssl() && 'yes' != $this->sandbox ) {
			return false;
		}

		if ( ! $this->public_key || ! $this->private_key ) {
			return false;
		}

		return true;
	}

	/**
	 * Initialise Gateway Settings Form Fields
	 */
	public function init_form_fields() {
		$this->form_fields = array(
			'enabled' => array(
				'title'       => __( 'Enable/Disable', 'woocommerce' ),
				'label'       => __( 'Enable Simplify Commerce', 'woocommerce' ),
				'type'        => 'checkbox',
				'description' => '',
				'default'     => 'no'
			),
			'title' => array(
				'title'       => __( 'Title', 'woocommerce' ),
				'type'        => 'text',
				'description' => __( 'This controls the title which the user sees during checkout.', 'woocommerce' ),
				'default'     => __( 'Credit card', 'woocommerce' ),
				'desc_tip'    => true
			),
			'description' => array(
				'title'       => __( 'Description', 'woocommerce' ),
				'type'        => 'text',
				'description' => __( 'This controls the description which the user sees during checkout.', 'woocommerce' ),
				'default'     => 'Pay with your credit card via Simplify Commerce by Mastercard.',
				'desc_tip'    => true
			),
			'sandbox' => array(
				'title'       => __( 'Sandbox', 'woocommerce' ),
				'label'       => __( 'Enable Sandbox Mode', 'woocommerce' ),
				'type'        => 'checkbox',
				'description' => __( 'Place the payment gateway in sandbox mode using sandbox API keys (real payments will not be taken).', 'woocommerce' ),
				'default'     => 'yes'
			),
			'sandbox_public_key' => array(
				'title'       => __( 'Sandbox Public Key', 'woocommerce' ),
				'type'        => 'text',
				'description' => __( 'Get your API keys from your Simplify account: Settings > API Keys.', 'woocommerce' ),
				'default'     => '',
				'desc_tip'    => true
			),
			'sandbox_private_key' => array(
				'title'       => __( 'Sandbox Private Key', 'woocommerce' ),
				'type'        => 'text',
				'description' => __( 'Get your API keys from your Simplify account: Settings > API Keys.', 'woocommerce' ),
				'default'     => '',
				'desc_tip'    => true
			),
			'public_key' => array(
				'title'       => __( 'Public Key', 'woocommerce' ),
				'type'        => 'text',
				'description' => __( 'Get your API keys from your Simplify account: Settings > API Keys.', 'woocommerce' ),
				'default'     => '',
				'desc_tip'    => true
			),
			'private_key' => array(
				'title'       => __( 'Private Key', 'woocommerce' ),
				'type'        => 'text',
				'description' => __( 'Get your API keys from your Simplify account: Settings > API Keys.', 'woocommerce' ),
				'default'     => '',
				'desc_tip'    => true
			),
		);
	}

	/**
	 * Payment form on checkout page
	 */
	public function payment_fields() {
		$description = $this->get_description();

		if ( 'yes' == $this->sandbox ) {
			$description .= ' ' . sprintf( __( 'TEST MODE ENABLED. Use a test card: %s', 'woocommerce' ), '<a href="https://www.simplify.com/commerce/docs/tutorial/index#testing">https://www.simplify.com/commerce/docs/tutorial/index#testing</a>' );
		}

		if ( $description ) {
			echo wpautop( wptexturize( trim( $description ) ) );
		}

		$this->credit_card_form( array( 'fields_have_names' => false ) );
	}

	/**
	 * payment_scripts function.
	 *
	 * Outputs scripts used for simplify payment
	 */
	public function payment_scripts() {
		if ( ! is_checkout() || ! $this->is_available() ) {
			return;
		}

		wp_enqueue_script( 'simplify-commerce', 'https://www.simplify.com/commerce/v1/simplify.js', array( 'jquery' ), WC_VERSION, true );
		wp_enqueue_script( 'wc-simplify-commerce', WC()->plugin_url() . '/includes/gateways/simplify-commerce/assets/js/simplify-commerce.js', array( 'simplify-commerce', 'wc-credit-card-form' ), WC_VERSION, true );
		wp_localize_script( 'wc-simplify-commerce', 'Simplify_commerce_params', array(
			'key'           => $this->public_key,
			'card.number'   => __( 'Card Number', 'woocommerce' ),
			'card.expMonth' => __( 'Expiry Month', 'woocommerce' ),
			'card.expYear'  => __( 'Expiry Year', 'woocommerce' ),
			'is_invalid'    => __( 'is invalid', 'woocommerce' )
		) );
	}

	/**
	 * Process the payment
	 * @param integer $order_id
	 */
	public function process_payment( $order_id ) {
		$order = wc_get_order( $order_id );
		$token = isset( $_POST['simplify_token'] ) ? wc_clean( $_POST['simplify_token'] ) : '';

		try {

			if ( empty( $token ) ) {
				$error_msg = __( 'Please make sure your card details have been entered correctly and that your browser supports JavaScript.', 'woocommerce' );

				if ( 'yes' == $this->sandbox ) {
					$error_msg .= ' ' . __( 'Developers: Please make sure that you\'re including jQuery and there are no JavaScript errors on the page.', 'woocommerce' );
				}

				throw new Simplify_ApiException( $error_msg );
			}

			$payment = Simplify_Payment::createPayment( array(
				'amount'              => $order->order_total * 100, // In cents
				'token'               => $token,
				'description'         => sprintf( __( '%s - Order %s', 'woocommerce' ), esc_html( get_bloginfo( 'name' ) ), $order->get_order_number() ),
				'currency'            => strtoupper( get_woocommerce_currency() ),
				'reference'           => $order->id,
				'card.addressCity'    => $order->billing_city,
				'card.addressCountry' => $order->billing_country,
				'card.addressLine1'   => $order->billing_address_1,
				'card.addressLine2'   => $order->billing_address_2,
				'card.addressState'   => $order->billing_state,
				'card.addressZip'     => $order->billing_postcode
			) );

			if ( 'APPROVED' == $payment->paymentStatus ) {
				// Payment complete
				$order->payment_complete( $payment->id );

				// Add order note
				$order->add_order_note( sprintf( __( 'Simplify payment approved (ID: %s, Auth Code: %s)', 'woocommerce' ), $payment->id, $payment->authCode ) );

				// Remove cart
				WC()->cart->empty_cart();

				// Return thank you page redirect
				return array(
					'result'   => 'success',
					'redirect' => $this->get_return_url( $order )
				);
			} else {
				$order->add_order_note( __( 'Simplify payment declined', 'woocommerce' ) );

				throw new Simplify_ApiException( __( 'Payment was declined - please try another card.', 'woocommerce' ) );
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
				'redirect' => ''
			);
		}
	}

	/**
	 * Process refunds
	 * WooCommerce 2.2 or later
	 *
	 * @param  int $order_id
	 * @param  float $amount
	 * @param  string $reason
	 * @return bool|WP_Error
	 */
	public function process_refund( $order_id, $amount = null, $reason = '' ) {
		try {
			$payment_id = get_post_meta( $order_id, '_transaction_id', true );

			$refund = Simplify_Refund::createRefund( array(
				'amount'    => $amount * 100, // In cents
				'payment'   => $payment_id,
				'reason'    => $reason,
				'reference' => $order_id
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
	 * get_icon function.
	 *
	 * @access public
	 * @return string
	 */
	public function get_icon() {
		$icon  = '<img src="' . WC_HTTPS::force_https_url(  WC()->plugin_url() . '/assets/images/icons/credit-cards/visa.png' ) . '" alt="Visa" />';
		$icon .= '<img src="' . WC_HTTPS::force_https_url(  WC()->plugin_url() . '/assets/images/icons/credit-cards/mastercard.png' ) . '" alt="Mastercard" />';
		$icon .= '<img src="' . WC_HTTPS::force_https_url(  WC()->plugin_url() . '/assets/images/icons/credit-cards/discover.png' ) . '" alt="Discover" />';
		$icon .= '<img src="' . WC_HTTPS::force_https_url(  WC()->plugin_url() . '/assets/images/icons/credit-cards/amex.png' ) . '" alt="Amex" />';
		$icon .= '<img src="' . WC_HTTPS::force_https_url(  WC()->plugin_url() . '/assets/images/icons/credit-cards/jcb.png' ) . '" alt="JCB" />';

		return apply_filters( 'woocommerce_gateway_icon', $icon, $this->id );
	}
}
