<?php
/**
 * Controller class file.
 */

declare( strict_types = 1 );

namespace Automattic\WooCommerce\Internal\POS\StoreApi\Routes;

use Automattic\WooCommerce\Internal\RegisterHooksInterface;
use Automattic\WooCommerce\StoreApi\RoutesController as StoreApiRoutesController;
use Automattic\WooCommerce\StoreApi\StoreApi;

/**
 * Registers POS Store API REST routes.
 *
 * Routes live under the `wc/pos/v1` namespace, intentionally separate from
 * `wc/store/v1`. Each POS route wraps a Store API delegate (see
 * {@see AbstractRoute}); this controller is responsible for:
 *
 *   - Maintaining the route → Store API identifier mapping.
 *   - Resolving Store API delegates via the Store API DI container.
 *   - Calling register_rest_route for each at the right WP lifecycle moment.
 *
 * Adding a new POS route means: writing the route class (typically just a
 * subclass of AbstractRoute with a STORE_API_IDENTIFIER constant) and
 * adding one entry to {@see ROUTE_CLASSES}.
 *
 * @internal Just for internal use.
 *
 * @since 10.9.0
 */
class Controller implements RegisterHooksInterface {

	/**
	 * REST namespace for POS routes.
	 */
	public const REST_NAMESPACE = 'wc/pos/v1';

	/**
	 * POS route classes to register.
	 *
	 * Each must extend AbstractRoute and declare STORE_API_IDENTIFIER.
	 *
	 * @var string[]
	 */
	private const ROUTE_CLASSES = array(
		CartAddItem::class,
	);

	/**
	 * Register hooks.
	 */
	public function register(): void {
		add_action( 'rest_api_init', array( $this, 'register_routes' ) );
	}

	/**
	 * Register all POS routes with WordPress.
	 *
	 * @internal For exclusive usage within this class, backwards compatibility not guaranteed.
	 */
	public function register_routes(): void {
		$store_api_routes = StoreApi::container()->get( StoreApiRoutesController::class );

		foreach ( self::ROUTE_CLASSES as $route_class ) {
			$route = new $route_class( $store_api_routes->get( $route_class::STORE_API_IDENTIFIER ) );

			register_rest_route(
				self::REST_NAMESPACE,
				$route->get_path(),
				$route->get_args()
			);
		}
	}
}
