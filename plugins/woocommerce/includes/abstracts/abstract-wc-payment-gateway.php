<?php
/**
 * Abstract payment gateway
 *
 * Handles generic payment gateway functionality which is extended by individual payment gateways.
 *
 * @class WC_Payment_Gateway
 * @version 2.1.0
 * @package WooCommerce\Abstracts
 */

use Automattic\Jetpack\Constants;
use Automattic\WooCommerce\Enums\PaymentGatewayFeature;
use Automattic\WooCommerce\Internal\Utilities\HtmlSanitizer;
use Automattic\WooCommerce\Admin\Features\Features;
use Automattic\WooCommerce\Utilities\FeaturesUtil;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * WooCommerce Payment Gateway class.
 *
 * Extended by individual payment gateways to handle payments.
 *
 * @class       WC_Payment_Gateway
 * @extends     WC_Settings_API
 * @version     2.1.0
 * @package     WooCommerce\Abstracts
 */
abstract class WC_Payment_Gateway extends WC_Settings_API {

	/**
	 * Set if the place order button should be renamed on selection.
	 *
	 * @var string
	 */
	public $order_button_text;

	/**
	 * Yes or no based on whether the method is enabled.
	 *
	 * @var string
	 */
	public $enabled = 'yes';

	/**
	 * Payment method title for the frontend.
	 *
	 * @var string
	 */
	public $title;

	/**
	 * Payment method description for the frontend.
	 *
	 * @var string
	 */
	public $description;

	/**
	 * Chosen payment method id.
	 *
	 * @var bool
	 */
	public $chosen;

	/**
	 * Gateway title.
	 *
	 * @var string
	 */
	public $method_title = '';

	/**
	 * Gateway description.
	 *
	 * @var string
	 */
	public $method_description = '';

	/**
	 * True if the gateway shows fields on the checkout.
	 *
	 * @var bool
	 */
	public $has_fields;

	/**
	 * Countries this gateway is allowed for.
	 *
	 * @var array
	 */
	public $countries;

	/**
	 * Available for all counties or specific.
	 *
	 * @var string
	 */
	public $availability;

	/**
	 * Icon for the gateway.
	 *
	 * @var string
	 */
	public $icon;

	/**
	 * Supported features such as 'default_credit_card_form', 'refunds'.
	 *
	 * @var array
	 */
	public $supports = array( 'products' );

	/**
	 * Maximum transaction amount, zero does not define a maximum.
	 *
	 * @var int
	 */
	public $max_amount = 0;

	/**
	 * Optional URL to view a transaction.
	 *
	 * @var string
	 */
	public $view_transaction_url = '';

	/**
	 * Optional label to show for "new payment method" in the payment
	 * method/token selection radio selection.
	 *
	 * @var string
	 */
	public $new_method_label = '';

	/**
	 * Pay button ID if supported.
	 *
	 * @var string
	 */
	public $pay_button_id = '';

	/**
	 * Contains a users saved tokens for this gateway.
	 *
	 * @var array
	 */
	protected $tokens = array();

	/**
	 * Returns a users saved tokens for this gateway.
	 *
	 * @since 2.6.0
	 * @return array
	 */
	public function get_tokens() {
		if ( count( $this->tokens ) > 0 ) {
			return $this->tokens;
		}

		if ( is_user_logged_in() && $this->supports( PaymentGatewayFeature::TOKENIZATION ) ) {
			$this->tokens = WC_Payment_Tokens::get_customer_tokens( get_current_user_id(), $this->id );
		}

		return $this->tokens;
	}

	/**
	 * Return the title for admin screens.
	 *
	 * @return string
	 */
	public function get_method_title() {
		/**
		 * Filter the method title.
		 *
		 * @since 2.6.0
		 * @param string $title Method title.
		 * @param WC_Payment_Gateway $this Payment gateway instance.
		 * @return string
		 */
		return apply_filters( 'woocommerce_gateway_method_title', $this->method_title, $this );
	}

	/**
	 * Return the description for admin screens.
	 *
	 * @return string
	 */
	public function get_method_description() {
		/**
		 * Filter the method description.
		 *
		 * @since 2.6.0
		 * @param string $description Method description.
		 * @param WC_Payment_Gateway $this Payment gateway instance.
		 * @return string
		 */
		return apply_filters( 'woocommerce_gateway_method_description', $this->method_description, $this );
	}

	/**
	 * Output the gateway settings screen.
	 */
	public function admin_options() {
		$offline_payment_gateways = array( WC_Gateway_BACS::ID, WC_Gateway_Cheque::ID, WC_Gateway_COD::ID );
		$is_offline_gateway       = in_array( $this->id, $offline_payment_gateways, true );

		$return_url = admin_url( 'admin.php?page=wc-settings&tab=checkout' );
		if ( $is_offline_gateway ) {
			$return_url = add_query_arg( 'section', 'offline', $return_url );
		}

		wc_back_header( $this->get_method_title(), __( 'Return to payments', 'woocommerce' ), $return_url );

		echo wp_kses_post( wpautop( $this->get_method_description() ) );
		parent::admin_options();
	}

	/**
	 * Init settings for gateways.
	 */
	public function init_settings() {
		parent::init_settings();
		$this->enabled = ! empty( $this->settings['enabled'] ) && 'yes' === $this->settings['enabled'] ? 'yes' : 'no';
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
		return false;
	}

	/**
	 * Get the return url (thank you page).
	 *
	 * @param WC_Order|null $order Order object.
	 * @return string
	 */
	public function get_return_url( $order = null ) {
		if ( $order ) {
			$return_url = $order->get_checkout_order_received_url();
		} else {
			$return_url = wc_get_endpoint_url( 'order-received', '', wc_get_checkout_url() );
		}

		/**
		 * Filter the return url.
		 *
		 * @since 2.4.0
		 * @param string $return_url Return URL.
		 * @param WC_Order|null $order Order object.
		 * @return string
		 */
		return apply_filters( 'woocommerce_get_return_url', $return_url, $order );
	}

	/**
	 * Get a link to the transaction on the 3rd party gateway site (if applicable).
	 *
	 * @param  WC_Order $order the order object.
	 * @return string transaction URL, or empty string.
	 */
	public function get_transaction_url( $order ) {

		$return_url     = '';
		$transaction_id = $order->get_transaction_id();

		if ( ! empty( $this->view_transaction_url ) && ! empty( $transaction_id ) ) {
			$return_url = sprintf( $this->view_transaction_url, $transaction_id );
		}

		/**
		 * Filter the transaction url.
		 *
		 * @since 2.2.0
		 * @param string $return_url Transaction URL.
		 * @param WC_Order|null $order Order object.
		 * @return string
		 */
		return apply_filters( 'woocommerce_get_transaction_url', $return_url, $order, $this );
	}

	/**
	 * Get the order total in checkout and pay_for_order.
	 *
	 * @return float
	 */
	protected function get_order_total() {

		$total    = 0;
		$order_id = absint( get_query_var( 'order-pay' ) );

		// Gets order total from "pay for order" page.
		if ( 0 < $order_id ) {
			$order = wc_get_order( $order_id );
			if ( $order ) {
				$total = (float) $order->get_total();
			}

			// Gets order total from cart/checkout.
		} elseif ( 0 < WC()->cart->total ) {
			$total = (float) WC()->cart->total;
		}

		return $total;
	}

	/**
	 * Check if the gateway is available for use.
	 *
	 * @return bool
	 */
	public function is_available() {
		$is_available = ( 'yes' === $this->enabled );

		if ( WC()->cart && 0 < $this->get_order_total() && 0 < $this->max_amount && $this->max_amount < $this->get_order_total() ) {
			$is_available = false;
		}

		return $is_available;
	}

	/**
	 * Check if the gateway has fields on the checkout.
	 *
	 * @return bool
	 */
	public function has_fields() {
		return (bool) $this->has_fields;
	}

	/**
	 * Return the gateway's title.
	 *
	 * @return string
	 */
	public function get_title() {
		$title = wc_get_container()->get( HtmlSanitizer::class )->sanitize( (string) $this->title, HtmlSanitizer::LOW_HTML_BALANCED_TAGS_NO_LINKS );

		/**
		 * Filter the gateway title.
		 *
		 * @since 1.5.8
		 * @param string $title Gateway title.
		 * @param string $id Gateway ID.
		 * @return string
		 */
		return apply_filters( 'woocommerce_gateway_title', $title, $this->id );
	}

	/**
	 * Return the gateway's description.
	 *
	 * @return string
	 */
	public function get_description() {
		/**
		 * Filters the gateway description.
		 *
		 * Descriptions can be overridden by extending this method or through the use of `woocommerce_gateway_description`
		 * To avoid breaking custom HTML that may be returned we cannot enforce KSES at render time, so we run it here.
		 *
		 * @since 1.5.8
		 * @since 9.0.0 wp_kses_post() is used to sanitize the description before passing it to the filter.
		 * @param string $description Gateway description.
		 * @param string $id Gateway ID.
		 * @return string
		 */
		return apply_filters( 'woocommerce_gateway_description', wp_kses_post( $this->description ), $this->id );
	}

	/**
	 * Return the gateway's icon.
	 *
	 * @return string
	 */
	public function get_icon() {
		$icon = $this->icon ? '<img src="' . esc_url( WC_HTTPS::force_https_url( $this->icon ) ) . '" alt="' . esc_attr( $this->get_title() ) . '" />' : '';
		/**
		 * Filter the gateway icon.
		 *
		 * @since 1.5.8
		 * @param string $icon Gateway icon.
		 * @param string $id Gateway ID.
		 * @return string
		 */
		return apply_filters( 'woocommerce_gateway_icon', $icon, $this->id );
	}

	/**
	 * Return the gateway's pay button ID.
	 *
	 * @since 3.9.0
	 * @return string
	 */
	public function get_pay_button_id() {
		return sanitize_html_class( $this->pay_button_id );
	}

	/**
	 * Set as current gateway.
	 *
	 * Set this as the current gateway.
	 */
	public function set_current() {
		$this->chosen = true;
	}

	/**
	 * Process Payment.
	 *
	 * Process the payment. Override this in your gateway. When implemented, this should.
	 * return the success and redirect in an array. e.g:
	 *
	 *        return array(
	 *            'result'   => 'success',
	 *            'redirect' => $this->get_return_url( $order )
	 *        );
	 *
	 * @param int $order_id Order ID.
	 * @return array
	 */
	public function process_payment( $order_id ) {
		return array();
	}

	/**
	 * Process refund.
	 *
	 * If the gateway declares 'refunds' support, this will allow it to refund.
	 * a passed in amount.
	 *
	 * @param  int        $order_id Order ID.
	 * @param  float|null $amount Refund amount.
	 * @param  string     $reason Refund reason.
	 * @return bool|\WP_Error True or false based on success, or a WP_Error object.
	 */
	public function process_refund( $order_id, $amount = null, $reason = '' ) {
		return false;
	}

	/**
	 * Validate frontend fields.
	 *
	 * Validate payment fields on the frontend.
	 *
	 * @return bool
	 */
	public function validate_fields() {
		return true;
	}

	/**
	 * Default payment fields display. Override this in your gateway to customize displayed fields.
	 *
	 * By default this renders the payment gateway description.
	 *
	 * @since 1.5.7
	 */
	public function payment_fields() {
		$description = $this->get_description();

		if ( $description ) {
			// KSES is ran within get_description, but not here since there may be custom HTML returned by extensions.
			echo wpautop( wptexturize( $description ) ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
		}

		if ( $this->supports( PaymentGatewayFeature::DEFAULT_CREDIT_CARD_FORM ) ) {
			$this->credit_card_form(); // Deprecated, will be removed in a future version.
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
		/**
		 * Filter the gateway supported features.
		 *
		 * @since 1.5.7
		 * @param boolean $supports If the gateway supports the feature.
		 * @param string $feature Feature to check.
		 * @param WC_Payment_Gateway $this Payment gateway instance.
		 * @return string
		 */
		return apply_filters( 'woocommerce_payment_gateway_supports', in_array( $feature, $this->supports, true ), $feature, $this );
	}

	/**
	 * Can the order be refunded via this gateway?
	 *
	 * Should be extended by gateways to do their own checks.
	 *
	 * @param  WC_Order $order Order object.
	 * @return bool If false, the automatic refund button is hidden in the UI.
	 */
	public function can_refund_order( $order ) {
		return $order && $this->supports( PaymentGatewayFeature::REFUNDS );
	}

	/**
	 * Core credit card form which gateways can use if needed. Deprecated - inherit WC_Payment_Gateway_CC instead.
	 *
	 * @param  array $args Arguments.
	 * @param  array $fields Fields.
	 */
	public function credit_card_form( $args = array(), $fields = array() ) {
		wc_deprecated_function( 'credit_card_form', '2.6', 'WC_Payment_Gateway_CC->form' );
		$cc_form           = new WC_Payment_Gateway_CC();
		$cc_form->id       = $this->id;
		$cc_form->supports = $this->supports;
		$cc_form->form();
	}

	/**
	 * Enqueues our tokenization script to handle some of the new form options.
	 *
	 * @since 2.6.0
	 */
	public function tokenization_script() {
		wp_enqueue_script(
			'woocommerce-tokenization-form',
			plugins_url( '/assets/js/frontend/tokenization-form' . ( Constants::is_true( 'SCRIPT_DEBUG' ) ? '' : '.min' ) . '.js', WC_PLUGIN_FILE ),
			array( 'jquery' ),
			WC()->version,
			false
		);

		wp_localize_script(
			'woocommerce-tokenization-form',
			'wc_tokenization_form_params',
			array(
				'is_registration_required' => WC()->checkout()->is_registration_required(),
				'is_logged_in'             => is_user_logged_in(),
			)
		);
	}

	/**
	 * Grab and display our saved payment methods.
	 *
	 * @since 2.6.0
	 */
	public function saved_payment_methods() {
		$html = '<ul class="woocommerce-SavedPaymentMethods wc-saved-payment-methods" data-count="' . esc_attr( count( $this->get_tokens() ) ) . '">';

		foreach ( $this->get_tokens() as $token ) {
			$html .= $this->get_saved_payment_method_option_html( $token );
		}

		$html .= $this->get_new_payment_method_option_html();
		$html .= '</ul>';

		echo apply_filters( 'wc_payment_gateway_form_saved_payment_methods_html', $html, $this ); // @codingStandardsIgnoreLine
	}

	/**
	 * Gets saved payment method HTML from a token.
	 *
	 * @since 2.6.0
	 * @param  WC_Payment_Token $token Payment Token.
	 * @return string Generated payment method HTML
	 */
	public function get_saved_payment_method_option_html( $token ) {
		$html = sprintf(
			'<li class="woocommerce-SavedPaymentMethods-token">
				<input id="wc-%1$s-payment-token-%2$s" type="radio" name="wc-%1$s-payment-token" value="%2$s" style="width:auto;" class="woocommerce-SavedPaymentMethods-tokenInput" %4$s />
				<label for="wc-%1$s-payment-token-%2$s">%3$s</label>
			</li>',
			esc_attr( $this->id ),
			esc_attr( $token->get_id() ),
			esc_html( $token->get_display_name() ),
			checked( $token->is_default(), true, false )
		);

		/**
		 * Filter the saved payment method HTML.
		 *
		 * @since 2.6.0
		 * @param string $html HTML for the saved payment methods.
		 * @param string $token Token.
		 * @param WC_Payment_Gateway $this Payment gateway instance.
		 * @return string
		 */
		return apply_filters( 'woocommerce_payment_gateway_get_saved_payment_method_option_html', $html, $token, $this );
	}

	/**
	 * Displays a radio button for entering a new payment method (new CC details) instead of using a saved method.
	 * Only displayed when a gateway supports tokenization.
	 *
	 * @since 2.6.0
	 */
	public function get_new_payment_method_option_html() {
		/**
		 * Filter the saved payment method label.
		 *
		 * @since 2.6.0
		 * @param string $label Label.
		 * @param WC_Payment_Gateway $this Payment gateway instance.
		 * @return string
		 */
		$label = apply_filters( 'woocommerce_payment_gateway_get_new_payment_method_option_html_label', $this->new_method_label ? $this->new_method_label : __( 'Use a new payment method', 'woocommerce' ), $this );
		$html  = sprintf(
			'<li class="woocommerce-SavedPaymentMethods-new">
				<input id="wc-%1$s-payment-token-new" type="radio" name="wc-%1$s-payment-token" value="new" style="width:auto;" class="woocommerce-SavedPaymentMethods-tokenInput" />
				<label for="wc-%1$s-payment-token-new">%2$s</label>
			</li>',
			esc_attr( $this->id ),
			esc_html( $label )
		);
		/**
		 * Filter the saved payment method option.
		 *
		 * @since 2.6.0
		 * @param string $html Option HTML.
		 * @param WC_Payment_Gateway $this Payment gateway instance.
		 * @return string
		 */
		return apply_filters( 'woocommerce_payment_gateway_get_new_payment_method_option_html', $html, $this );
	}

	/**
	 * Outputs a checkbox for saving a new payment method to the database.
	 *
	 * @since 2.6.0
	 */
	public function save_payment_method_checkbox() {
		$html = sprintf(
			'<p class="form-row woocommerce-SavedPaymentMethods-saveNew">
				<input id="wc-%1$s-new-payment-method" name="wc-%1$s-new-payment-method" type="checkbox" value="true" style="width:auto;" />
				<label for="wc-%1$s-new-payment-method" style="display:inline;">%2$s</label>
			</p>',
			esc_attr( $this->id ),
			esc_html__( 'Save to account', 'woocommerce' )
		);
		/**
		 * Filter the saved payment method checkbox HTML
		 *
		 * @since 2.6.0
		 * @param string $html Checkbox HTML.
		 * @param WC_Payment_Gateway $this Payment gateway instance.
		 * @return string
		 */
		echo apply_filters( 'woocommerce_payment_gateway_save_new_payment_method_option_html', $html, $this ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
	}

	/**
	 * Add payment method via account screen. This should be extended by gateway plugins.
	 *
	 * @since 3.2.0 Included here from 3.2.0, but supported from 3.0.0.
	 * @return array
	 */
	public function add_payment_method() {
		return array(
			'result'   => 'failure',
			'redirect' => wc_get_endpoint_url( 'payment-methods' ),
		);
	}
}
