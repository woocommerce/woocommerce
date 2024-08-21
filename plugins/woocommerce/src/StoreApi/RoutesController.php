<?php
namespace Automattic\WooCommerce\StoreApi;

use Automattic\WooCommerce\StoreApi\Routes\V1\AbstractRoute;

/**
 * RoutesController class.
 */
class RoutesController {
	/**
	 * Stores schema_controller.
	 *
	 * @var SchemaController
	 */
	protected $schema_controller;

	/**
	 * Stores routes.
	 *
	 * @var array
	 */
	protected $routes = [];

	/**
	 * Namespace for the API.
	 *
	 * @var string
	 */
	private static $api_namespace = 'wc/store';

	/**
	 * Constructor.
	 *
	 * @param SchemaController $schema_controller Schema controller class passed to each route.
	 */
	public function __construct( SchemaController $schema_controller ) {
		$this->schema_controller = $schema_controller;
		$this->routes            = [
			'v1'      => [
				Routes\V1\Batch::IDENTIFIER              => Routes\V1\Batch::class,
				Routes\V1\Cart::IDENTIFIER               => Routes\V1\Cart::class,
				Routes\V1\CartAddItem::IDENTIFIER        => Routes\V1\CartAddItem::class,
				Routes\V1\CartApplyCoupon::IDENTIFIER    => Routes\V1\CartApplyCoupon::class,
				Routes\V1\CartCoupons::IDENTIFIER        => Routes\V1\CartCoupons::class,
				Routes\V1\CartCouponsByCode::IDENTIFIER  => Routes\V1\CartCouponsByCode::class,
				Routes\V1\CartExtensions::IDENTIFIER     => Routes\V1\CartExtensions::class,
				Routes\V1\CartItems::IDENTIFIER          => Routes\V1\CartItems::class,
				Routes\V1\CartItemsByKey::IDENTIFIER     => Routes\V1\CartItemsByKey::class,
				Routes\V1\CartRemoveCoupon::IDENTIFIER   => Routes\V1\CartRemoveCoupon::class,
				Routes\V1\CartRemoveItem::IDENTIFIER     => Routes\V1\CartRemoveItem::class,
				Routes\V1\CartSelectShippingRate::IDENTIFIER => Routes\V1\CartSelectShippingRate::class,
				Routes\V1\CartUpdateItem::IDENTIFIER     => Routes\V1\CartUpdateItem::class,
				Routes\V1\CartUpdateCustomer::IDENTIFIER => Routes\V1\CartUpdateCustomer::class,
				Routes\V1\Checkout::IDENTIFIER           => Routes\V1\Checkout::class,
				Routes\V1\CheckoutOrder::IDENTIFIER      => Routes\V1\CheckoutOrder::class,
				Routes\V1\Order::IDENTIFIER              => Routes\V1\Order::class,
				Routes\V1\ProductAttributes::IDENTIFIER  => Routes\V1\ProductAttributes::class,
				Routes\V1\ProductAttributesById::IDENTIFIER => Routes\V1\ProductAttributesById::class,
				Routes\V1\ProductAttributeTerms::IDENTIFIER => Routes\V1\ProductAttributeTerms::class,
				Routes\V1\ProductCategories::IDENTIFIER  => Routes\V1\ProductCategories::class,
				Routes\V1\ProductCategoriesById::IDENTIFIER => Routes\V1\ProductCategoriesById::class,
				Routes\V1\ProductCollectionData::IDENTIFIER => Routes\V1\ProductCollectionData::class,
				Routes\V1\ProductReviews::IDENTIFIER     => Routes\V1\ProductReviews::class,
				Routes\V1\ProductTags::IDENTIFIER        => Routes\V1\ProductTags::class,
				Routes\V1\Products::IDENTIFIER           => Routes\V1\Products::class,
				Routes\V1\ProductsById::IDENTIFIER       => Routes\V1\ProductsById::class,
				Routes\V1\ProductsBySlug::IDENTIFIER     => Routes\V1\ProductsBySlug::class,
			],
			// @todo Migrate internal AI routes to WooCommerce Core codebase.
			'private' => [
				Routes\V1\AI\Products::IDENTIFIER => Routes\V1\AI\Products::class,
				Routes\V1\Patterns::IDENTIFIER    => Routes\V1\Patterns::class,
			],
		];
	}

	/**
	 * Register all Store API routes. This includes routes under specific version namespaces.
	 */
	public function register_all_routes() {
		$this->register_routes( 'v1', self::$api_namespace );
		$this->register_routes( 'v1', self::$api_namespace . '/v1' );
		$this->register_routes( 'private', 'wc/private' );
	}

	/**
	 * Get a route class instance.
	 *
	 * Each route class is instantized with the SchemaController instance, and its main Schema Type.
	 *
	 * @throws \Exception If the schema does not exist.
	 * @param string $name Name of schema.
	 * @param string $version API Version being requested.
	 * @return AbstractRoute
	 */
	public function get( $name, $version = 'v1' ) {
		$route = $this->routes[ $version ][ $name ] ?? false;

		if ( ! $route ) {
			throw new \Exception( "{$name} {$version} route does not exist" );
		}

		return new $route(
			$this->schema_controller,
			$this->schema_controller->get( $route::SCHEMA_TYPE, $route::SCHEMA_VERSION )
		);
	}

	/**
	 * Get a route path without instantiating the corresponding RoutesController object.
	 *
	 * @throws \Exception If the schema does not exist.
	 *
	 * @param string $version API Version being requested.
	 * @param string $controller Whether to return controller name. If false, returns empty array. Note:
	 * When $controller param is true, the output should not be used directly in front-end code, to prevent class names from leaking. It's not a security issue necessarily, but it's not a good practice.
	 * When $controller param is false, it currently returns and empty array. But it can be modified in future to return include more details about the route info that can be used in frontend.
	 *
	 * @return string[] List of route paths.
	 */
	public function get_all_routes( $version = 'v1', $controller = false ) {
		$routes = array();

		foreach ( $this->routes[ $version ] as $key => $route_class ) {

			if ( ! method_exists( $route_class, 'get_path_regex' ) ) {
				throw new \Exception( esc_html( "{$route_class} route does not have a get_path_regex method" ) );
			}

			$route_path = '/' . trailingslashit( self::$api_namespace ) . $version . $route_class::get_path_regex();

			$routes[ $route_path ] = $controller ? $route_class : array();
		}

		return $routes;
	}

	/**
	 * Register defined list of routes with WordPress.
	 *
	 * @param string $version API Version being registered..
	 * @param string $namespace Overrides the default route namespace.
	 */
	protected function register_routes( $version = 'v1', $namespace = 'wc/store/v1' ) {
		if ( ! isset( $this->routes[ $version ] ) ) {
			return;
		}
		$route_identifiers = array_keys( $this->routes[ $version ] );
		foreach ( $route_identifiers as $route ) {
			$route_instance = $this->get( $route, $version );
			$route_instance->set_namespace( $namespace );

			register_rest_route(
				$route_instance->get_namespace(),
				$route_instance->get_path(),
				$route_instance->get_args()
			);
		}
	}
}
