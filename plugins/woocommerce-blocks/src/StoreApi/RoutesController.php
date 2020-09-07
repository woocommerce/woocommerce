<?php
namespace Automattic\WooCommerce\Blocks\StoreApi;

use Routes\AbstractRoute;

/**
 * RoutesController class.
 *
 * @internal This API is used internally by Blocks--it is still in flux and may be subject to revisions.
 */
class RoutesController {

	/**
	 * Stores schemas.
	 *
	 * @var SchemaController
	 */
	protected $schemas;

	/**
	 * Stores routes.
	 *
	 * @var AbstractRoute[]
	 */
	protected $routes = [];

	/**
	 * Constructor.
	 *
	 * @param SchemaController $schemas Schema controller class passed to each route.
	 */
	public function __construct( SchemaController $schemas ) {
		$this->schemas = $schemas;
		$this->initialize();
	}

	/**
	 * Get a route class instance.
	 *
	 * @throws Exception If the schema does not exist.
	 *
	 * @param string $name Name of schema.
	 * @return AbstractRoute
	 */
	public function get( $name ) {
		if ( ! isset( $this->routes[ $name ] ) ) {
			throw new Exception( $name . ' route does not exist' );
		}
		return $this->routes[ $name ];
	}

	/**
	 * Register defined list of routes with WordPress.
	 */
	public function register_routes() {
		foreach ( $this->routes as $route ) {
			register_rest_route(
				$route->get_namespace(),
				$route->get_path(),
				$route->get_args()
			);
		}
	}

	/**
	 * Load route class instances.
	 */
	protected function initialize() {
		$this->routes = [
			'cart'                      => new Routes\Cart( $this->schemas->get( 'cart' ) ),
			'cart-add-item'             => new Routes\CartAddItem( $this->schemas->get( 'cart' ) ),
			'cart-apply-coupon'         => new Routes\CartApplyCoupon( $this->schemas->get( 'cart' ) ),
			'cart-coupons'              => new Routes\CartCoupons( $this->schemas->get( 'cart-coupon' ) ),
			'cart-coupons-by-code'      => new Routes\CartCouponsByCode( $this->schemas->get( 'cart-coupon' ) ),
			'cart-items'                => new Routes\CartItems( $this->schemas->get( 'cart-item' ) ),
			'cart-items-by-key'         => new Routes\CartItemsByKey( $this->schemas->get( 'cart-item' ) ),
			'cart-remove-coupon'        => new Routes\CartRemoveCoupon( $this->schemas->get( 'cart' ) ),
			'cart-remove-item'          => new Routes\CartRemoveItem( $this->schemas->get( 'cart' ) ),
			'cart-select-shipping-rate' => new Routes\CartSelectShippingRate( $this->schemas->get( 'cart' ) ),
			'cart-update-item'          => new Routes\CartUpdateItem( $this->schemas->get( 'cart' ) ),
			'cart-update-shipping'      => new Routes\CartUpdateShipping( $this->schemas->get( 'cart' ) ),
			'checkout'                  => new Routes\Checkout( $this->schemas->get( 'checkout' ) ),
			'product-attributes'        => new Routes\ProductAttributes( $this->schemas->get( 'product-attribute' ) ),
			'product-attributes-by-id'  => new Routes\ProductAttributesById( $this->schemas->get( 'product-attribute' ) ),
			'product-attribute-terms'   => new Routes\ProductAttributeTerms( $this->schemas->get( 'term' ) ),
			'product-categories'        => new Routes\ProductCategories( $this->schemas->get( 'product-category' ) ),
			'product-categories-by-id'  => new Routes\ProductCategoriesById( $this->schemas->get( 'product-category' ) ),
			'product-collection-data'   => new Routes\ProductCollectionData( $this->schemas->get( 'product-collection-data' ) ),
			'product-reviews'           => new Routes\ProductReviews( $this->schemas->get( 'product-review' ) ),
			'product-tags'              => new Routes\ProductTags( $this->schemas->get( 'term' ) ),
			'products'                  => new Routes\Products( $this->schemas->get( 'product' ) ),
			'products-by-id'            => new Routes\ProductsById( $this->schemas->get( 'product' ) ),
		];
	}
}
