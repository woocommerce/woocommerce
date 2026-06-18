<?php
/**
 * WooPaymentsEventIngestor class file.
 */

declare( strict_types = 1 );

namespace Automattic\WooCommerce\Internal\Payments\Providers\WooPayments;

use Automattic\WooCommerce\Internal\Payments\OrderPaymentLifecycleService;
use Automattic\WooCommerce\Internal\Payments\OrderPaymentStore;
use Automattic\WooCommerce\Internal\Payments\PaymentLifecycleEvent;
use Automattic\WooCommerce\Internal\Payments\Providers\WooPayments\Api\WooPaymentsApiClient;
use Automattic\WooCommerce\Proxies\LegacyProxy;
use InvalidArgumentException;
use RuntimeException;
use Throwable;
use WC_Order;

/**
 * Ingests WooPayments provider webhook events into native payment lifecycle effects.
 *
 * @since 11.0.0
 * @internal Transitional internal component for the native payments runtime.
 */
class WooPaymentsEventIngestor {

	/**
	 * Filter that reports whether the native WooPayments runtime is in live mode.
	 *
	 * @var string
	 */
	const FILTER_LIVE_MODE = 'woocommerce_native_payments_woopayments_live_mode';

	/**
	 * Known WooPayments event types whose side effects are not migrated in A2.
	 *
	 * These must fail closed instead of returning success, otherwise upstream
	 * webhook delivery would be acknowledged and the side effect would be lost.
	 *
	 * @var string[]
	 */
	const KNOWN_UNHANDLED_EVENT_TYPES = array(
		'account.deleted',
		'account.updated',
		'charge.refund.updated',
		'charge.refunded',
		'invoice.paid',
		'invoice.payment_failed',
		'invoice.upcoming',
		'wcpay.notification',
	);

	/**
	 * Stripe zero-decimal currencies.
	 *
	 * @var string[]
	 */
	const ZERO_DECIMAL_CURRENCIES = array(
		'bif',
		'clp',
		'djf',
		'gnf',
		'jpy',
		'kmf',
		'krw',
		'mga',
		'pyg',
		'rwf',
		'vnd',
		'vuv',
		'xaf',
		'xof',
		'xpf',
	);

	/**
	 * Order lifecycle service.
	 *
	 * @var OrderPaymentLifecycleService
	 */
	private OrderPaymentLifecycleService $lifecycle_service;

	/**
	 * Legacy proxy.
	 *
	 * @var LegacyProxy
	 */
	private LegacyProxy $legacy_proxy;

	/**
	 * WooPayments legacy runtime.
	 *
	 * @var WooPaymentsLegacyRuntime
	 */
	private WooPaymentsLegacyRuntime $legacy_runtime;

	/**
	 * Native WooPayments API client.
	 *
	 * @var WooPaymentsApiClient
	 */
	private WooPaymentsApiClient $api_client;

	/**
	 * Dispute event handler.
	 *
	 * @var WooPaymentsDisputeEventHandler
	 */
	private WooPaymentsDisputeEventHandler $dispute_event_handler;

	/**
	 * WooPayments order data service.
	 *
	 * @var WooPaymentsOrderDataService|null
	 */
	private ?WooPaymentsOrderDataService $order_data_service = null;

	/**
	 * Initialize the class instance.
	 *
	 * @internal
	 *
	 * @param OrderPaymentLifecycleService        $lifecycle_service     Order lifecycle service.
	 * @param LegacyProxy                         $legacy_proxy          Legacy proxy.
	 * @param WooPaymentsLegacyRuntime            $legacy_runtime        WooPayments legacy runtime.
	 * @param WooPaymentsApiClient                $api_client            Native WooPayments API client.
	 * @param WooPaymentsOrderDataService|null    $order_data_service    WooPayments order data service.
	 * @param WooPaymentsDisputeCacheService|null $dispute_cache_service Dispute cache service.
	 */
	final public function init( OrderPaymentLifecycleService $lifecycle_service, LegacyProxy $legacy_proxy, WooPaymentsLegacyRuntime $legacy_runtime, WooPaymentsApiClient $api_client, ?WooPaymentsOrderDataService $order_data_service = null, ?WooPaymentsDisputeCacheService $dispute_cache_service = null ): void {
		$this->lifecycle_service  = $lifecycle_service;
		$this->legacy_proxy       = $legacy_proxy;
		$this->legacy_runtime     = $legacy_runtime;
		$this->api_client         = $api_client;
		$this->order_data_service = $order_data_service;

		$this->dispute_event_handler = new WooPaymentsDisputeEventHandler();
		$this->dispute_event_handler->init(
			$legacy_runtime,
			$api_client,
			$dispute_cache_service ?? wc_get_container()->get( WooPaymentsDisputeCacheService::class )
		);
	}

	/**
	 * Process a WooPayments webhook event.
	 *
	 * @since 11.0.0
	 *
	 * @param array<string,mixed> $event Event payload.
	 * @throws InvalidArgumentException When the event shape is invalid.
	 */
	public function process( array $event ): void {
		$event_type = $event['type'] ?? null;
		if ( ! is_string( $event_type ) || '' === $event_type ) {
			throw new InvalidArgumentException( 'WooPayments webhook event is missing a type.' );
		}

		if ( $this->is_webhook_mode_mismatch( $event ) ) {
			return;
		}

		$this->run_delivery_hook( 'woocommerce_payments_before_webhook_delivery', $event_type, $event );

		$event_object = $this->get_event_object( $event );
		if ( $this->dispute_event_handler->is_supported_event( $event_type ) ) {
			$this->dispute_event_handler->process( $event_type, $event_object );
			$this->run_delivery_hook( 'woocommerce_payments_after_webhook_delivery', $event_type, $event );
			return;
		}

		$lifecycle_event = $this->build_lifecycle_event( $event_type, $event_object );
		if ( null === $lifecycle_event ) {
			$this->run_delivery_hook( 'woocommerce_payments_after_webhook_delivery', $event_type, $event );
			return;
		}

		$order = $this->get_order_for_event_object( $event_type, $event_object );
		if ( ! $order instanceof WC_Order || ! $this->is_woopayments_order( $order ) ) {
			$this->run_delivery_hook( 'woocommerce_payments_after_webhook_delivery', $event_type, $event );
			return;
		}

		$this->lifecycle_service->apply( $order, $lifecycle_event );
		$this->run_delivery_hook( 'woocommerce_payments_after_webhook_delivery', $event_type, $event );
	}

	/**
	 * Get the provider object from an event payload.
	 *
	 * @param array<string,mixed> $event Event payload.
	 * @return array<string,mixed>
	 * @throws InvalidArgumentException When the object shape is invalid.
	 */
	private function get_event_object( array $event ): array {
		$object = $event['data']['object'] ?? null;
		if ( ! is_array( $object ) ) {
			throw new InvalidArgumentException( 'WooPayments webhook event is missing an object.' );
		}

		return $object;
	}

	/**
	 * Resolve the order named by the event object.
	 *
	 * @param string              $event_type Event type.
	 * @param array<string,mixed> $event_object Provider object.
	 * @return WC_Order|null
	 */
	private function get_order_for_event_object( string $event_type, array $event_object ): ?WC_Order {
		if ( 'charge.expired' === $event_type ) {
			return $this->get_order_by_payment_meta( '_charge_id', $this->get_object_id( $event_object ) );
		}

		$order = $this->get_order_by_payment_meta( '_intent_id', $this->get_object_id( $event_object ) );
		if ( $order instanceof WC_Order && $this->does_order_key_match_event_object( $order, $event_object ) ) {
			return $order;
		}

		return $this->get_order_from_event_object_metadata( $event_object );
	}

	/**
	 * Resolve the order named by provider metadata.
	 *
	 * The order key guard prevents cross-site order ID collisions from mutating
	 * another site's order when webhooks are delivered in multisite contexts.
	 *
	 * @param array<string,mixed> $event_object Provider object.
	 * @return WC_Order|null
	 */
	private function get_order_from_event_object_metadata( array $event_object ): ?WC_Order {
		$metadata = $event_object['metadata'] ?? null;
		if ( ! is_array( $metadata ) ) {
			return null;
		}

		$order_id  = isset( $metadata['order_id'] ) ? absint( $metadata['order_id'] ) : 0;
		$order_key = isset( $metadata['order_key'] ) ? (string) $metadata['order_key'] : '';
		if ( 0 === $order_id ) {
			$order_id = $this->get_order_id_from_first_charge_metadata( $event_object );
		}
		if ( 0 === $order_id ) {
			return null;
		}

		$order = wc_get_order( $order_id );
		if ( ! $order instanceof WC_Order ) {
			return null;
		}

		if ( '' !== $order_key && $order_key !== $order->get_order_key() ) {
			return null;
		}

		return $order;
	}

	/**
	 * Tell whether a found order matches the event order key when one is present.
	 *
	 * @param WC_Order            $order        Order object.
	 * @param array<string,mixed> $event_object Provider object.
	 * @return bool
	 */
	private function does_order_key_match_event_object( WC_Order $order, array $event_object ): bool {
		$order_key = $event_object['metadata']['order_key'] ?? null;

		return ! is_string( $order_key ) || '' === $order_key || $order_key === $order->get_order_key();
	}

	/**
	 * Get the order ID from first-charge metadata.
	 *
	 * @param array<string,mixed> $event_object Provider object.
	 * @return int
	 */
	private function get_order_id_from_first_charge_metadata( array $event_object ): int {
		$order_id = $event_object['charges']['data'][0]['metadata']['order_id'] ?? 0;

		return absint( $order_id );
	}

	/**
	 * Get a WooPayments order by a preserved payment meta key.
	 *
	 * @param string $meta_key   Payment meta key.
	 * @param string $meta_value Payment meta value.
	 * @return WC_Order|null
	 */
	private function get_order_by_payment_meta( string $meta_key, string $meta_value ): ?WC_Order {
		if ( '' === $meta_value ) {
			return null;
		}

		$orders = wc_get_orders(
			array(
				'limit'      => 1,
				'meta_key'   => $meta_key, // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_key
				'meta_value' => $meta_value, // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_value
			)
		);

		if ( ! is_array( $orders ) ) {
			return null;
		}

		return isset( $orders[0] ) && $orders[0] instanceof WC_Order ? $orders[0] : null;
	}

	/**
	 * Build a neutral lifecycle event for a supported WooPayments webhook type.
	 *
	 * @param string              $event_type Event type.
	 * @param array<string,mixed> $event_object Provider object.
	 * @return PaymentLifecycleEvent|null
	 * @throws RuntimeException When the event type is known but not migrated yet.
	 */
	private function build_lifecycle_event( string $event_type, array $event_object ): ?PaymentLifecycleEvent {
		switch ( $event_type ) {
			case 'payment_intent.succeeded':
				return new PaymentLifecycleEvent(
					PaymentLifecycleEvent::STATUS_COMPLETED,
					$this->get_object_id( $event_object ),
					$this->without_empty_values(
						array(
							'_intent_id'             => $this->get_object_id( $event_object ),
							'_charge_id'             => $this->get_charge_id_from_intent( $event_object ),
							'_payment_method_id'     => $this->get_payment_method_id_from_intent( $event_object ),
							'_intention_status'      => isset( $event_object['status'] ) ? (string) $event_object['status'] : '',
							'_wcpay_intent_currency' => isset( $event_object['currency'] ) ? (string) $event_object['currency'] : '',
							'_stripe_mandate_id'     => $this->get_mandate_id_from_intent( $event_object ),
							'_wcpay_transaction_fee' => $this->get_transaction_fee_from_intent( $event_object ),
							'_wcpay_net'             => $this->get_net_from_intent( $event_object ),
							'_wcpay_ipp_channel'     => $this->get_ipp_channel_from_intent( $event_object ),
						)
					),
					array(),
					$this->get_completed_payment_note_from_intent( $event_object )
				);

			case 'payment_intent.payment_failed':
				if ( ! $this->should_process_payment_failed_event( $event_object ) ) {
					return null;
				}

				return new PaymentLifecycleEvent(
					PaymentLifecycleEvent::STATUS_FAILED,
					$this->get_object_id( $event_object ),
					$this->without_empty_values(
						array(
							'_intent_id'        => $this->get_object_id( $event_object ),
							'_intention_status' => isset( $event_object['status'] ) ? (string) $event_object['status'] : '',
						)
					),
					array(),
					'Payment failed.'
				);

			case 'payment_intent.canceled':
			case 'payment_intent.amount_capturable_updated':
				return null;

			case 'charge.expired':
				return new PaymentLifecycleEvent(
					PaymentLifecycleEvent::STATUS_CAPTURE_EXPIRED,
					$this->get_object_id( $event_object ),
					$this->without_empty_values(
						array(
							'_charge_id' => $this->get_object_id( $event_object ),
						)
					),
					array(),
					'Payment authorization expired.'
				);
		}

		if ( in_array( $event_type, self::KNOWN_UNHANDLED_EVENT_TYPES, true ) ) {
			throw new RuntimeException( esc_html( sprintf( 'Native WooPayments webhook handling is not implemented for event type: %s', $event_type ) ) );
		}

		return null;
	}

	/**
	 * Tell whether a payment_intent.payment_failed event is actionable.
	 *
	 * @param array<string,mixed> $event_object PaymentIntent object.
	 * @return bool
	 */
	private function should_process_payment_failed_event( array $event_object ): bool {
		$last_payment_error  = $event_object['last_payment_error'] ?? null;
		$payment_method      = is_array( $last_payment_error ) ? ( $last_payment_error['payment_method'] ?? null ) : null;
		$payment_method_type = is_array( $payment_method ) && isset( $payment_method['type'] ) ? (string) $payment_method['type'] : '';

		return in_array(
			$payment_method_type,
			array(
				'card',
				'card_present',
				'us_bank_account',
				'bacs_debit',
				'wechat_pay',
			),
			true
		);
	}

	/**
	 * Get a provider object's ID.
	 *
	 * @param array<string,mixed> $event_object Provider object.
	 * @return string
	 */
	private function get_object_id( array $event_object ): string {
		return isset( $event_object['id'] ) ? (string) $event_object['id'] : '';
	}

	/**
	 * Get the first charge ID from a PaymentIntent object.
	 *
	 * @param array<string,mixed> $event_object PaymentIntent object.
	 * @return string
	 */
	private function get_charge_id_from_intent( array $event_object ): string {
		$charge_id = $event_object['charges']['data'][0]['id'] ?? '';

		return is_string( $charge_id ) ? $charge_id : '';
	}

	/**
	 * Get the payment method ID from a PaymentIntent object.
	 *
	 * @param array<string,mixed> $event_object PaymentIntent object.
	 * @return string
	 */
	private function get_payment_method_id_from_intent( array $event_object ): string {
		$payment_method_id = $event_object['charges']['data'][0]['payment_method'] ?? $event_object['payment_method'] ?? '';

		return is_string( $payment_method_id ) ? $payment_method_id : '';
	}

	/**
	 * Get the mandate ID from a PaymentIntent object.
	 *
	 * @param array<string,mixed> $event_object PaymentIntent object.
	 * @return string
	 */
	private function get_mandate_id_from_intent( array $event_object ): string {
		$mandate_id = $event_object['charges']['data'][0]['payment_method_details']['card']['mandate'] ?? '';

		return is_string( $mandate_id ) ? $mandate_id : '';
	}

	/**
	 * Get the IPP channel from a PaymentIntent object.
	 *
	 * @param array<string,mixed> $event_object PaymentIntent object.
	 * @return string
	 */
	private function get_ipp_channel_from_intent( array $event_object ): string {
		$ipp_channel      = $event_object['metadata']['ipp_channel'] ?? '';
		$allowed_channels = array( 'mobile_pos', 'mobile_store_management' );

		return is_string( $ipp_channel ) && in_array( $ipp_channel, $allowed_channels, true ) ? $ipp_channel : '';
	}

	/**
	 * Get the completed-payment note from a PaymentIntent object.
	 *
	 * @param array<string,mixed> $event_object PaymentIntent object.
	 * @return string
	 */
	private function get_completed_payment_note_from_intent( array $event_object ): string {
		$fee_breakdown_note = $this->get_order_data_service()->get_fee_breakdown_note_from_intent( $event_object );
		if ( '' === $fee_breakdown_note ) {
			$fee_breakdown_note = $this->get_fee_breakdown_note_from_latest_intent( $event_object );
		}

		return '' !== $fee_breakdown_note ? $fee_breakdown_note : 'Payment complete.';
	}

	/**
	 * Get a fee breakdown note from a fresh PaymentIntent read.
	 *
	 * @param array<string,mixed> $event_object PaymentIntent object.
	 * @return string
	 */
	private function get_fee_breakdown_note_from_latest_intent( array $event_object ): string {
		$intent_id = $this->get_object_id( $event_object );
		if ( '' === $intent_id ) {
			return '';
		}

		try {
			return $this->get_order_data_service()->get_fee_breakdown_note_from_intent( $this->api_client->get_payment_intention( $intent_id ) );
		} catch ( Throwable $exception ) {
			return '';
		}
	}

	/**
	 * Get the transaction fee from a PaymentIntent object.
	 *
	 * @param array<string,mixed> $event_object PaymentIntent object.
	 * @return string
	 */
	private function get_transaction_fee_from_intent( array $event_object ): string {
		$fee_breakdown_v1 = $event_object['charges']['data'][0]['fee_breakdown_v1'] ?? null;
		if ( is_array( $fee_breakdown_v1 ) && isset( $fee_breakdown_v1['totals']['fee']['amount'], $fee_breakdown_v1['totals']['fee']['currency'] ) ) {
			return (string) $this->interpret_stripe_amount( (int) $fee_breakdown_v1['totals']['fee']['amount'], (string) $fee_breakdown_v1['totals']['fee']['currency'] );
		}

		$application_fee_amount = $event_object['charges']['data'][0]['application_fee_amount'] ?? null;
		$currency               = $event_object['currency'] ?? '';
		if ( ! $application_fee_amount || ! is_string( $currency ) ) {
			return '';
		}

		return (string) $this->interpret_stripe_amount( (int) $application_fee_amount, $currency );
	}

	/**
	 * Get the net amount from a PaymentIntent object.
	 *
	 * @param array<string,mixed> $event_object PaymentIntent object.
	 * @return string
	 */
	private function get_net_from_intent( array $event_object ): string {
		$fee_breakdown_v1 = $event_object['charges']['data'][0]['fee_breakdown_v1'] ?? null;
		if ( is_array( $fee_breakdown_v1 ) && isset( $fee_breakdown_v1['totals']['net']['amount'], $fee_breakdown_v1['totals']['net']['currency'] ) ) {
			return (string) $this->interpret_stripe_amount( (int) $fee_breakdown_v1['totals']['net']['amount'], (string) $fee_breakdown_v1['totals']['net']['currency'] );
		}

		$transaction_fee = $this->get_transaction_fee_from_intent( $event_object );
		$charge_amount   = $event_object['amount'] ?? null;
		$currency        = $event_object['currency'] ?? '';
		if ( '' === $transaction_fee || null === $charge_amount || ! is_string( $currency ) ) {
			return '';
		}

		return (string) ( $this->interpret_stripe_amount( (int) $charge_amount, $currency ) - (float) $transaction_fee );
	}

	/**
	 * Interpret a Stripe integer amount for a currency.
	 *
	 * @param int    $amount   Stripe integer amount.
	 * @param string $currency Currency code.
	 * @return float
	 */
	private function interpret_stripe_amount( int $amount, string $currency ): float {
		return in_array( strtolower( $currency ), self::ZERO_DECIMAL_CURRENCIES, true ) ? (float) $amount : (float) $amount / 100;
	}

	/**
	 * Remove empty string meta values.
	 *
	 * @param array<string,string> $values Raw values.
	 * @return array<string,string>
	 */
	private function without_empty_values( array $values ): array {
		return array_filter(
			$values,
			static function ( string $value ): bool {
				return '' !== $value;
			}
		);
	}

	/**
	 * Get the WooPayments order data service.
	 *
	 * @return WooPaymentsOrderDataService
	 */
	private function get_order_data_service(): WooPaymentsOrderDataService {
		if ( null === $this->order_data_service ) {
			$this->order_data_service = wc_get_container()->get( WooPaymentsOrderDataService::class );
		}

		return $this->order_data_service;
	}

	/**
	 * Tell whether the webhook livemode does not match native runtime mode.
	 *
	 * @param array<string,mixed> $event Event payload.
	 * @return bool
	 */
	private function is_webhook_mode_mismatch( array $event ): bool {
		if ( ! array_key_exists( 'livemode', $event ) ) {
			return false;
		}

		return $this->is_native_live_mode() !== (bool) $event['livemode'];
	}

	/**
	 * Tell whether native WooPayments is in live mode.
	 *
	 * @return bool
	 */
	private function is_native_live_mode(): bool {
		$settings = $this->legacy_proxy->call_function( 'get_option', 'woocommerce_woocommerce_payments_settings', array() );
		$live     = ! is_array( $settings ) || 'yes' !== ( $settings['test_mode'] ?? 'no' );

		/**
		 * Filters whether native WooPayments webhook processing is in live mode.
		 *
		 * @since 11.0.0
		 *
		 * @param bool $live Whether native WooPayments is in live mode.
		 */
		return (bool) apply_filters( self::FILTER_LIVE_MODE, $live );
	}

	/**
	 * Run a WooPayments-compatible webhook delivery hook.
	 *
	 * @param string              $hook       Hook name.
	 * @param string              $event_type Event type.
	 * @param array<string,mixed> $event      Event payload.
	 */
	private function run_delivery_hook( string $hook, string $event_type, array $event ): void {
		try {
			$this->legacy_proxy->call_function( 'do_action', $hook, $event_type, $event );
		} catch ( Throwable $exception ) {
			$logger = $this->legacy_runtime->get_logger();
			if ( is_object( $logger ) && is_callable( array( $logger, 'error' ) ) ) {
				$logger->error(
					$exception->getMessage(),
					array(
						'source' => 'native-payments-webhook',
					)
				);
			}
		}
	}

	/**
	 * Tell whether an order belongs to WooPayments.
	 *
	 * @param WC_Order $order Order object.
	 * @return bool
	 */
	private function is_woopayments_order( WC_Order $order ): bool {
		$payment_method = (string) $order->get_payment_method();

		return OrderPaymentStore::GATEWAY_ID === $payment_method || 0 === strpos( $payment_method, OrderPaymentStore::GATEWAY_ID_PREFIX );
	}
}
