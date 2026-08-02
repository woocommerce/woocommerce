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
		return \WC_Unit_Test_Case::with_direct_product_attribute_lookup_updates( $callback );
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
	 * Delete class-owned products through WooCommerce data stores.
	 *
	 * @param int[] $product_ids Product IDs to delete.
	 */
	protected static function delete_class_fixture_products( array $product_ids ): void {
		self::with_direct_product_attribute_lookup_updates(
			static function () use ( $product_ids ) {
				foreach ( $product_ids as $product_id ) {
					$product = wc_get_product( $product_id );
					if ( $product ) {
						$product->delete( true );
					}
				}
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

		\WC_Unit_Test_Case::with_rest_route_context(
			'/wc/store/v1',
			static function () use ( $wp_rest_server ) {
				// phpcs:ignore WooCommerce.Commenting.CommentHooks.MissingHookComment
				do_action( 'rest_api_init', $wp_rest_server );
			}
		);
	}
}
