<?php
declare( strict_types = 1 );

namespace Automattic\WooCommerce\Tests\Internal\MultiLocationInventory;

use Automattic\WooCommerce\Internal\MultiLocationInventory\ProductInventoryController;
use Automattic\WooCommerce\Internal\MultiLocationInventory\ProductInventoryService;
use WC_Unit_Test_Case;

/**
 * Tests for the ProductInventoryService atomic ledger.
 */
class ProductInventoryServiceTest extends WC_Unit_Test_Case {

	/**
	 * The System Under Test.
	 *
	 * @var ProductInventoryService
	 */
	private $sut;

	/**
	 * Set up the test environment.
	 */
	public static function setUpBeforeClass(): void {
		parent::setUpBeforeClass();
		wc_get_container()->get( ProductInventoryController::class )->create_tables();
	}

	/**
	 * Set up the test case.
	 */
	public function setUp(): void {
		parent::setUp();
		$this->sut = wc_get_container()->get( ProductInventoryService::class );
	}

	/**
	 * @testdox set_stock() then get_stock() returns the set quantity.
	 */
	public function test_set_and_get_stock(): void {
		$this->sut->set_stock( 10, 0, 1, 25.0 );
		$this->assertEquals( 25.0, $this->sut->get_stock( 10, 0, 1 ) );
	}

	/**
	 * @testdox get_stock() returns 0 for an unknown triple.
	 */
	public function test_get_stock_unknown_is_zero(): void {
		$this->assertEquals( 0.0, $this->sut->get_stock( 999, 0, 1 ) );
	}

	/**
	 * @testdox increase_stock() adds to the existing quantity.
	 */
	public function test_increase_stock_adds(): void {
		$this->sut->set_stock( 10, 0, 1, 5.0 );
		$this->assertEquals( 8.0, $this->sut->increase_stock( 10, 0, 1, 3.0 ) );
	}

	/**
	 * @testdox set_stock() upserts — one row per (product, variation, location) triple.
	 */
	public function test_set_stock_upsert_no_duplicate_rows(): void {
		global $wpdb;
		$this->sut->set_stock( 10, 0, 1, 5.0 );
		$this->sut->set_stock( 10, 0, 1, 7.0 );

		$count = (int) $wpdb->get_var(
			$wpdb->prepare(
				'SELECT COUNT(*) FROM %i WHERE product_id = 10 AND variation_id = 0 AND location_id = 1',
				$this->sut->get_table_name()
			)
		);
		$this->assertEquals( 1, $count );
		$this->assertEquals( 7.0, $this->sut->get_stock( 10, 0, 1 ) );
	}

	/**
	 * @testdox Variation and simple/parent keys are stored as distinct rows.
	 */
	public function test_variation_and_simple_keying_distinct(): void {
		$this->sut->set_stock( 10, 0, 1, 5.0 );
		$this->sut->set_stock( 10, 55, 1, 8.0 );
		$this->assertEquals( 5.0, $this->sut->get_stock( 10, 0, 1 ) );
		$this->assertEquals( 8.0, $this->sut->get_stock( 10, 55, 1 ) );
	}

	/**
	 * @testdox decrease_stock() reduces the quantity and returns the new value.
	 */
	public function test_decrease_stock_success(): void {
		$this->sut->set_stock( 10, 0, 1, 5.0 );
		$this->assertEquals( 2.0, $this->sut->decrease_stock( 10, 0, 1, 3.0 ) );
	}

	/**
	 * @testdox decrease_stock() returns null and leaves the row unchanged when insufficient.
	 */
	public function test_decrease_stock_insufficient_returns_null_unchanged(): void {
		$this->sut->set_stock( 10, 0, 1, 2.0 );
		$this->assertNull( $this->sut->decrease_stock( 10, 0, 1, 5.0 ) );
		$this->assertEquals( 2.0, $this->sut->get_stock( 10, 0, 1 ), 'Row must be unchanged after a failed decrement.' );
	}

	/**
	 * @testdox decrease_stock() returns null for an unknown triple.
	 */
	public function test_decrease_stock_unknown_returns_null(): void {
		$this->assertNull( $this->sut->decrease_stock( 999, 0, 1, 1.0 ) );
	}

	/**
	 * @testdox Repeated decrements can never drive stock negative.
	 */
	public function test_decrease_never_negative(): void {
		$this->sut->set_stock( 10, 0, 1, 3.0 );
		$this->assertEquals( 0.0, $this->sut->decrease_stock( 10, 0, 1, 3.0 ) );
		$this->assertNull( $this->sut->decrease_stock( 10, 0, 1, 1.0 ) );
		$this->assertEquals( 0.0, $this->sut->get_stock( 10, 0, 1 ) );
	}
}
