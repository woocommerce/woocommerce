<?php

use Automattic\WooCommerce\Enums\ProductType;

/**
 * Products Factory Tests
 * @package WooCommerce\Tests\Product
 * @since 3.0.0
 */
class WC_Tests_Product_Factory extends WC_Unit_Test_Case {

	/**
	 * Test getting product type.
	 *
	 * @since 3.0.0
	 */
	function test_get_product_type() {
		$simple   = WC_Helper_Product::create_simple_product();
		$external = WC_Helper_Product::create_external_product();
		$grouped  = WC_Helper_Product::create_grouped_product();
		$variable = WC_Helper_Product::create_variation_product();
		$children = $variable->get_children();
		$child_id = $children[0];

		$this->assertEquals( ProductType::SIMPLE, WC()->product_factory->get_product_type( $simple->get_id() ) );
		$this->assertEquals( ProductType::EXTERNAL, WC()->product_factory->get_product_type( $external->get_id() ) );
		$this->assertEquals( ProductType::GROUPED, WC()->product_factory->get_product_type( $grouped->get_id() ) );
		$this->assertEquals( ProductType::VARIABLE, WC()->product_factory->get_product_type( $variable->get_id() ) );
		$this->assertEquals( ProductType::VARIATION, WC()->product_factory->get_product_type( $child_id ) );

		$simple->delete( true );
		$external->delete( true );
		$grouped->delete( true );
		$variable->delete( true );
	}

	/**
	 * Test the helper method that returns a class name for a specific product type.
	 *
	 * @since 3.0.0
	 */
	function test_get_classname_from_product_type() {
		$this->assertEquals( 'WC_Product_Grouped', WC()->product_factory->get_classname_from_product_type( ProductType::GROUPED ) );
		$this->assertEquals( 'WC_Product_Simple', WC()->product_factory->get_classname_from_product_type( ProductType::SIMPLE ) );
		$this->assertEquals( 'WC_Product_Variable', WC()->product_factory->get_classname_from_product_type( ProductType::VARIABLE ) );
		$this->assertEquals( 'WC_Product_Variation', WC()->product_factory->get_classname_from_product_type( ProductType::VARIATION ) );
		$this->assertEquals( 'WC_Product_External', WC()->product_factory->get_classname_from_product_type( ProductType::EXTERNAL ) );
	}

	/**
	 * Tests getting a product using the factory.
	 *
	 * @since 3.0.0
	 */
	function test_get_product() {
		$test_product = WC_Helper_Product::create_simple_product();
		$get_product  = WC()->product_factory->get_product( $test_product->get_id() );
		$this->assertEquals( $test_product->get_data(), $get_product->get_data() );
	}

	/**
	 * Tests that an incorrect product returns false.
	 *
	 * @since 3.0.0
	 */
	function test_get_invalid_product_returns_false() {
		$product = WC()->product_factory->get_product( 50000 );
		$this->assertFalse( $product );
	}

	/**
	 * Test that products returned from factory have DUPLICATE clone mode.
	 * This ensures backward compatibility for code that clones products for duplication.
	 */
	public function test_factory_returns_product_with_duplicate_mode() {
		$test_product = WC_Helper_Product::create_simple_product();
		$product      = WC()->product_factory->get_product( $test_product->get_id() );

		$this->assertEquals( WC_Data::CLONE_MODE_DUPLICATE, $product->get_clone_mode(), 'Products from factory should have DUPLICATE mode for backward compatibility' );
	}

	/**
	 * Test that products returned from cache preserve their meta IDs.
	 * This is critical for the caching feature to work correctly.
	 */
	public function test_factory_cached_product_preserves_meta_ids() {
		// Enable the feature for this test.
		$option_name    = 'woocommerce_feature_' . \Automattic\WooCommerce\Internal\Caches\ProductCacheController::FEATURE_NAME . '_enabled';
		$original_value = get_option( $option_name );
		update_option( $option_name, 'yes' );

		try {
			// Create product with meta.
			$test_product = WC_Helper_Product::create_simple_product();
			$test_product->add_meta_data( 'test_cache_key', 'test_cache_value', true );
			$test_product->save();

			$product_id = $test_product->get_id();

			// Get original meta ID.
			$original_meta    = $test_product->get_meta_data();
			$original_meta_id = null;
			foreach ( $original_meta as $meta ) {
				if ( 'test_cache_key' === $meta->key ) {
					$original_meta_id = $meta->id;
					break;
				}
			}

			// Clear cache to ensure fresh retrieval.
			$product_cache = wc_get_container()->get( \Automattic\WooCommerce\Internal\Caches\ProductCache::class );
			$product_cache->remove( $product_id );

			// Get product via factory (first time - will cache it).
			$product1 = WC()->product_factory->get_product( $product_id );

			// Get product again (second time - should come from cache).
			$product2 = WC()->product_factory->get_product( $product_id );

			// Verify both retrievals preserve meta ID.
			$meta1     = $product1->get_meta_data();
			$meta_id_1 = null;
			foreach ( $meta1 as $meta ) {
				if ( 'test_cache_key' === $meta->key ) {
					$meta_id_1 = $meta->id;
					break;
				}
			}

			$meta2     = $product2->get_meta_data();
			$meta_id_2 = null;
			foreach ( $meta2 as $meta ) {
				if ( 'test_cache_key' === $meta->key ) {
					$meta_id_2 = $meta->id;
					break;
				}
			}

			$this->assertEquals( $original_meta_id, $meta_id_1, 'First retrieval should preserve meta ID' );
			$this->assertEquals( $original_meta_id, $meta_id_2, 'Cached retrieval should preserve meta ID' );
			$this->assertEquals( 'test_cache_value', $product1->get_meta( 'test_cache_key' ) );
			$this->assertEquals( 'test_cache_value', $product2->get_meta( 'test_cache_key' ) );
		} finally {
			// Restore original option value.
			if ( false === $original_value ) {
				delete_option( $option_name );
			} else {
				update_option( $option_name, $original_value );
			}
		}
	}

	/**
	 * Test that cloning a product from factory clears meta IDs (duplicate mode).
	 * This verifies backward compatibility for product duplication.
	 */
	public function test_factory_product_clone_clears_meta_ids() {
		$test_product = WC_Helper_Product::create_simple_product();
		$test_product->add_meta_data( 'test_key', 'test_value', true );
		$test_product->save();

		// Get product from factory.
		$product = WC()->product_factory->get_product( $test_product->get_id() );

		// Clone the product (should use DUPLICATE mode).
		$cloned_product = clone $product;

		// Get meta from cloned product.
		$cloned_meta = $cloned_product->get_meta_data();

		// Find the test meta.
		$cloned_meta_id = null;
		foreach ( $cloned_meta as $meta ) {
			if ( 'test_key' === $meta->key ) {
				$cloned_meta_id = $meta->id;
				break;
			}
		}

		// Verify meta ID is cleared (backward compatible duplication behavior).
		$this->assertNull( $cloned_meta_id, 'Cloning a product from factory should clear meta IDs for duplication' );

		// Verify meta value is preserved.
		$this->assertEquals( 'test_value', $cloned_product->get_meta( 'test_key' ) );
	}

	/**
	 * Test that cache is used when retrieving the same product multiple times.
	 */
	public function test_factory_uses_cache_for_repeated_retrievals() {
		// Enable the feature for this test.
		$option_name    = 'woocommerce_feature_' . \Automattic\WooCommerce\Internal\Caches\ProductCacheController::FEATURE_NAME . '_enabled';
		$original_value = get_option( $option_name );
		update_option( $option_name, 'yes' );

		try {
			$test_product = WC_Helper_Product::create_simple_product();
			$product_id   = $test_product->get_id();

			// Clear cache.
			$product_cache = wc_get_container()->get( \Automattic\WooCommerce\Internal\Caches\ProductCache::class );
			$product_cache->remove( $product_id );

			// Verify not cached initially.
			$this->assertFalse( $product_cache->is_cached( $product_id ), 'Product should not be cached initially' );

			// Get product (should cache it).
			$product1 = WC()->product_factory->get_product( $product_id );

			// Verify it's now cached.
			$this->assertTrue( $product_cache->is_cached( $product_id ), 'Product should be cached after first retrieval' );

			// Get product again (should use cache).
			$product2 = WC()->product_factory->get_product( $product_id );

			// Verify both are valid products with same ID.
			$this->assertEquals( $product_id, $product1->get_id() );
			$this->assertEquals( $product_id, $product2->get_id() );

			// Verify they are different instances (cache returns clones).
			$this->assertNotSame( $product1, $product2, 'Cached products should be different instances' );
		} finally {
			// Restore original option value.
			if ( false === $original_value ) {
				delete_option( $option_name );
			} else {
				update_option( $option_name, $original_value );
			}
		}
	}

	/**
	 * Test that cache is bypassed when feature is disabled.
	 */
	public function test_factory_bypasses_cache_when_feature_disabled() {
		if ( \Automattic\WooCommerce\Utilities\FeaturesUtil::feature_is_enabled( \Automattic\WooCommerce\Internal\Caches\ProductCacheController::FEATURE_NAME ) ) {
			$this->markTestSkipped( 'Product instance caching feature is enabled. This test requires it to be disabled.' );
		}

		$test_product = WC_Helper_Product::create_simple_product();
		$product_id   = $test_product->get_id();

		// Get product via factory.
		$product = WC()->product_factory->get_product( $product_id );

		// Verify product is retrieved but not cached.
		$product_cache = wc_get_container()->get( \Automattic\WooCommerce\Internal\Caches\ProductCache::class );
		$this->assertFalse( $product_cache->is_cached( $product_id ), 'Product should not be cached when feature is disabled' );

		// Verify product is still valid.
		$this->assertEquals( $product_id, $product->get_id() );
	}

}
