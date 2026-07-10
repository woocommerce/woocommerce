<?php
/**
 * Shared REST server setup for Store API route tests.
 */

declare( strict_types = 1 );

namespace Automattic\WooCommerce\Tests\Blocks\StoreApi\Routes;

/**
 * Provides namespace-scoped Store API route registration.
 */
trait StoreApiRestTestCaseTrait {
	/**
	 * Run class fixture changes without leaving asynchronous lookup actions.
	 *
	 * @param callable $callback Fixture lifecycle callback.
	 * @return mixed
	 */
	protected static function with_direct_product_attribute_lookup_updates( callable $callback ) {
		$enable_direct_updates = static function () {
			return 'yes';
		};

		add_filter( 'pre_option_woocommerce_attribute_lookup_direct_updates', $enable_direct_updates );

		try {
			return $callback();
		} finally {
			remove_filter( 'pre_option_woocommerce_attribute_lookup_direct_updates', $enable_direct_updates );
		}
	}

	/**
	 * Create class-owned products without leaving asynchronous lookup actions.
	 *
	 * @param array[] $product_properties Product properties for each fixture.
	 * @return \WC_Product[]
	 */
	protected static function create_class_fixture_products( array $product_properties ): array {
		return self::with_direct_product_attribute_lookup_updates(
			static function () use ( $product_properties ) {
				$fixtures = new \Automattic\WooCommerce\Tests\Blocks\Helpers\FixtureData();

				return array_map(
					static function ( array $properties ) use ( $fixtures ) {
						return $fixtures->get_simple_product( $properties );
					},
					$product_properties
				);
			}
		);
	}

	/**
	 * Create a REST server with only the relevant WooCommerce namespace loaded.
	 */
	protected function initialize_store_api_server(): void {
		/** @var \WP_REST_Server $wp_rest_server */
		global $wp_rest_server;
		$wp_rest_server = new \Spy_REST_Server();

		$had_rest_route = array_key_exists( 'rest_route', $GLOBALS['wp']->query_vars );
		$rest_route     = $GLOBALS['wp']->query_vars['rest_route'] ?? null;

		$GLOBALS['wp']->query_vars['rest_route'] = '/wc/store/v1';

		try {
			// phpcs:ignore WooCommerce.Commenting.CommentHooks.MissingHookComment
			do_action( 'rest_api_init', $wp_rest_server );
		} finally {
			if ( $had_rest_route ) {
				$GLOBALS['wp']->query_vars['rest_route'] = $rest_route;
			} else {
				unset( $GLOBALS['wp']->query_vars['rest_route'] );
			}
		}
	}
}
