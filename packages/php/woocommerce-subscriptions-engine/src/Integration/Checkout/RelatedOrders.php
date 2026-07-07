<?php
/**
 * RelatedOrders - reads the orders linked to a contract from the order side, via the
 * {@see OrderLinkage} meta the engine tags onto every contract-related order (the origin
 * order at checkout, plus renewals / switches / resubscribes). Returns live WC_Order
 * objects newest first; shaping them for presentation is the caller's job.
 *
 * Integration zone: WordPress-native. The flat `meta_key`/`meta_value` lookup round-trips
 * through both the HPOS and legacy order stores.
 *
 * @package Automattic\WooCommerce\SubscriptionsEngine\Integration\Checkout
 */

declare( strict_types=1 );

namespace Automattic\WooCommerce\SubscriptionsEngine\Integration\Checkout;

use WC_Order;

defined( 'ABSPATH' ) || exit;

/**
 * Order-side read of a contract's related orders.
 */
final class RelatedOrders {

	/**
	 * The orders linked to `$contract_id`, newest first.
	 *
	 * Reads the orders tagged with this contract through the order-side
	 * {@see OrderLinkage::META_CONTRACT_ID} meta; the contract row carries the reverse
	 * `origin_order_id`. Returns an empty array when none are linked.
	 *
	 * The window args exist because a long-running contract accumulates one renewal
	 * order per period - unbounded reads grow with contract age, so paging consumers
	 * pass a window. The default stays "all", newest first.
	 *
	 * @param int $contract_id Contract id.
	 * @param int $limit       Maximum orders to return; any negative (default -1) for all, 0 for none.
	 * @param int $offset      Orders to skip (for paging). Default 0.
	 * @return array<int, WC_Order> Linked orders, newest first.
	 */
	public function for_contract( int $contract_id, int $limit = -1, int $offset = 0 ): array {
		if ( 0 === $limit ) {
			return array();
		}

		// Any other non-positive limit means "all": -1 is the wc_get_orders sentinel,
		// and an unguarded 0 would fall back to the site's posts-per-page default.
		$limit = $limit < 0 ? -1 : $limit;

		$orders = wc_get_orders(
			array(
				'limit'      => $limit,
				'offset'     => max( 0, $offset ),
				'status'     => 'any',
				'type'       => 'shop_order',
				'orderby'    => 'date',
				'order'      => 'DESC',
				'meta_key'   => OrderLinkage::META_CONTRACT_ID, // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_key
				'meta_value' => (string) $contract_id,          // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_value
			)
		);

		if ( ! is_array( $orders ) ) {
			return array();
		}

		$result = array();
		foreach ( $orders as $order ) {
			if ( $order instanceof WC_Order ) {
				$result[] = $order;
			}
		}

		return $result;
	}
}
