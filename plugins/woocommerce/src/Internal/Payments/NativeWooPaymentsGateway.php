<?php
/**
 * NativeWooPaymentsGateway class file.
 */

declare( strict_types = 1 );

namespace Automattic\WooCommerce\Internal\Payments;

use Automattic\WooCommerce\Enums\PaymentGatewayFeature;
use Automattic\WooCommerce\Internal\Payments\Providers\WooPayments\Api\WooPaymentsApiClient;
use Automattic\WooCommerce\Internal\Payments\Providers\WooPayments\WooPaymentsCheckoutBridge;
use Automattic\WooCommerce\Internal\Payments\Providers\WooPayments\WooPaymentsProvider;
use Throwable;
use WC_Order;
use WC_Payment_Gateway_CC;
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
			PaymentGatewayFeature::TOKENIZATION,
		);

		$this->init_settings();

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
	 */
	final public function init( PaymentProcessingService $processing_service, WooPaymentsProvider $provider, ?WooPaymentsCheckoutBridge $checkout_bridge = null, ?WooPaymentsApiClient $api_client = null ): void {
		$this->processing_service = $processing_service;
		$this->provider           = $provider;

		if ( null !== $checkout_bridge ) {
			$this->checkout_bridge = $checkout_bridge;
		}

		if ( null !== $api_client ) {
			$this->api_client = $api_client;
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
	 * Return the shopper-facing card brand icons.
	 *
	 * @return string
	 */
	public function get_icon() {
		$icons                 = array();
		$brands                = array_slice( self::CARD_BRAND_ICONS, 0, 3, true );
		$additional_icon_count = count( self::CARD_BRAND_ICONS ) - count( $brands );

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

		return array(
			'cvc_confirmation'          => $this->sanitize_post_string( $cvc_key ),
			'fingerprint'               => $this->sanitize_post_string( 'wcpay-fingerprint' ),
			'payment_method_error'      => $this->sanitize_post_string( 'wcpay-payment-method-error-message' ),
			'payment_method_error_code' => $this->sanitize_post_string( 'wcpay-payment-method-error-code' ),
			'is_woopay'                 => ! empty( $_POST['is_woopay'] ), // phpcs:ignore WordPress.Security.NonceVerification.Missing
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
