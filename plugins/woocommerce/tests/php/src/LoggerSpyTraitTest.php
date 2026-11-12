<?php
declare( strict_types = 1 );

namespace Automattic\WooCommerce\Tests;

use Automattic\WooCommerce\RestApi\UnitTests\LoggerSpyTrait;

/**
 * Sits between the test class and WC_Unit_Test_Case, so its set_up() runs when the trait calls
 * parent::set_up(): the last point before WordPress may snapshot the hooks.
 *
 * phpcs:disable Squiz.Classes.ClassFileName.NoMatch
 * phpcs:disable SlevomatCodingStandard.Files.TypeNameMatchesFileName.NoMatchBetweenTypeNameAndFileName
 * phpcs:disable Generic.Files.OneObjectStructurePerFile.MultipleFound
 */
abstract class LoggerSpyTraitTestBase extends \WC_Unit_Test_Case {

	/**
	 * Whether woocommerce_logging_class had any callback when the base set-up started.
	 *
	 * @var bool
	 */
	protected $logging_class_filtered_before_base_set_up;

	/**
	 * Record whether the logging class is filtered, then run the base set-up.
	 */
	public function set_up() {
		$this->logging_class_filtered_before_base_set_up = has_filter( 'woocommerce_logging_class' );
		parent::set_up();
	}
}

/**
 * Tests when LoggerSpyTrait puts its spy in place.
 *
 * WordPress snapshots the hooks in the first set-up of the process and restores that snapshot
 * after every test, so a filter added before the base set-up would come back in every later
 * test. Checking the timing here holds in any test order.
 */
class LoggerSpyTraitTest extends LoggerSpyTraitTestBase {
	use LoggerSpyTrait;

	/**
	 * Whether the spy was in place right after the base set-up, for the rest of setUp().
	 *
	 * @var bool
	 */
	private $spy_in_place_after_base_set_up;

	/**
	 * Record whether the spy is in place once the base set-up has run.
	 */
	public function setUp(): void {
		parent::setUp();
		$this->spy_in_place_after_base_set_up = wc_get_logger() === $this->get_spy_logger();
	}

	/**
	 * @testdox The spy logger is added after the base set-up, and is in place for the rest of setUp().
	 */
	public function test_spy_is_added_after_the_base_set_up(): void {
		$this->assertFalse( $this->logging_class_filtered_before_base_set_up, 'A filter added before the base set-up joins the hook snapshot and leaks into later tests' );
		$this->assertTrue( $this->spy_in_place_after_base_set_up, 'Classes that build their subject in setUp() need the spy by then' );
	}
}
