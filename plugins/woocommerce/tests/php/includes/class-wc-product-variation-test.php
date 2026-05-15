<?php
declare( strict_types=1 );

use Automattic\WooCommerce\Internal\CostOfGoodsSold\CogsAwareUnitTestSuiteTrait;

/**
 * Tests for the WC_Product_Variation class.
 */
class WC_Product_Variation_Test extends WC_Unit_Test_Case {
	use CogsAwareUnitTestSuiteTrait;

	/**
	 * @var WC_Product_Variable
	 */
	private WC_Product_Variable $parent_product;

	/**
	 * @var WC_Product_Variation
	 */
	private WC_Product_Variation $variation;

	/**
	 * Runs before each test.
	 */
	public function setUp(): void {
		parent::setUp();

		$this->enable_cogs_feature();

		$this->parent_product = WC_Helper_Product::create_variation_product();
		$this->variation      = wc_get_product( $this->parent_product->get_children()[0] );
	}

	/**
	 * Runs after each test.
	 */
	public function tearDown(): void {
		parent::tearDown();
		$this->disable_cogs_feature();
	}

	/**
	 * @testdox By default the defined Cost of Goods Sold is null, and the value is absolute.
	 */
	public function test_default_cogs_values() {
		$this->assertNull( $this->variation->get_cogs_value() );
		$this->assertFalse( $this->variation->get_cogs_value_is_additive() );
	}

	/**
	 * @testdox The defined Cost of Goods Sold can be set to zero, overriding the default behavior.
	 */
	public function test_cogs_value_can_be_set_to_zero() {
		$this->variation->set_cogs_value( 0 );
		$this->assertEquals( 0, $this->variation->get_cogs_value() );
	}

	/**
	 * @testdox The effective Cost of Goods Sold value is equal to the defined value, but null yielding zero.
	 */
	public function test_cogs_effective_value() {
		$this->variation->set_cogs_value( null );
		$this->assertEquals( 0, $this->variation->get_cogs_effective_value() );

		$this->variation->set_cogs_value( 0 );
		$this->assertEquals( 0, $this->variation->get_cogs_effective_value() );

		$this->variation->set_cogs_value( 12.34 );
		$this->assertEquals( 12.34, $this->variation->get_cogs_effective_value() );
	}

	/**
	 * @testdox When the "additive" flag is set, the total Cost of Goods Sold value is the sum of the parent's and the variation effective values.
	 *
	 * @testWith [null, 12.34]
	 *           [0, 12.34]
	 *           [10, 22.34]
	 *
	 * @param float|null $defined_value Defined value to test with.
	 * @param float      $expected_value Expected total value.
	 * @return void
	 */
	public function test_cogs_additive_total_value( ?float $defined_value, float $expected_value ) {
		$this->parent_product->set_cogs_value( 12.34 );
		$this->parent_product->save();

		$this->variation->set_cogs_value_is_additive( true );

		$this->variation->set_cogs_value( $defined_value );
		$this->assertEquals( $expected_value, $this->variation->get_cogs_total_value() );
	}

	/**
	 * @testdox When the "additive" flag is not set, the total Cost of Goods Sold value is the parent's effective value if the variation's value is null, or the variation's effective value otherwise.
	 *
	 * @testWith [null, 12.34]
	 *           [0, 0]
	 *           [10, 10]
	 *
	 * @param float|null $defined_value Defined value to test with.
	 * @param float      $expected_value Expected total value.
	 */
	public function test_cogs_absolute_total_value( ?float $defined_value, float $expected_value ) {
		$this->parent_product->set_cogs_value( 12.34 );
		$this->parent_product->save();

		$this->variation->set_cogs_value_is_additive( false );

		$this->variation->set_cogs_value( $defined_value );
		$this->assertEquals( $expected_value, $this->variation->get_cogs_total_value() );
	}

	/**
	 * Ensure get_permalink() handles non-array variation data without fataling.
	 *
	 * @testdox get_permalink() returns a URL without fataling when $item_object['variation'] is a string rather than the expected variation-attributes array.
	 */
	public function test_get_permalink_handles_non_array_variation_value() {
		$url = $this->variation->get_permalink( array( 'variation' => 'some-string-value' ) );

		$this->assertIsString( $url );
		$this->assertNotEmpty( $url );
	}

	/**
	 * @testdox set_attributes() converts a taxonomy term label containing fraction/special characters to its slug so the variation editor can resolve it back to a term (regression for woo#26233 / RSMAPGJ-356).
	 */
	public function test_set_attributes_converts_fraction_term_label_to_slug() {
		$attribute_data = WC_Helper_Product::create_attribute( 'shoe-size-frac', array( '6½', '7', '7½' ) );
		$taxonomy       = $attribute_data['attribute_taxonomy'];

		$term = get_term_by( 'name', '7½', $taxonomy );
		$this->assertNotEmpty( $term, 'Term with fraction label should exist.' );
		$this->assertNotSame( '7½', $term->slug, 'Slug should differ from label for fraction names.' );

		$variation = new WC_Product_Variation();
		$variation->set_parent_id( $this->parent_product->get_id() );
		$variation->set_attributes( array( $taxonomy => '7½' ) );

		$stored = $variation->get_attributes();
		$this->assertArrayHasKey( $taxonomy, $stored );
		$this->assertSame( $term->slug, $stored[ $taxonomy ], 'Fraction label should be converted to the term slug.' );

		// And the slug should resolve back to the original display label via get_attribute().
		$this->assertSame( '7½', $variation->get_attribute( $taxonomy ) );
	}

	/**
	 * @testdox set_attributes() leaves an already-correct term slug untouched.
	 */
	public function test_set_attributes_preserves_existing_slug() {
		$attribute_data = WC_Helper_Product::create_attribute( 'shoe-size-slug', array( 'small', 'medium' ) );
		$taxonomy       = $attribute_data['attribute_taxonomy'];

		$variation = new WC_Product_Variation();
		$variation->set_parent_id( $this->parent_product->get_id() );
		$variation->set_attributes( array( $taxonomy => 'small' ) );

		$stored = $variation->get_attributes();
		$this->assertSame( 'small', $stored[ $taxonomy ] );
	}

	/**
	 * @testdox set_attributes() leaves non-taxonomy (custom) attribute values untouched.
	 */
	public function test_set_attributes_leaves_custom_attribute_values_untouched() {
		$variation = new WC_Product_Variation();
		$variation->set_parent_id( $this->parent_product->get_id() );
		$variation->set_attributes( array( 'colour' => 'Royal Blue ½' ) );

		$stored = $variation->get_attributes();
		$this->assertSame( 'Royal Blue ½', $stored['colour'] );
	}
}
