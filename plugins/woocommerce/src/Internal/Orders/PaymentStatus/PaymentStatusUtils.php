<?php declare(strict_types=1);

namespace Automattic\WooCommerce\Internal\Orders\PaymentStatus;

use Automattic\WooCommerce\Enums\OrderStatus;
use WC_Order;

/**
 * Class PaymentStatusUtils
 *
 * Utility class for handling order payment statuses.
 */
class PaymentStatusUtils {

	/**
	 * Get the payment status of an order.
	 *
	 * @param WC_Order $order The order object.
	 * @return string The payment status.
	 */
	public static function get_order_payment_status( WC_Order $order ): string {
		if ( ! $order->meta_exists( '_payment_status' ) ) {
			return self::update_order_payment_status( $order, self::get_synced_payment_status_for_order( $order ) );
		}
		return $order->get_meta( '_payment_status', true );
	}

	/**
	 * Get the payment statuses.
	 *
	 * @return array The payment statuses.
	 */
	public static function get_order_payment_statuses(): array {
		return array(
			OrderPaymentStatus::PENDING            => array(
				'label' => __( 'Pending', 'woocommerce' ),
			),
			OrderPaymentStatus::PAID               => array(
				'label' => __( 'Paid', 'woocommerce' ),
			),
			OrderPaymentStatus::ON_SITE            => array(
				'label' => __( 'Pay on site', 'woocommerce' ),
			),
			OrderPaymentStatus::FAILED             => array(
				'label' => __( 'Failed', 'woocommerce' ),
			),
			OrderPaymentStatus::CANCELLED          => array(
				'label' => __( 'Cancelled', 'woocommerce' ),
			),
			OrderPaymentStatus::REFUNDED           => array(
				'label' => __( 'Refunded', 'woocommerce' ),
			),
			OrderPaymentStatus::PARTIALLY_REFUNDED => array(
				'label' => __( 'Partially refunded', 'woocommerce' ),
			),
		);
	}

	/**
	 * Update the payment status of an order by ID or object.
	 *
	 * @param WC_Order $order The order object.
	 * @param string   $status The status to update to.
	 * @return string The updated payment status.
	 */
	public static function update_order_payment_status( WC_Order $order, string $status ): string {
		if ( $order->get_meta( '_payment_status' ) === $status ) {
			return $status;
		}
		$order->update_meta_data( '_payment_status', $status );
		$order->save();
		return $status;
	}

	/**
	 * Gets a payment status based on the order status.
	 *
	 * This is based on multiple factors in order of priority.
	 *
	 * @param WC_Order $order The order object.
	 */
	public static function get_synced_payment_status_for_order( WC_Order $order ): string {
		$order_status = $order->get_status();

		switch ( $order_status ) {
			case OrderStatus::FAILED:
				$payment_status = OrderPaymentStatus::FAILED;
				break;
			case OrderStatus::REFUNDED:
				$payment_status = OrderPaymentStatus::REFUNDED;
				break;
			case OrderStatus::CANCELLED:
				$payment_status = OrderPaymentStatus::CANCELLED;
				break;
			default:
				$payment_status = OrderPaymentStatus::PENDING;
				break;
		}

		if ( in_array( $order_status, wc_get_is_paid_statuses(), true ) ) {
			$payment_status = OrderPaymentStatus::PAID;
		}

		// Paid order may be partially refunded.
		if ( OrderPaymentStatus::PAID === $payment_status && $order->get_total_refunded() > 0 ) {
			$payment_status = OrderPaymentStatus::PARTIALLY_REFUNDED;
		}

		// Should this move to extensions?
		if ( OrderPaymentStatus::PENDING === $payment_status && $order->get_payment_method() === 'cod' ) {
			$payment_status = OrderPaymentStatus::ON_SITE;
		}

		/**
		 * Filter the payment status to sync. The status must be registered otherwise it will be ignored.
		 *
		 * @since 10.1.0
		 *
		 * @param string     $payment_status The payment status.
		 * @param WC_Order   $order The order object.
		 * @return string The payment status.
		 */
		$filtered_payment_status = (string) apply_filters( 'woocommerce_synced_payment_status_for_order', $payment_status, $order );

		if ( in_array( $filtered_payment_status, array_keys( self::get_order_payment_statuses() ), true ) ) {
			$payment_status = $filtered_payment_status;
		}

		return $payment_status;
	}

	/**
	 * Check if the given payment status is valid.
	 *
	 * @param string|null $status The payment status to check.
	 * @return bool True if the status is valid, false otherwise.
	 */
	public static function is_valid_order_payment_status( ?string $status ): bool {
		if ( is_null( $status ) ) {
			return false;
		}
		return in_array( $status, array_keys( self::get_order_payment_statuses() ), true );
	}

	/**
	 * Get the payment status meta query.
	 *
	 * @param array $statuses The statuses to get the meta query for.
	 * @return array The meta query.
	 */
	public static function get_order_payment_status_meta_query( array $statuses ): array {
		if ( is_string( $statuses ) ) {
			$statuses = array( $statuses );
		}

		$valid_statuses = array_filter( $statuses, array( self::class, 'is_valid_order_payment_status' ) );
		if ( empty( $valid_statuses ) ) {
			return array();
		}

		return array(
			'key'     => '_payment_status',
			'value'   => $valid_statuses,
			'compare' => 'IN',
		);
	}
}
