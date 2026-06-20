<?php
/**
 * NativeWooPaymentsGateway class file.
 */

declare( strict_types = 1 );

namespace Automattic\WooCommerce\Internal\Payments;

use Automattic\WooCommerce\Enums\PaymentGatewayFeature;
use Automattic\WooCommerce\Internal\Payments\Providers\WooPayments\Api\WooPaymentsApiClient;
use Automattic\WooCommerce\Internal\Payments\Providers\WooPayments\WooPaymentsAccountService;
use Automattic\WooCommerce\Internal\Payments\Providers\WooPayments\WooPaymentsCheckoutBridge;
use Automattic\WooCommerce\Internal\Payments\Providers\WooPayments\WooPaymentsExpressPaymentMethodTypes;
use Automattic\WooCommerce\Internal\Payments\Providers\WooPayments\WooPaymentsPlatformPaymentMethodContext;
use Automattic\WooCommerce\Internal\Payments\Providers\WooPayments\WooPaymentsProvider;
use Automattic\WooCommerce\Internal\Payments\Providers\WooPayments\Subscriptions\WooPaymentsFailedAuthenticationRetryEmail;
use Automattic\WooCommerce\Internal\Payments\Providers\WooPayments\Subscriptions\WooPaymentsFailedRenewalAuthenticationEmail;
use Automattic\WooCommerce\Internal\Payments\Providers\WooPayments\WooPaymentsTokenService;
use Throwable;
use WC_Order;
use WC_Payment_Token;
use WC_Payment_Gateway_CC;
use WC_Payment_Tokens;
use WP_Error;

/**
 * Native WooPayments payment gateway shell.
 *
 * @since 11.0.0
 * @internal Transitional internal component for the native payments runtime.
 */
class NativeWooPaymentsGateway extends WC_Payment_Gateway_CC {

	/**
	 * Untranslated gateway title.
	 */
	private const METHOD_TITLE = 'WooPayments';

	/**
	 * Untranslated shopper-facing card title.
	 */
	private const CHECKOUT_TITLE = 'Card';

	/**
	 * Untranslated gateway description.
	 */
	private const METHOD_DESCRIPTION = 'Accept payments with WooPayments.';

	/**
	 * Shopper-facing card brand icons.
	 *
	 * @var array<string,string>
	 */
	private const CARD_BRAND_ICONS = array(
		'visa'       => 'Visa',
		'mastercard' => 'Mastercard',
		'amex'       => 'American Express',
		'discover'   => 'Discover',
		'jcb'        => 'JCB',
		'unionpay'   => 'Union Pay',
	);

	/**
	 * France-only shopper-facing card brand icons.
	 *
	 * @var array<string,string>
	 */
	private const FR_CARD_BRAND_ICONS = array(
		'cartes_bancaires' => 'Cartes Bancaires',
	);

	/**
	 * Recommended payment methods cache key.
	 */
	private const RECOMMENDED_PAYMENT_METHODS_CACHE_KEY = 'woocommerce_woocommerce_payments_recommended_payment_methods';

	/**
	 * Recommended payment methods cache TTL.
	 */
	private const RECOMMENDED_PAYMENT_METHODS_CACHE_TTL = DAY_IN_SECONDS;

	/**
	 * Payment processing service.
	 *
	 * @var PaymentProcessingService
	 */
	private PaymentProcessingService $processing_service;

	/**
	 * WooPayments provider.
	 *
	 * @var WooPaymentsProvider
	 */
	private WooPaymentsProvider $provider;

	/**
	 * WooPayments checkout bridge.
	 *
	 * @var WooPaymentsCheckoutBridge
	 */
	private WooPaymentsCheckoutBridge $checkout_bridge;

	/**
	 * Native WooPayments API client.
	 *
	 * @var WooPaymentsApiClient
	 */
	private WooPaymentsApiClient $api_client;

	/**
	 * Native WooPayments account service.
	 *
	 * @var WooPaymentsAccountService
	 */
	private WooPaymentsAccountService $account_service;

	/**
	 * WooPayments token service.
	 *
	 * @var WooPaymentsTokenService
	 */
	private WooPaymentsTokenService $token_service;

	/**
	 * Constructor.
	 */
	public function __construct() {
		$this->id                 = OrderPaymentStore::GATEWAY_ID;
		$this->title              = self::CHECKOUT_TITLE;
		$this->method_title       = self::METHOD_TITLE;
		$this->method_description = self::METHOD_DESCRIPTION;
		$this->has_fields         = true;
		$this->supports           = array(
			'products',
			'refunds',
		);

		$this->init_settings();
		$this->init_supported_features();

		if ( did_action( 'init' ) ) {
			$this->handle_init();
		} else {
			add_action( 'init', array( $this, 'handle_init' ) );
		}
	}

	/**
	 * Handle the init hook.
	 *
	 * @internal
	 */
	public function handle_init(): void {
		$this->title              = __( 'Card', 'woocommerce' );
		$this->method_title       = __( 'WooPayments', 'woocommerce' );
		$this->method_description = __( 'Accept payments with WooPayments.', 'woocommerce' );
	}

	/**
	 * Initialize the class instance.
	 *
	 * @internal
	 *
	 * @param PaymentProcessingService       $processing_service Payment processing service.
	 * @param WooPaymentsProvider            $provider           WooPayments provider.
	 * @param WooPaymentsCheckoutBridge|null $checkout_bridge    Optional checkout bridge.
	 * @param WooPaymentsApiClient|null      $api_client         Optional API client.
	 * @param WooPaymentsAccountService|null $account_service    Optional account service.
	 * @param WooPaymentsTokenService|null   $token_service      Optional token service.
	 */
	final public function init( PaymentProcessingService $processing_service, WooPaymentsProvider $provider, ?WooPaymentsCheckoutBridge $checkout_bridge = null, ?WooPaymentsApiClient $api_client = null, ?WooPaymentsAccountService $account_service = null, ?WooPaymentsTokenService $token_service = null ): void {
		$this->processing_service = $processing_service;
		$this->provider           = $provider;

		if ( null !== $checkout_bridge ) {
			$this->checkout_bridge = $checkout_bridge;
		}

		if ( null !== $api_client ) {
			$this->api_client = $api_client;
		}

		if ( null !== $account_service ) {
			$this->account_service = $account_service;
		}

		if ( null !== $token_service ) {
			$this->token_service = $token_service;
		}
	}

	/**
	 * Render the native WooPayments payment form.
	 *
	 * @return void
	 */
	public function form() {
		$this->get_checkout_bridge()->render_payment_fields();
	}

	/**
	 * Add a WooPayments payment method from the My Account payment-method form.
	 *
	 * @return array<string,string>
	 */
	public function add_payment_method() {
		try {
			$setup_intent_id = $this->sanitize_post_string( 'wcpay-setup-intent' );

			if ( '' === $setup_intent_id ) {
				return $this->add_payment_method_error( __( 'A WooPayments payment method was not provided.', 'woocommerce' ) );
			}

			$user_id = get_current_user_id();
			if ( 0 >= $user_id ) {
				return $this->add_payment_method_error( __( "We're not able to add this payment method. Please log in and try again.", 'woocommerce' ) );
			}

			$setup_intent = $this->get_api_client()->get_setup_intention( $setup_intent_id );
			$status       = isset( $setup_intent['status'] ) ? (string) $setup_intent['status'] : '';
			if ( 'succeeded' !== $status ) {
				return $this->add_payment_method_error( __( 'Failed to add the provided payment method. Please try again later.', 'woocommerce' ) );
			}

			$payment_method_id = $this->get_setup_intent_payment_method_id( $setup_intent );
			if ( '' === $payment_method_id ) {
				return $this->add_payment_method_error( __( "We're not able to add this payment method. Please try again later.", 'woocommerce' ) );
			}

			$token = $this->get_token_service()->get_or_create_card_token_for_user( $payment_method_id, $user_id );
			if ( ! $token instanceof WC_Payment_Token ) {
				return $this->add_payment_method_error( __( "We're not able to add this payment method. Please try again later.", 'woocommerce' ) );
			}

			return array(
				'result'   => 'success',
				/**
				 * Filters the redirect URL after adding a WooPayments payment method.
				 *
				 * @since 11.0.0
				 * @param string $url Redirect URL.
				 */
				'redirect' => apply_filters( 'wcpay_get_add_payment_method_redirect_url', wc_get_endpoint_url( 'payment-methods' ) ),
			);
		} catch ( Throwable $exception ) {
			wc_get_logger()->error(
				'Error when adding native WooPayments payment method: ' . $exception->getMessage(),
				array( 'source' => 'wcpay-add-payment-method' )
			);

			return $this->add_payment_method_error( __( "We're not able to add this payment method. Please try again later.", 'woocommerce' ) );
		}
	}

	/**
	 * Process a scheduled subscription renewal payment.
	 *
	 * @param float    $amount        Renewal amount.
	 * @param WC_Order $renewal_order Renewal order.
	 * @return void
	 */
	public function scheduled_subscription_payment( $amount, $renewal_order ): void {
		unset( $amount );

		if ( ! $renewal_order instanceof WC_Order ) {
			return;
		}

		$token = $this->get_payment_token_from_order( $renewal_order );
		if ( ! $token instanceof WC_Payment_Token ) {
			$renewal_order->add_order_note( __( 'Subscription renewal failed: No saved payment method found.', 'woocommerce' ) );
			$renewal_order->update_status( 'failed' );
			return;
		}

		$provider_data = array( 'scheduled_subscription_payment' => true );
		$mandate       = $this->get_renewal_order_mandate( $renewal_order );
		if ( '' !== $mandate ) {
			$provider_data['renewal_mandate'] = $mandate;
		}

		$customer_id = $this->get_renewal_order_customer_id( $renewal_order );
		if ( '' !== $customer_id ) {
			$renewal_order->update_meta_data( '_stripe_customer_id', $customer_id );
			$renewal_order->save_meta_data();
		}

		$outcome = $this->get_processing_service()->process_checkout_outcome(
			PaymentContext::for_checkout(
				$renewal_order,
				$this->id,
				'',
				array(
					'payment_token'       => (string) $token->get_id(),
					'save_payment_method' => false,
				),
				$provider_data
			),
			$this->get_provider()
		);

		$this->maybe_handle_subscription_customer_action_required( $renewal_order, $outcome );
	}

	/**
	 * Handle a scheduled renewal that requires customer authentication.
	 *
	 * @param WC_Order       $renewal_order Renewal order.
	 * @param PaymentOutcome $outcome       Provider payment outcome.
	 * @return void
	 */
	private function maybe_handle_subscription_customer_action_required( WC_Order $renewal_order, PaymentOutcome $outcome ): void {
		if ( PaymentOutcome::STATUS_REQUIRES_CUSTOMER_ACTION !== $outcome->get_status() ) {
			return;
		}

		$data      = $outcome->get_data();
		$meta      = isset( $data['meta'] ) && is_array( $data['meta'] ) ? $data['meta'] : array();
		$charge_id = isset( $data['charge_id'] ) ? (string) $data['charge_id'] : ( isset( $meta['_charge_id'] ) ? (string) $meta['_charge_id'] : '' );

		try {
			/**
			 * Fires when a native WooPayments payment requires customer authentication.
			 *
			 * @param WC_Order $renewal_order     The renewal order that requires authentication.
			 * @param string   $intent_id         The provider payment intent ID.
			 * @param string   $payment_method_id The provider payment method ID.
			 * @param string   $customer_id       The provider customer ID.
			 * @param string   $charge_id         The provider charge ID.
			 * @param string   $currency          The order currency.
			 *
			 * @since 11.0.0
			 */
			do_action(
				'woocommerce_woocommerce_payments_payment_requires_action',
				$renewal_order,
				$outcome->get_provider_payment_id(),
				$outcome->get_payment_method_id(),
				$outcome->get_customer_id(),
				$charge_id,
				$renewal_order->get_currency()
			);
		} catch ( Throwable $exception ) {
			wc_get_logger()->error(
				'Failed to run WooPayments subscription renewal authentication hooks: ' . $exception->getMessage(),
				array( 'source' => 'woopayments-subscriptions' )
			);
		}

		if ( ! $renewal_order->has_status( 'failed' ) ) {
			$renewal_order->update_status( 'failed' );
		}

		$failure_note = $this->get_subscription_customer_action_failure_note( $renewal_order, $outcome, $charge_id );
		if ( '' !== $failure_note && ! $this->order_has_note_containing( $renewal_order, $failure_note ) ) {
			$renewal_order->add_order_note( $failure_note );
		}
	}

	/**
	 * Get the failed-renewal note for customer-action-required outcomes.
	 *
	 * @param WC_Order       $renewal_order Renewal order.
	 * @param PaymentOutcome $outcome       Provider payment outcome.
	 * @param string         $charge_id     Provider charge ID.
	 * @return string Order note.
	 */
	private function get_subscription_customer_action_failure_note( WC_Order $renewal_order, PaymentOutcome $outcome, string $charge_id ): string {
		$transaction_id = '' !== $charge_id ? $charge_id : $outcome->get_provider_payment_id();
		if ( '' === $transaction_id ) {
			return '';
		}

		return wp_kses_post(
			sprintf(
				/* translators: %1$s: the failed payment amount, %2$s: WooPayments, %3$s: transaction ID. */
				__( 'A payment of %1$s <strong>failed</strong> using %2$s (<code>%3$s</code>).', 'woocommerce' ),
				wc_price( $renewal_order->get_total(), array( 'currency' => $renewal_order->get_currency() ) ),
				'WooPayments',
				esc_html( $transaction_id )
			)
		);
	}

	/**
	 * Tell whether an order already has a note containing the expected text.
	 *
	 * @param WC_Order $order         Order object.
	 * @param string   $expected_note Expected note text.
	 * @return bool
	 */
	private function order_has_note_containing( WC_Order $order, string $expected_note ): bool {
		$notes = wc_get_order_notes(
			array(
				'order_id' => $order->get_id(),
				'type'     => 'any',
			)
		);

		foreach ( $notes as $note ) {
			if ( str_contains( (string) $note->content, $expected_note ) ) {
				return true;
			}
		}

		return false;
	}

	/**
	 * Get the mandate ID from the subscription parent order for a renewal order.
	 *
	 * @param WC_Order $renewal_order Renewal order.
	 * @return string Mandate ID, or an empty string when not available.
	 */
	private function get_renewal_order_mandate( WC_Order $renewal_order ): string {
		$parent_order = $this->get_subscription_parent_order_for_renewal( $renewal_order );
		if ( ! $parent_order instanceof WC_Order ) {
			return '';
		}

		return (string) $parent_order->get_meta( '_stripe_mandate_id', true );
	}

	/**
	 * Get the WooPayments customer ID from the renewal order, current subscription, or subscription parent order.
	 *
	 * @param WC_Order $renewal_order Renewal order.
	 * @return string Customer ID, or an empty string when not available.
	 */
	private function get_renewal_order_customer_id( WC_Order $renewal_order ): string {
		$customer_id = (string) $renewal_order->get_meta( '_stripe_customer_id', true );
		if ( '' !== $customer_id ) {
			return $customer_id;
		}

		$subscription = $this->get_subscription_for_renewal_order( $renewal_order );
		if ( $subscription instanceof WC_Order ) {
			$customer_id = (string) $subscription->get_meta( '_stripe_customer_id', true );
			if ( '' !== $customer_id ) {
				return $customer_id;
			}
		}

		$parent_order = $this->get_subscription_parent_order_for_renewal( $renewal_order );
		if ( ! $parent_order instanceof WC_Order ) {
			return '';
		}

		return (string) $parent_order->get_meta( '_stripe_customer_id', true );
	}

	/**
	 * Get the subscription parent order associated with a renewal order.
	 *
	 * @param WC_Order $renewal_order Renewal order.
	 * @return WC_Order|null Parent order, or null when not available.
	 */
	private function get_subscription_parent_order_for_renewal( WC_Order $renewal_order ): ?WC_Order {
		$subscription = $this->get_subscription_for_renewal_order( $renewal_order );
		if ( ! $subscription instanceof WC_Order ) {
			return null;
		}

		$parent_order = wc_get_order( (int) $subscription->get_parent_id() );
		if ( ! $parent_order instanceof WC_Order ) {
			return null;
		}

		return $parent_order;
	}

	/**
	 * Get the subscription associated with a renewal order.
	 *
	 * @param WC_Order $renewal_order Renewal order.
	 * @return WC_Order|null Subscription order, or null when not available.
	 */
	private function get_subscription_for_renewal_order( WC_Order $renewal_order ): ?WC_Order {
		$subscriptions = array();
		if ( function_exists( 'wcs_get_subscriptions_for_renewal_order' ) ) {
			$subscriptions = wcs_get_subscriptions_for_renewal_order( $renewal_order->get_id() );
		}

		$subscriptions = is_array( $subscriptions ) ? $subscriptions : array();

		/**
		 * Filters native WooPayments subscriptions related to a renewal order.
		 *
		 * @since 11.0.0
		 *
		 * @param array<int,mixed> $subscriptions Related subscriptions.
		 * @param WC_Order         $renewal_order Renewal order.
		 */
		$subscriptions = apply_filters( 'woocommerce_native_woopayments_subscriptions_for_renewal_order', $subscriptions, $renewal_order );
		$subscriptions = is_array( $subscriptions ) ? $subscriptions : array();
		$subscription  = reset( $subscriptions );

		return $subscription instanceof WC_Order ? $subscription : null;
	}

	/**
	 * Copy the successful renewal token to the failing subscription.
	 *
	 * @param WC_Order $subscription  Subscription order.
	 * @param WC_Order $renewal_order Renewal order.
	 * @return void
	 */
	public function update_failing_payment_method( $subscription, $renewal_order ): void {
		if ( ! $subscription instanceof WC_Order || ! $renewal_order instanceof WC_Order ) {
			return;
		}

		$token = $this->get_payment_token_from_order( $renewal_order );
		if ( ! $token instanceof WC_Payment_Token ) {
			$renewal_order->add_order_note( __( 'Unable to update subscription payment method: No valid payment token or method found.', 'woocommerce' ) );
			return;
		}

		$subscription_token_ids = array_map( 'absint', $subscription->get_payment_tokens() );
		if ( ! in_array( $token->get_id(), $subscription_token_ids, true ) ) {
			$subscription->add_payment_token( $token );
			$subscription->save();
		}
	}

	/**
	 * Tell whether saved payment methods are enabled.
	 *
	 * @return bool
	 */
	public function is_saved_cards_enabled(): bool {
		return 'yes' === $this->get_option( 'saved_cards' );
	}

	/**
	 * Tell whether subscriptions support is available.
	 *
	 * @return bool
	 */
	public function is_subscriptions_enabled(): bool {
		if ( $this->is_subscriptions_plugin_active() ) {
			return version_compare( (string) $this->get_subscriptions_plugin_version(), '2.2.0', '>=' );
		}

		return class_exists( 'WC_Subscriptions_Core_Plugin' );
	}

	/**
	 * Tell whether WooCommerce Subscriptions is active.
	 *
	 * @return bool
	 */
	public function is_subscriptions_plugin_active(): bool {
		return class_exists( 'WC_Subscriptions' );
	}

	/**
	 * Get the active WooCommerce Subscriptions version.
	 *
	 * @return string|null
	 */
	public function get_subscriptions_plugin_version(): ?string {
		if ( ! class_exists( 'WC_Subscriptions' ) || ! isset( \WC_Subscriptions::$version ) ) {
			return null;
		}

		return (string) \WC_Subscriptions::$version;
	}

	/**
	 * Tell whether WooPayments is in test mode.
	 *
	 * @return bool
	 */
	public function is_test_mode(): bool {
		return $this->get_account_service()->is_test_mode_enabled();
	}

	/**
	 * Tell whether WooPayments is in development mode.
	 *
	 * @return bool
	 */
	public function is_dev_mode(): bool {
		return $this->get_account_service()->is_dev_mode_enabled();
	}

	/**
	 * Return the shopper-facing card brand icons.
	 *
	 * @return string
	 */
	public function get_icon() {
		$icons                 = array();
		$brand_labels          = $this->get_card_brand_icon_labels();
		$brands                = array_slice( $brand_labels, 0, 3, true );
		$additional_icon_count = count( $brand_labels ) - count( $brands );

		if ( $this->is_test_mode() ) {
			$badge_style = implode(
				'',
				array(
					'background-color:#fff2d7;',
					'border-radius:4px;',
					'color:#4d3716;',
					'display:inline-block;',
					'font-size:12px;',
					'font-weight:400;',
					'line-height:16px;',
					'margin-left:8px;',
					'padding:4px 6px;',
				)
			);
			$icons[]     = sprintf(
				'<span class="test-mode badge" style="%1$s">%2$s</span>',
				esc_attr( $badge_style ),
				esc_html__( 'Test Mode', 'woocommerce' )
			);
		}

		foreach ( $brands as $brand => $label ) {
			$icons[] = sprintf(
				'<img src="%1$s" alt="%2$s" width="38" height="24" />',
				esc_url( \WC_HTTPS::force_https_url( WC()->plugin_url() . '/assets/images/payment-methods/' . $brand . '.svg' ) ),
				esc_attr( $label )
			);
		}

		if ( $additional_icon_count > 0 ) {
			$icons[] = sprintf(
				'<span class="payment-methods--logos-count">+ %d</span>',
				$additional_icon_count
			);
		}

		$icon = '<span class="wcpay-core-card-brand-icons payment-methods--logos">' . implode( '', $icons ) . '</span>';

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
	 * Get shopper-facing card brand labels for the connected account country.
	 *
	 * @return array<string,string>
	 */
	private function get_card_brand_icon_labels(): array {
		if ( 'FR' === $this->get_account_country() ) {
			return array_merge( self::CARD_BRAND_ICONS, self::FR_CARD_BRAND_ICONS );
		}

		return self::CARD_BRAND_ICONS;
	}

	/**
	 * Get the connected account country, falling back to the store base country.
	 *
	 * @return string
	 */
	private function get_account_country(): string {
		$account_data = $this->get_account_service()->get_cached_account_data();
		$country      = isset( $account_data['country'] ) && is_scalar( $account_data['country'] )
			? strtoupper( (string) $account_data['country'] )
			: '';

		if ( '' === $country && function_exists( 'WC' ) && WC() && WC()->countries ) {
			$country = strtoupper( (string) WC()->countries->get_base_country() );
		}

		if ( false !== strpos( $country, ':' ) ) {
			$base_country = strtok( $country, ':' );
			$country      = is_string( $base_country ) ? $base_country : '';
		}

		return '' !== $country ? $country : 'US';
	}

	/**
	 * Process payment for an order.
	 *
	 * @param int $order_id Order ID.
	 * @return array<string,string>
	 */
	public function process_payment( $order_id ) {
		$order = wc_get_order( $order_id );
		if ( ! $order instanceof WC_Order ) {
			return array(
				'result'         => 'fail',
				'redirect'       => '',
				'payment_method' => '',
			);
		}

		return $this->get_processing_service()->process_checkout(
			PaymentContext::for_checkout(
				$order,
				$this->id,
				$this->get_request_payment_method_id(),
				$this->get_checkout_payment_data(),
				$this->get_checkout_provider_data()
			),
			$this->get_provider()
		);
	}

	/**
	 * Process refund for an order.
	 *
	 * @param int        $order_id Order ID.
	 * @param float|null $amount   Refund amount.
	 * @param string     $reason   Refund reason.
	 * @return bool|\WP_Error
	 */
	public function process_refund( $order_id, $amount = null, $reason = '' ) {
		$order = wc_get_order( $order_id );
		if ( ! $order instanceof WC_Order ) {
			return false;
		}

		$refund_amount = null === $amount ? 0.0 : (float) $amount;
		if ( '0.00' !== sprintf( '%0.2f', $refund_amount ) && ! $this->can_refund_order( $order ) ) {
			return new WP_Error( 'native_payment_refund_missing_charge', __( 'This order does not have a WooPayments charge to refund.', 'woocommerce' ) );
		}

		return $this->get_processing_service()->process_refund(
			PaymentContext::for_refund( $order, $this->id, $refund_amount, (string) $reason ),
			$this->get_provider()
		);
	}

	/**
	 * Tell whether an order can be refunded through WooPayments.
	 *
	 * @param WC_Order|mixed $order Order object.
	 * @return bool
	 */
	public function can_refund_order( $order ) {
		return $order instanceof WC_Order
			&& $this->supports( PaymentGatewayFeature::REFUNDS )
			&& '' !== (string) $order->get_meta( '_charge_id', true );
	}

	/**
	 * Get the recommended payment methods list for onboarding.
	 *
	 * @param string $country_code Optional. Business location country code.
	 * @return array<int,array<string,mixed>>
	 */
	public function get_recommended_payment_methods( string $country_code = '' ): array {
		$country_code = strtoupper( trim( $country_code ) );
		if ( '' === $country_code ) {
			return array();
		}

		$locale = get_user_locale();
		$cached = $this->get_cached_recommended_payment_methods( $country_code, $locale );
		if ( null !== $cached ) {
			return $cached;
		}

		try {
			$recommended_pms = $this->get_api_client()->get_recommended_payment_methods( $country_code, $locale );
		} catch ( Throwable $exception ) {
			return array();
		}

		$recommended_pms = $this->normalize_recommended_payment_methods( $recommended_pms );
		if ( ! empty( $recommended_pms ) ) {
			$this->set_cached_recommended_payment_methods( $country_code, $locale, $recommended_pms );
		}

		return $recommended_pms;
	}

	/**
	 * Normalize recommended payment methods for the settings provider pipeline.
	 *
	 * @param array $recommended_pms Raw recommended payment methods.
	 * @return array<int,array<string,mixed>>
	 */
	private function normalize_recommended_payment_methods( array $recommended_pms ): array {
		$recommended_pms = array_values(
			array_filter(
				$recommended_pms,
				static function ( $payment_method ): bool {
					return is_array( $payment_method ) && isset( $payment_method['id'], $payment_method['title'] );
				}
			)
		);

		return array_map(
			static function ( array $payment_method, int $index ): array {
				if ( ! isset( $payment_method['enabled'] ) ) {
					$payment_method['enabled'] = ! isset( $payment_method['type'] ) || 'available' !== $payment_method['type'];
				}

				$payment_method['priority'] = isset( $payment_method['priority'] ) ? (int) $payment_method['priority'] : $index;

				return $payment_method;
			},
			$recommended_pms,
			array_keys( $recommended_pms )
		);
	}

	/**
	 * Get cached recommended payment methods for a country and locale.
	 *
	 * @param string $country_code Business location country code.
	 * @param string $locale       User locale.
	 * @return array<int,array<string,mixed>>|null
	 */
	private function get_cached_recommended_payment_methods( string $country_code, string $locale ): ?array {
		$cached = get_transient( self::RECOMMENDED_PAYMENT_METHODS_CACHE_KEY );
		if ( ! is_array( $cached ) ||
			( $cached['country_code'] ?? '' ) !== $country_code ||
			( $cached['__locale'] ?? '' ) !== $locale ||
			! isset( $cached['payment_methods'] ) ||
			! is_array( $cached['payment_methods'] ) ) {

			return null;
		}

		return $cached['payment_methods'];
	}

	/**
	 * Cache recommended payment methods for a country and locale.
	 *
	 * @param string $country_code    Business location country code.
	 * @param string $locale          User locale.
	 * @param array  $payment_methods Recommended payment methods.
	 * @return void
	 */
	private function set_cached_recommended_payment_methods( string $country_code, string $locale, array $payment_methods ): void {
		set_transient(
			self::RECOMMENDED_PAYMENT_METHODS_CACHE_KEY,
			array(
				'payment_methods' => $payment_methods,
				'__locale'        => $locale,
				'country_code'    => $country_code,
			),
			self::RECOMMENDED_PAYMENT_METHODS_CACHE_TTL
		);
	}

	/**
	 * Get the payment processing service.
	 *
	 * @return PaymentProcessingService
	 */
	private function get_processing_service(): PaymentProcessingService {
		if ( ! isset( $this->processing_service ) ) {
			$this->processing_service = wc_get_container()->get( PaymentProcessingService::class );
		}

		return $this->processing_service;
	}

	/**
	 * Get the WooPayments provider.
	 *
	 * @return WooPaymentsProvider
	 */
	private function get_provider(): WooPaymentsProvider {
		if ( ! isset( $this->provider ) ) {
			$this->provider = wc_get_container()->get( WooPaymentsProvider::class );
		}

		return $this->provider;
	}

	/**
	 * Get the WooPayments checkout bridge.
	 *
	 * @return WooPaymentsCheckoutBridge
	 */
	private function get_checkout_bridge(): WooPaymentsCheckoutBridge {
		if ( ! isset( $this->checkout_bridge ) ) {
			$this->checkout_bridge = wc_get_container()->get( WooPaymentsCheckoutBridge::class );
		}

		return $this->checkout_bridge;
	}

	/**
	 * Get the native WooPayments API client.
	 *
	 * @return WooPaymentsApiClient
	 */
	private function get_api_client(): WooPaymentsApiClient {
		if ( ! isset( $this->api_client ) ) {
			$this->api_client = wc_get_container()->get( WooPaymentsApiClient::class );
		}

		return $this->api_client;
	}

	/**
	 * Get the native WooPayments account service.
	 *
	 * @return WooPaymentsAccountService
	 */
	private function get_account_service(): WooPaymentsAccountService {
		if ( ! isset( $this->account_service ) ) {
			$this->account_service = wc_get_container()->get( WooPaymentsAccountService::class );
		}

		return $this->account_service;
	}

	/**
	 * Get the native WooPayments token service.
	 *
	 * @return WooPaymentsTokenService
	 */
	private function get_token_service(): WooPaymentsTokenService {
		if ( ! isset( $this->token_service ) ) {
			$this->token_service = wc_get_container()->get( WooPaymentsTokenService::class );
		}

		return $this->token_service;
	}

	/**
	 * Initialize the WooPayments support list.
	 *
	 * @return void
	 */
	private function init_supported_features(): void {
		if ( $this->is_subscriptions_enabled() ) {
			$this->supports = array_merge(
				$this->supports,
				array(
					'multiple_subscriptions',
					'subscription_cancellation',
					'subscription_payment_method_change_admin',
					'subscription_payment_method_change_customer',
					'subscription_payment_method_change',
					'subscription_reactivation',
					'subscription_suspension',
					'subscriptions',
				)
			);

			$this->supports = array_merge(
				$this->supports,
				array( 'subscription_amount_changes', 'subscription_date_changes' )
			);
		}

		if ( $this->is_saved_cards_enabled() ) {
			$this->supports[] = PaymentGatewayFeature::TOKENIZATION;
			$this->supports[] = PaymentGatewayFeature::ADD_PAYMENT_METHOD;
		}

		$this->supports = array_values( array_unique( $this->supports ) );
		$this->register_subscription_handlers();
	}

	/**
	 * Register gateway-specific subscription handlers.
	 *
	 * @return void
	 */
	private function register_subscription_handlers(): void {
		if ( ! $this->is_subscriptions_enabled() ) {
			return;
		}

		$scheduled_hook = 'woocommerce_scheduled_subscription_payment_' . $this->id;
		$failing_hook   = 'woocommerce_subscription_failing_payment_method_updated_' . $this->id;

		if ( false === has_filter( 'woocommerce_email_classes', array( self::class, 'add_subscription_emails' ) ) ) {
			add_filter( 'woocommerce_email_classes', array( self::class, 'add_subscription_emails' ), 20 );
		}

		if ( false === has_action( $scheduled_hook, array( $this, 'scheduled_subscription_payment' ) ) ) {
			add_action( $scheduled_hook, array( $this, 'scheduled_subscription_payment' ), 10, 2 );
		}

		if ( false === has_action( $failing_hook, array( $this, 'update_failing_payment_method' ) ) ) {
			add_action( $failing_hook, array( $this, 'update_failing_payment_method' ), 10, 2 );
		}
	}

	/**
	 * Add WooPayments subscription emails to WooCommerce.
	 *
	 * @internal
	 *
	 * @param array<string,mixed> $email_classes WooCommerce email classes.
	 * @return array<string,mixed>
	 */
	public static function add_subscription_emails( array $email_classes ): array {
		if ( ! class_exists( 'WC_Email_Failed_Order' ) ) {
			require_once WC_ABSPATH . 'includes/emails/class-wc-email-failed-order.php';
		}

		$failed_renewal_authentication = new WooPaymentsFailedRenewalAuthenticationEmail( $email_classes );
		$failed_renewal_authentication->init_hooks();
		$email_classes['WC_Payments_Email_Failed_Renewal_Authentication'] = $failed_renewal_authentication;

		$failed_authentication_retry = new WooPaymentsFailedAuthenticationRetryEmail();
		$failed_authentication_retry->init_hooks();
		$email_classes['WC_Payments_Email_Failed_Authentication_Retry'] = $failed_authentication_retry;

		return $email_classes;
	}

	/**
	 * Get the saved payment token from an order.
	 *
	 * @param WC_Order $order Order object.
	 * @return WC_Payment_Token|null
	 */
	private function get_payment_token_from_order( WC_Order $order ): ?WC_Payment_Token {
		$token_ids = array_map( 'absint', $order->get_payment_tokens() );
		foreach ( $token_ids as $token_id ) {
			$token = WC_Payment_Tokens::get( $token_id );
			if ( $token instanceof WC_Payment_Token && $this->id === $token->get_gateway_id() ) {
				return $token;
			}
		}

		return null;
	}

	/**
	 * Build an add-payment-method error response and customer notice.
	 *
	 * @param string $message Error message.
	 * @return array<string,string>
	 */
	private function add_payment_method_error( string $message ): array {
		wc_add_notice( $message, 'error', array( 'icon' => 'error' ) );

		return array( 'result' => 'error' );
	}

	/**
	 * Get the payment method ID from a SetupIntent response.
	 *
	 * @param array<string,mixed> $setup_intent SetupIntent response.
	 * @return string
	 */
	private function get_setup_intent_payment_method_id( array $setup_intent ): string {
		if ( isset( $setup_intent['payment_method'] ) && is_string( $setup_intent['payment_method'] ) ) {
			return $setup_intent['payment_method'];
		}

		if ( isset( $setup_intent['payment_method'] ) && is_array( $setup_intent['payment_method'] ) && isset( $setup_intent['payment_method']['id'] ) ) {
			return (string) $setup_intent['payment_method']['id'];
		}

		return '';
	}

	/**
	 * Read the submitted provider payment method ID.
	 *
	 * @return string
	 */
	private function get_request_payment_method_id(): string {
		foreach ( array( 'wcpay-confirmation-token', 'wcpay-payment-method', 'wcpay-payment-method-sepa' ) as $key ) {
			if ( empty( $_POST[ $key ] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Missing
				continue;
			}

			return $this->sanitize_post_string( $key );
		}

		return '';
	}

	/**
	 * Get generic checkout payment data.
	 *
	 * @return array<string,mixed>
	 */
	private function get_checkout_payment_data(): array {
		$token_key = 'wc-' . $this->id . '-payment-token';

		return array(
			'payment_token'       => $this->sanitize_post_string( $token_key ),
			'save_payment_method' => ! empty( $_POST[ 'wc-' . $this->id . '-new-payment-method' ] ), // phpcs:ignore WordPress.Security.NonceVerification.Missing
		);
	}

	/**
	 * Get WooPayments-scoped checkout provider data.
	 *
	 * @return array<string,mixed>
	 */
	private function get_checkout_provider_data(): array {
		$cvc_key = 'wc-' . $this->id . '-payment-cvc-confirmation';

		return array_merge(
			WooPaymentsPlatformPaymentMethodContext::provider_data_from_checkout_value( $this->sanitize_post_string( WooPaymentsPlatformPaymentMethodContext::CHECKOUT_FIELD ) ),
			WooPaymentsExpressPaymentMethodTypes::provider_data_from_checkout_value( $this->sanitize_post_string( WooPaymentsExpressPaymentMethodTypes::CHECKOUT_FIELD ) ),
			WooPaymentsExpressPaymentMethodTypes::provider_context_from_checkout_value( $this->sanitize_post_string( WooPaymentsExpressPaymentMethodTypes::CONTEXT_FIELD ) ),
			array(
				'cvc_confirmation'          => $this->sanitize_post_string( $cvc_key ),
				'fingerprint'               => $this->sanitize_post_string( 'wcpay-fingerprint' ),
				'payment_method_error'      => $this->sanitize_post_string( 'wcpay-payment-method-error-message' ),
				'payment_method_error_code' => $this->sanitize_post_string( 'wcpay-payment-method-error-code' ),
				'is_woopay'                 => ! empty( $_POST['is_woopay'] ), // phpcs:ignore WordPress.Security.NonceVerification.Missing
			)
		);
	}

	/**
	 * Safely read a string from the POST payload.
	 *
	 * @param string $key POST key.
	 * @return string
	 */
	private function sanitize_post_string( string $key ): string {
		if ( ! isset( $_POST[ $key ] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Missing
			return '';
		}

		$value = wc_clean( wp_unslash( $_POST[ $key ] ) ); // phpcs:ignore WordPress.Security.NonceVerification.Missing

		return is_string( $value ) ? $value : '';
	}
}
