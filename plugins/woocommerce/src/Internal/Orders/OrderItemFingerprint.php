<?php
/**
 * OrderItemFingerprint class file.
 */

declare( strict_types = 1 );

namespace Automattic\WooCommerce\Internal\Orders;

use Automattic\WooCommerce\Enums\OrderItemType;
use WC_Order_Item;

/**
 * Fingerprints the items a checkout created from the cart, so a retried order can tell which ones changed since.
 * Used by the classic checkout and the Store API.
 *
 * @internal Just for internal use.
 *
 * @since 11.5.0
 */
final class OrderItemFingerprint {

	/**
	 * Order item types that checkout creates from the cart.
	 *
	 * @var string[]
	 */
	public const CART_ITEM_TYPES = array(
		OrderItemType::LINE_ITEM,
		OrderItemType::FEE,
		OrderItemType::SHIPPING,
		OrderItemType::TAX,
		OrderItemType::COUPON,
	);

	/**
	 * Hashes an item's stored amounts and properties. Names and meta are left out, so renaming an item or adding meta
	 * to it, as gateways do, doesn't count as a change.
	 *
	 * @param WC_Order_Item $item         The order item.
	 * @param string[]      $ignored_keys Other data keys to leave out.
	 * @return string
	 */
	public static function get( WC_Order_Item $item, array $ignored_keys = array() ): string {
		// Include unsaved changes, so an item can be compared before and after it's saved.
		$data = array_replace_recursive( $item->get_data(), $item->get_changes() );
		$data = array_diff_key( $data, array_flip( array_merge( array( 'id', 'order_id', 'name', 'meta_data' ), $ignored_keys ) ) );

		return md5( (string) wp_json_encode( self::sort_keys_recursively( $data ) ) );
	}

	/**
	 * Whether every fingerprinted item is still on the order with the same fingerprint.
	 *
	 * @param WC_Order_Item[]    $order_items  The order's items of one type, keyed by ID.
	 * @param array<int, string> $fingerprints Fingerprints of the items the cart created, keyed by ID.
	 * @param string[]           $ignored_keys Data keys the fingerprints left out.
	 * @return bool
	 */
	public static function items_match( array $order_items, array $fingerprints, array $ignored_keys = array() ): bool {
		foreach ( $fingerprints as $item_id => $fingerprint ) {
			if ( ! isset( $order_items[ $item_id ] ) || self::get( $order_items[ $item_id ], $ignored_keys ) !== (string) $fingerprint ) {
				return false;
			}
		}

		return true;
	}

	/**
	 * Sorts an array's keys at every level, since saving an item can reorder nested keys such as its taxes.
	 *
	 * @param array $data The array to sort.
	 * @return array
	 */
	private static function sort_keys_recursively( array $data ): array {
		ksort( $data );
		foreach ( $data as $key => $value ) {
			if ( is_array( $value ) ) {
				$data[ $key ] = self::sort_keys_recursively( $value );
			}
		}

		return $data;
	}
}
