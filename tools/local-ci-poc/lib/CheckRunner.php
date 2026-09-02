<?php
/**
 * Running checks concurrently.
 *
 * Repo-agnostic: it runs whatever command each job carries.
 */

namespace LocalCi;

/**
 * Runs planned jobs a few at a time and reports each as it finishes.
 *
 * CI runs its matrix across separate runners; the nearest a laptop gets is a
 * pool. Jobs start in order but finish in whatever order they finish, so results
 * go to a callback rather than coming back as a batch — a fifteen-second package
 * should not sit behind a three-minute one before the reader hears about it.
 *
 * Everything it starts, it is responsible for stopping. A run that is interrupted
 * must not leave test processes behind holding CPU, so the pool registers its
 * children for termination and cleans up its log files either way.
 */
final class CheckRunner {

	/**
	 * How long a single check may run before it is treated as hung, in seconds.
	 *
	 * Generously above the slowest package observed (about five minutes) so a
	 * genuinely slow suite is never cut off, but bounded so one wedged process
	 * cannot hold the pool open indefinitely.
	 */
	private const TIMEOUT_SECONDS = 1800;

	/**
	 * How long to wait between polls, in microseconds.
	 *
	 * There is nothing to do but wait for a child to exit, and polling faster
	 * would spend the CPU the checks themselves need.
	 */
	private const POLL_INTERVAL = 200000;

	/**
	 * Processes started and not yet reaped, so they can be terminated on exit.
	 *
	 * @var array<int, array<string, mixed>>
	 */
	private static $running = array();

	/**
	 * How many checks to run at once when the caller does not say.
	 *
	 * Half the cores, because each check is itself a test runner that spreads its
	 * own files across workers. Capped at eight: past that the checks contend for
	 * the same CPU and each one slows down more than the extra concurrency wins.
	 * Measured over eight packages on a 16-core machine: 67s at 1, 41s at 4, 27s
	 * at 8.
	 */
	public static function default_concurrency(): int {
		$cores = Shell::processor_count();

		return $cores < 2 ? 2 : max( 2, min( 8, intdiv( $cores, 2 ) ) );
	}

	/**
	 * Terminate anything still running and remove its log file.
	 *
	 * Safe to call more than once, and safe to call when nothing is running.
	 */
	public static function stop_everything(): void {
		foreach ( self::$running as $process ) {
			if ( is_resource( $process['handle'] ) ) {
				// The whole tree, not just the process this tool spawned: that one
				// is a package manager whose grandchildren do the actual work.
				Cleanup::kill_process_tree( proc_get_status( $process['handle'] )['pid'] );
				proc_close( $process['handle'] );
			}

			self::forget_log( $process['log'] );
		}

		self::$running = array();
	}

	/**
	 * Run jobs, at most $concurrency at a time.
	 *
	 * @param array<int, array{name: string, projectName: string, command: string}> $jobs        Jobs to run.
	 * @param int                                                                   $concurrency How many at once.
	 * @param callable                                                              $on_finish   Called with (job, result).
	 */
	public function run( array $jobs, int $concurrency, callable $on_finish ): void {
		$queue = $jobs;

		while ( array() !== $queue || array() !== self::$running ) {
			while ( array() !== $queue && count( self::$running ) < $concurrency ) {
				$job     = array_shift( $queue );
				$started = $this->start( $job );

				if ( null === $started ) {
					// The process could not be created at all, which is a local
					// problem rather than a test result. No receipt, so CI runs it.
					$on_finish(
						$job,
						array(
							'passed'  => false,
							'seconds' => 0,
							'summary' => 'could not start',
						)
					);
					continue;
				}

				self::$running[] = $started;
			}

			foreach ( self::$running as $index => $process ) {
				$result = $this->reap( $process );

				if ( null === $result ) {
					continue;
				}

				unset( self::$running[ $index ] );
				$on_finish( $process['job'], $result );
			}

			if ( array() !== self::$running ) {
				usleep( self::POLL_INTERVAL );
			}
		}
	}

	/**
	 * Start one job exactly as CI would run it.
	 *
	 * The command comes from the planner rather than being assumed, so this
	 * cannot drift from what CI runs for the same job. Output goes straight to a
	 * per-job file: with several running at once, interleaving them on one
	 * terminal would make all of them unreadable.
	 *
	 * @param array{name: string, projectName: string, command: string} $job Job to start.
	 *
	 * @return array<string, mixed>|null Null when the process could not be created.
	 */
	private function start( array $job ): ?array {
		// The pid is part of the name so two runs of this tool in the same
		// checkout cannot overwrite each other's output.
		$log = sprintf( '%s/local-ci-%d-%s.log', sys_get_temp_dir(), getmypid(), md5( $job['name'] ) );

		$descriptors = array(
			0 => array( 'file', '/dev/null', 'r' ),
			1 => array( 'file', $log, 'w' ),
			2 => array( 'file', $log, 'a' ),
		);

		$handle = @proc_open(
			sprintf( 'pnpm --filter=%s %s', escapeshellarg( $job['projectName'] ), $job['command'] ),
			$descriptors,
			$pipes
		);

		if ( ! is_resource( $handle ) ) {
			self::forget_log( $log );

			return null;
		}

		return array(
			'job'     => $job,
			'handle'  => $handle,
			'log'     => $log,
			'started' => time(),
		);
	}

	/**
	 * Collect a finished job, or stop one that has outstayed the timeout.
	 *
	 * @param array<string, mixed> $process Running process.
	 *
	 * @return array{passed: bool, seconds: int, summary: string}|null Null while still running.
	 */
	private function reap( array $process ): ?array {
		$status  = proc_get_status( $process['handle'] );
		$elapsed = time() - $process['started'];

		if ( $status['running'] && $elapsed < self::TIMEOUT_SECONDS ) {
			return null;
		}

		if ( $status['running'] ) {
			Cleanup::kill_process_tree( $status['pid'] );
			proc_close( $process['handle'] );
			self::forget_log( $process['log'] );

			return array(
				'passed'  => false,
				'seconds' => $elapsed,
				'summary' => 'timed out',
			);
		}

		// proc_get_status reports the real exit code only the first time it sees
		// the process end; proc_close would return -1 here, so read it from the
		// status that has just been taken.
		$exit_code = $status['exitcode'];
		proc_close( $process['handle'] );

		$summary = $this->summarise( $process['log'] );
		self::forget_log( $process['log'] );

		return array(
			'passed'  => 0 === $exit_code,
			'seconds' => $elapsed,
			'summary' => $summary,
		);
	}

	/**
	 * Pull a one-line summary out of a check's output.
	 *
	 * @param string $log Path to the log file.
	 */
	private function summarise( string $log ): string {
		$output = is_readable( $log ) ? (string) file_get_contents( $log ) : '';

		return preg_match( '/Tests: +\d+ passed/', $output, $matches ) ? $matches[0] : 'ran';
	}

	/**
	 * Remove a log file, ignoring one that is already gone.
	 *
	 * @param string $log Path to the log file.
	 */
	private static function forget_log( string $log ): void {
		if ( is_file( $log ) ) {
			@unlink( $log );
		}
	}
}
