<?php
/**
 * Leaving nothing behind.
 *
 * Repo-agnostic.
 */

namespace LocalCi;

/**
 * Whatever this tool starts, it stops — however the run ends.
 *
 * Armed once at startup rather than when the first resource is created. The
 * longest part of a run is the checks, so waiting until something needs cleaning
 * up would leave exactly the window that matters unguarded: an interrupt there
 * used to leave a pool of test processes holding the CPU.
 */
final class Cleanup {

	/**
	 * Callbacks to run on the way out, in registration order.
	 *
	 * @var callable[]
	 */
	private static $tasks = array();

	/**
	 * Whether the handlers are in place.
	 *
	 * @var bool
	 */
	private static $armed = false;

	/**
	 * Whether cleanup has already run, so it happens once.
	 *
	 * @var bool
	 */
	private static $done = false;

	/**
	 * Install the handlers. Safe to call more than once.
	 */
	public static function arm(): void {
		if ( self::$armed ) {
			return;
		}

		self::$armed = true;

		register_shutdown_function( array( self::class, 'run' ) );

		if ( ! function_exists( 'pcntl_signal' ) ) {
			return;
		}

		pcntl_async_signals( true );

		foreach ( array( SIGINT, SIGTERM, SIGHUP ) as $signal ) {
			pcntl_signal(
				$signal,
				static function (): void {
					self::run();
					exit( 130 );
				}
			);
		}
	}

	/**
	 * Register something to undo.
	 *
	 * @param callable $task Called with no arguments on the way out.
	 */
	public static function add( callable $task ): void {
		self::$tasks[] = $task;
	}

	/**
	 * Run every registered task once.
	 */
	public static function run(): void {
		if ( self::$done ) {
			return;
		}

		self::$done = true;

		foreach ( self::$tasks as $task ) {
			$task();
		}
	}

	/**
	 * Stop a process and everything it started.
	 *
	 * Signalling the immediate child is not enough: it is a package manager,
	 * which runs a test runner, which forks its own workers. Terminating only the
	 * one this tool spawned leaves that pool running and holding the CPU, which
	 * is the thing an interrupted run most needs to avoid.
	 *
	 * Children are killed before their parent so the parent cannot notice a child
	 * die and start a replacement.
	 *
	 * @param int $pid Process to stop.
	 */
	public static function kill_process_tree( int $pid ): void {
		if ( $pid < 2 ) {
			return;
		}

		foreach ( self::child_pids( $pid ) as $child ) {
			self::kill_process_tree( $child );
		}

		// SIGTERM first so a well-behaved process can tidy up, then SIGKILL for
		// anything still there. Both are best-effort: a process that has already
		// exited is not an error.
		@posix_kill( $pid, SIGTERM );
		usleep( 50000 );

		if ( @posix_kill( $pid, 0 ) ) {
			@posix_kill( $pid, SIGKILL );
		}
	}

	/**
	 * The immediate children of a process.
	 *
	 * @param int $pid Parent process.
	 *
	 * @return int[]
	 */
	private static function child_pids( int $pid ): array {
		$output = Shell::output( sprintf( 'pgrep -P %d 2>/dev/null', $pid ) );

		if ( '' === $output ) {
			return array();
		}

		return array_values(
			array_filter(
				array_map( 'intval', explode( "\n", $output ) ),
				static function ( int $child ): bool {
					return $child > 1;
				}
			)
		);
	}
}
