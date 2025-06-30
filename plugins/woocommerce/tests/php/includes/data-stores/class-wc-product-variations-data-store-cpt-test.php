<?php
declare( strict_types=1 );

use Automattic\WooCommerce\Internal\CostOfGoodsSold\CogsAwareUnitTestSuiteTrait;

/**
 * Class WC_Product_Variation_Data_Store_CPT_Test
 */
class WC_Product_Variation_Data_Store_CPT_Test extends WC_Unit_Test_Case {
	use CogsAwareUnitTestSuiteTrait;

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
