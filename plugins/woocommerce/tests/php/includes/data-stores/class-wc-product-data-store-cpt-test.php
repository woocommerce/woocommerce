<?php

use Automattic\WooCommerce\Internal\CostOfGoodsSold\CogsAwareUnitTestSuiteTrait;

/**
 * Class WC_Product_Data_Store_CPT_Test
 */
class WC_Product_Data_Store_CPT_Test extends WC_Unit_Test_Case {
	use CogsAwareUnitTestSuiteTrait;

	/**
	 * The default URI.
	 *
	 * @var string
	 */
	private static $default_uri;

	/**
	 * Store the default URI.
	 */
	public static function setUpBeforeClass(): void {
		parent::setUpBeforeClass();
		self::$default_uri = isset( $_SERVER['REQUEST_URI'] ) ? $_SERVER['REQUEST_URI'] : ''; // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.MissingUnslash, WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
	}

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
	 * Restore the default URI.
	 */
	public static function tearDownAfterClass(): void {
		parent::tearDownAfterClass();
		$_SERVER['REQUEST_URI'] = self::$default_uri;
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
	 * Create a simple product with the given name and status, for OR-term search tests.
	 *
	 * @param string $name   Product name.
	 * @param string $status Post status.
	 * @return WC_Product_Simple The saved product.
	 */
	private function create_search_test_product( string $name, string $status = 'publish' ): WC_Product_Simple {
		$product = new WC_Product_Simple();
		$product->set_name( $name );
		$product->set_status( $status );
		$product->set_regular_price( '10' );
		$product->save();

		return $product;
	}

	/**
	 * @testdox Search with an OR term should apply the exclude list to every OR group.
	 */
	public function test_search_products_or_term_applies_exclude(): void {
		$alpha = $this->create_search_test_product( 'Searchable alpha product' );
		$beta  = $this->create_search_test_product( 'Searchable beta product' );

		$data_store = WC_Data_Store::load( 'product' );
		$results    = $data_store->search_products( 'Searchable alpha product or Searchable beta product', '', false, true, null, null, array( $alpha->get_id() ) );

		$this->assertNotContains( $alpha->get_id(), $results, 'Excluded product matched by the first OR group should not be returned' );
		$this->assertContains( $beta->get_id(), $results, 'Non-excluded product should still be returned' );
	}

	/**
	 * @testdox Search with an OR term should apply the include list to every OR group.
	 */
	public function test_search_products_or_term_applies_include(): void {
		$alpha = $this->create_search_test_product( 'Searchable alpha product' );
		$beta  = $this->create_search_test_product( 'Searchable beta product' );

		$data_store = WC_Data_Store::load( 'product' );
		$results    = $data_store->search_products( 'Searchable alpha product or Searchable beta product', '', false, true, null, array( $beta->get_id() ) );

		$this->assertNotContains( $alpha->get_id(), $results, 'Product outside the include list matched by the first OR group should not be returned' );
		$this->assertContains( $beta->get_id(), $results, 'Included product should be returned' );
	}

	/**
	 * @testdox Search with an OR term should apply the status filter to every OR group.
	 */
	public function test_search_products_or_term_applies_status_filter(): void {
		$draft = $this->create_search_test_product( 'Searchable gamma product', 'draft' );
		$beta  = $this->create_search_test_product( 'Searchable beta product' );

		$data_store = WC_Data_Store::load( 'product' );
		$results    = $data_store->search_products( 'Searchable gamma product or Searchable beta product', '', false, false );

		$this->assertNotContains( $draft->get_id(), $results, 'Draft product matched by the first OR group should not be returned when searching published only' );
		$this->assertContains( $beta->get_id(), $results, 'Published product should be returned' );
	}

	/**
	 * @testdox Search with an OR term should apply the product type filter to every OR group.
	 */
	public function test_search_products_or_term_applies_type_filter(): void {
		$plain        = $this->create_search_test_product( 'Searchable alpha product' );
		$downloadable = $this->create_search_test_product( 'Searchable beta product' );
		$downloadable->set_downloadable( true );
		$downloadable->save();

		$data_store = WC_Data_Store::load( 'product' );
		$results    = $data_store->search_products( 'Searchable alpha product or Searchable beta product', 'downloadable', false, true );

		$this->assertNotContains( $plain->get_id(), $results, 'Non-downloadable product matched by the first OR group should not be returned for a downloadable search' );
		$this->assertContains( $downloadable->get_id(), $results, 'Downloadable product should be returned' );
	}

	/**
	 * @testdox Search with an OR term including variations should apply the type and status filters to every OR group.
	 */
	public function test_search_products_or_term_applies_filters_with_variations(): void {
		$plain = $this->create_search_test_product( 'Searchable alpha product' );

		$parent = new WC_Product_Variable();
		$parent->set_name( 'Searchable beta product' );
		$parent->save();

		$variation = new WC_Product_Variation();
		$variation->set_parent_id( $parent->get_id() );
		$variation->set_regular_price( '10' );
		$variation->set_downloadable( true );
		$variation->save();

		$data_store = WC_Data_Store::load( 'product' );
		$results    = $data_store->search_products( 'Searchable alpha product or Searchable beta product', 'downloadable', true, false );

		$this->assertNotContains( $plain->get_id(), $results, 'Non-downloadable product matched by the first OR group should not be returned for a downloadable variations search' );
		$this->assertContains( $variation->get_id(), $results, 'Downloadable variation matched by the second OR group should be returned' );
	}

	/**
	 * @testdox Search with an OR term should apply the post type constraint to every OR group.
	 */
	public function test_search_products_or_term_applies_post_type(): void {
		$alpha   = $this->create_search_test_product( 'Searchable alpha product' );
		$page_id = wp_insert_post(
			array(
				'post_type'   => 'page',
				'post_status' => 'publish',
				'post_title'  => 'Searchable beta product page',
			)
		);

		$this->assertGreaterThan( 0, $page_id, 'Page fixture should have been created' );

		$data_store = WC_Data_Store::load( 'product' );
		$results    = $data_store->search_products( 'Searchable alpha product or Searchable beta product page', '', false, true );

		$this->assertNotContains( $page_id, $results, 'A page matched by the last OR group should not be returned from a product search' );
		$this->assertContains( $alpha->get_id(), $results, 'Product matched by the first OR group should be returned' );
	}

	/**
	 * @testdox Search with a three-group OR term should apply the status filter to the middle group.
	 */
	public function test_search_products_or_term_applies_status_filter_to_middle_group(): void {
		$alpha = $this->create_search_test_product( 'Searchable alpha product' );
		$draft = $this->create_search_test_product( 'Searchable gamma product', 'draft' );
		$beta  = $this->create_search_test_product( 'Searchable beta product' );

		$data_store = WC_Data_Store::load( 'product' );
		$results    = $data_store->search_products( 'Searchable alpha product or Searchable gamma product or Searchable beta product', '', false, false );

		$this->assertNotContains( $draft->get_id(), $results, 'Draft product matched by the middle OR group should not be returned when searching published only' );
		$this->assertContains( $alpha->get_id(), $results, 'Published product matched by the first OR group should be returned' );
		$this->assertContains( $beta->get_id(), $results, 'Published product matched by the last OR group should be returned' );
	}

	/**
	 * @testdox Search with an OR term without extra filters should return matches from every OR group.
	 */
	public function test_search_products_or_term_returns_all_groups(): void {
		$alpha = $this->create_search_test_product( 'Searchable alpha product' );
		$beta  = $this->create_search_test_product( 'Searchable beta product' );

		$data_store = WC_Data_Store::load( 'product' );
		$results    = $data_store->search_products( 'Searchable alpha product or Searchable beta product', '', false, true );

		$this->assertContains( $alpha->get_id(), $results, 'Product matched by the first OR group should be returned' );
		$this->assertContains( $beta->get_id(), $results, 'Product matched by the second OR group should be returned' );
	}

	/**
	 * @testdox Excluded variable products should not be re-added to search results by their matching variations.
	 */
	public function test_search_products_excludes_variable_products_with_matching_variations(): void {
		$parent = new WC_Product_Variable();
		$parent->set_name( 'Excludable variable product' );
		$parent->save();

		$variation = new WC_Product_Variation();
		$variation->set_parent_id( $parent->get_id() );
		$variation->save();

		$data_store = WC_Data_Store::load( 'product' );

		$results = $data_store->search_products( 'Excludable variable', '', true, true );
		$this->assertContains( $parent->get_id(), $results );
		$this->assertContains( $variation->get_id(), $results );

		$results = $data_store->search_products( 'Excludable variable', '', true, true, null, null, array( $parent->get_id() ) );
		$this->assertNotContains( $parent->get_id(), $results, 'An excluded parent must not be re-added through its matching variations' );
		$this->assertContains( $variation->get_id(), $results, 'Excluding a parent must not exclude its variations' );

		$results = $data_store->search_products( 'Excludable variable', '', true, true, null, null, array( $variation->get_id() ) );
		$this->assertNotContains( $variation->get_id(), $results, 'An excluded variation must not be returned' );
		$this->assertContains( $parent->get_id(), $results, 'Excluding a variation must not exclude its parent' );
	}

	/**
	 * @testdox Excluded products should not be re-added to search results when the search term is their numeric ID.
	 */
	public function test_search_products_excludes_numeric_term_matches() {
		$product = new WC_Product_Simple();
		$product->set_name( 'Numeric term widget' );
		$product->save();

		$data_store = WC_Data_Store::load( 'product' );

		$results = $data_store->search_products( (string) $product->get_id(), '', false, true );
		$this->assertContains( $product->get_id(), $results );

		$results = $data_store->search_products( (string) $product->get_id(), '', false, true, null, null, array( $product->get_id() ) );
		$this->assertNotContains( $product->get_id(), $results, 'An excluded product must not be re-added by the numeric term match' );
	}

	/**
	 * A numeric term appends both the searched ID and its parent, so the parent needs its own
	 * exclusion check. Searching a variation by ID is the case that exercises it, since a
	 * top-level product has no parent to re-add.
	 *
	 * @testdox Excluded parents should not be re-added when the search term is a variation's numeric ID.
	 */
	public function test_search_products_excludes_parent_for_numeric_variation_term() {
		$parent = new WC_Product_Variable();
		$parent->set_name( 'Numeric parent widget' );
		$parent->save();

		$variation = new WC_Product_Variation();
		$variation->set_parent_id( $parent->get_id() );
		$variation->save();

		$data_store = WC_Data_Store::load( 'product' );

		$results = $data_store->search_products( (string) $variation->get_id(), '', true, true );
		$this->assertContains( $variation->get_id(), $results );
		$this->assertContains( $parent->get_id(), $results, 'A numeric variation term should surface its parent' );

		$results = $data_store->search_products( (string) $variation->get_id(), '', true, true, null, null, array( $parent->get_id() ) );
		$this->assertNotContains( $parent->get_id(), $results, 'An excluded parent must not be re-added by a numeric variation term' );
		$this->assertContains( $variation->get_id(), $results, 'Excluding the parent must not drop the searched variation' );
	}

	/**
	 * absint() maps any non-numeric exclude value to 0, and 0 is the post_parent every top-level
	 * product carries. Zeros are filtered out of the exclusion list so that such input stays the
	 * no-op it has always been, rather than matching every top-level row.
	 *
	 * @testdox Exclude values that normalise to zero should leave search results untouched.
	 */
	public function test_search_products_ignores_zero_exclude_values() {
		$product = new WC_Product_Simple();
		$product->set_name( 'Zero exclude widget' );
		$product->save();

		$data_store = WC_Data_Store::load( 'product' );

		$baseline = $data_store->search_products( 'Zero exclude', '', true, true );
		$this->assertContains( $product->get_id(), $baseline );

		$zero_like = array(
			'integer zero'      => 0,
			'string zero'       => '0',
			'non-numeric value' => 'abc',
			'empty string'      => '',
		);

		foreach ( $zero_like as $label => $value ) {
			$results = $data_store->search_products( 'Zero exclude', '', true, true, null, null, array( $value ) );
			$this->assertEqualSets( $baseline, $results, "An exclude list holding a {$label} must not change the results" );
		}
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
			'The product with SKU (DUMMY SKU) you are trying to insert is already present in the lookup table'
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
	 * @testdox Cost of Goods Sold information is persisted when the feature is enabled and the value is not null.
	 */
	public function test_cogs_is_persisted_when_feature_is_enabled_and_value_is_not_null() {
		$this->enable_cogs_feature();

		// phpcs:disable Squiz.Commenting
		$product = new class() extends WC_Product {
			protected function adjust_cogs_value_before_set( ?float $value ): ?float {
				return $value;
			}
		};
		// phpcs:enable Squiz.Commenting
		$product->set_cogs_value( 12.34 );
		$product->save();

		$this->assertEquals( '12.34', get_post_meta( $product->get_id(), '_cogs_total_value', true ) );

		// Explicitly test for zero, as in the past the COGS value was not nullable
		// and the behavior of the data store was "don't store when it's zero".

		$product->set_cogs_value( 0 );
		$product->save();

		$this->assertEquals( '0', get_post_meta( $product->get_id(), '_cogs_total_value', true ) );
	}

	/**
	 * @testdox Cost of Goods Sold information is not persisted when the feature is enabled but the value is null.
	 */
	public function test_cogs_is_not_persisted_when_feature_is_enabled_and_value_is_null() {
		$this->enable_cogs_feature();

		// phpcs:disable Squiz.Commenting
		$product = new class() extends WC_Product {
			protected function adjust_cogs_value_before_set( ?float $value ): ?float {
				return $value;
			}
		};
		// phpcs:enable Squiz.Commenting
		$product->set_cogs_value( 12.34 );
		$product->save();

		$this->assertEquals( '12.34', get_post_meta( $product->get_id(), '_cogs_total_value', true ) );

		$product->set_cogs_value( null );
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

		add_filter( 'woocommerce_load_product_cogs_value', fn( $value, $product ) => $value + $product->get_id(), 10, 2 );

		// The save above populates the product object cache; flush so the re-read goes through the
		// data store and applies the load filter registered after the save.
		wp_cache_flush();

		$product = wc_get_product( $product->get_id() );
		$this->assertEquals( 12.34 + $product->get_id(), $product->get_cogs_value() );
	}

	/**
	 * @testdox Saved Cost of Goods Sold information can be modified using the woocommerce_save_cogs_value filter.
	 */
	public function test_cogs_saved_value_can_be_altered_via_filter() {
		$this->enable_cogs_feature();

		add_filter( 'woocommerce_save_product_cogs_value', fn( $value, $product ) => $value + $product->get_id(), 10, 2 );

		$product = new WC_Product();
		$product->set_cogs_value( 12.34 );
		$product->save();

		$this->assertEquals( (string) ( 12.34 + $product->get_id() ), get_post_meta( $product->get_id(), '_cogs_total_value', true ) );
	}

	/**
	 * @testdox Saving of the Cost of Goods Sold information can be suppressed using the woocommerce_save_cogs_value filter with a return value of false.
	 */
	public function test_cogs_saved_value_saving_can_be_suppressed_via_filter() {
		$this->enable_cogs_feature();

		$product = new WC_Product();
		$product->set_cogs_value( 12.34 );
		$product->save();

		// phpcs:ignore Generic.CodeAnalysis.UnusedFunctionParameter.FoundAfterLastUsed
		add_filter( 'woocommerce_save_product_cogs_value', fn( $value, $product ) => false, 10, 2 );

		$product->set_cogs_value( 56.78 );
		$product->save();

		$this->assertEquals( '12.34', get_post_meta( $product->get_id(), '_cogs_total_value', true ) );
	}

	/**
	 * Test update_product_stock updates on the meta-entry.
	 */
	public function test_update_product_stock_meta_update(): void {
		/** @var WC_Product_Data_Store_CPT $store */
		$store = WC_Data_Store::load( 'product' );

		$product = new WC_Product();
		$product->save();
		$product_id = $product->get_id();

		$store->update_product_stock( $product_id, null, 'set' );
		$this->assertSame( '0.000000', get_post_meta( $product_id, '_stock', true ) );
		$store->update_product_stock( $product_id, 10, 'set' );
		$this->assertSame( '10.000000', get_post_meta( $product_id, '_stock', true ) );
		$store->update_product_stock( $product_id, 20.0, 'set' );
		$this->assertSame( '20.000000', get_post_meta( $product_id, '_stock', true ) );
		$store->update_product_stock( $product_id, 30.5, 'set' );
		$this->assertSame( '30.000000', get_post_meta( $product_id, '_stock', true ) );
	}

	/**
	 * @testdox update_version_and_type sets the product_type term when the type changes, skips it when unchanged, and writes _product_version when stale.
	 */
	public function test_update_version_and_type_fires_when_type_changes(): void {
		$store = new class() extends WC_Product_Data_Store_CPT {
			public function update_version_and_type( &$product ): void { // phpcs:ignore Generic.CodeAnalysis.UselessOverridingMethod.Found, Squiz.Commenting.FunctionComment.Missing
				parent::update_version_and_type( $product );
			}
		};

		$product = new WC_Product_Simple();
		$product->save();
		$product_id       = $product->get_id();
		$external_product = new WC_Product_External( $product_id );

		update_post_meta( $product_id, '_product_version', '1.0.0-stale' );

		$store->update_version_and_type( $external_product );

		$this->assertSame( 'external', get_the_terms( $product_id, 'product_type' )[0]->slug );
		$this->assertSame( WC_VERSION, get_post_meta( $product_id, '_product_version', true ) );

		// Type is now unchanged — calling again must not alter the term or the version.
		$store->update_version_and_type( $external_product );

		$this->assertSame( 'external', get_the_terms( $product_id, 'product_type' )[0]->slug );

		$product->delete();
	}

	/**
	 * @testdox update_version_and_type uses the filtered product type as $old_type, not the stored term.
	 */
	public function test_update_version_and_type_respects_product_type_query_filter(): void {
		$store = new class() extends WC_Product_Data_Store_CPT {
			public function update_version_and_type( &$product ): void { // phpcs:ignore Generic.CodeAnalysis.UselessOverridingMethod.Found, Squiz.Commenting.FunctionComment.Missing
				parent::update_version_and_type( $product );
			}
		};

		// Mimics WC_Product_Booking: stored as 'variable' term, but filter overrides type to 'booking' at runtime.
		$product = new WC_Product_Variable();
		$product->save();
		$product_id = $product->get_id();

		$external = new WC_Product_External( $product_id );
		$filter   = static fn ( $override, $id ) => $id === $product_id ? 'external' : false;
		add_filter( 'woocommerce_product_type_query', $filter, 10, 2 );

		$fired_args = null;
		$tracker    = function ( $p, $from, $to ) use ( &$fired_args ) {
			$fired_args = array( $from, $to );
		};
		add_action( 'woocommerce_product_type_changed', $tracker, 10, 3 );

		try {
			$store->update_version_and_type( $external );
		} finally {
			remove_filter( 'woocommerce_product_type_query', $filter, 10 );
			remove_action( 'woocommerce_product_type_changed', $tracker, 10 );
		}

		// Filter overrides old type to 'external', matching new type — no transition expected.
		$this->assertNull( $fired_args );

		$product->delete( true );
	}

	/**
	 * @testdox update_version_and_type corrects a stale stored term when a filter makes $old_type match $new_type but the DB term differs.
	 */
	public function test_update_version_and_type_corrects_stale_term_under_filter(): void {
		$store = new class() extends WC_Product_Data_Store_CPT {
			public function update_version_and_type( &$product ): void { // phpcs:ignore Generic.CodeAnalysis.UselessOverridingMethod.Found, Squiz.Commenting.FunctionComment.Missing
				parent::update_version_and_type( $product );
			}
		};

		// Create a variable product — stored term is 'variable'.
		$product = new WC_Product_Variable();
		$product->save();
		$product_id = $product->get_id();
		$this->assertSame( 'variable', get_the_terms( $product_id, 'product_type' )[0]->slug );

		// Filter makes old_type match new_type ('external'), but stored term is still 'variable' — must be corrected.
		$external = new WC_Product_External( $product_id );
		$filter   = static fn ( $override, $id ) => $id === $product_id ? 'external' : false;
		add_filter( 'woocommerce_product_type_query', $filter, 10, 2 );

		try {
			$store->update_version_and_type( $external );
		} finally {
			remove_filter( 'woocommerce_product_type_query', $filter, 10 );
		}

		// The stored term must now be 'external', not the stale 'variable'.
		$terms = get_the_terms( $product_id, 'product_type' );
		$this->assertSame( 'external', $terms[0]->slug, 'Stored term should be corrected to match $new_type even when the filter masks the mismatch.' );

		$product->delete( true );
	}

	/**
	 * Test update_product_sales updates on the meta-entry.
	 */
	public function test_update_product_sales_meta_update(): void {
		/** @var WC_Product_Data_Store_CPT $store */
		$store = WC_Data_Store::load( 'product' );

		$product = new WC_Product();
		$product->save();
		$product_id = $product->get_id();

		$store->update_product_sales( $product_id, null, 'set' );
		$this->assertSame( '0.000000', get_post_meta( $product_id, 'total_sales', true ) );
		$store->update_product_sales( $product_id, 10, 'set' );
		$this->assertSame( '10.000000', get_post_meta( $product_id, 'total_sales', true ) );
		$store->update_product_sales( $product_id, 20.0, 'set' );
		$this->assertSame( '20.000000', get_post_meta( $product_id, 'total_sales', true ) );
		$store->update_product_sales( $product_id, 30.5, 'set' );
		$this->assertSame( '30.500000', get_post_meta( $product_id, 'total_sales', true ) );
	}
}
