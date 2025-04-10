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
}
