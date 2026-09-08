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
	 * @testdox A variation's image ID defaults to integer zero in all public data contexts.
	 */
	public function test_image_id_defaults_to_integer_zero() {
		$variation = new WC_Product_Variation();

		$this->assertSame( 0, $variation->get_image_id(), 'The view-context image ID should default to integer zero.' );
		$this->assertSame( 0, $variation->get_image_id( 'edit' ), 'The edit-context image ID should default to integer zero.' );
		$this->assertSame( 0, $variation->get_data()['image_id'], 'The raw image ID should default to integer zero.' );
	}

	/**
	 * @testdox A numeric-string image ID is exposed as an integer before and after saving a variation.
	 */
	public function test_numeric_string_image_id_is_integer_before_and_after_save() {
		$image_id = self::factory()->post->create( array( 'post_type' => 'attachment' ) );
		$this->variation->set_image_id( (string) $image_id );

		$this->assertSame( $image_id, $this->variation->get_image_id(), 'The view-context image ID should be an integer before saving.' );
		$this->assertSame( $image_id, $this->variation->get_image_id( 'edit' ), 'The edit-context image ID should be an integer before saving.' );
		$this->assertSame( $image_id, $this->variation->get_changes()['image_id'], 'The pending image ID should be an integer before saving.' );
		$this->assertSame( 0, $this->variation->get_data()['image_id'], 'Committed image data should remain the integer-zero default before saving.' );

		$variation_id       = $this->variation->save();
		$reloaded_variation = new WC_Product_Variation( $variation_id );

		$this->assertSame( $image_id, $reloaded_variation->get_image_id(), 'The view-context image ID should remain an integer after reloading.' );
		$this->assertSame( $image_id, $reloaded_variation->get_image_id( 'edit' ), 'The edit-context image ID should remain an integer after reloading.' );
		$this->assertSame( $image_id, $reloaded_variation->get_data()['image_id'], 'The raw image ID should remain an integer after reloading.' );
	}

	/**
	 * @testdox Parent image data is exposed as an integer without changing a variation's own image data.
	 */
	public function test_parent_data_image_id_is_integer() {
		$variation = new WC_Product_Variation();

		$variation->set_parent_data( array( 'image_id' => '123' ) );

		$this->assertSame( 123, $variation->get_parent_data()['image_id'], 'A numeric-string parent image ID should be an integer.' );
		$this->assertSame( 123, $variation->get_image_id(), 'The inherited view-context image ID should be an integer.' );
		$this->assertSame( 0, $variation->get_image_id( 'edit' ), 'The variation edit-context image ID should remain integer zero.' );
		$this->assertSame( 0, $variation->get_data()['image_id'], 'The variation raw image ID should remain integer zero.' );

		$variation->set_parent_data( array( 'image_id' => false ) );

		$this->assertSame( 0, $variation->get_parent_data()['image_id'], 'A missing parent image should be integer zero.' );
		$this->assertSame( 0, $variation->get_image_id(), 'The inherited view-context image ID should be integer zero when the parent has no image.' );
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
	 * @testdox A variation's viewability follows its parent's status.
	 */
	public function test_is_viewable_variation_follows_parent_status() {
		wp_set_current_user( 0 );
		$this->assertTrue( $this->variation->is_viewable(), 'A variation of a published parent is viewable when logged out.' );
		$this->assertTrue( $this->variation->is_publicly_viewable(), 'A variation of a published parent is publicly viewable.' );

		$this->parent_product->set_status( 'draft' );
		$this->parent_product->save();

		wp_set_current_user( 0 );
		$this->assertFalse( $this->variation->is_viewable(), 'A variation whose parent is a draft is not viewable when logged out.' );

		$admin = self::factory()->user->create( array( 'role' => 'administrator' ) );
		wp_set_current_user( $admin );
		$this->assertTrue( $this->variation->is_viewable(), 'A variation whose parent is a draft is viewable by admins.' );
		$this->assertFalse( $this->variation->is_publicly_viewable(), 'A variation whose parent is a draft is never publicly viewable.' );
	}
}
