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
 * suites, so its state is independent of the run this test is part of. The shutdown check itself
 * exits the process, so it is tested in a separate PHP process.
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

	/**
	 * @testdox A process that ends before the run finishes exits with status 1 and names the test; one that ends after keeps status 0 and prints nothing.
	 *
	 * @dataProvider provider_process_endings
	 *
	 * @param string $scenario        Whether the process ends during a test or after the run finished.
	 * @param int    $expected_status Exit status the process should end with.
	 * @param string $expected_stderr What the process should write to stderr, or a part of it.
	 */
	public function test_shutdown_check_sets_the_exit_status( string $scenario, int $expected_status, string $expected_stderr ): void {
		// phpcs:ignore WordPress.PHP.DiscouragedPHPFunctions.system_calls_proc_open -- Runs the PHP CLI on a fixed script with no user input.
		$process = proc_open(
			array( PHP_BINARY, '-d', 'display_errors=stderr', dirname( __DIR__ ) . '/helpers/early-exit-guard-runner.php', $scenario ),
			array(
				1 => array( 'pipe', 'w' ),
				2 => array( 'pipe', 'w' ),
			),
			$pipes
		);
		$this->assertIsResource( $process, 'The PHP CLI should start the runner script.' );
		$stdout = stream_get_contents( $pipes[1] );
		$stderr = stream_get_contents( $pipes[2] );
		fclose( $pipes[1] ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_fclose
		fclose( $pipes[2] ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_fclose
		$status = proc_close( $process );

		$this->assertSame( '', $stdout, 'The runner script should print nothing to stdout.' );
		$this->assertSame( $expected_status, $status, "The process should exit with status $expected_status. Its stderr: $stderr" );
		if ( '' === $expected_stderr ) {
			$this->assertSame( '', $stderr, 'A finished run should print nothing.' );
		} else {
			$this->assertStringContainsString( $expected_stderr, $stderr );
		}
	}

	/**
	 * Ways the runner script's process can end.
	 *
	 * @return array<string, array{0: string, 1: int, 2: string}>
	 */
	public function provider_process_endings(): array {
		return array(
			'exit during a test' => array( 'unfinished', 1, 'PHPUnit did not finish: the PHP process ended while running WC_Early_Exit_Guard_Runner_Test::test_noop.' ),
			'exit after the run' => array( 'finished', 0, '' ),
		);
	}
}
