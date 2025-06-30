<?php
/**
 * Tests for the WC_Order_Item class.
 *
 * @package WooCommerce\Tests\Order_Item
 */

declare( strict_types=1 );

use Automattic\WooCommerce\Internal\CostOfGoodsSold\CogsAwareUnitTestSuiteTrait;

/**
 * Tests for the WC_Order_Item class.
 */
class WC_Tests_Base_Order_Item extends WC_Unit_Test_Case {
	use CogsAwareUnitTestSuiteTrait;

	/**
	 * The system under test.
	 *
	 * @var WC_Order_Item
	 */
	private WC_Order_Item $sut;

	/**
	 * Runs before each test.
	 */
	public function setUp(): void {
		parent::setUp();

		// phpcs:disable Squiz.Commenting
		$this->sut = new class() extends WC_Order_Item {
			public bool $has_cogs_value = false;
			public $cogs_core_value     = -1;

			public function __construct( $item = 0 ) {
				$this->data['cogs_value'] = null;
				parent::__construct( $item );
			}

			public function has_cogs(): bool {
				return $this->has_cogs_value;
			}

			protected function calculate_cogs_value_core(): ?float {
				return -1 === $this->cogs_core_value ? parent::calculate_cogs_value_core() : $this->cogs_core_value;
			}
		};
		// phpcs:enable Squiz.Commenting
	}

	/**
	 * Runs after each test.
	 */
	public function tearDown(): void {
		parent::tearDown();
		$this->disable_cogs_feature();

		remove_all_filters( 'woocommerce_calculated_order_item_cogs_value' );
	}

	/**
	 * @testdox Order item classes don't manage Cost of Goods Sold by default.
	 */
	public function test_order_items_dont_have_cogs_by_default() {
		$sut = new WC_Order_Item();
		$this->assertFalse( $sut->has_cogs() );
	}

	/**
	 * @testdox 'calculate_cogs_value' returns false, and 'doing it wrong' is thrown, if the Cost of Goods Sold feature is disabled.
	 */
	public function test_calculate_cogs_simply_returns_false_if_cogs_disabled() {
		$this->sut->has_cogs_value = true;
		$this->expect_doing_it_wrong_cogs_disabled( 'WC_Order_Item::calculate_cogs_value' );

		$this->assertFalse( $this->sut->calculate_cogs_value() );
	}

	/**
	 * @testdox 'calculate_cogs_value' returns false if the Cost of Goods Sold feature is enabled but the class doesn't manage it.
	 */
	public function test_calculate_cogs_simply_returns_false_if_cogs_enabled_but_class_has_no_cogs() {
		$this->enable_cogs_feature();

		$this->assertFalse( $this->sut->calculate_cogs_value() );
	}

	/**
	 * @testdox 'calculate_cogs_value' throws an exception if the derived class doesn't override the default method implementation.
	 */
	public function test_calculate_cogs_throws_if_class_does_not_override_core_calculation() {
		$this->sut->has_cogs_value = true;
		$this->enable_cogs_feature();

		$this->expectExceptionMessage( 'Method WC_Order_Item::calculate_cogs_value_core is not implemented. Classes overriding has_cogs must override this method too.' );

		$this->sut->calculate_cogs_value();
	}

	/**
	 * @testdox 'calculate_cogs_value' sets the value returned by the 'calculate_cogs_core' override, and returns true.
	 */
	public function test_calculate_cogs_sets_value_from_core_calculation_overriden_in_child_class() {
		$this->sut->has_cogs_value = true;
		$this->enable_cogs_feature();

		$this->sut->cogs_core_value = 12.34;

		$this->assertTrue( $this->sut->calculate_cogs_value() );
		$this->assertEquals( 12.34, $this->sut->get_cogs_value() );
	}

	/**
	 * @testdox 'get_cogs_value' returns zero if the Cost of Goods Sold feature is enabled but the class doesn't manage it.
	 */
	public function test_get_cogs_value_returns_zero_if_item_has_no_cogs() {
		$this->sut->cogs_core_value = 12.34;
		$this->enable_cogs_feature();

		$this->assertEquals( 0, $this->sut->get_cogs_value() );
	}

	/**
	 * @testdox 'get_cogs_value' returns zero, and 'doing it wrong' is thrown, if the Cost of Goods Sold feature is disabled.
	 */
	public function test_get_cogs_value_returns_zero_if_cogs_feature_is_not_enabled() {
		$this->sut->cogs_core_value = 12.34;
		$this->sut->has_cogs_value  = true;
		$this->expect_doing_it_wrong_cogs_disabled( 'WC_Order_Item::get_cogs_value' );

		$this->assertEquals( 0, $this->sut->get_cogs_value() );
	}

	/**
	 * @testdox 'set_cogs_value' does nothing if the Cost of Goods Sold feature is enabled but the class doesn't manage it.
	 */
	public function test_set_cogs_value_does_nothing_if_item_has_no_cogs() {
		$this->enable_cogs_feature();

		$this->sut->set_cogs_value( 12.34 );
		$this->assertEquals( 0, $this->sut->get_cogs_value() );
	}

	/**
	 * @testdox 'set_cogs_value' does nothing, and 'doing it wrong' is thrown, if the Cost of Goods Sold feature is disabled.
	 */
	public function test_set_cogs_value_does_nothing_if_cogs_feature_is_not_enabled() {
		$this->sut->has_cogs_value = true;
		$this->expect_doing_it_wrong_cogs_disabled( 'WC_Order_Item::set_cogs_value' );

		$this->sut->set_cogs_value( 12.34 );
		$this->assertEquals( 0, $this->sut->get_cogs_value() );
	}

	/**
	 * @testdox 'set_cogs_value' properly sets the value then the Cost of Goods Sold feature is enabled and the class manages it.
	 */
	public function test_set_cogs_value_works_as_expected_if_item_has_cogs_and_feature_is_enabled() {
		$this->sut->has_cogs_value = true;
		$this->enable_cogs_feature();

		$this->sut->set_cogs_value( 12.34 );
		$this->assertEquals( 12.34, $this->sut->get_cogs_value() );
	}

	/**
	 * @testdox 'calculate_cogs_value_core' can return null to signal that the calculation failed.
	 */
	public function test_calculate_core_returning_null_means_failure_in_calculation() {
		$this->sut->has_cogs_value = true;
		$this->enable_cogs_feature();
		$this->sut->cogs_core_value = null;
		$this->sut->set_cogs_value( 12.34 );

		$this->assertFalse( $this->sut->calculate_cogs_value() );
		$this->assertEquals( 12.34, $this->sut->get_cogs_value() );
	}

	/**
	 * @testdox The calculated value for Cost of Goods Sold can be modified using the 'woocommerce_calculated_order_item_cogs_value' filter, returning null means a failure in the calculation.
	 *
	 * @testWith [90.12, true, 90.12]
	 *           [null, false, 56.78]
	 *
	 * @param mixed $value_returned_by_filter The value that the filter will return.
	 * @param bool  $expected_calculate_method_result The expected value returned by 'calculate_cogs_value'.
	 * @param float $expected_obtained_cogs_value The expected value returned by 'get_cogs_value'.
	 * @return void
	 */
	public function test_filter_can_be_used_to_alter_calculated_cogs_value( $value_returned_by_filter, bool $expected_calculate_method_result, float $expected_obtained_cogs_value ) {
		$filter_received_value = null;
		$filter_received_item  = null;

		$this->sut->has_cogs_value = true;
		$this->enable_cogs_feature();
		$this->sut->cogs_core_value = 12.34;
		$this->sut->set_cogs_value( 56.78 );

		add_filter(
			'woocommerce_calculated_order_item_cogs_value',
			function ( $value, $item ) use ( &$filter_received_value, &$filter_received_item, $value_returned_by_filter ) {
				$filter_received_value = $value;
				$filter_received_item  = $item;
				return $value_returned_by_filter;
			},
			10,
			2
		);

		$calculate_method_result = $this->sut->calculate_cogs_value();

		$this->assertEquals( $expected_calculate_method_result, $calculate_method_result );
		$this->assertEquals( $expected_obtained_cogs_value, $this->sut->get_cogs_value() );
		$this->assertEquals( 12.34, $filter_received_value );
		$this->assertSame( $this->sut, $filter_received_item );
	}
}
