<?php
declare( strict_types = 1 );

namespace Automattic\WooCommerce\StoreApi\Routes\V1\SavedLists;

use Automattic\WooCommerce\StoreApi\Exceptions\RouteException;
use Automattic\WooCommerce\StoreApi\Routes\V1\AbstractCartRoute;
use Automattic\WooCommerce\StoreApi\Schemas\V1\SavedListItemSchema;
use Automattic\WooCommerce\StoreApi\Utilities\SavedListsStore;

/**
 * SaveForLaterItems route.
 *
 * Copies a cart line item's product / variation / quantity / cart_item_data into the current
 * user's save-for-later list (user meta). This route does NOT mutate the cart — the client is
 * expected to remove the item itself via the existing cart removal flow so the optimistic UI
 * and @wordpress/data cart store stay the source of truth for cart state.
 */
class SaveForLaterItems extends AbstractCartRoute {
	/**
	 * The route identifier.
	 *
	 * @var string
	 */
	const IDENTIFIER = 'saved-lists-save-for-later-items';

	/**
	 * Get the path of this REST route.
	 *
	 * @return string
	 */
	public function get_path() {
		return self::get_path_regex();
	}

	/**
	 * Get the path of this rest route.
	 *
	 * @return string
	 */
	public static function get_path_regex() {
		return '/saved-lists/save-for-later/items';
	}

	/**
	 * Get method arguments for this REST route.
	 *
	 * @return array
	 */
	public function get_args() {
		return array(
			array(
				'methods'             => \WP_REST_Server::CREATABLE,
				'callback'            => array( $this, 'get_response' ),
				'permission_callback' => array( $this, 'check_user_logged_in' ),
				'args'                => array(
					'cart_item_key' => array(
						'description' => __( 'Key of the cart item to move into the save-for-later list.', 'woocommerce' ),
						'type'        => 'string',
						'required'    => true,
					),
				),
			),
			'schema'      => array( $this->schema, 'get_public_item_schema' ),
			'allow_batch' => array( 'v1' => true ),
		);
	}

	/**
	 * Gate access to logged-in users only.
	 *
	 * @return bool
	 */
	public function check_user_logged_in() {
		return is_user_logged_in();
	}

	/**
	 * Copy a cart item into the save-for-later list. The cart itself is left untouched — the
	 * caller is responsible for removing the item via the cart store API.
	 *
	 * @throws RouteException If the cart item does not exist.
	 * @param \WP_REST_Request $request Request object.
	 * @phpstan-param \WP_REST_Request<array<string, mixed>> $request
	 * @return \WP_REST_Response
	 */
	protected function get_route_post_response( \WP_REST_Request $request ) {
		$cart_item_key = (string) $request['cart_item_key'];
		$cart_item     = $this->cart_controller->get_cart_item( $cart_item_key );

		if ( empty( $cart_item ) ) {
			throw new RouteException(
				'woocommerce_rest_cart_invalid_key',
				esc_html__( 'Cart item no longer exists or is invalid.', 'woocommerce' ),
				409
			);
		}

		$store      = new SavedListsStore();
		$saved_item = $store->add_item(
			SavedListsStore::SAVE_FOR_LATER,
			array(
				'product_id'     => (int) ( $cart_item['product_id'] ?? 0 ),
				'variation_id'   => (int) ( $cart_item['variation_id'] ?? 0 ),
				'quantity'       => (int) ( $cart_item['quantity'] ?? 1 ),
				'variation'      => (array) ( $cart_item['variation'] ?? array() ),
				'cart_item_data' => $this->extract_cart_item_data( $cart_item ),
			)
		);

		$item_schema = $this->schema_controller->get( SavedListItemSchema::IDENTIFIER );
		return rest_ensure_response( $item_schema->get_item_response( $saved_item ) );
	}

	/**
	 * Extract the plugin-added cart item data from a cart item, excluding the runtime fields
	 * WooCommerce adds during add_to_cart (product, key, quantity, variation, ids, data_hash).
	 * This is what must round-trip through user meta so third-party customizations survive the
	 * save → restore cycle.
	 *
	 * @param array $cart_item Cart item as returned by WC()->cart->get_cart().
	 * @return array
	 */
	protected function extract_cart_item_data( array $cart_item ): array {
		$reserved = array( 'key', 'product_id', 'variation_id', 'variation', 'quantity', 'data', 'data_hash', 'line_tax_data', 'line_subtotal', 'line_subtotal_tax', 'line_total', 'line_tax' );
		return array_diff_key( $cart_item, array_flip( $reserved ) );
	}
}
