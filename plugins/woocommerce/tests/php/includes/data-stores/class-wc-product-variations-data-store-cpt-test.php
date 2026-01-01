<?php
declare( strict_types=1 );

use Automattic\WooCommerce\Enums\ProductStatus;
use Automattic\WooCommerce\Enums\ProductStockStatus;
use Automattic\WooCommerce\Enums\ProductTaxStatus;
use Automattic\WooCommerce\Internal\CostOfGoodsSold\CogsAwareUnitTestSuiteTrait;

/**
 * Class WC_Product_Variation_Data_Store_CPT_Test
 */
class WC_Product_Variation_Data_Store_CPT_Test extends WC_Unit_Test_Case {
	use CogsAwareUnitTestSuiteTrait;

	/**
	 * @var WC_Product_Variation_Data_Store_CPT Data store instance.
	 */
	private WC_Product_Variation_Data_Store_CPT $data_store;

	/**
	 * Setup test fixtures
	 */
	public function setUp(): void {
		parent::setUp();

		$this->data_store = new WC_Product_Variation_Data_Store_CPT();
	}

	/**
	 * Runs after each test.
	 */
	public function tearDown(): void {
		parent::tearDown();
		$this->disable_cogs_feature();
		remove_all_filters( 'woocommerce_load_product_cogs_is_additive_flag' );
		remove_all_filters( 'woocommerce_save_product_cogs_is_additive_flag' );
	}

	/**
	 * Test that read_product_data loads basic variation properties correctly
	 */
	public function test_read_product_data_loads_all_properties_for_the_variation() {
		$this->enable_cogs_feature();

		$parent = WC_Helper_Product::create_variation_product();
		$parent->set_sold_individually( true );
		$parent->set_tax_status( ProductTaxStatus::SHIPPING );
		$parent->set_cross_sell_ids( array( 1, 2, 3 ) );
		$parent_id = $parent->save();

		$variation = new WC_Product_Variation();
		$variation->set_parent_id( $parent_id );
		$variation->set_description( 'This is a test variation description' );
		$variation->set_regular_price( '29.99' );
		$variation->set_sale_price( '24.99' );
		$variation->set_date_on_sale_from( '2024-01-01' );
		$variation->set_date_on_sale_to( '2024-12-31' );
		$variation->set_manage_stock( true );
		$variation->set_virtual( true );
		$variation->set_downloadable( true );
		$variation->set_gallery_image_ids( array( 123, 456, 789 ) );
		$variation->set_download_limit( 3 );
		$variation->set_download_expiry( 30 );
		$variation->set_backorders( 'no' );
		$variation->set_sku( 'TEST-VARIATION-SKU' );
		$variation->set_global_unique_id( '1234567890123' );
		$variation->set_stock_quantity( 22 );
		$variation->set_weight( '2.5' );
		$variation->set_length( '10' );
		$variation->set_width( '8' );
		$variation->set_height( '5' );
		$variation->set_tax_class( 'reduced-rate' );
		$variation->set_cogs_value_is_additive( true );
		$variation->set_cogs_value( 43 );
		$variation->set_sold_individually( true );
		$variation->save();

		$product = new WC_Product_Variation();
		$product->set_id( $variation->get_id() );

		$this->data_store->read( $product );

		$this->assertEquals( 'This is a test variation description', $product->get_description() );
		$this->assertEquals( '29.99', $product->get_regular_price() );
		$this->assertEquals( '24.99', $product->get_sale_price() );
		$this->assertEquals( '2024-01-01', $product->get_date_on_sale_from()->format( 'Y-m-d' ) );
		$this->assertEquals( '2024-12-31', $product->get_date_on_sale_to()->format( 'Y-m-d' ) );
		$this->assertTrue( $product->get_manage_stock() );
		$this->assertEquals( ProductStockStatus::IN_STOCK, $product->get_stock_status() );
		$this->assertTrue( $product->is_virtual() );
		$this->assertTrue( $product->is_downloadable() );
		$this->assertEquals( array( '123', '456', '789' ), $product->get_gallery_image_ids() );
		$this->assertEquals( 3, $product->get_download_limit() );
		$this->assertEquals( 30, $product->get_download_expiry() );
		$this->assertEquals( 'no', $product->get_backorders() );
		$this->assertEquals( 'TEST-VARIATION-SKU', $product->get_sku() );
		$this->assertEquals( '1234567890123', $product->get_global_unique_id() );
		$this->assertEquals( 22, $product->get_stock_quantity() );
		$this->assertEquals( '2.5', $product->get_weight() );
		$this->assertEquals( '10', $product->get_length() );
		$this->assertEquals( '8', $product->get_width() );
		$this->assertEquals( '5', $product->get_height() );
		$this->assertEquals( 'reduced-rate', $product->get_tax_class() );
		$this->assertTrue( $product->get_cogs_value_is_additive() );
		$this->assertEquals( 43, $product->get_cogs_value() );
		$this->assertTrue( $product->get_sold_individually() );
		$this->assertEquals( ProductTaxStatus::SHIPPING, $product->get_tax_status() );
		$this->assertEquals( array( 1, 2, 3 ), $product->get_cross_sell_ids() );
	}

	/**
	 * Test that read_product_data loads stock properties correctly
	 */
	public function test_read_product_data_loads_stock_properties_for_the_variation() {
		$variation = new WC_Product_Variation();
		$variation->set_manage_stock( true );
		$variation->set_stock_status( ProductStockStatus::OUT_OF_STOCK );
		$variation->set_low_stock_amount( 5 );
		$variation->save();

		$product = new WC_Product_Variation();
		$product->set_id( $variation->get_id() );

		$this->data_store->read( $product );

		$this->assertEquals( ProductStockStatus::OUT_OF_STOCK, $product->get_stock_status() );
		$this->assertEquals( 5, $product->get_low_stock_amount() );

		$variation->set_backorders( 'yes' );
		$variation->save();

		$this->data_store->read( $product );

		$this->assertEquals( 'yes', $product->get_backorders() );
	}

	/**
	 * Test that read_product_data loads properties for the parent
	 */
	public function test_read_product_data_loads_properties_for_the_parent() {
		$parent = WC_Helper_Product::create_variation_product();
		$parent->set_name( 'Test Parent Product' );
		$parent->set_status( ProductStatus::DRAFT );
		$parent->set_sku( 'TEST-PARENT-SKU' );
		$parent->set_global_unique_id( '1234567890123' );
		$parent->set_manage_stock( true );
		$parent->set_backorders( 'notify' );
		$parent->set_stock_quantity( 32 );
		$parent->set_weight( '5.5' );
		$parent->set_length( '15' );
		$parent->set_width( '10' );
		$parent->set_height( '7' );
		$parent->set_tax_class( 'reduced-rate' );
		$parent->set_purchase_note( 'This is a purchase note' );
		$parent_id = $parent->save();

		$variation = new WC_Product_Variation();
		$variation->set_parent_id( $parent_id );
		$variation->save();

		$product = new WC_Product_Variation();
		$product->set_id( $variation->get_id() );

		$this->data_store->read( $product );

		$parent_data = $product->get_parent_data();
		$this->assertEquals( 'Test Parent Product', $parent_data['title'] );
		$this->assertEquals( ProductStatus::DRAFT, $parent_data['status'] );
		$this->assertEquals( 'TEST-PARENT-SKU', $parent_data['sku'] );
		$this->assertEquals( '1234567890123', $parent_data['global_unique_id'] );
		$this->assertEquals( 'yes', $parent_data['manage_stock'] );
		$this->assertEquals( 'notify', $parent_data['backorders'] );
		$this->assertEquals( 32, $parent_data['stock_quantity'] );
		$this->assertEquals( '5.5', $parent_data['weight'] );
		$this->assertEquals( '15', $parent_data['length'] );
		$this->assertEquals( '10', $parent_data['width'] );
		$this->assertEquals( '7', $parent_data['height'] );
		$this->assertEquals( 'reduced-rate', $parent_data['tax_class'] );
		$this->assertEquals( 'This is a purchase note', $parent_data['purchase_note'] );
		$this->assertArrayHasKey( 'shipping_class_id', $parent_data );
		$this->assertArrayHasKey( 'catalog_visibility', $parent_data );
		$this->assertArrayHasKey( 'image_id', $parent_data );
	}

	/**
	 * @testdox Cost of Goods Sold "value is additive" flag is not persisted when the feature is disabled.
	 */
	public function test_cogs_additive_flag_is_not_persisted_when_feature_is_disabled() {
		$this->disable_cogs_feature();

		$product = $this->get_variation();
		$product->set_cogs_value_is_additive( true );
		$product->save();

		$this->assertEmpty( get_post_meta( $product->get_id(), '_cogs_value_is_additive', true ) );
	}

	/**
	 * @testdox Cost of Goods Sold "value is additive" flag is persisted when the feature is enabled and the value is "true".
	 */
	public function test_cogs_additive_flag_is_persisted_when_feature_is_enabled_and_value_is_true() {
		$this->enable_cogs_feature();

		$product = $this->get_variation();
		$product->set_cogs_value_is_additive( true );
		$product->save();

		$this->assertEquals( 'yes', get_post_meta( $product->get_id(), '_cogs_value_is_additive', true ) );
	}

	/**
	 * @testdox Cost of Goods Sold "value is additive" flag is not persisted when the feature is enabled and the value is "false".
	 */
	public function test_cogs_additive_flag_is_not_persisted_when_feature_is_enabled_and_value_is_false() {
		$this->enable_cogs_feature();

		$product = $this->get_variation();
		$product->set_cogs_value_is_additive( true );
		$product->save();

		$this->assertEquals( 'yes', get_post_meta( $product->get_id(), '_cogs_value_is_additive', true ) );

		$product->set_cogs_value_is_additive( false );
		$product->save();

		$this->assertEmpty( get_post_meta( $product->get_id(), '_cogs_value_is_additive', true ) );
	}

	/**
	 * @testdox Loaded Cost of Goods Sold "value is additive" flag can be modified using the woocommerce_load_product_cogs_is_additive_flag filter.
	 *
	 * @testWith [true]
	 *           [false]
	 *
	 * @param bool $flag_value Value of the additive flag to test with.
	 */
	public function test_cogs_additive_flag_loaded_value_can_be_altered_via_filter( bool $flag_value ) {
		$this->enable_cogs_feature();

		$product = $this->get_variation();
		$product->set_cogs_value_is_additive( $flag_value );
		$product->save();

		// phpcs:ignore Generic.CodeAnalysis.UnusedFunctionParameter.FoundAfterLastUsed
		add_filter( 'woocommerce_load_product_cogs_is_additive_flag', fn( $value, $product ) => ! $value, 10, 2 );

		$product = wc_get_product( $product->get_id() );
		$this->assertEquals( ! $flag_value, $product->get_cogs_value_is_additive() );
	}

	/**
	 * @testdox Saved Cost of Goods Sold "value is additive" flag can be modified using the woocommerce_save_product_cogs_is_additive_flag filter.
	 *
	 * @testWith [true]
	 *           [false]
	 *
	 * @param bool $flag_value Value of the additive flag to test with.
	 */
	public function test_cogs_saved_additive_flag_can_be_altered_via_filter( bool $flag_value ) {
		$this->enable_cogs_feature();

		// phpcs:ignore Generic.CodeAnalysis.UnusedFunctionParameter.FoundAfterLastUsed
		add_filter( 'woocommerce_save_product_cogs_is_additive_flag', fn( $value, $product ) => ! $value, 10, 2 );

		$product = $this->get_variation();
		$product->set_cogs_value_is_additive( $flag_value );
		$product->save();

		// We expect to get the inverse of what we saved.
		$this->assertEquals( $flag_value ? '' : 'yes', get_post_meta( $product->get_id(), '_cogs_value_is_additive', true ) );
	}

	/**
	 * @testdox Saving of the Cost of Goods Sold "value is additive" flag can be suppressed using the woocommerce_save_product_cogs_is_additive_flag filter with a return value of null.
	 *
	 * @testWith [true]
	 *           [false]
	 *
	 * @param bool $flag_value Value of the additive flag to test with.
	 */
	public function test_cogs_saved_additive_flag_saving_can_be_suppressed_via_filter( bool $flag_value ) {
		$this->enable_cogs_feature();

		$product = $this->get_variation();
		$product->set_cogs_value_is_additive( $flag_value );
		$product->save();

		// phpcs:ignore Generic.CodeAnalysis.UnusedFunctionParameter.FoundAfterLastUsed
		add_filter( 'woocommerce_save_product_cogs_is_additive_flag', fn( $value, $product ) => null, 10, 2 );

		$product->set_cogs_value_is_additive( ! $flag_value );
		$product->save();

		// We expect to get what we saved the first time.
		$this->assertEquals( $flag_value ? 'yes' : '', get_post_meta( $product->get_id(), '_cogs_value_is_additive', true ) );
	}

	/**
	 * Create a variable product and return one of its variations.
	 *
	 * @return WC_Product_Variation The variation created.
	 */
	private function get_variation(): WC_Product_Variation {
		return wc_get_product( ( WC_Helper_Product::create_variation_product() )->get_children()[0] );
	}
}
