<?php

declare( strict_types = 1 );

namespace Automattic\WooCommerce\Tests\Blocks\BlockTypes;

use Automattic\WooCommerce\Blocks\SharedStores\ProductsStore as TestedProductsStore;
use Automattic\WooCommerce\Tests\Blocks\Helpers\FixtureData;
use WC_Unit_Test_Case;

/**
 * Tests for the ProductRating block type.
 */
class ProductRatingTest extends WC_Unit_Test_Case {

	/**
	 * @testdox Product Rating visibility follows the product and global review settings.
	 * @dataProvider review_visibility_settings
	 *
	 * @param bool   $product_reviews_allowed Whether reviews are enabled for the product.
	 * @param string $global_reviews_setting  The global product review option value.
	 * @param bool   $expected_visible        Whether the Product Rating markup should render.
	 */
	public function test_render_visibility_follows_review_settings( bool $product_reviews_allowed, string $global_reviews_setting, bool $expected_visible ): void {
		$option_name              = 'woocommerce_enable_reviews';
		$previous_reviews_setting = get_option( $option_name, null );
		$had_global_post          = array_key_exists( 'post', $GLOBALS );
		$previous_global_post     = $GLOBALS['post'] ?? null;
		$had_global_product       = array_key_exists( 'product', $GLOBALS );
		$previous_global_product  = $GLOBALS['product'] ?? null;
		$products_store_state     = $this->snapshot_products_store_static_state();
		$interactivity_state      = $this->snapshot_interactivity_state();
		$product                  = null;

		try {
			update_option( $option_name, $global_reviews_setting );

			$fixtures = new FixtureData();
			$product  = $fixtures->get_simple_product(
				array(
					'name'            => 'Rated product',
					'regular_price'   => '10',
					'status'          => 'publish',
					'reviews_allowed' => true,
				)
			);
			$fixtures->add_product_review( $product->get_id(), 5 );

			$product->set_reviews_allowed( $product_reviews_allowed );
			$product->save();
			\WC_Comments::clear_transients( $product->get_id() );
			clean_post_cache( $product->get_id() );

			$product = wc_get_product( $product->get_id() );
			if ( ! $product instanceof \WC_Product ) {
				throw new \RuntimeException( 'The review fixture should remain a loadable product.' );
			}
			$this->assertGreaterThan( 0, $product->get_review_count(), 'The review fixture must have a real approved review before rendering.' );

			$markup = do_blocks(
				'<!-- wp:woocommerce/single-product {"productId":' . $product->get_id() . '} -->' .
				'<!-- wp:woocommerce/product-rating {"isDescendentOfSingleProductBlock":true} /-->' .
				'<!-- /wp:woocommerce/single-product -->'
			);

			if ( $expected_visible ) {
				$this->assertStringContainsString( 'wc-block-components-product-rating', $markup, 'A rated product with both review settings enabled should render Product Rating markup.' );
			} else {
				$this->assertStringNotContainsString( 'wc-block-components-product-rating', $markup, 'Product Rating markup should not render when either review setting is disabled.' );
			}
		} finally {
			try {
				if ( $product instanceof \WC_Product ) {
					$product_id = $product->get_id();
					/** @var int[] $comment_ids */
					$comment_ids = get_comments(
						array(
							'fields'  => 'ids',
							'post_id' => $product_id,
							'type'    => 'review',
						)
					);

					foreach ( $comment_ids as $comment_id ) {
						wp_delete_comment( $comment_id, true );
					}

					\WC_Comments::clear_transients( $product_id );
					wc_delete_product_transients( $product_id );
					clean_post_cache( $product_id );
					$product->delete( true );
					clean_post_cache( $product_id );
				}

				if ( null === $previous_reviews_setting ) {
					delete_option( $option_name );
				} else {
					update_option( $option_name, $previous_reviews_setting );
				}

				if ( $had_global_post ) {
					// phpcs:ignore WordPress.WP.GlobalVariablesOverride.Prohibited -- Restore the exact pre-test global after registered block rendering.
					$GLOBALS['post'] = $previous_global_post;
				} else {
					unset( $GLOBALS['post'] );
				}

				if ( $had_global_product ) {
					$GLOBALS['product'] = $previous_global_product;
				} else {
					unset( $GLOBALS['product'] );
				}
			} finally {
				try {
					$this->restore_products_store_static_state( $products_store_state );
				} finally {
					$this->restore_interactivity_state( $interactivity_state );
				}
			}
		}

		$this->assertSame( $products_store_state, $this->snapshot_products_store_static_state(), 'The registered render should not leak ProductsStore static state.' );
		$this->assertSame( $interactivity_state, $this->snapshot_interactivity_state(), 'The registered render should not leak global Interactivity API state.' );
	}

	/**
	 * Snapshot the four private static properties mutated by ProductsStore.
	 *
	 * @return array<string, mixed>
	 */
	private function snapshot_products_store_static_state(): array {
		$reflection = new \ReflectionClass( TestedProductsStore::class );
		$state      = array();

		foreach ( array( 'products', 'product_variations', 'loaded_variation_parents', 'getters_registered' ) as $name ) {
			$property = $reflection->getProperty( $name );
			$property->setAccessible( true );
			$state[ $name ] = $property->getValue();
		}

		return $state;
	}

	/**
	 * Restore the four private static properties mutated by ProductsStore.
	 *
	 * @param array<string, mixed> $state ProductsStore state captured before rendering.
	 */
	private function restore_products_store_static_state( array $state ): void {
		$reflection = new \ReflectionClass( TestedProductsStore::class );

		foreach ( $state as $name => $value ) {
			$property = $reflection->getProperty( $name );
			$property->setAccessible( true );
			$property->setValue( null, $value );
		}
	}

	/**
	 * Snapshot the global Interactivity API properties mutated by ProductsStore.
	 *
	 * @return array{api: object, properties: array<string, mixed>}|null
	 */
	private function snapshot_interactivity_state(): ?array {
		if ( ! function_exists( 'wp_interactivity' ) ) {
			return null;
		}

		$api        = wp_interactivity();
		$reflection = new \ReflectionClass( $api );
		$properties = array();
		foreach ( array( 'state_data', 'config_data', 'derived_state_closures' ) as $name ) {
			if ( ! $reflection->hasProperty( $name ) ) {
				continue;
			}

			$property = $reflection->getProperty( $name );
			$property->setAccessible( true );
			$properties[ $name ] = $property->getValue( $api );
		}

		return array(
			'api'        => $api,
			'properties' => $properties,
		);
	}

	/**
	 * Restore the global Interactivity API properties mutated by ProductsStore.
	 *
	 * @param array{api: object, properties: array<string, mixed>}|null $state Interactivity state captured before rendering.
	 */
	private function restore_interactivity_state( ?array $state ): void {
		if ( null === $state ) {
			return;
		}

		$api        = $state['api'];
		$reflection = new \ReflectionClass( $api );
		foreach ( $state['properties'] as $name => $value ) {
			$property = $reflection->getProperty( $name );
			$property->setAccessible( true );
			$property->setValue( $api, $value );
		}
	}

	/**
	 * Review visibility settings.
	 *
	 * @return array<string, array{bool, string, bool}>
	 */
	public static function review_visibility_settings(): array {
		return array(
			'reviews enabled'          => array( true, 'yes', true ),
			'product reviews disabled' => array( false, 'yes', false ),
			'global reviews disabled'  => array( true, 'no', false ),
		);
	}
}
