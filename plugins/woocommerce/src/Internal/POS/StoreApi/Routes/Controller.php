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
 * Store API middleware (rate limiting, the cart-token session swap, …) applies
 * unless we add it explicitly. Registration is gated on the `point_of_sale`
 * feature so the routes exist exactly when POS does — there is no separate
 * enablement step for the mobile app.
 *
 * Each POS route subclasses the corresponding Store API concrete route
 * (mirroring the agentic commerce pattern), so it reuses the Store API schema,
 * validation and response pipeline unchanged. The route subclasses are
 * deliberately near-empty: the POS-specific endpoint-shape changes are applied
 * here in {@see self::apply_pos_endpoint_overrides()} at registration time, and
 * the runtime behaviour changes live in the POS policy hooks. Keeping the
 * divergence in those two places (and out of the route bodies) means there is
 * one obvious spot to reason about how POS differs from the web Store API, and
 * less surface to drift if the web routes are refactored.
 *
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
	public const REST_NAMESPACE = 'wc/internal/pos/v1';

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
				$this->apply_pos_endpoint_overrides( $route, $route->get_args() )
			);
		}
	}

	/**
	 * Apply the POS-specific endpoint-shape changes to a route's arguments at
	 * registration time, keeping every divergence from the web Store API in one
	 * place rather than scattered across route subclasses.
	 *
	 * For each endpoint definition (the int-keyed entries; the string-keyed
	 * `schema`/`allow_batch` metadata is left untouched) this:
	 *
	 * - swaps the permission callback for the route's capability check;
	 * - adds the `cart_token` URL parameter so mobile clients can carry the cart
	 *   session without a custom header;
	 * - relaxes the schema-level `required` flag on billing/shipping address so
	 *   POS can submit empty addresses at parse time (the deeper address
	 *   validation is relaxed separately by the POS policy hooks). This is a
	 *   no-op for routes without those args.
	 *
	 * @param object $route     The POS route instance (uses {@see PosRouteTrait}).
	 * @param array  $endpoints Result of the route's `get_args()`.
	 * @return array
	 */
	private function apply_pos_endpoint_overrides( object $route, array $endpoints ): array {
		foreach ( $endpoints as $key => &$endpoint ) {
			if ( ! is_int( $key ) || ! is_array( $endpoint ) || ! isset( $endpoint['methods'] ) ) {
				continue;
			}

			$endpoint['permission_callback'] = array( $route, 'check_permission' );
			$endpoint['args']                = array_merge(
				$endpoint['args'] ?? array(),
				array(
					'cart_token' => array(
						'description' => __( 'Cart session token returned by a prior POS Store API response. Pass it back on subsequent requests to keep the cart scoped to the same transaction.', 'woocommerce' ),
						'type'        => 'string',
						'context'     => array( 'view', 'edit' ),
					),
				)
			);

			foreach ( array( 'billing_address', 'shipping_address' ) as $address_arg ) {
				if ( isset( $endpoint['args'][ $address_arg ] ) && is_array( $endpoint['args'][ $address_arg ] ) ) {
					$endpoint['args'][ $address_arg ]['required'] = false;
				}
			}
		}
		unset( $endpoint );

		return $endpoints;
	}
}
