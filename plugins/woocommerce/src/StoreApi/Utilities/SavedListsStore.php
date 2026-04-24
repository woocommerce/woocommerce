<?php
declare( strict_types = 1 );

namespace Automattic\WooCommerce\StoreApi\Utilities;

use Automattic\WooCommerce\StoreApi\Exceptions\RouteException;

/**
 * SavedListsStore class.
 *
 * Reads and writes user-scoped saved lists (e.g. save-for-later) backed by user meta.
 * Each list is stored under its own meta key so unrelated list types do not contend on
 * writes and can be deleted independently.
 */
class SavedListsStore {

	/**
	 * Save-for-later list identifier.
	 */
	public const SAVE_FOR_LATER = 'save-for-later';

	/**
	 * Meta key prefix for saved lists.
	 */
	private const META_KEY_PREFIX = '_wc_saved_list_';

	/**
	 * Get the user meta key used to store a given list for the current site.
	 *
	 * @param string $list_id List identifier (e.g. 'save-for-later').
	 * @return string
	 */
	protected function get_meta_key( string $list_id ): string {
		return self::META_KEY_PREFIX . str_replace( '-', '_', $list_id ) . '_' . get_current_blog_id();
	}

	/**
	 * Resolve the current user ID, throwing if no user is logged in.
	 *
	 * @throws RouteException If the user is not logged in.
	 * @return int
	 */
	protected function require_user_id(): int {
		$user_id = get_current_user_id();
		if ( 0 === $user_id ) {
			throw new RouteException(
				'woocommerce_rest_saved_list_not_logged_in',
				esc_html__( 'You must be logged in to use saved lists.', 'woocommerce' ),
				401
			);
		}
		return $user_id;
	}

	/**
	 * Retrieve all items in a saved list for the current user, keyed by item key.
	 *
	 * @throws RouteException If the user is not logged in.
	 * @param string $list_id List identifier.
	 * @return array<string, array>
	 */
	public function get_items( string $list_id ): array {
		$items = get_user_meta( $this->require_user_id(), $this->get_meta_key( $list_id ), true );
		return is_array( $items ) ? $items : array();
	}

	/**
	 * Retrieve a single saved item by key, or null if it does not exist.
	 *
	 * @throws RouteException If the user is not logged in.
	 * @param string $list_id List identifier.
	 * @param string $key     Item key.
	 * @return array|null
	 */
	public function get_item( string $list_id, string $key ): ?array {
		$items = $this->get_items( $list_id );
		return $items[ $key ] ?? null;
	}

	/**
	 * Add an item to a saved list.
	 *
	 * If an equivalent item (same product, variation, and cart item data) already exists,
	 * its quantity is increased. Otherwise a new entry is created with a freshly generated key.
	 *
	 * @throws RouteException If the user is not logged in.
	 * @param string $list_id   List identifier.
	 * @param array  $item_data Item data. Accepted keys: product_id, variation_id, quantity, variation, cart_item_data.
	 * @return array The stored item, including its generated key.
	 */
	public function add_item( string $list_id, array $item_data ): array {
		$item_data = wp_parse_args(
			$item_data,
			array(
				'product_id'     => 0,
				'variation_id'   => 0,
				'quantity'       => 1,
				'variation'      => array(),
				'cart_item_data' => array(),
			)
		);

		$items = $this->get_items( $list_id );
		$key   = $this->generate_key( $item_data );

		if ( isset( $items[ $key ] ) ) {
			$items[ $key ]['quantity'] += (int) $item_data['quantity'];
		} else {
			$items[ $key ] = array(
				'key'            => $key,
				'product_id'     => (int) $item_data['product_id'],
				'variation_id'   => (int) $item_data['variation_id'],
				'quantity'       => (int) $item_data['quantity'],
				'variation'      => (array) $item_data['variation'],
				'cart_item_data' => (array) $item_data['cart_item_data'],
				'saved_at'       => time(),
			);
		}

		$this->save_items( $list_id, $items );

		return $items[ $key ];
	}

	/**
	 * Remove an item from a saved list.
	 *
	 * @throws RouteException If the user is not logged in or the item does not exist.
	 * @param string $list_id List identifier.
	 * @param string $key     Item key.
	 */
	public function remove_item( string $list_id, string $key ): void {
		$items = $this->get_items( $list_id );

		if ( ! isset( $items[ $key ] ) ) {
			throw new RouteException(
				'woocommerce_rest_saved_list_invalid_key',
				esc_html__( 'Saved item does not exist.', 'woocommerce' ),
				404
			);
		}

		unset( $items[ $key ] );
		$this->save_items( $list_id, $items );
	}

	/**
	 * Persist the full item list to user meta.
	 *
	 * @throws RouteException If the user is not logged in.
	 * @param string               $list_id List identifier.
	 * @param array<string, array> $items   Items keyed by item key.
	 */
	protected function save_items( string $list_id, array $items ): void {
		update_user_meta( $this->require_user_id(), $this->get_meta_key( $list_id ), $items );
	}

	/**
	 * Generate a stable 32-character key from item identity. Uses the same shape as WooCommerce
	 * cart item keys (md5) so equivalent items produce the same key and can merge on quantity.
	 *
	 * @param array $item_data Item data.
	 * @return string
	 */
	protected function generate_key( array $item_data ): string {
		return md5(
			(string) ( $item_data['product_id'] ?? 0 )
			. '|' . (string) ( $item_data['variation_id'] ?? 0 )
			. '|' . wp_json_encode( $item_data['variation'] ?? array() )
			. '|' . wp_json_encode( $item_data['cart_item_data'] ?? array() )
		);
	}
}
