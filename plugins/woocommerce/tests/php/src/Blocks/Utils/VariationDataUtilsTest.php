<?php
declare( strict_types = 1 );

namespace Automattic\WooCommerce\Tests\Blocks\Utils;

use Automattic\WooCommerce\Blocks\Utils\VariationDataUtils;
use WC_Helper_Product;

/**
 * Tests for the VariationDataUtils class.
 */
class VariationDataUtilsTest extends \WC_Unit_Test_Case {

	/**
	 * Test that should_lazy_load_variations returns false for null product.
	 */
	public function test_returns_false_for_null_product() {
		$this->assertFalse( VariationDataUtils::should_lazy_load_variations( null ) );
	}

	/**
	 * Test that should_lazy_load_variations returns false for false product.
	 */
	public function test_returns_false_for_false_product() {
		$this->assertFalse( VariationDataUtils::should_lazy_load_variations( false ) );
	}

	/**
	 * Test that should_lazy_load_variations returns false for simple product.
	 */
	public function test_returns_false_for_simple_product() {
		$product = WC_Helper_Product::create_simple_product();
		$this->assertFalse( VariationDataUtils::should_lazy_load_variations( $product ) );
		$product->delete( true );
	}

	/**
	 * Test that should_lazy_load_variations returns false for variable product below threshold.
	 */
	public function test_returns_false_for_variable_product_below_threshold() {
		// Default threshold is 30, create_variation_product creates ~4 variations.
		$product = WC_Helper_Product::create_variation_product();
		$this->assertFalse( VariationDataUtils::should_lazy_load_variations( $product ) );
		$product->delete( true );
	}

	/**
	 * Test that should_lazy_load_variations returns true for variable product above threshold.
	 */
	public function test_returns_true_for_variable_product_above_threshold() {
		$product = $this->create_variable_product_with_many_variations( 35 );
		$this->assertTrue( VariationDataUtils::should_lazy_load_variations( $product ) );
		$product->delete( true );
	}

	/**
	 * Test that should_lazy_load_variations returns false when product equals threshold.
	 */
	public function test_returns_false_when_product_equals_threshold() {
		$product = $this->create_variable_product_with_many_variations( 30 );
		$this->assertFalse( VariationDataUtils::should_lazy_load_variations( $product ) );
		$product->delete( true );
	}

	/**
	 * Test that the woocommerce_ajax_variation_threshold filter is respected.
	 */
	public function test_respects_threshold_filter() {
		$product         = WC_Helper_Product::create_variation_product();
		$variation_count = count( $product->get_children() );

		// Set threshold below variation count - should enable lazy loading.
		add_filter(
			'woocommerce_ajax_variation_threshold',
			function () use ( $variation_count ) {
				return $variation_count - 1;
			}
		);

		$this->assertTrue( VariationDataUtils::should_lazy_load_variations( $product ) );

		// Remove filter and verify default behavior.
		remove_all_filters( 'woocommerce_ajax_variation_threshold' );
		$this->assertFalse( VariationDataUtils::should_lazy_load_variations( $product ) );

		$product->delete( true );
	}

	/**
	 * Test that the filter receives the product as second argument.
	 */
	public function test_filter_receives_product_argument() {
		$product          = WC_Helper_Product::create_variation_product();
		$received_product = null;

		add_filter(
			'woocommerce_ajax_variation_threshold',
			function ( $threshold, $filter_product ) use ( &$received_product ) {
				$received_product = $filter_product;
				return $threshold;
			},
			10,
			2
		);

		VariationDataUtils::should_lazy_load_variations( $product );

		$this->assertSame( $product, $received_product );

		remove_all_filters( 'woocommerce_ajax_variation_threshold' );
		$product->delete( true );
	}

	/**
	 * Helper to create a variable product with a specific number of variations.
	 *
	 * @param int $variation_count Number of variations to create.
	 * @return \WC_Product_Variable The created product.
	 */
	private function create_variable_product_with_many_variations( int $variation_count ): \WC_Product_Variable {
		$product = new \WC_Product_Variable();
		$product->set_name( 'Test Variable Product' );
		$product->set_sku( 'TEST-VAR-' . microtime( true ) );

		// Create enough attribute values to generate the required variations.
		$values = array();
		for ( $i = 1; $i <= $variation_count; $i++ ) {
			$values[] = "option-$i";
		}

		$attribute = WC_Helper_Product::create_product_attribute_object( 'test-attr', $values );
		$product->set_attributes( array( $attribute ) );
		$product->save();

		// Create variations.
		for ( $i = 1; $i <= $variation_count; $i++ ) {
			WC_Helper_Product::create_product_variation_object(
				$product->get_id(),
				"TEST-VAR-$i",
				10,
				array( 'pa_test-attr' => "option-$i" )
			);
		}

		// Clear cache so get_children() returns fresh data.
		wc_delete_product_transients( $product->get_id() );
		$product = wc_get_product( $product->get_id() );

		return $product;
	}
}

