<?php
declare( strict_types = 1 );

namespace Automattic\WooCommerce\Tests;

use Automattic\WooCommerce\RestApi\UnitTests\LoggerSpyTrait;

/**
 * Tests when LoggerSpyTrait puts its spy in place.
 *
 * WordPress snapshots the hooks in the first set-up of the process and restores that snapshot
 * after every test, so a filter added before the base set-up would come back in every later
 * test. Checking the timing here holds in any test order.
 */
class LoggerSpyTraitTest extends \WC_Unit_Test_Case {
	use LoggerSpyTrait;

	/**
	 * Whether the spy filter was in place before the base set-up ran.
	 *
	 * @var bool
	 */
	private $spy_filter_added_before_base_set_up;

	/**
	 * Whether the spy was in place right after the base set-up, for the rest of setUp().
	 *
	 * @var bool
	 */
	private $spy_in_place_after_base_set_up;

	/**
	 * Record when the spy filter appears around the base set-up.
	 */
	public function setUp(): void {
		$this->spy_filter_added_before_base_set_up = false !== has_filter( 'woocommerce_logging_class', array( $this, 'get_spy_logger' ) );
		parent::setUp();
		$this->spy_in_place_after_base_set_up = wc_get_logger() === $this->get_spy_logger();
	}

	/**
	 * @testdox The spy logger is added after the base set-up, and is in place for the rest of setUp().
	 */
	public function test_spy_is_added_after_the_base_set_up(): void {
		$this->assertFalse( $this->spy_filter_added_before_base_set_up, 'A filter added before the base set-up joins the hook snapshot and leaks into later tests' );
		$this->assertTrue( $this->spy_in_place_after_base_set_up, 'Classes that build their subject in setUp() need the spy by then' );
	}
}
