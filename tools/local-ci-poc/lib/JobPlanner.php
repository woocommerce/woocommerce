<?php
/**
 * Asking CI's own planner what a diff would run.
 *
 * This is the repository-specific file. Everything else in lib/ works anywhere;
 * to use this tool in another repository, replace this class with one that knows
 * how that repository decides what CI runs, and keep the same return shape.
 */

namespace LocalCi;

/**
 * The jobs CI would schedule for the current diff, filtered to the ones a laptop
 * can honestly run.
 */
final class JobPlanner {

	/**
	 * Test type this tool is willing to substitute.
	 *
	 * JavaScript unit tests only: they need no Docker, no database and no built
	 * plugin, so a laptop runs them the way CI does. PHP, e2e and performance
	 * jobs are excluded because a local run would not be equivalent.
	 */
	private const ELIGIBLE_TEST_TYPE = 'unit';

	/**
	 * Job-name prefix this tool is willing to substitute.
	 */
	private const ELIGIBLE_NAME_PREFIX = 'JavaScript';

	/**
	 * Commit the planner compares against.
	 *
	 * A resolved SHA, never a branch name: a local branch called trunk can be
	 * stale, and diffing against a stale base plans jobs the pull request does
	 * not actually require.
	 *
	 * @var string
	 */
	private $base_sha;

	/**
	 * @param string $base_sha Commit the planner compares against.
	 */
	public function __construct( string $base_sha ) {
		$this->base_sha = $base_sha;
	}

	/**
	 * Every job CI would run for this diff that this tool can run locally.
	 *
	 * The planner only emits its full matrix when it believes it is running in
	 * Actions, so it is run that way deliberately, with GITHUB_OUTPUT pointed at
	 * a temporary file. That yields the same JSON CI feeds into `matrix`,
	 * including each job's real command — parsing the human-readable listing
	 * instead would mean guessing at commands.
	 *
	 * @return array<int, array{name: string, projectName: string, command: string}>
	 */
	public function eligible_jobs(): array {
		$planned  = $this->planned_test_jobs();
		$eligible = array();

		foreach ( $planned as $job ) {
			$name = (string) ( $job['name'] ?? '' );

			if ( self::ELIGIBLE_TEST_TYPE !== ( $job['testType'] ?? '' ) ) {
				continue;
			}

			if ( ! str_starts_with( $name, self::ELIGIBLE_NAME_PREFIX ) ) {
				continue;
			}

			$eligible[] = array(
				'name'        => $name,
				'projectName' => (string) ( $job['projectName'] ?? '' ),
				'command'     => (string) ( $job['command'] ?? '' ),
			);
		}

		return $eligible;
	}

	/**
	 * Keep only jobs whose project name contains one of these substrings.
	 *
	 * Substitution is per job, so running a subset is legitimate: whatever is
	 * left out simply gets no receipt and CI runs it.
	 *
	 * @param array<int, array{projectName: string}> $jobs      Jobs to filter.
	 * @param string[]                               $substrings Wanted substrings.
	 *
	 * @return array<int, array<string, mixed>>
	 */
	public static function only( array $jobs, array $substrings ): array {
		if ( array() === $substrings ) {
			return $jobs;
		}

		return array_values(
			array_filter(
				$jobs,
				static function ( array $job ) use ( $substrings ): bool {
					foreach ( $substrings as $wanted ) {
						if ( str_contains( $job['projectName'], $wanted ) ) {
							return true;
						}
					}

					return false;
				}
			)
		);
	}

	/**
	 * The planner's raw test-job matrix.
	 *
	 * @return array<int, array<string, mixed>>
	 */
	private function planned_test_jobs(): array {
		$output_file = sprintf( '%s/local-ci-jobs-%d.txt', sys_get_temp_dir(), getmypid() );

		if ( false === @touch( $output_file ) ) {
			return array();
		}

		Shell::output(
			sprintf(
				'GITHUB_ACTIONS=true GITHUB_OUTPUT=%s pnpm utils ci-jobs --base-ref %s 2>/dev/null',
				escapeshellarg( $output_file ),
				escapeshellarg( $this->base_sha )
			)
		);

		$raw = is_readable( $output_file ) ? (string) file_get_contents( $output_file ) : '';
		@unlink( $output_file );

		// Actions writes multi-line outputs as `name<<DELIMITER ... DELIMITER`.
		if ( ! preg_match( '/test-jobs<<(\S+)\n(.*?)\n\1/s', $raw, $matches ) ) {
			return array();
		}

		$decoded = json_decode( $matches[2], true );

		return is_array( $decoded ) ? $decoded : array();
	}
}
