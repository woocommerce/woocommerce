<?php
declare( strict_types=1 );

namespace Automattic\WooCommerce\StoreApi\Utilities;

use Automattic\WooCommerce\StoreApi\Exceptions\RouteException;

/**
 * The single-item add-to-cart pipeline of the web cart/add-item route,
 * extracted so alternative routes (e.g. the internal POS cart/add-items)
 * compose the identical code path — request filter, cart controller add and
 * post-add action — instead of duplicating it. Behaviour-preserving move
 * from Routes\V1\CartAddItem::get_route_post_response(); the response
 * shaping stays with each route.
 *
 * Consumers must provide $cart_controller (AbstractCartRoute does).
 *
 * @internal Just for internal use.
 */
trait AddItemToCartTrait {

	/**
	 * Add one item from a single-item-shaped request to the cart.
	 *
	 * @throws RouteException On error.
	 * @param \WP_REST_Request $request Request object with top-level id/quantity/variation params.
	 * @phpstan-param \WP_REST_Request<array<string, mixed>> $request
	 * @return string The cart item key of the added item.
	 */
	protected function add_item_to_cart_from_request( \WP_REST_Request $request ) {
		// Do not allow key to be specified during creation.
		if ( ! empty( $request['key'] ) ) {
			throw new RouteException( 'woocommerce_rest_cart_item_exists', esc_html__( 'Cannot create an existing cart item.', 'woocommerce' ), 400 );
		}

		/**
		 * Filters cart item data sent via the API before it is passed to the cart controller.
		 *
		 * This hook filters cart items. It allows the request data to be changed, for example, quantity, or
		 * supplemental cart item data, before it is passed into CartController::add_to_cart and stored to session.
		 *
		 * CartController::add_to_cart only expects the keys id, quantity, variation, and cart_item_data, so other values
		 * may be ignored. CartController::add_to_cart (and core) do already have a filter hook called
		 * woocommerce_add_cart_item, but this does not have access to the original Store API request like this hook does.
		 *
		 * @since 8.8.0
		 *
		 * @param array $add_to_cart_data An array of cart item data.
		 * @return array
		 */
		$add_to_cart_data = apply_filters(
			'woocommerce_store_api_add_to_cart_data',
			array(
				'id'             => $request['id'],
				'quantity'       => $request['quantity'],
				'variation'      => $request['variation'],
				'cart_item_data' => array(),
			),
			$request
		);

		$item_id   = $this->cart_controller->add_to_cart( $add_to_cart_data );
		$cart      = $this->cart_controller->get_cart_instance();
		$cart_item = $cart->get_cart_item( $item_id );

		if ( ! empty( $cart_item ) ) {
			$product_id = $cart_item['variation_id'] ? $cart_item['variation_id'] : $cart_item['product_id'];
			$quantity   = $add_to_cart_data['quantity'] ?? $cart_item['quantity'];

			/**
			 * Fires when an item is added to the cart from a user request.
			 *
			 * @param int       $product_id Product ID (variation ID for variable products).
			 * @param int|float $quantity   Quantity added to the cart.
			 *
			 * @since 10.6.0
			 */
			do_action( 'internal_woocommerce_cart_item_added_from_user_request', $product_id, $quantity );
		}

		return (string) $item_id;
	}
}
