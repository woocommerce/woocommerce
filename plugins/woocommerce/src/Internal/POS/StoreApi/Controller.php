<?php
/**
 * Controller class file.
 */

declare( strict_types = 1 );

namespace Automattic\WooCommerce\Internal\POS\StoreApi;

use Automattic\WooCommerce\Internal\POS\StoreApi\PolicyHooks\CartPersistencePolicy;
use Automattic\WooCommerce\Internal\POS\StoreApi\PolicyHooks\CheckoutPolicy;
use Automattic\WooCommerce\Internal\POS\StoreApi\PolicyHooks\CurrentUserSwap;
use Automattic\WooCommerce\Internal\POS\StoreApi\PolicyHooks\CustomFeesPolicy;
use Automattic\WooCommerce\Internal\POS\StoreApi\PolicyHooks\CustomerAccountPolicy;
use Automattic\WooCommerce\Internal\POS\StoreApi\PolicyHooks\CustomerSwap;
use Automattic\WooCommerce\Internal\POS\StoreApi\PolicyHooks\SessionHandlerSwap;
use Automattic\WooCommerce\Internal\POS\StoreApi\PolicyHooks\ShippingPolicy;
use Automattic\WooCommerce\Internal\POS\StoreApi\PolicyHooks\StockPolicy;
use Automattic\WooCommerce\Internal\POS\StoreApi\PolicyHooks\TaxLocationPolicy;
use Automattic\WooCommerce\Internal\POS\StoreApi\Routes\CartAddFee;
use Automattic\WooCommerce\Internal\POS\StoreApi\Routes\CartAddItems;
use Automattic\WooCommerce\Internal\POS\StoreApi\Routes\CartApplyCoupon;
use Automattic\WooCommerce\Internal\POS\StoreApi\Routes\Checkout;
use Automattic\WooCommerce\Internal\POS\StoreApi\Schemas\AddItemsSchema;
use Automattic\WooCommerce\Internal\RegisterHooksInterface;
use Automattic\WooCommerce\StoreApi\Schemas\ExtendSchema;
use Automattic\WooCommerce\StoreApi\SchemaController;
use Automattic\WooCommerce\StoreApi\Schemas\V1\AbstractSchema;
use Automattic\WooCommerce\StoreApi\StoreApi;

/**
 * Registers the POS Store API surface: REST routes and per-request policy hooks.
 *
 * Routes live under the `wc/internal/pos/v1` namespace, separate from both
 * `wc/store/v1` and the public `wc/pos/v1` catalog feed. The `internal`
 * segment is deliberate: these routes exist for the WooCommerce POS clients
 * and are not a committed public contract. Keeping them out of `wc/store/*`
 * also means none of the Store API request middleware (rate limiting, CORS,
 * the checkout opt-in — all keyed on the `wc/store/` prefix) applies
 * implicitly; in particular, WordPress core's standard REST cookie/nonce
 * authentication runs in full here, which is what makes the routes' Store API
 * nonce opt-out safe (see {@see Routes\PosRouteTrait::requires_nonce()}).
 *
 * Deliberately not gated on any feature flag: the `point_of_sale` feature is
 * deprecated (always-enabled) as of 11.0.0 and must not be consulted. Access
 * control is each route's capability check, and the policy hooks gate
 * themselves on {@see Context::is_pos_request()} lazily.
 *
 * Adding a surface in a follow-up change: add one entry to
 * {@see ROUTE_CLASSES} (a route) or {@see POLICY_HOOK_CLASSES} (a policy
 * hook), and — if the route's response is not a shared Store API schema — one
 * arm to {@see self::make_schema()}.
 *
 * @internal Just for internal use.
 *
 * @since 11.0.0
 */
class Controller implements RegisterHooksInterface {

	/**
	 * REST namespace for POS routes. Derived from the detection prefix so the
	 * two can never drift apart.
	 */
	public const REST_NAMESPACE = Context::URI_PREFIX . 'v1';

	/**
	 * POS route classes to register.
	 *
	 * @var string[]
	 */
	private const ROUTE_CLASSES = array(
		CartAddItems::class,
		CartAddFee::class,
		CartApplyCoupon::class,
		Checkout::class,
	);

	/**
	 * POS policy hook classes to register.
	 *
	 * Each gates itself on {@see Context::is_pos_request()} per call, so
	 * registering them unconditionally is a no-op outside POS requests.
	 *
	 * @var string[]
	 */
	private const POLICY_HOOK_CLASSES = array(
		SessionHandlerSwap::class,
		CartPersistencePolicy::class,
		StockPolicy::class,
		ShippingPolicy::class,
		TaxLocationPolicy::class,
		CustomFeesPolicy::class,
		CurrentUserSwap::class,
		CustomerSwap::class,
		CustomerAccountPolicy::class,
		CheckoutPolicy::class,
	);

	/**
	 * Register hooks.
	 *
	 * Policy hooks are registered right away — the session-handler swap must
	 * be in place before WooCommerce initializes the session, which happens on
	 * `init` for `?rest_route=`-style requests. The hooks decide lazily, so
	 * nothing here evaluates request context or feature state at plugin load.
	 */
	public function register(): void {
		foreach ( self::POLICY_HOOK_CLASSES as $policy_hook_class ) {
			wc_get_container()->get( $policy_hook_class )->register();
		}

		add_action( 'rest_api_init', array( $this, 'register_routes' ) );
	}

	/**
	 * Register all POS routes with WordPress.
	 *
	 * The namespace load check skips the work when the request targets another
	 * known namespace.
	 *
	 * @internal For exclusive usage within this class, backwards compatibility not guaranteed.
	 */
	public function register_routes(): void {
		if ( ! wc_rest_should_load_namespace( self::REST_NAMESPACE ) ) {
			return;
		}

		$schema_controller = StoreApi::container()->get( SchemaController::class );

		foreach ( self::ROUTE_CLASSES as $route_class ) {
			$route = new $route_class( $schema_controller, $this->make_schema( $route_class, $schema_controller ) );
			$route->set_namespace( self::REST_NAMESPACE );

			register_rest_route(
				self::REST_NAMESPACE,
				$route->get_path(),
				$route->get_args()
			);
		}
	}

	/**
	 * Build the schema instance for a POS route.
	 *
	 * POS-only schemas live under Internal\POS and are constructed here, so
	 * the Store API's SchemaController registry needs no knowledge of them;
	 * routes whose response is a shared Store API schema resolve it from the
	 * registry instead.
	 *
	 * @param string           $route_class       Route class name.
	 * @param SchemaController $schema_controller Store API schema controller.
	 * @return AbstractSchema
	 */
	private function make_schema( string $route_class, SchemaController $schema_controller ): AbstractSchema {
		if ( CartAddItems::class === $route_class ) {
			return new AddItemsSchema( StoreApi::container()->get( ExtendSchema::class ), $schema_controller );
		}

		return $schema_controller->get( $route_class::SCHEMA_TYPE, $route_class::SCHEMA_VERSION );
	}
}
