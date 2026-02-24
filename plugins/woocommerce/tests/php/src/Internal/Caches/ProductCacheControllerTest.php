<?php
declare( strict_types=1 );

namespace Automattic\WooCommerce\Tests\Internal\Caches;

use Automattic\WooCommerce\Internal\Caches\ProductCache;
use Automattic\WooCommerce\Internal\Caches\ProductCacheController;
use Automattic\WooCommerce\Utilities\FeaturesUtil;
use WC_Helper_Product;

/**
 * Tests for ProductCacheController.
 */
class ProductCacheControllerTest extends \WC_Unit_Test_Case {

	/**
	 * System under test.
	 *
	 * @var ProductCacheController
	 */
	private $sut;

	/**
	 * Product cache instance.
	 *
	 * @var ProductCache
	 */
	private $product_cache;

	/**
	 * Original feature option value.
	 *
	 * @var mixed
	 */
	private $original_feature_value;

	/**
	 * Feature option name.
	 *
	 * @var string
	 */
	private $feature_option_name;

	/**
	 * Setup test.
	 */
	public function setUp(): void {
		parent::setUp();
		$this->product_cache       = wc_get_container()->get( ProductCache::class );
		$this->sut                 = wc_get_container()->get( ProductCacheController::class );
		$this->feature_option_name = 'woocommerce_feature_' . ProductCacheController::FEATURE_NAME . '_enabled';
	}

	/**
	 * Teardown test.
	 */
	public function tearDown(): void {
		$this->product_cache->flush();
		$this->unhook_controller( $this->sut );
		$this->restore_feature_option();
		parent::tearDown();
	}

	/**
	 * Remove all hooks registered by a controller instance.
	 *
	 * @param ProductCacheController $controller The controller to unhook.
	 */
	private function unhook_controller( ProductCacheController $controller ): void {
		remove_action( 'init', array( $controller, 'on_init' ), 0 );
		remove_action( 'clean_post_cache', array( $controller, 'invalidate_product_cache_on_clean' ), 10 );
		remove_action( 'updated_post_meta', array( $controller, 'invalidate_product_cache_by_meta' ), 10 );
		remove_action( 'added_post_meta', array( $controller, 'invalidate_product_cache_by_meta' ), 10 );
		remove_action( 'deleted_post_meta', array( $controller, 'invalidate_product_cache_by_meta' ), 10 );
		remove_action( 'woocommerce_updated_product_stock', array( $controller, 'invalidate_product_cache' ), 10 );
		remove_action( 'woocommerce_updated_product_sales', array( $controller, 'invalidate_product_cache' ), 10 );
	}

	/**
	 * Enable the product instance caching feature for a test.
	 */
	private function enable_feature(): void {
		$this->original_feature_value = get_option( $this->feature_option_name );
		update_option( $this->feature_option_name, 'yes' );
		$this->sut->register_hooks();
	}

	/**
	 * Disable the product instance caching feature for a test.
	 */
	private function disable_feature(): void {
		$this->original_feature_value = get_option( $this->feature_option_name );
		update_option( $this->feature_option_name, 'no' );
	}

	/**
	 * Restore the original feature option value.
	 */
	private function restore_feature_option(): void {
		if ( isset( $this->original_feature_value ) ) {
			if ( false === $this->original_feature_value ) {
				delete_option( $this->feature_option_name );
			} else {
				update_option( $this->feature_option_name, $this->original_feature_value );
			}
			$this->original_feature_value = null;
		}
	}

	/**
	 * @testdox Controller does not register hooks when feature is disabled.
	 */
	public function test_controller_does_not_register_hooks_when_disabled() {
		$this->disable_feature();

		// Create a fresh controller instance to test hook registration.
		$controller = new ProductCacheController();
		$controller->init( $this->product_cache );
		$controller->on_init();

		try {
			// Verify hooks are NOT registered when feature is disabled.
			$this->assertFalse(
				has_action( 'clean_post_cache', array( $controller, 'invalidate_product_cache_on_clean' ) ),
				'clean_post_cache hook should NOT be registered when feature is disabled'
			);

			$this->assertFalse(
				has_action( 'updated_post_meta', array( $controller, 'invalidate_product_cache_by_meta' ) ),
				'updated_post_meta hook should NOT be registered when feature is disabled'
			);
		} finally {
			$this->unhook_controller( $controller );
		}
	}

	/**
	 * @testdox Controller registers cache invalidation hooks when feature is enabled.
	 */
	public function test_controller_registers_hooks_when_enabled() {
		$this->enable_feature();

		// Create a fresh controller instance to test hook registration.
		$controller = new ProductCacheController();
		$controller->init( $this->product_cache );
		$controller->on_init();

		try {
			// Verify clean_post_cache hook is registered.
			$this->assertNotFalse(
				has_action( 'clean_post_cache', array( $controller, 'invalidate_product_cache_on_clean' ) ),
				'clean_post_cache hook should be registered'
			);

			// Verify meta update hooks are registered.
			$this->assertNotFalse(
				has_action( 'updated_post_meta', array( $controller, 'invalidate_product_cache_by_meta' ) ),
				'updated_post_meta hook should be registered'
			);

			$this->assertNotFalse(
				has_action( 'added_post_meta', array( $controller, 'invalidate_product_cache_by_meta' ) ),
				'added_post_meta hook should be registered'
			);

			$this->assertNotFalse(
				has_action( 'deleted_post_meta', array( $controller, 'invalidate_product_cache_by_meta' ) ),
				'deleted_post_meta hook should be registered'
			);

			// Verify stock/sales hooks are registered.
			$this->assertNotFalse(
				has_action( 'woocommerce_updated_product_stock', array( $controller, 'invalidate_product_cache' ) ),
				'woocommerce_updated_product_stock hook should be registered'
			);

			$this->assertNotFalse(
				has_action( 'woocommerce_updated_product_sales', array( $controller, 'invalidate_product_cache' ) ),
				'woocommerce_updated_product_sales hook should be registered'
			);
		} finally {
			$this->unhook_controller( $controller );
		}
	}

	/**
	 * @testdox Product cache is invalidated when product is saved via CRUD.
	 */
	public function test_cache_invalidated_on_product_save() {
		$this->enable_feature();

		$product = WC_Helper_Product::create_simple_product();
		$product->set_name( 'Original Name' );
		$product->save();

		$product_id = $product->get_id();

		// Cache the product.
		$this->product_cache->remove( $product_id );
		wc_get_product( $product_id );
		$this->assertTrue( $this->product_cache->is_cached( $product_id ), 'Product should be cached after retrieval' );

		// Update and save product.
		$product->set_name( 'Updated Name' );
		$product->save();

		// Verify cache was invalidated.
		$this->assertFalse( $this->product_cache->is_cached( $product_id ), 'Cache should be invalidated after save' );

		// Verify fresh data is returned.
		$fresh_product = wc_get_product( $product_id );
		$this->assertEquals( 'Updated Name', $fresh_product->get_name(), 'Should return updated name' );
	}

	/**
	 * @testdox Product cache is invalidated when stock is updated via direct SQL.
	 */
	public function test_cache_invalidated_on_stock_update() {
		$this->enable_feature();

		$product = WC_Helper_Product::create_simple_product();
		$product->set_manage_stock( true );
		$product->set_stock_quantity( 10 );
		$product->save();

		$product_id = $product->get_id();

		// Cache the product.
		$this->product_cache->remove( $product_id );
		$cached_product = wc_get_product( $product_id );
		$this->assertEquals( 10, $cached_product->get_stock_quantity() );
		$this->assertTrue( $this->product_cache->is_cached( $product_id ) );

		// Update stock directly (uses SQL, bypasses CRUD).
		$data_store = \WC_Data_Store::load( 'product' );
		$data_store->update_product_stock( $product_id, 5, 'set' );

		// Verify cache was invalidated.
		$this->assertFalse( $this->product_cache->is_cached( $product_id ), 'Cache should be invalidated after stock update' );

		// Verify fresh stock is returned.
		$fresh_product = wc_get_product( $product_id );
		$this->assertEquals( 5, $fresh_product->get_stock_quantity(), 'Should return updated stock quantity' );
	}

	/**
	 * @testdox Product cache is invalidated when stock is increased.
	 */
	public function test_cache_invalidated_on_stock_increase() {
		$this->enable_feature();

		$product = WC_Helper_Product::create_simple_product();
		$product->set_manage_stock( true );
		$product->set_stock_quantity( 10 );
		$product->save();

		$product_id = $product->get_id();

		// Cache the product.
		$this->product_cache->remove( $product_id );
		wc_get_product( $product_id );
		$this->assertTrue( $this->product_cache->is_cached( $product_id ) );

		// Increase stock.
		$data_store = \WC_Data_Store::load( 'product' );
		$data_store->update_product_stock( $product_id, 5, 'increase' );

		// Verify cache was invalidated.
		$this->assertFalse( $this->product_cache->is_cached( $product_id ), 'Cache should be invalidated after stock increase' );

		// Verify fresh stock is returned.
		$fresh_product = wc_get_product( $product_id );
		$this->assertEquals( 15, $fresh_product->get_stock_quantity(), 'Stock should be increased to 15' );
	}

	/**
	 * @testdox Product cache is invalidated when stock is decreased.
	 */
	public function test_cache_invalidated_on_stock_decrease() {
		$this->enable_feature();

		$product = WC_Helper_Product::create_simple_product();
		$product->set_manage_stock( true );
		$product->set_stock_quantity( 10 );
		$product->save();

		$product_id = $product->get_id();

		// Cache the product.
		$this->product_cache->remove( $product_id );
		wc_get_product( $product_id );
		$this->assertTrue( $this->product_cache->is_cached( $product_id ) );

		// Decrease stock (simulates order placement).
		$data_store = \WC_Data_Store::load( 'product' );
		$data_store->update_product_stock( $product_id, 3, 'decrease' );

		// Verify cache was invalidated.
		$this->assertFalse( $this->product_cache->is_cached( $product_id ), 'Cache should be invalidated after stock decrease' );

		// Verify fresh stock is returned.
		$fresh_product = wc_get_product( $product_id );
		$this->assertEquals( 7, $fresh_product->get_stock_quantity(), 'Stock should be decreased to 7' );
	}

	/**
	 * @testdox Product cache is invalidated when total sales is updated.
	 */
	public function test_cache_invalidated_on_sales_update() {
		$this->enable_feature();

		$product = WC_Helper_Product::create_simple_product();
		$product->set_total_sales( 0 );
		$product->save();

		$product_id = $product->get_id();

		// Cache the product.
		$this->product_cache->remove( $product_id );
		$cached_product = wc_get_product( $product_id );
		$this->assertEquals( 0, $cached_product->get_total_sales() );
		$this->assertTrue( $this->product_cache->is_cached( $product_id ) );

		// Update sales directly (uses SQL, bypasses CRUD).
		$data_store = \WC_Data_Store::load( 'product' );
		$data_store->update_product_sales( $product_id, 10, 'set' );

		// Verify cache was invalidated.
		$this->assertFalse( $this->product_cache->is_cached( $product_id ), 'Cache should be invalidated after sales update' );

		// Verify fresh sales count is returned.
		$fresh_product = wc_get_product( $product_id );
		$this->assertEquals( 10, $fresh_product->get_total_sales(), 'Should return updated total sales' );
	}

	/**
	 * @testdox Product cache is invalidated when product meta is added directly.
	 */
	public function test_cache_invalidated_on_meta_add() {
		$this->enable_feature();

		$product    = WC_Helper_Product::create_simple_product();
		$product_id = $product->get_id();

		// Cache the product.
		$this->product_cache->remove( $product_id );
		$cached_product = wc_get_product( $product_id );
		$this->assertEmpty( $cached_product->get_meta( '_test_meta' ) );
		$this->assertTrue( $this->product_cache->is_cached( $product_id ) );

		// Add meta directly (bypasses CRUD).
		add_post_meta( $product_id, '_test_meta', 'test_value' );

		// Verify cache was invalidated.
		$this->assertFalse( $this->product_cache->is_cached( $product_id ), 'Cache should be invalidated after meta add' );

		// Verify fresh meta is returned.
		$fresh_product = wc_get_product( $product_id );
		$this->assertEquals( 'test_value', $fresh_product->get_meta( '_test_meta' ), 'Should return new meta value' );
	}

	/**
	 * @testdox Product cache is invalidated when product meta is updated directly.
	 */
	public function test_cache_invalidated_on_meta_update() {
		$this->enable_feature();

		$product = WC_Helper_Product::create_simple_product();
		$product->add_meta_data( '_test_meta', 'original_value', true );
		$product->save();

		$product_id = $product->get_id();

		// Cache the product.
		$this->product_cache->remove( $product_id );
		$cached_product = wc_get_product( $product_id );
		$this->assertEquals( 'original_value', $cached_product->get_meta( '_test_meta' ) );
		$this->assertTrue( $this->product_cache->is_cached( $product_id ) );

		// Update meta directly (bypasses CRUD).
		update_post_meta( $product_id, '_test_meta', 'updated_value' );

		// Verify cache was invalidated.
		$this->assertFalse( $this->product_cache->is_cached( $product_id ), 'Cache should be invalidated after meta update' );

		// Verify fresh meta is returned.
		$fresh_product = wc_get_product( $product_id );
		$this->assertEquals( 'updated_value', $fresh_product->get_meta( '_test_meta' ), 'Should return updated meta value' );
	}

	/**
	 * @testdox Product cache is invalidated when product meta is deleted directly.
	 */
	public function test_cache_invalidated_on_meta_delete() {
		$this->enable_feature();

		$product = WC_Helper_Product::create_simple_product();
		$product->add_meta_data( '_test_meta', 'test_value', true );
		$product->save();

		$product_id = $product->get_id();

		// Cache the product.
		$this->product_cache->remove( $product_id );
		$cached_product = wc_get_product( $product_id );
		$this->assertEquals( 'test_value', $cached_product->get_meta( '_test_meta' ) );
		$this->assertTrue( $this->product_cache->is_cached( $product_id ) );

		// Delete meta directly (bypasses CRUD).
		delete_post_meta( $product_id, '_test_meta' );

		// Verify cache was invalidated.
		$this->assertFalse( $this->product_cache->is_cached( $product_id ), 'Cache should be invalidated after meta delete' );

		// Verify meta is gone.
		$fresh_product = wc_get_product( $product_id );
		$this->assertEmpty( $fresh_product->get_meta( '_test_meta' ), 'Meta should be deleted' );
	}

	/**
	 * @testdox Product cache is invalidated when product is deleted.
	 */
	public function test_cache_invalidated_on_product_delete() {
		$this->enable_feature();

		$product    = WC_Helper_Product::create_simple_product();
		$product_id = $product->get_id();

		// Cache the product.
		$this->product_cache->remove( $product_id );
		wc_get_product( $product_id );
		$this->assertTrue( $this->product_cache->is_cached( $product_id ) );

		// Delete the product.
		$product->delete( true );

		// Verify cache was invalidated.
		$this->assertFalse( $this->product_cache->is_cached( $product_id ), 'Cache should be invalidated after delete' );

		// Verify product is gone.
		$deleted_product = wc_get_product( $product_id );
		$this->assertFalse( $deleted_product, 'Deleted product should return false' );
	}

	/**
	 * @testdox Variation cache is invalidated when variation is updated.
	 */
	public function test_cache_invalidated_on_variation_update() {
		$this->enable_feature();

		$variable_product = WC_Helper_Product::create_variation_product();
		$variations       = $variable_product->get_children();
		$variation_id     = $variations[0];

		// Cache the variation.
		$this->product_cache->remove( $variation_id );
		$variation = wc_get_product( $variation_id );
		$this->assertTrue( $this->product_cache->is_cached( $variation_id ) );

		// Update variation.
		$variation->set_regular_price( 19.99 );
		$variation->save();

		// Verify cache was invalidated.
		$this->assertFalse( $this->product_cache->is_cached( $variation_id ), 'Variation cache should be invalidated after save' );

		// Verify fresh data is returned.
		$fresh_variation = wc_get_product( $variation_id );
		$this->assertEquals( '19.99', $fresh_variation->get_regular_price(), 'Should return updated price' );
	}

	/**
	 * @testdox Cache invalidation respects feature flag being disabled.
	 */
	public function test_invalidation_respects_feature_flag() {
		$this->disable_feature();

		$product = WC_Helper_Product::create_simple_product();
		$product->set_name( 'Test Product' );
		$product->save();

		$product_id = $product->get_id();

		// Prime the normal retrieval path and verify it does not populate the cache when the feature is disabled.
		wc_get_product( $product_id );
		$this->assertFalse(
			$this->product_cache->is_cached( $product_id ),
			'Product should not be cached when feature is disabled, even after retrieval'
		);

		// Update product.
		$product->set_name( 'Updated Product' );
		$product->save();

		// After an update and another retrieval, the cache should still not be used.
		wc_get_product( $product_id );
		$this->assertFalse(
			$this->product_cache->is_cached( $product_id ),
			'Product should still not be cached after update when feature is disabled'
		);
	}
}
