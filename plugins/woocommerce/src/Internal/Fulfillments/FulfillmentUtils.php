<?php declare(strict_types=1);

namespace Automattic\WooCommerce\Internal\Fulfillments;

use Automattic\WooCommerce\Internal\DataStores\Fulfillments\FulfillmentsDataStore;
use WC_Order;

/**
 * Class FulfillmentUtils
 *
 * Utility class for handling order fulfillments.
 */
class FulfillmentUtils {

	/**
	 * Get pending items for an order.
	 *
	 * @param WC_Order $order The order object.
	 * @param array    $fulfillments Optional. An array of fulfillments to check. If not provided, it will fetch from the data store.
	 *
	 * @return array An array of pending items.
	 */
	public static function get_pending_items( WC_Order $order, $fulfillments = array() ): array {
		if ( empty( $fulfillments ) ) {
			// If no fulfillments are provided, fetch them from the data store.
			$fulfillments_data_store = wc_get_container()->get( FulfillmentsDataStore::class );
			$fulfillments            = $fulfillments_data_store->read_fulfillments( WC_Order::class, (string) $order->get_id() );
		}

		$items_in_fulfillments = self::get_all_items_of_fulfillments( $fulfillments );
		$order_items           = array_map(
			function ( $item ) use ( $order ) {
				return array(
					'item_id' => $item->get_id(),
					'item'    => $item,
					'qty'     => $item->get_quantity() - $order->get_qty_refunded_for_item( $item ),
				);
			},
			$order->get_items() ?? array()
		);

		foreach ( $order_items as $item_id => &$item ) {
			if ( isset( $items_in_fulfillments[ $item_id ] ) ) {
				$item['qty'] = $item['qty'] - $items_in_fulfillments[ $item_id ];
			}
		}

		return array_filter(
			$order_items,
			function ( $item ) {
				return $item['qty'] > 0; // Only return items with a positive quantity.
			}
		);
	}

	/**
	 * Get all items from the fulfillments.
	 *
	 * @param array $fulfillments An array of fulfillments.
	 *
	 * @return array An associative array of item IDs and their quantities.
	 */
	public static function get_all_items_of_fulfillments( array $fulfillments ): array {
		$items = array();
		foreach ( $fulfillments as $fulfillment ) {
			$fulfillment_items = $fulfillment->get_items();
			foreach ( $fulfillment_items as $item ) {
				$items[ $item['item_id'] ] = $item['qty'];
			}
		}
		return $items;
	}
}
