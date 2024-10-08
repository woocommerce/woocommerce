<?php

use Automattic\WooCommerce\Internal\CostOfGoodsSold\CogsAwareUnitTestSuiteTrait;

/**
 * Class WC_Product_Data_Store_CPT_Test
 */
class WC_Product_Data_Store_CPT_Test extends WC_Unit_Test_Case {
	use CogsAwareUnitTestSuiteTrait;

	/**
	 * Runs after each test.
	 */
	public function tearDown(): void {
		parent::tearDown();
		$this->disable_cogs_feature();
		remove_all_filters( 'woocommerce_load_cogs_value' );
		remove_all_filters( 'woocommerce_save_cogs_value' );
	}

	/**
	 * @testdox Variations should appear when searching for parent product's SKU.
	 */
	public function test_variation_searches_parent_sku() {
		$parent = new WC_Product_Variable();
		$parent->set_name( 'Blue widget' );
		$parent->set_sku( 'blue-widget-1' );
		$parent->save();

		$variation = new WC_Product_Variation();
		$variation->set_parent_id( $parent->get_id() );
		$variation->set_sku( '' );
		$variation->save();

		$data_store = WC_Data_Store::load( 'product' );

		// No variations should be found searching for just the parent.
		$results = $data_store->search_products( 'blue-widget-1', '', false, true );
		$this->assertContains( $parent->get_id(), $results );
		$this->assertNotContains( $variation->get_id(), $results );

		// Variation should be found when searching for it.
		$results = $data_store->search_products( 'blue-widget-1', '', true, true );
		$this->assertContains( $parent->get_id(), $results );
		$this->assertContains( $variation->get_id(), $results );

		$variation->set_sku( 'test-widget' );
		$variation->save();

		// Variations should be found when searching for their specific SKU.
		$results = $data_store->search_products( 'test-widget', '', true, true );
		$this->assertContains( $variation->get_id(), $results );
	}

	/**
	 * Ensure product rating counts are calculated correctly.
	 *
	 * @return void
	 */
	public function test_rating_counts_are_summed_correctly(): void {
		$product = WC_Helper_Product::create_simple_product(
			true,
			array( 'manage_stock' => true )
		);

		// Introduce an empty string as one of the values (to simulate bad or legacy data). Doing this through the
		// product model won't work, because of sanitization (the empty string will become 0).
		update_post_meta( $product->get_id(), '_wc_rating_count', array( 1, 2, 3, '', '4' ) );

		// We alter the manage stock property not as part of the test but as a way to ensure a lookup table update
		// takes place when we save (which won't happen if the product model doesn't know of any property changes).
		$product->set_manage_stock( false );

		// No type errors should be raised during this process, since in #41203 we discovered that a type error could be
		// raised from within WC_Product_Data_Store_CPT::get_data_for_lookup_table().
		$product->save();

		// Grab a fresh instance of the product (to avoid caching problems) and verify the rating count.
		$product = wc_get_product( $product->get_id() );
		$this->assertEquals( 10, $product->get_rating_count(), 'The product rating count is the expected value.' );
	}

	/**
	 * Test that only one product is created with a unique SKU
	 * during concurrent requests and when request is initiated via REST API.
	 *
	 * Throw error when two concurrent requests try to create a product with the same SKU.
	 *
	 * @return void
	 */
	public function test_create_product_with_unique_sku_on_concurrent_requests() {
		$this->expectException(
			'Exception',
		);
		$this->expectExceptionMessage(
			'The SKU (DUMMY SKU) you are trying to insert is already under processing'
		);

		// exception is only thrown during the REST API request.
		$_SERVER['REQUEST_URI'] = '/wp-json/wc/v3/products';
		$this->create_products_concurrently();
	}

	/**
	 * Helper function to create products concurrently with same SKU
	 *
	 * @return void
	 */
	private static function create_products_concurrently() {
		$default_props =
			array(
				'name'          => 'Dummy Product',
				'regular_price' => 10,
				'price'         => 10,
				'sku'           => 'DUMMY SKU',
			);

		$product1 = new WC_Product_Simple();
		$product2 = new WC_Product_Simple();
		$product3 = new WC_Product_Simple();

		$product1->set_props( $default_props );
		$product2->set_props( $default_props );
		$product3->set_props( $default_props );

		$product1->save();
		$product2->save();
		$product3->save();
	}

	/**
	 * @testDox Test that meta cache key is changed on direct post meta add.
	 */
	public function test_get_meta_data_is_busted_on_post_meta_add() {
		$product = new WC_Product();
		$product->save();

		// Set the cache.
		$product->get_meta_data();

		$object_id_cache_key = WC_Cache_Helper::get_cache_prefix( 'object_' . $product->get_id() );
		add_post_meta( $product->get_id(), 'test', 'value' );

		$r_object_id_cache_key = WC_Cache_Helper::get_cache_prefix( 'object_' . $product->get_id() );
		$this->assertNotEquals( $object_id_cache_key, $r_object_id_cache_key );

		$product = wc_get_product( $product->get_id() );
		$this->assertEquals( 'value', $product->get_meta( 'test', true ) );
	}


	/**
	 * @testDox Test that meta cache key is changed on direct post meta update.
	 */
	public function test_get_meta_data_is_busted_on_post_meta_update() {
		$product = new WC_Product();
		$product->add_meta_data( 'test', 'value' );
		$product->save();

		// Set the cache.
		$product->get_meta_data();

		$object_id_cache_key = WC_Cache_Helper::get_cache_prefix( 'object_' . $product->get_id() );
		update_post_meta( $product->get_id(), 'test', 'value2' );

		$r_object_id_cache_key = WC_Cache_Helper::get_cache_prefix( 'object_' . $product->get_id() );
		$this->assertNotEquals( $object_id_cache_key, $r_object_id_cache_key );

		$product = wc_get_product( $product->get_id() );
		$this->assertEquals( 'value2', $product->get_meta( 'test', true ) );
	}

	/**
	 * @testDox Test that meta cache key is changed on direct post meta delete.
	 */
	public function test_get_meta_data_is_busted_on_post_meta_delete() {
		$product = new WC_Product();
		$product->add_meta_data( 'test', 'value' );
		$product->save();

		// Set the cache.
		$product->get_meta_data();

		$object_id_cache_key = WC_Cache_Helper::get_cache_prefix( 'object_' . $product->get_id() );
		delete_post_meta( $product->get_id(), 'test' );

		$r_object_id_cache_key = WC_Cache_Helper::get_cache_prefix( 'object_' . $product->get_id() );
		$this->assertNotEquals( $object_id_cache_key, $r_object_id_cache_key );

		$product = wc_get_product( $product->get_id() );
		$this->assertEmpty( $product->get_meta( 'test', true ) );
	}

	/**
	 * @testdox Cost of Goods Sold information is not persisted when the feature is disabled.
	 */
	public function test_cogs_is_not_persisted_when_feature_is_disabled() {
		$this->disable_cogs_feature();
		$error_message = '';

		$this->register_legacy_proxy_function_mocks(
			array(
				'wc_doing_it_wrong' => function ( $function_name, $message ) use ( &$error_message ) {
					$error_message = $message; },
			)
		);

		$product = new WC_Product();
		$product->set_cogs_value( 12.34 );
		$product->save();

		$this->assertEmpty( get_post_meta( $product->get_id(), '_cogs_total_value', true ) );
		$this->assertMatchesRegularExpression( '/The Cost of Goods sold feature is disabled, thus the method called will do nothing and will return dummy data/', $error_message );
	}

	/**
	 * @testdox Cost of Goods Sold information is persisted when the feature is enabled and the value is non-zero.
	 */
	public function test_cogs_is_persisted_when_feature_is_enabled_and_value_is_non_zero() {
		$this->enable_cogs_feature();

		$product = new WC_Product();
		$product->set_cogs_value( 12.34 );
		$product->save();

		$this->assertEquals( '12.34', get_post_meta( $product->get_id(), '_cogs_total_value', true ) );
	}

	/**
	 * @testdox Cost of Goods Sold information is not persisted when the feature is enabled but the value is zero.
	 */
	public function test_cogs_is_not_persisted_when_feature_is_enabled_and_value_is_zero() {
		$this->enable_cogs_feature();

		$product = new WC_Product();
		$product->set_cogs_value( 12.34 );
		$product->save();

		$this->assertEquals( '12.34', get_post_meta( $product->get_id(), '_cogs_total_value', true ) );

		$product->set_cogs_value( 0 );
		$product->save();

		$this->assertEmpty( get_post_meta( $product->get_id(), '_cogs_total_value', true ) );
	}

	/**
	 * @testdox Loaded Cost of Goods Sold information can be modified using the woocommerce_load_cogs_value filter.
	 */
	public function test_cogs_loaded_value_can_be_altered_via_filter() {
		$this->enable_cogs_feature();

		$product = new WC_Product();
		$product->set_cogs_value( 12.34 );
		$product->save();

		add_filter( 'woocommerce_load_cogs_value', fn( $value, $product ) => $value + $product->get_id(), 10, 2 );

		$product = wc_get_product( $product->get_id() );
		$this->assertEquals( 12.34 + $product->get_id(), $product->get_cogs_value() );
	}

	/**
	 * @testdox Saved Cost of Goods Sold information can be modified using the woocommerce_save_cogs_value filter.
	 */
	public function test_cogs_saved_value_can_be_altered_via_filter() {
		$this->enable_cogs_feature();

		add_filter( 'woocommerce_save_cogs_value', fn( $value, $product ) => $value + $product->get_id(), 10, 2 );

		$product = new WC_Product();
		$product->set_cogs_value( 12.34 );
		$product->save();

		$this->assertEquals( (string) ( 12.34 + $product->get_id() ), get_post_meta( $product->get_id(), '_cogs_total_value', true ) );
	}

	/**
	 * @testdox Saving of the Cost of Goods Sold information can be suppressed using the woocommerce_save_cogs_value filter with a return value of null.
	 */
	public function test_cogs_saved_value_saving_can_be_suppressed_via_filter() {
		$this->enable_cogs_feature();

		$product = new WC_Product();
		$product->set_cogs_value( 12.34 );
		$product->save();

		// phpcs:ignore Generic.CodeAnalysis.UnusedFunctionParameter.FoundAfterLastUsed
		add_filter( 'woocommerce_save_cogs_value', fn( $value, $product ) => null, 10, 2 );

		$product->set_cogs_value( 56.78 );
		$product->save();

		$this->assertEquals( '12.34', get_post_meta( $product->get_id(), '_cogs_total_value', true ) );
	}
}
