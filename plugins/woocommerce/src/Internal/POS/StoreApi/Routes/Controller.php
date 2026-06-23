<?php
/**
 * Controller class file.
 */

declare( strict_types = 1 );

namespace Automattic\WooCommerce\Internal\POS\StoreApi\Routes;

use Automattic\WooCommerce\Internal\RegisterHooksInterface;
use Automattic\WooCommerce\StoreApi\SchemaController;
use Automattic\WooCommerce\StoreApi\StoreApi;
use Automattic\WooCommerce\Utilities\FeaturesUtil;

/**
 * Registers POS Store API REST routes.
 *
 * Routes live under the `wc/internal/pos/v1` namespace, separate from both
 * `wc/store/v1` and the public `wc/pos/v1` (used by the POS catalog feed). The
 * `internal` segment is deliberate: the cart/checkout shape is still a spike and
 * not a committed public contract, and keeping it out of `wc/store/v1` means no
 * Store API request middleware (rate limiting, CORS, the checkout opt-in — all
 * keyed on `wc/store/` in {@see \Automattic\WooCommerce\StoreApi\Authentication})
 * applies unless we add it explicitly. Registration is gated on the
 * `point_of_sale` feature so the routes exist exactly when POS does.
 *
 * Each POS route is adapter-style: it extends the abstract
 * {@see \Automattic\WooCommerce\StoreApi\Routes\V1\AbstractCartRoute} (the
 * designed extension point, the same one agentic commerce builds on) and owns
 * its own endpoint shape, delegating the cart/checkout work to the shared
 * controllers and traits. Because each route declares its full `get_args()`
 * (auth callback, the `cart_token` parameter, schema relaxations and all), this
 * Controller is a thin register loop — there is no registration-time argument
 * rewriting.
 *
 * Adding a route: write the route class and add one entry to {@see ROUTE_CLASSES}.
 *
 * @internal Just for internal use.
 *
 * @since 10.9.0
 */
class Controller implements RegisterHooksInterface {

	/**
	 * REST namespace for POS routes.
	 */
	public const REST_NAMESPACE = 'wc/internal/pos/v1';

	/**
	 * POS route classes to register.
	 *
	 * @var string[]
	 */
	private const ROUTE_CLASSES = array(
		CartAddItem::class,
		CartApplyCoupon::class,
		CartAddFee::class,
		Checkout::class,
	);

	/**
	 * Register hooks.
	 *
	 * Gated on the `point_of_sale` feature so the POS Store API routes are only
	 * registered when POS is enabled. {@see self::register_routes()} stays
	 * ungated so tests can register the routes directly without toggling the
	 * feature.
	 */
	public function register(): void {
		if ( ! FeaturesUtil::feature_is_enabled( 'point_of_sale' ) ) {
			return;
		}
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
