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
use Automattic\WooCommerce\StoreApi\Utilities\CartController;

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

				try {
					$cart_controller = new CartController();
					$cart_controller->load_cart();
					$cart_controller->normalize_cart();
				} catch ( \Exception $e ) {
					return new \WP_Error(
						'woocommerce_rest_cart_error',
						$e->getMessage(),
						array( 'status' => 500 )
					);
				} catch ( \Error $e ) {
					return new \WP_Error(
						'woocommerce_rest_cart_error',
						$e->getMessage(),
						array( 'status' => 500 )
					);
				}
			},
			11
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
}
