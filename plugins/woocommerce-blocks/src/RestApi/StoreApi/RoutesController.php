<?php
/**
 * Register Routes.
 *
 * @internal This API is used internally by Blocks--it is still in flux and may be subject to revisions.
 * @package WooCommerce/Blocks
 */

namespace Automattic\WooCommerce\Blocks\RestApi\StoreApi;

defined( 'ABSPATH' ) || exit;

/**
 * RoutesController class.
 */
class RoutesController {
	/**
	 * Register defined list of routes with WordPress.
	 */
	public static function register_routes() {
		$schemas = [
			'cart'                    => new Schemas\CartSchema(),
			'coupon'                  => new Schemas\CartCouponSchema(),
			'cart-item'               => new Schemas\CartItemSchema(),
			'product-attribute'       => new Schemas\ProductAttributeSchema(),
			'term'                    => new Schemas\TermSchema(),
			'product'                 => new Schemas\ProductSchema(),
			'product-collection-data' => new Schemas\ProductCollectionDataSchema(),
		];

		$routes = [
			new Routes\Cart( $schemas['cart'] ),
			new Routes\CartApplyCoupon( $schemas['cart'] ),
			new Routes\CartCoupons( $schemas['coupon'] ),
			new Routes\CartCouponsByCode( $schemas['coupon'] ),
			new Routes\CartItems( $schemas['cart-item'] ),
			new Routes\CartItemsByKey( $schemas['cart-item'] ),
			new Routes\CartRemoveCoupon( $schemas['cart'] ),
			new Routes\CartRemoveItem( $schemas['cart'] ),
			new Routes\CartSelectShippingRate( $schemas['cart'] ),
			new Routes\CartUpdateItem( $schemas['cart'] ),
			new Routes\CartUpdateShipping( $schemas['cart'] ),
			new Routes\ProductAttributes( $schemas['product-attribute'] ),
			new Routes\ProductAttributesById( $schemas['product-attribute'] ),
			new Routes\ProductAttributeTerms( $schemas['term'] ),
			new Routes\ProductCollectionData( $schemas['product-collection-data'] ),
			new Routes\Products( $schemas['product'] ),
			new Routes\ProductsById( $schemas['product'] ),
		];

		foreach ( $routes as $route ) {
			register_rest_route(
				$route->get_namespace(),
				$route->get_path(),
				$route->get_args()
			);
		}
	}
}
