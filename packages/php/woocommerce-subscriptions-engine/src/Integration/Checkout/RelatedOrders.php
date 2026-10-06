<?php
/**
 * RelatedOrders - reads the orders related to a contract: the origin order by the
 * contract's `origin_order_id`, plus the orders tagged with the {@see OrderLinkage} meta
 * (renewals / switches / resubscribes). Returns live WC_Order objects newest first;
 * shaping them for presentation is the caller's job.
 *
 * Integration zone: WordPress-native. The flat `meta_key`/`meta_value` lookup round-trips
 * through both the HPOS and legacy order stores.
 *
 * @package Automattic\WooCommerce\SubscriptionsEngine\Integration\Checkout
 */

declare( strict_types=1 );

namespace Automattic\WooCommerce\SubscriptionsEngine\Integration\Checkout;

use WC_Order;
use Automattic\WooCommerce\SubscriptionsEngine\Integration\Storage\ContractRepository;

defined( 'ABSPATH' ) || exit;

/**
 * Read of a contract's related orders.
 */
final class RelatedOrders {

	/**
	 * The orders related to `$contract_id`, newest first.
	 *
	 * The origin order is read by the contract's `origin_order_id`; the others through the
	 * order-side {@see OrderLinkage::META_CONTRACT_ID} meta. An order found both ways appears
	 * once. Returns an empty array for an unknown contract or when none are related.
	 *
	 * The window args exist because a long-running contract accumulates one renewal
	 * order per period, so paging consumers pass a window. The default stays "all".
	 *
	 * @param int $contract_id Contract id.
	 * @param int $limit       Maximum orders to return; any negative (default -1) for all, 0 for none.
	 * @param int $offset      Orders to skip (for paging). Default 0.
	 * @return array<int, WC_Order> Related orders, newest first.
	 */
	public function for_contract( int $contract_id, int $limit = -1, int $offset = 0 ): array {
		if ( 0 === $limit ) {
			return array();
		}

		$contract = ( new ContractRepository() )->find_summary( $contract_id );
		if ( null === $contract ) {
			return array();
		}

		$by_id = array();

		$origin_id = $contract->get_origin_order_id();
		$origin    = null === $origin_id ? false : wc_get_order( $origin_id );
		if ( $origin instanceof WC_Order ) {
			$by_id[ $origin->get_id() ] = $origin;
		}

		$linked = wc_get_orders(
			array(
				'limit'      => -1,
				'status'     => 'any',
				'type'       => 'shop_order',
				'meta_key'   => OrderLinkage::META_CONTRACT_ID, // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_key
				'meta_value' => (string) $contract_id,          // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_value
			)
		);
		foreach ( is_array( $linked ) ? $linked : array() as $order ) {
			if ( $order instanceof WC_Order ) {
				$by_id[ $order->get_id() ] = $order;
			}
		}

		$orders = array_values( $by_id );
		usort(
			$orders,
			static function ( WC_Order $a, WC_Order $b ): int {
				$a_created = $a->get_date_created();
				$b_created = $b->get_date_created();
				$by_date   = ( null === $b_created ? 0 : $b_created->getTimestamp() ) <=> ( null === $a_created ? 0 : $a_created->getTimestamp() );

				return 0 !== $by_date ? $by_date : $b->get_id() <=> $a->get_id();
			}
		);

		return array_slice( $orders, max( 0, $offset ), $limit < 0 ? null : $limit );
	}
}
