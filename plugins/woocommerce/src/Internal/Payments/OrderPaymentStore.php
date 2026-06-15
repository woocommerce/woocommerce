<?php
/**
 * OrderPaymentStore class file.
 */

declare( strict_types = 1 );

namespace Automattic\WooCommerce\Internal\Payments;

use WC_Order;
use WC_Order_Refund;
use WC_Abstract_Order;

/**
 * HPOS-safe order payment projection and WooPayments-compatible payment locks.
 *
 * @since 11.0.0
 * @internal Transitional internal component for the native payments runtime.
 */
class OrderPaymentStore {

	/**
	 * Preserved WooPayments gateway ID.
	 *
	 * @var string
	 */
	const GATEWAY_ID = 'woocommerce_payments';

	/**
	 * Preserved WooPayments split-UPE gateway ID prefix.
	 *
	 * @var string
	 */
	const GATEWAY_ID_PREFIX = 'woocommerce_payments_';

	/**
	 * WooPayments-compatible order processing lock transient prefix.
	 *
	 * @var string
	 */
	const LOCK_TRANSIENT_PREFIX = 'wcpay_processing_intent_';

	/**
	 * WooPayments-compatible sentinel used when the order is locked without a payment reference.
	 *
	 * @var string
	 */
	const LOCK_SENTINEL = '-1';

	/**
	 * WooPayments lock time-to-live, in seconds.
	 *
	 * @var int
	 */
	const LOCK_TTL_SECONDS = 300;

	/**
	 * WooPayments Bucket-E order/refund meta keys that native code must preserve.
	 *
	 * @var string[]
	 */
	private const PAYMENT_META_KEYS = array(
		'_intent_id',
		'_payment_method_id',
		'_charge_id',
		'_intention_status',
		'_charge_risk_level',
		'_stripe_customer_id',
		'_wcpay_fraud_meta_box_type',
		'_wcpay_fraud_outcome_status',
		'_wcpay_intent_currency',
		'_wcpay_refund_id',
		'_wcpay_refund_transaction_id',
		'_wcpay_refund_status',
		'_wcpay_transaction_fee',
		'_wcpay_mode',
		'_wcpay_payment_transaction_id',
		'_wcpay_multibanco_entity',
		'_wcpay_multibanco_reference',
		'_wcpay_multibanco_expiry',
		'_wcpay_multibanco_url',
		'_wcpay_payment_method_details',
		'_wcpay_ipp_channel',
		'_wcpay_net',
		'_stripe_mandate_id',
		'_wcpay_express_checkout_payment_method',
		'_wcpay_multi_currency_stripe_exchange_rate',
		'_wcpay_multi_currency_order_exchange_rate',
		'_wcpay_multi_currency_order_default_currency',
		'_wcpay_fraud_outcome_manual_entry',
		'is_woopay',
		'last4',
		'_card_brand',
	);

	/**
	 * Get the preserved WooPayments order/refund payment meta keys.
	 *
	 * @since 11.0.0
	 *
	 * @return string[]
	 */
	public static function get_payment_meta_keys(): array {
		return self::PAYMENT_META_KEYS;
	}

	/**
	 * Read a stable, HPOS-safe projection of an order's payment surface.
	 *
	 * The returned structure is intentionally limited to persisted payment state. A1 shadow mode
	 * compares this read projection without writing back to the order.
	 *
	 * @since 11.0.0
	 *
	 * @param WC_Order $order Order to project.
	 * @return array<string,mixed>
	 */
	public function read_payment_surface( WC_Order $order ): array {
		return array(
			'order_id'       => (int) $order->get_id(),
			'status'         => (string) $order->get_status(),
			'payment_method' => (string) $order->get_payment_method(),
			'transaction_id' => (string) $order->get_transaction_id(),
			'currency'       => (string) $order->get_currency(),
			'total'          => (string) $order->get_total(),
			'meta'           => $this->read_payment_meta( $order ),
			'refunds'        => $this->read_refund_surfaces( $order ),
		);
	}

	/**
	 * Tell whether an order is locked for payment processing.
	 *
	 * This preserves the WooPayments lock semantics exactly: a sentinel lock blocks all references,
	 * while a reference-specific lock blocks only that same reference.
	 *
	 * @since 11.0.0
	 *
	 * @param WC_Order    $order             Order being checked.
	 * @param string|null $payment_reference Payment reference currently being processed.
	 * @return bool True when processing is locked.
	 */
	public function is_order_payment_locked( WC_Order $order, ?string $payment_reference = null ): bool {
		$processing = get_transient( $this->get_order_payment_lock_key( $order ) );

		return self::LOCK_SENTINEL === $processing || ( null !== $payment_reference && $processing === $payment_reference );
	}

	/**
	 * Lock an order for payment processing.
	 *
	 * @since 11.0.0
	 *
	 * @param WC_Order    $order             Order being locked.
	 * @param string|null $payment_reference Payment reference being processed.
	 */
	public function lock_order_payment( WC_Order $order, ?string $payment_reference = null ): void {
		set_transient(
			$this->get_order_payment_lock_key( $order ),
			empty( $payment_reference ) ? self::LOCK_SENTINEL : $payment_reference,
			self::LOCK_TTL_SECONDS
		);
	}

	/**
	 * Unlock an order for payment processing.
	 *
	 * @since 11.0.0
	 *
	 * @param WC_Order $order Order being unlocked.
	 */
	public function unlock_order_payment( WC_Order $order ): void {
		delete_transient( $this->get_order_payment_lock_key( $order ) );
	}

	/**
	 * Read preserved payment meta from an order or refund object.
	 *
	 * @param WC_Abstract_Order $order Order or refund object.
	 * @return array<string,string>
	 */
	private function read_payment_meta( WC_Abstract_Order $order ): array {
		$payment_meta = array();
		$allowed_keys = array_fill_keys( self::PAYMENT_META_KEYS, true );

		foreach ( $order->get_meta_data() as $meta ) {
			$meta_data = $meta->get_data();
			$key       = (string) ( $meta_data['key'] ?? '' );

			if ( ! isset( $allowed_keys[ $key ] ) ) {
				continue;
			}

			$payment_meta[ $key ] = $this->normalize_meta_value( $meta_data['value'] ?? null );
		}

		ksort( $payment_meta );

		return $payment_meta;
	}

	/**
	 * Read stable refund projections for an order.
	 *
	 * @param WC_Order $order Order object.
	 * @return array<int,array<string,mixed>>
	 */
	private function read_refund_surfaces( WC_Order $order ): array {
		$refunds = array();

		foreach ( $order->get_refunds() as $refund ) {
			if ( ! $refund instanceof WC_Order_Refund ) {
				continue;
			}

			$refunds[] = array(
				'refund_id' => (int) $refund->get_id(),
				'amount'    => (string) $refund->get_amount(),
				'currency'  => (string) $refund->get_currency(),
				'reason'    => (string) $refund->get_reason(),
				'meta'      => $this->read_payment_meta( $refund ),
			);
		}

		usort(
			$refunds,
			static function ( array $left, array $right ): int {
				return $left['refund_id'] <=> $right['refund_id'];
			}
		);

		return $refunds;
	}

	/**
	 * Normalize meta values for stable machine-readable comparisons.
	 *
	 * @param mixed $value Meta value.
	 * @return string Normalized scalar value.
	 */
	private function normalize_meta_value( $value ): string {
		if ( is_scalar( $value ) || null === $value ) {
			return (string) $value;
		}

		$encoded = wp_json_encode( $value );
		return false === $encoded ? '' : $encoded;
	}

	/**
	 * Get the WooPayments-compatible transient key for an order lock.
	 *
	 * @param WC_Order $order Order object.
	 * @return string Transient key.
	 */
	private function get_order_payment_lock_key( WC_Order $order ): string {
		return self::LOCK_TRANSIENT_PREFIX . $order->get_id();
	}
}
