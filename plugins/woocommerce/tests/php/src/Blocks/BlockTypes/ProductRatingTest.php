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
		$had_global_product      = array_key_exists( 'product', $GLOBALS );
		$previous_global_product = $GLOBALS['product'] ?? null;
		$products_store_state    = $this->snapshot_products_store_static_state();
		$interactivity_state     = $this->snapshot_interactivity_state();

		try {
			update_option( 'woocommerce_enable_reviews', $global_reviews_setting );

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
				'<!-- wp:woocommerce/product-rating /-->' .
				'<!-- /wp:woocommerce/single-product -->'
			);

			if ( $expected_visible ) {
				$this->assertStringContainsString( 'wc-block-components-product-rating', $markup, 'A rated product with both review settings enabled should render Product Rating markup.' );
			} else {
				$this->assertStringNotContainsString( 'wc-block-components-product-rating', $markup, 'Product Rating markup should not render when either review setting is disabled.' );
			}
		} finally {
			// The option row, the fixtures and $GLOBALS['post'] go back with tear_down().
			// The product global, the ProductsStore statics and the Interactivity store
			// do not. Rendering really does populate all three -- ProductsStore caches
			// the product and flips getters_registered -- so these restores are what
			// keeps the render out of the next test, not a precaution.
			try {
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

	/**
	 * @testdox Single Product block review links follow the current page and ignore $GLOBALS['product'].
	 *
	 * @testWith ["woocommerce/product-rating", false, false]
	 *           ["woocommerce/product-rating", true, false]
	 *           ["woocommerce/product-rating", false, true]
	 *           ["woocommerce/product-rating", true, true]
	 *           ["woocommerce/product-rating-counter", false, false]
	 *           ["woocommerce/product-rating-counter", true, false]
	 *           ["woocommerce/product-rating-counter", false, true]
	 *           ["woocommerce/product-rating-counter", true, true]
	 *
	 * @param string $inner_block_name       Inner rating block to render.
	 * @param bool   $global_product_matches Whether to set $GLOBALS['product'] to the block product.
	 * @param bool   $on_product_page        Whether to render on the product permalink.
	 */
	public function test_single_product_block_review_link_ignores_matching_global_product( string $inner_block_name, bool $global_product_matches, bool $on_product_page ): void {
		$had_global_product      = array_key_exists( 'product', $GLOBALS );
		$previous_global_product = $GLOBALS['product'] ?? null;
		$products_store_state    = $this->snapshot_products_store_static_state();
		$interactivity_state     = $this->snapshot_interactivity_state();

		try {
			$product        = $this->create_rated_product();
			$permalink_href = 'href="' . esc_url( get_permalink( $product->get_id() ) ) . '#reviews"';

			if ( $on_product_page ) {
				$this->go_to( get_permalink( $product->get_id() ) );
			}

			if ( $global_product_matches ) {
				// phpcs:ignore WordPress.WP.GlobalVariablesOverride.Prohibited
				$GLOBALS['product'] = $product;
			} else {
				unset( $GLOBALS['product'] );
			}

			$markup = do_blocks(
				'<!-- wp:woocommerce/single-product {"productId":' . $product->get_id() . '} -->' .
				'<!-- wp:' . $inner_block_name . ' /-->' .
				'<!-- /wp:woocommerce/single-product -->'
			);

			$context = ( $on_product_page ? 'on the product page' : 'off the product page' ) . ' ' . ( $global_product_matches ? 'when the global product matches' : 'when the global product is not set' );

			if ( $on_product_page ) {
				$this->assertStringContainsString(
					'href="#reviews"',
					$markup,
					$inner_block_name . ' inside a Single Product block should emit a same-page #reviews anchor ' . $context . '.'
				);
				$this->assertStringNotContainsString(
					$permalink_href,
					$markup,
					$inner_block_name . ' inside a Single Product block should not link to the product permalink ' . $context . '.'
				);
			} else {
				$this->assertStringContainsString(
					$permalink_href,
					$markup,
					$inner_block_name . ' inside a Single Product block should link to the product permalink plus #reviews ' . $context . '.'
				);
				$this->assertStringNotContainsString(
					'href="#reviews"',
					$markup,
					$inner_block_name . ' inside a Single Product block should not emit a bare #reviews anchor ' . $context . '.'
				);
			}
		} finally {
			try {
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
	}

	/**
	 * Create a published product that has one approved review.
	 *
	 * @return \WC_Product
	 */
	private function create_rated_product(): \WC_Product {
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

		return $product;
	}
}
