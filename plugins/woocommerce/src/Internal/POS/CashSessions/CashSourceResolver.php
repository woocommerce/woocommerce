<?php
declare( strict_types = 1 );

namespace Automattic\WooCommerce\Internal\POS\CashSessions;

defined( 'ABSPATH' ) || exit;

use DomainException;
use InvalidArgumentException;
use OverflowException;
use WC_DateTime;
use WC_Order;
use WC_Order_Refund;

/**
 * Validates the order and refund references of cash sale and cash refund movements.
 *
 * Amounts and times come from the source record, never from the client, and are frozen in the movement.
 * Nothing here changes the order or refund, so recording a movement cannot charge or refund anything.
 *
 * @since 11.3.0
 */
class CashSourceResolver {

	/**
	 * Order meta the POS app sets on cash payments. It is the only POS cash marker Core already reads
	 * (see PointOfSaleOrderUtil::is_order_paid_at_pos()).
	 */
	private const CASH_CHANGE_META_KEY = '_cash_change_amount';

	/**
	 * Resolve a paid POS cash order.
	 *
	 * @since 11.3.0
	 *
	 * @param int    $order_id  Order ID.
	 * @param string $currency  Session currency.
	 * @param int    $precision Session precision.
	 * @return array{amount: int, occurred_at_gmt: string, source_key: string}
	 * @throws CashSessionException When the order is not a valid cash sale for the session.
	 */
	public function resolve_sale( int $order_id, string $currency, int $precision ): array {
		$order = $this->get_cash_order( $order_id );

		if ( ! $order->is_paid() ) {
			throw $this->invalid_source( esc_html__( 'The order is not paid.', 'woocommerce' ) );
		}

		$this->check_currency( $order->get_currency(), $currency );

		return array(
			'amount'          => $this->parse_amount( (string) $order->get_total( 'edit' ), $precision ),
			'occurred_at_gmt' => $this->to_gmt( $order->get_date_paid() ?? $order->get_date_created() ),
			'source_key'      => 'order:' . $order->get_id(),
		);
	}

	/**
	 * Resolve a refund of a POS cash order.
	 *
	 * The parent order is not required to be paid: a full refund moves it to the refunded status.
	 *
	 * @since 11.3.0
	 *
	 * @param int    $order_id  Order ID.
	 * @param int    $refund_id Refund ID.
	 * @param string $currency  Session currency.
	 * @param int    $precision Session precision.
	 * @return array{amount: int, occurred_at_gmt: string, source_key: string}
	 * @throws CashSessionException When the refund is not a valid cash refund for the session.
	 */
	public function resolve_refund( int $order_id, int $refund_id, string $currency, int $precision ): array {
		$order  = $this->get_cash_order( $order_id );
		$refund = $this->get_refund_of_order( $refund_id, $order_id );

		$this->check_currency( $order->get_currency(), $currency );
		$this->check_currency( $refund->get_currency(), $currency );

		return array(
			'amount'          => $this->parse_amount( (string) $refund->get_amount( 'edit' ), $precision ),
			'occurred_at_gmt' => $this->to_gmt( $refund->get_date_created() ),
			'source_key'      => 'refund:' . $refund->get_id(),
		);
	}

	/**
	 * Check the optional order and refund references of a drawer event.
	 *
	 * @since 11.3.0
	 *
	 * @param int|null $order_id  Order ID.
	 * @param int|null $refund_id Refund ID; requires the order ID.
	 * @throws CashSessionException When a reference does not exist or does not match.
	 */
	public function check_references( ?int $order_id, ?int $refund_id ): void {
		if ( null !== $refund_id && null === $order_id ) {
			throw $this->invalid_reference( esc_html__( 'A refund reference also needs its order ID.', 'woocommerce' ) );
		}
		if ( null !== $order_id && ! wc_get_order( $order_id ) instanceof WC_Order ) {
			throw $this->invalid_reference( esc_html__( 'The order does not exist.', 'woocommerce' ) );
		}
		if ( null !== $refund_id ) {
			$refund = wc_get_order( $refund_id );
			if ( ! $refund instanceof WC_Order_Refund || $refund->get_parent_id() !== $order_id ) {
				throw $this->invalid_reference( esc_html__( 'The refund does not belong to the order.', 'woocommerce' ) );
			}
		}
	}

	/**
	 * Load an order paid in cash at the POS.
	 *
	 * @param int $order_id Order ID.
	 * @return WC_Order
	 * @throws CashSessionException When the order does not exist or was not paid in cash.
	 */
	private function get_cash_order( int $order_id ): WC_Order {
		$order = wc_get_order( $order_id );
		if ( ! $order instanceof WC_Order ) {
			throw $this->invalid_source( esc_html__( 'The order does not exist.', 'woocommerce' ) );
		}
		if ( '' === (string) $order->get_meta( self::CASH_CHANGE_META_KEY, true ) ) {
			throw $this->invalid_source( esc_html__( 'The order was not paid in cash at the point of sale.', 'woocommerce' ) );
		}
		return $order;
	}

	/**
	 * Load a refund and check that it belongs to the order.
	 *
	 * @param int $refund_id Refund ID.
	 * @param int $order_id  Expected parent order ID.
	 * @return WC_Order_Refund
	 * @throws CashSessionException When the refund does not exist or belongs to another order.
	 */
	private function get_refund_of_order( int $refund_id, int $order_id ): WC_Order_Refund {
		$refund = wc_get_order( $refund_id );
		if ( ! $refund instanceof WC_Order_Refund || $refund->get_parent_id() !== $order_id ) {
			throw $this->invalid_source( esc_html__( 'The refund does not belong to the order.', 'woocommerce' ) );
		}
		return $refund;
	}

	/**
	 * Check that a source uses the session currency.
	 *
	 * @param string $source_currency  Source currency.
	 * @param string $session_currency Session currency.
	 * @throws CashSessionException On mismatch.
	 */
	private function check_currency( string $source_currency, string $session_currency ): void {
		if ( strtoupper( $source_currency ) !== $session_currency ) {
			throw CashSessionException::invalid(
				'woocommerce_rest_cash_currency_mismatch',
				esc_html__( 'The source currency does not match the cash session currency.', 'woocommerce' )
			);
		}
	}

	/**
	 * Parse a source amount at the session precision.
	 *
	 * @param string $value     Source amount.
	 * @param int    $precision Session precision.
	 * @return int Positive minor units.
	 * @throws CashSessionException When the amount cannot be represented exactly or is not positive.
	 */
	private function parse_amount( string $value, int $precision ): int {
		try {
			$amount = CashMoney::parse_source( $value, $precision );
		} catch ( DomainException $e ) {
			throw CashSessionException::invalid(
				'woocommerce_rest_cash_precision_mismatch',
				esc_html__( 'The source amount has more decimal places than the cash session currency.', 'woocommerce' )
			);
		} catch ( InvalidArgumentException | OverflowException $e ) {
			throw $this->invalid_source( esc_html__( 'The source amount is not a valid cash amount.', 'woocommerce' ) );
		}

		if ( $amount <= 0 ) {
			throw $this->invalid_source( esc_html__( 'The source amount must be greater than zero.', 'woocommerce' ) );
		}
		return $amount;
	}

	/**
	 * Convert a source date to a UTC storage value.
	 *
	 * @param WC_DateTime|null $date Source date.
	 * @return string
	 * @throws CashSessionException When the source has no date.
	 */
	private function to_gmt( ?WC_DateTime $date ): string {
		if ( null === $date ) {
			throw $this->invalid_source( esc_html__( 'The source has no date.', 'woocommerce' ) );
		}
		return gmdate( 'Y-m-d H:i:s', $date->getTimestamp() );
	}

	/**
	 * Build an invalid source error.
	 *
	 * @param string $message Translated message.
	 * @return CashSessionException
	 */
	private function invalid_source( string $message ): CashSessionException {
		return CashSessionException::invalid( 'woocommerce_rest_cash_invalid_source', $message );
	}

	/**
	 * Build an invalid reference error.
	 *
	 * @param string $message Translated message.
	 * @return CashSessionException
	 */
	private function invalid_reference( string $message ): CashSessionException {
		return CashSessionException::invalid( 'woocommerce_rest_cash_invalid_reference', $message );
	}
}
