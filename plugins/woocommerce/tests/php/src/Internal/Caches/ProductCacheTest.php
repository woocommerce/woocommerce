<?php
declare( strict_types=1 );

namespace Automattic\WooCommerce\Tests\Internal\Caches;

use Automattic\WooCommerce\Internal\Caches\ProductCache;
use Automattic\WooCommerce\Internal\Caches\ProductCacheController;
use Automattic\WooCommerce\Utilities\FeaturesUtil;
use WC_Data;
use WC_Helper_Product;

/**
 * Tests for the ProductCache class.
 */
class ProductCacheTest extends \WC_Unit_Test_Case {

	/**
	 * System under test.
	 *
	 * @var ProductCache
	 */
	private $sut;

	/**
	 * Setup test.
	 */
	public function setUp(): void {
		parent::setUp();
		$this->sut = new ProductCache();
	}

	/**
	 * Teardown test.
	 */
	public function tearDown(): void {
		$this->sut->flush();
		parent::tearDown();
	}

	/**
	 * Test that set() preserves and restores the original clone mode.
	 */
	public function test_set_preserves_and_restores_clone_mode() {
		$product = WC_Helper_Product::create_simple_product();
		$product->add_meta_data( 'test_key', 'test_value' );
		$product->save();

		// Verify product starts with default DUPLICATE mode.
		$this->assertEquals( WC_Data::CLONE_MODE_DUPLICATE, $product->get_clone_mode() );

		// Cache the product.
		$this->sut->set( $product );

		// Verify mode is restored to DUPLICATE after caching.
		$this->assertEquals( WC_Data::CLONE_MODE_DUPLICATE, $product->get_clone_mode() );
	}

	/**
	 * Test that set() preserves CACHE mode if explicitly set.
	 */
	public function test_set_with_cache_mode_restores_cache() {
		$product = WC_Helper_Product::create_simple_product();
		$product->add_meta_data( 'test_key', 'test_value' );
		$product->save();

		// Explicitly set to CACHE mode.
		$product->set_clone_mode( WC_Data::CLONE_MODE_CACHE );

		// Cache the product.
		$this->sut->set( $product );

		// Verify mode is still CACHE after caching.
		$this->assertEquals( WC_Data::CLONE_MODE_CACHE, $product->get_clone_mode() );
	}

	/**
	 * Test that get() resets clone mode to DUPLICATE.
	 */
	public function test_get_resets_to_duplicate_mode() {
		$product = WC_Helper_Product::create_simple_product();
		$product->add_meta_data( 'test_key', 'test_value' );
		$product->save();

		// Set to CACHE mode and cache it.
		$product->set_clone_mode( WC_Data::CLONE_MODE_CACHE );
		$this->sut->set( $product );

		// Retrieve from cache.
		$retrieved_product = $this->sut->get( $product->get_id() );

		// Verify retrieved product has DUPLICATE mode.
		$this->assertEquals( WC_Data::CLONE_MODE_DUPLICATE, $retrieved_product->get_clone_mode() );
	}

	/**
	 * Test that caching preserves meta IDs through WordPress double-clone.
	 *
	 * This tests the complete workflow:
	 * 1. Product with meta is cached (wp_cache_set clones with CACHE mode)
	 * 2. Product is retrieved (wp_cache_get clones with CACHE mode)
	 * 3. Meta IDs are preserved through both clones
	 * 4. Retrieved product has DUPLICATE mode for backward compatibility
	 */
	public function test_cache_preserves_meta_ids_through_double_clone() {
		$product = WC_Helper_Product::create_simple_product();
		$product->add_meta_data( 'test_key', 'test_value', true );
		$product->save();

		// Get original meta ID.
		$original_meta    = $product->get_meta_data();
		$original_meta_id = $original_meta[0]->id;

		// Cache the product.
		$this->sut->set( $product );

		// Retrieve from cache.
		$retrieved_product = $this->sut->get( $product->get_id() );

		// Get meta from retrieved product.
		$retrieved_meta = $retrieved_product->get_meta_data();

		// Verify meta ID is preserved through caching.
		$this->assertEquals( $original_meta_id, $retrieved_meta[0]->id, 'Meta ID should be preserved through cache double-clone' );

		// Verify meta key and value are correct.
		$this->assertEquals( 'test_key', $retrieved_meta[0]->key );
		$this->assertEquals( 'test_value', $retrieved_meta[0]->value );

		// Verify retrieved product has DUPLICATE mode.
		$this->assertEquals( WC_Data::CLONE_MODE_DUPLICATE, $retrieved_product->get_clone_mode() );
	}

	/**
	 * Test that cloning a retrieved product uses DUPLICATE mode.
	 *
	 * This verifies backward compatibility - products retrieved from cache
	 * should behave like normal products when cloned for duplication.
	 */
	public function test_cloning_cached_product_clears_meta_ids() {
		$product = WC_Helper_Product::create_simple_product();
		$product->add_meta_data( 'test_key', 'test_value', true );
		$product->save();

		// Cache and retrieve the product.
		$this->sut->set( $product );
		$retrieved_product = $this->sut->get( $product->get_id() );

		// Clone the retrieved product (should use DUPLICATE mode).
		$cloned_product = clone $retrieved_product;

		// Get meta from cloned product.
		$cloned_meta = $cloned_product->get_meta_data();

		// Verify meta IDs are cleared (DUPLICATE mode behavior).
		$this->assertNull( $cloned_meta[0]->id, 'Meta IDs should be null when cloning in DUPLICATE mode' );

		// Verify meta key and value are preserved.
		$this->assertEquals( 'test_key', $cloned_meta[0]->key );
		$this->assertEquals( 'test_value', $cloned_meta[0]->value );
	}

	/**
	 * Test integration with product_instance_caching feature flag.
	 *
	 * This test verifies the complete integration with WC_Product_Factory
	 * when the feature is enabled.
	 */
	public function test_integration_with_feature_enabled() {
		// Enable the feature for this test.
		$original_value = get_option( 'woocommerce_feature_' . ProductCacheController::FEATURE_NAME . '_enabled' );
		update_option( 'woocommerce_feature_' . ProductCacheController::FEATURE_NAME . '_enabled', 'yes' );

		try {
			// Create a product with meta.
			$product = WC_Helper_Product::create_simple_product();
			$product->add_meta_data( 'test_key', 'test_value', true );
			$product->save();

			$product_id = $product->get_id();

			// Clear any existing cache.
			$this->sut->remove( $product_id );

			// Get product via factory (should cache it).
			$factory_product = wc_get_product( $product_id );

			// Verify product was cached.
			$this->assertTrue( $this->sut->is_cached( $product_id ), 'Product should be cached after retrieval' );

			// Verify product has DUPLICATE mode (ready for normal use).
			$this->assertEquals( WC_Data::CLONE_MODE_DUPLICATE, $factory_product->get_clone_mode() );

			// Get product again (should come from cache).
			$cached_product = wc_get_product( $product_id );

			// Verify it's a different instance (cloned from cache).
			$this->assertNotSame( $factory_product, $cached_product, 'Cached retrieval should return a new instance' );

			// Verify meta is preserved.
			$cached_meta = $cached_product->get_meta_data();
			$this->assertNotNull( $cached_meta[0]->id, 'Meta ID should be preserved from cache' );
			$this->assertEquals( 'test_value', $cached_product->get_meta( 'test_key' ) );
		} finally {
			// Restore original option value.
			if ( false === $original_value ) {
				delete_option( 'woocommerce_feature_' . ProductCacheController::FEATURE_NAME . '_enabled' );
			} else {
				update_option( 'woocommerce_feature_' . ProductCacheController::FEATURE_NAME . '_enabled', $original_value );
			}
		}
	}

	/**
	 * Test that caching respects the feature flag being disabled.
	 */
	public function test_respects_feature_flag_disabled() {
		if ( FeaturesUtil::feature_is_enabled( ProductCacheController::FEATURE_NAME ) ) {
			$this->markTestSkipped( 'Product instance caching feature is enabled. This test requires it to be disabled.' );
		}

		$product    = WC_Helper_Product::create_simple_product();
		$product_id = $product->get_id();

		// Get product via factory (should NOT cache when feature disabled).
		$factory_product = wc_get_product( $product_id );

		// Verify product was NOT cached.
		$this->assertFalse( $this->sut->is_cached( $product_id ), 'Product should not be cached when feature is disabled' );
	}

	/**
	 * Test that variable products with variations preserve meta correctly.
	 */
	public function test_variable_product_with_variations() {
		$product = WC_Helper_Product::create_variation_product();
		$product->add_meta_data( 'parent_meta', 'parent_value', true );
		$product->save();

		$variations = $product->get_children();
		$variation  = wc_get_product( $variations[0] );
		$variation->add_meta_data( 'variation_meta', 'variation_value', true );
		$variation->save();

		// Get original meta IDs.
		$parent_meta    = $product->get_meta_data();
		$parent_meta_id = $parent_meta[0]->id;

		$variation_meta    = $variation->get_meta_data();
		$variation_meta_id = null;
		foreach ( $variation_meta as $meta ) {
			if ( 'variation_meta' === $meta->key ) {
				$variation_meta_id = $meta->id;
				break;
			}
		}

		// Cache both products.
		$this->sut->set( $product );
		$this->sut->set( $variation );

		// Retrieve from cache.
		$cached_product   = $this->sut->get( $product->get_id() );
		$cached_variation = $this->sut->get( $variation->get_id() );

		// Verify parent meta ID preserved.
		$cached_parent_meta = $cached_product->get_meta_data();
		$this->assertEquals( $parent_meta_id, $cached_parent_meta[0]->id );

		// Verify variation meta ID preserved.
		$cached_variation_meta = $cached_variation->get_meta_data();
		$found_meta_id         = null;
		foreach ( $cached_variation_meta as $meta ) {
			if ( 'variation_meta' === $meta->key ) {
				$found_meta_id = $meta->id;
				break;
			}
		}
		$this->assertEquals( $variation_meta_id, $found_meta_id );
	}
}
