<?php
namespace Automattic\WooCommerce\StoreApi;

use Automattic\WooCommerce\Blocks\Registry\Container;
use Automattic\WooCommerce\StoreApi\Formatters;
use Automattic\WooCommerce\StoreApi\Authentication;
use Automattic\WooCommerce\StoreApi\Legacy;
use Automattic\WooCommerce\StoreApi\Formatters\CurrencyFormatter;
use Automattic\WooCommerce\StoreApi\Formatters\HtmlFormatter;
use Automattic\WooCommerce\StoreApi\Formatters\MoneyFormatter;
use Automattic\WooCommerce\StoreApi\RoutesController;
use Automattic\WooCommerce\StoreApi\SchemaController;
use Automattic\WooCommerce\StoreApi\Schemas\ExtendSchema;

/**
 * StoreApi Main Class.
 */
final class StoreApi {
	/**
	 * Init and hook in Store API functionality.
	 */
	public function init() {
		add_action(
			'rest_api_init',
			function () {
				if ( ! wc_rest_should_load_namespace( 'wc/store' ) && ! wc_rest_should_load_namespace( 'wc/private' ) ) {
					return;
				}
				self::container()->get( Legacy::class )->init();
				self::container()->get( RoutesController::class )->register_all_routes();
			}
		);
		// Runs on priority 11 after rest_api_default_filters() which is hooked at 10.
		add_action(
			'rest_api_init',
			function () {
				if ( ! wc_rest_should_load_namespace( 'wc/store' ) ) {
					return;
				}
				self::container()->get( Authentication::class )->init();
			},
			11
		);

		// Make billing_address optional for POS checkout requests.
		// This filter runs before validation, allowing us to provide a default.
		add_filter(
			'rest_pre_dispatch',
			array( $this, 'maybe_default_pos_billing_address' ),
			10,
			3
		);

		add_action(
			'woocommerce_blocks_pre_get_routes_from_namespace',
			function ( $routes, $ns ) {
				if ( 'wc/store/v1' !== $ns ) {
					return $routes;
				}

				$routes = array_merge(
					$routes,
					self::container()->get( RoutesController::class )->get_all_routes( 'v1' )
				);

				return $routes;
			},
			10,
			2
		);
	}

	/**
	 * Loads the DI container for Store API.
	 *
	 * @internal This uses the Blocks DI container. If Store API were to move to core, this container could be replaced
	 * with a different compatible container.
	 *
	 * @param boolean $reset Used to reset the container to a fresh instance. Note: this means all dependencies will be reconstructed.
	 * @return mixed
	 */
	public static function container( $reset = false ) {
		static $container;

		if ( $reset ) {
			$container = null;
		}

		if ( $container ) {
			return $container;
		}

		$container = new Container();
		$container->register(
			Authentication::class,
			function () {
				return new Authentication();
			}
		);
		$container->register(
			Legacy::class,
			function () {
				return new Legacy();
			}
		);
		$container->register(
			RoutesController::class,
			function ( $container ) {
				return new RoutesController(
					$container->get( SchemaController::class )
				);
			}
		);
		$container->register(
			SchemaController::class,
			function ( $container ) {
				return new SchemaController(
					$container->get( ExtendSchema::class )
				);
			}
		);
		$container->register(
			ExtendSchema::class,
			function ( $container ) {
				return new ExtendSchema(
					$container->get( Formatters::class )
				);
			}
		);
		$container->register(
			Formatters::class,
			function () {
				$formatters = new Formatters();
				$formatters->register( 'money', MoneyFormatter::class );
				$formatters->register( 'html', HtmlFormatter::class );
				$formatters->register( 'currency', CurrencyFormatter::class );
				return $formatters;
			}
		);
		return $container;
	}

	/**
	 * Provide a default empty billing_address for POS checkout requests.
	 *
	 * POS sessions don't require billing address, but the REST API schema marks it
	 * as required. This filter runs before validation, allowing us to provide a
	 * default so the request passes validation. The actual address validation is
	 * already bypassed for POS in Checkout.php.
	 *
	 * @param mixed            $result  Response to replace the requested version with.
	 * @param \WP_REST_Server  $server  Server instance.
	 * @param \WP_REST_Request $request Request used to generate the response.
	 * @return mixed
	 */
	public function maybe_default_pos_billing_address( $result, $server, $request ) {
		// Only apply to checkout POST requests.
		$route = $request->get_route();
		if ( ! preg_match( '#^/wc/store(/v1)?/checkout$#', $route ) ) {
			return $result;
		}

		if ( 'POST' !== $request->get_method() ) {
			return $result;
		}

		// Only apply to POS sessions (requires X-WC-POS header).
		$pos_header = $request->get_header( 'X-WC-POS' );
		if ( ! $pos_header ) {
			return $result;
		}

		// Provide default empty billing_address if not set.
		if ( null === $request->get_param( 'billing_address' ) ) {
			$request->set_param( 'billing_address', array() );
		}

		return $result;
	}
}
