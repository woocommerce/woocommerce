<?php
/**
 * Tests for the listener that fails a run the PHP process ended early.
 *
 * @package WooCommerce\Tests\Framework
 */

declare( strict_types = 1 );

use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\TestSuite;

/**
 * WC_Early_Exit_Guard_Listener_Test class.
 *
 * Drives a listener that registers no shutdown check against a separate, never-run test and
 * suites, so its state is independent of the run this test is part of.
 */
class WC_Early_Exit_Guard_Listener_Test extends WC_Unit_Test_Case {

	/**
	 * The System Under Test.
	 *
	 * @var WC_Early_Exit_Guard_Listener
	 */
	private $sut;

	/**
	 * The test the listener observes.
	 *
	 * @var TestCase
	 */
	private $observed_test;

	/**
	 * A suite for the listener to observe.
	 *
	 * @var TestSuite
	 */
	private $observed_suite;

	/**
	 * Create the listener and the test and suite it observes.
	 */
	public function setUp(): void {
		parent::setUp();

		$this->sut            = new WC_Early_Exit_Guard_Listener( false );
		$this->observed_suite = new TestSuite( 'observed' );
		$this->observed_test  = new class( 'test_noop' ) extends TestCase {
			/**
			 * Never run; the listener only needs a test to observe.
			 */
			public function test_noop(): void {}
		};
	}

	/**
	 * @testdox A run whose suites all ended has nothing to report.
	 */
	public function test_finished_run_reports_nothing(): void {
		$this->sut->startTestSuite( $this->observed_suite );
		$this->sut->startTestSuite( $this->observed_suite );
		$this->sut->startTest( $this->observed_test );
		$this->sut->endTest( $this->observed_test, 0.0 );
		$this->sut->endTestSuite( $this->observed_suite );
		$this->sut->endTestSuite( $this->observed_suite );

		$this->assertNull( $this->sut->early_exit_message() );
	}

	/**
	 * @testdox A process that ends during a test names that test.
	 */
	public function test_exit_during_a_test_names_the_test(): void {
		$this->sut->startTestSuite( $this->observed_suite );
		$this->sut->startTest( $this->observed_test );

		$message = $this->sut->early_exit_message();

		$this->assertNotNull( $message );
		$this->assertStringContainsString( 'while running ' . get_class( $this->observed_test ) . '::test_noop', $message );
	}

	/**
	 * @testdox A process that ends between tests names the last test that ended.
	 */
	public function test_exit_between_tests_names_the_previous_test(): void {
		$this->sut->startTestSuite( $this->observed_suite );
		$this->sut->startTest( $this->observed_test );
		$this->sut->endTest( $this->observed_test, 0.0 );

		$message = $this->sut->early_exit_message();

		$this->assertNotNull( $message );
		$this->assertStringContainsString( 'outside a test, after ' . get_class( $this->observed_test ) . '::test_noop ended', $message );
	}

	/**
	 * @testdox A process that ends before the first test says so.
	 */
	public function test_exit_before_the_first_test_is_reported(): void {
		$this->sut->startTestSuite( $this->observed_suite );

		$message = $this->sut->early_exit_message();

		$this->assertNotNull( $message );
		$this->assertStringContainsString( 'before the first test started', $message );
	}

	/**
	 * @testdox A run is unfinished while an outer suite is still open, even after an inner one ended.
	 */
	public function test_open_outer_suite_counts_as_unfinished(): void {
		$this->sut->startTestSuite( $this->observed_suite );
		$this->sut->startTestSuite( $this->observed_suite );
		$this->sut->endTestSuite( $this->observed_suite );

		$this->assertNotNull( $this->sut->early_exit_message() );
	}
}
