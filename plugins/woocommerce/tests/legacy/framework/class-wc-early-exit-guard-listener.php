<?php
/**
 * Fails the run when the PHP process ends before PHPUnit finishes, for example when code under test calls exit().
 *
 * @package WooCommerce\Tests
 */

declare( strict_types = 1 );

use PHPUnit\Framework\Test;
use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\TestListener;
use PHPUnit\Framework\TestListenerDefaultImplementation;
use PHPUnit\Framework\TestSuite;

/**
 * A test that reaches an exit() or die() ends the whole process. PHPUnit prints no summary and
 * the process exits with status 0, so every test after it silently does not run and the run
 * still passes. This listener tracks the suites and the test in progress, and on shutdown, if
 * the run has not finished, names the test and exits with status 1.
 *
 * Registered in phpunit.xml, so it covers every test whatever its base class. Tests that run in
 * a separate process are covered in the parent process only: PHPUnit does not load listeners in
 * the child.
 */
final class WC_Early_Exit_Guard_Listener implements TestListener {

	use TestListenerDefaultImplementation;

	/**
	 * Exit status for a run the process ended early.
	 */
	public const EXIT_STATUS = 1;

	/**
	 * Suites started and not yet ended. Zero once the outermost suite ends.
	 *
	 * @var int
	 */
	private $open_suites = 0;

	/**
	 * The test in progress, or the last test that ended when none is in progress.
	 *
	 * @var string|null
	 */
	private $current_test = null;

	/**
	 * Whether a test is in progress.
	 *
	 * @var bool
	 */
	private $in_test = false;

	/**
	 * Register the shutdown check. PHPUnit creates listeners with no arguments; tests of the
	 * listener pass false so their instances do not check anything at shutdown.
	 *
	 * @param bool $register_shutdown_check Whether to check the run at shutdown.
	 */
	public function __construct( bool $register_shutdown_check = true ) {
		if ( $register_shutdown_check ) {
			register_shutdown_function( array( $this, 'check_on_shutdown' ) );
		}
	}

	/**
	 * Count a started suite.
	 *
	 * @param TestSuite $suite The suite.
	 */
	public function startTestSuite( TestSuite $suite ): void {
		++$this->open_suites;
	}

	/**
	 * Count an ended suite.
	 *
	 * @param TestSuite $suite The suite.
	 */
	public function endTestSuite( TestSuite $suite ): void {
		--$this->open_suites;
	}

	/**
	 * Remember the test in progress.
	 *
	 * @param Test $test The test about to run.
	 */
	public function startTest( Test $test ): void {
		$this->current_test = self::test_name( $test );
		$this->in_test      = true;
	}

	/**
	 * Mark that no test is in progress.
	 *
	 * @param Test  $test The test that just ran.
	 * @param float $time How long it took, in seconds.
	 */
	public function endTest( Test $test, float $time ): void {
		$this->in_test = false;
	}

	/**
	 * Explain why the run ended early, or return null when it finished.
	 *
	 * @return string|null
	 */
	public function early_exit_message(): ?string {
		if ( $this->open_suites <= 0 ) {
			return null;
		}

		if ( $this->in_test ) {
			$where = sprintf( 'while running %s', $this->current_test );
		} elseif ( null !== $this->current_test ) {
			$where = sprintf( 'outside a test, after %s ended (class setup or teardown)', $this->current_test );
		} else {
			$where = 'before the first test started (class setup)';
		}

		return sprintf(
			'PHPUnit did not finish: the PHP process ended %s. The tests after it did not run. If no fatal error is shown above, look for an exit() or die() reached by the code under test, often after a redirect, and stop the test from reaching it.',
			$where
		);
	}

	/**
	 * Report a run that ended early and fail it. A fatal error lands here too; it already shows its
	 * own message, and this exit status replaces PHP's 255. error_get_last() cannot tell the two
	 * apart: WordPress's fatal error handler runs first and can raise a warning that replaces it.
	 */
	public function check_on_shutdown(): void {
		$message = $this->early_exit_message();
		if ( null === $message ) {
			return;
		}

		fwrite( STDERR, "\n\n" . $message . "\n" ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_fwrite -- Shutdown output for the test runner.

		exit( self::EXIT_STATUS ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- An exit status, not output.
	}

	/**
	 * Name a test the way PHPUnit reports it.
	 *
	 * @param Test $test The test.
	 * @return string
	 */
	private static function test_name( Test $test ): string {
		return $test instanceof TestCase ? get_class( $test ) . '::' . $test->getName() : get_class( $test );
	}
}
