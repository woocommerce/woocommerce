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
	 * @param array    $fulfillments An array of fulfillments to check.
	 *
	 * @return array An array of pending items.
	 */
	public static function get_pending_items( WC_Order $order, $fulfillments ): array {

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
	 * Get order items for a fulfillment.
	 *
	 * @param WC_Order    $order The order object.
	 * @param Fulfillment $fulfillment The fulfillment object.
	 *
	 * @return array An array of order items.
	 */
	public static function get_fulfillment_items( WC_Order $order, Fulfillment $fulfillment ): array {
		$fulfillment_items = array_combine(
			array_column( $fulfillment->get_items(), 'item_id' ),
			array_column( $fulfillment->get_items(), 'qty' )
		);

		$order_items = array_map(
			function ( $item ) use ( $order ) {
				return array(
					'item_id' => $item->get_id(),
					'item'    => $item,
					'qty'     => $item->get_quantity() - $order->get_qty_refunded_for_item( $item ),
				);
			},
			$order->get_items()
		);

		return array_map(
			function ( $item ) use ( $fulfillment_items ) {
				$item['qty'] = $fulfillment_items[ $item['item_id'] ];
				return $item;
			},
			array_filter(
				$order_items,
				function ( $item ) use ( $fulfillment_items ) {
					return isset( $fulfillment_items[ $item['item_id'] ] );
				}
			)
		);
	}

	/**
	 * Check if an order has pending items.
	 *
	 * @param WC_Order $order The order object.
	 * @param array    $fulfillments An array of fulfillments to check.
	 *
	 * @return bool True if there are pending items, false otherwise.
	 */
	public static function has_pending_items( WC_Order $order, array $fulfillments ): bool {
		$pending_items = self::get_pending_items( $order, $fulfillments );
		return ! empty( $pending_items );
	}

	/**
	 * Get the fulfillment status of the entity. This runs like a computed property, where
	 * it checks the fulfillment status of each fulfillment attached to the order,
	 * and computes the overall fulfillment status of the order.
	 *
	 * @param WC_Order $order The order object.
	 * @param array    $fulfillments An array of fulfillments to check.
	 *
	 * @return string The fulfillment status.
	 */
	public static function get_fulfillment_status( WC_Order $order, array $fulfillments ): string {
		$pending_items    = empty( $fulfillments ) ? array() : self::get_pending_items( $order, $fulfillments );
		$has_fulfillments = ! empty( $fulfillments );
		$all_fulfilled    = true;
		$some_fulfilled   = false;

		if ( $has_fulfillments ) {
			foreach ( $fulfillments as $fulfillment ) {
				if ( ! $fulfillment->get_is_fulfilled() ) {
					$all_fulfilled = false;
				} else {
					$some_fulfilled = true;
				}
			}

			if ( $all_fulfilled && empty( $pending_items ) ) {
				return 'fulfilled';
			} elseif ( $some_fulfilled ) {
				return 'partially_fulfilled';
			} else {
				return 'unfulfilled';
			}
		} else {
			return 'no_fulfillments';
		}
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
				if ( ! isset( $items[ $item['item_id'] ] ) ) {
					$items[ $item['item_id'] ] = 0; // Initialize if not set.
				}
				// Sum the quantities for each item.
				$items[ $item['item_id'] ] += $item['qty'];
			}
		}
		return $items;
	}

	/**
	 * Get the HTML for the fulfillment tracking number.
	 *
	 * @param Fulfillment $fulfillment The fulfillment object.
	 *
	 * @return string The HTML for the tracking number.
	 */
	public static function get_tracking_info_html( Fulfillment $fulfillment ): string {
		$tracking_html   = '';
		$tracking_url    = $fulfillment->get_meta( '_tracking_url', true );
		$tracking_number = $fulfillment->get_meta( '_tracking_number', true );
		if ( ! empty( $tracking_url ) && ! empty( $tracking_number ) ) {
			$tracking_html .= '<a href="' . esc_url( $tracking_url ) . '" target="_blank" rel="noopener noreferrer">';
			$tracking_html .= esc_html( $tracking_number );
			$tracking_html .= '</a>';
		} elseif ( ! empty( $tracking_number ) ) {
			$tracking_html .= esc_html( $tracking_number );
		} else {
			$tracking_html .= '<span class="no-tracking">' . esc_html__( 'No tracking number available', 'woocommerce' ) . '</span>';
		}
		return $tracking_html;
	}

	/**
	 * Get the fulfillment status text for an order.
	 *
	 * @param WC_Order $order The order object.
	 * @param array    $fulfillments An array of fulfillments to check.
	 *
	 * @return string The fulfillment status text.
	 */
	public static function get_order_fulfillment_status_text( WC_Order $order, array $fulfillments ): string {
		$fulfillment_status = self::get_fulfillment_status( $order, $fulfillments );

		switch ( $fulfillment_status ) {
			case 'fulfilled':
				return ' ' . __( 'It has been <mark class="fulfillment-status">Fulfilled</mark>.', 'woocommerce' );
			case 'partially_fulfilled':
				return ' ' . __( 'It has been <mark class="fulfillment-status">Partially fulfilled</mark>.', 'woocommerce' );
			case 'unfulfilled':
				return ' ' . __( 'It is currently <mark class="fulfillment-status">Unfulfilled</mark>.', 'woocommerce' );
			case 'no_fulfillments':
				return ' ' . __( 'It has <mark class="fulfillment-status">no fulfillments</mark> yet.', 'woocommerce' );
			default:
				return '';
		}
	}
}
