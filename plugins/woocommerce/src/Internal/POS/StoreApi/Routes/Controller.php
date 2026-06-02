<?php
/**
 * Controller class file.
 */

declare( strict_types = 1 );

namespace Automattic\WooCommerce\Internal\POS\StoreApi\Routes;

use Automattic\WooCommerce\Internal\RegisterHooksInterface;
use Automattic\WooCommerce\StoreApi\SchemaController;
use Automattic\WooCommerce\StoreApi\StoreApi;

/**
 * Registers POS Store API REST routes.
 *
 * Routes live under the `wc/pos/v1` namespace, separate from `wc/store/v1`.
 * Each POS route subclasses the corresponding Store API concrete route
 * (mirroring the agentic commerce pattern), so it reuses the Store API schema,
 * validation and response shape while overriding only what's POS-specific.
 * Adding a route: write the subclass and add one entry to {@see ROUTE_CLASSES}.
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
	 * @var string[]
	 */
	private const ROUTE_CLASSES = array(
		CartAddItem::class,
		CartApplyCoupon::class,
		Checkout::class,
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
		$schema_controller = StoreApi::container()->get( SchemaController::class );

		foreach ( self::ROUTE_CLASSES as $route_class ) {
			$route = new $route_class(
				$schema_controller,
				$schema_controller->get( $route_class::SCHEMA_TYPE, $route_class::SCHEMA_VERSION )
			);

			register_rest_route(
				self::REST_NAMESPACE,
				$route->get_path(),
				$route->get_args()
			);
		}
	}
}
