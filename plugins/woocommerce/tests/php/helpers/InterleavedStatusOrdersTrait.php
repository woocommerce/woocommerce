<?php
declare( strict_types = 1 );

namespace Automattic\WooCommerce\Tests\Helpers;

use Automattic\WooCommerce\Enums\OrderStatus;

/**
 * Trait InterleavedStatusOrdersTrait.
 *
 * Creates orders with known statuses and creation dates, for tests that filter or sort orders by status.
 */
trait InterleavedStatusOrdersTrait {

	/**
	 * Creates orders cycling through pending, processing and completed statuses, with strictly decreasing creation dates.
	 *
	 * @param int $count Number of orders to create.
	 * @return int[] IDs of the created orders, newest first.
	 */
	private function create_orders_with_interleaved_statuses( int $count ): array {
		$statuses = array( OrderStatus::PENDING, OrderStatus::PROCESSING, OrderStatus::COMPLETED );
		$ids      = array();

		for ( $i = 0; $i < $count; $i++ ) {
			$order = new \WC_Order();
			$order->set_status( $statuses[ $i % count( $statuses ) ] );
			$order->set_date_created( strtotime( '2023-06-01 12:00:00' ) - ( $i * HOUR_IN_SECONDS ) );
			$order->save();
			$ids[] = $order->get_id();
		}

		return $ids;
	}
}
