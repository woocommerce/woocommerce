<?php
/**
 * Opt-in, scalar-only PHPUnit performance measurements.
 *
 * @package WooCommerce\Tests
 */

declare( strict_types = 1 );

use PHPUnit\Framework\Test;
use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\TestListener;
use PHPUnit\Framework\TestListenerDefaultImplementation;
use PHPUnit\Framework\TestSuite;

/** Streams class measurements without retaining tests or changing collection. */
final class WC_PHPUnit_Performance_Recorder implements TestListener {

	use TestListenerDefaultImplementation;

	/** @var resource|null Output stream; null disables all sampling. */
	private $stream;

	/** @var int Current suite depth, including provider suites. */
	private $depth = 0;

	/** @var array<int, array<string, mixed>> Active class snapshots, containing no objects. */
	private $classes = array();

	/** @var array<string, mixed> Root suite snapshot. */
	private $run = array();

	/** @var int Completed PHPUnit cases. */
	private $tests = 0;

	/** @var float Sum of PHPUnit's case timers, in seconds. */
	private $case_seconds = 0.0;

	/** @var array<string, int|null>|null Last observed database identity and counter. */
	private $database;

	/** @var int Queries observed across continuous counter segments. */
	private $queries = 0;

	/** @var int Observed counter resets, missing counters or database replacements. */
	private $query_discontinuities = 0;

	/**
	 * Open the opt-in output before executing tests.
	 *
	 * @param string|null $path Output filename; null reads WC_PHPUNIT_PROFILE, empty disables capture.
	 * @throws RuntimeException When the requested output cannot be opened.
	 */
	public function __construct( ?string $path = null ) {
		if ( null === $path ) {
			$configured = getenv( 'WC_PHPUNIT_PROFILE' );
			$path       = false === $configured ? '' : $configured;
		}
		if ( '' !== $path ) {
			$stream = fopen( $path, 'wb' ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_fopen -- Stream diagnostics without WordPress filesystem initialization or hooks.
			if ( false === $stream ) {
				throw new RuntimeException( 'Cannot open PHPUnit performance output.' );
			}
			$this->stream = $stream;
		}
	}

	/**
	 * Include class hooks while ignoring data-provider suite boundaries.
	 *
	 * @param TestSuite $suite Starting suite.
	 */
	public function startTestSuite( TestSuite $suite ): void {
		if ( null === $this->stream ) {
			return;
		}
		if ( 0 === $this->depth ) {
			$this->write(
				array(
					'type'          => 'metadata',
					'schema'        => 1,
					'php'           => PHP_VERSION,
					'phpunit'       => PHPUnit\Runner\Version::id(),
					'wordpress'     => $GLOBALS['wp_version'] ?? null,
					'disable_hpos'  => getenv( 'DISABLE_HPOS' ),
					'opcache_cli'   => ini_get( 'opcache.enable_cli' ),
					'counter_scope' => 'parent_process',
				)
			);
			$this->run = $this->snapshot();
		}
		++$this->depth;
		$class = $suite->getName();
		if ( class_exists( $class, false ) && is_subclass_of( $class, TestCase::class ) ) {
			$this->classes[ $this->depth ] = array(
				'class' => $class,
				'start' => $this->snapshot(),
			);
		}
	}

	/**
	 * Flush each completed class so partial runs remain useful.
	 *
	 * @param TestSuite $suite Completed suite.
	 */
	public function endTestSuite( TestSuite $suite ): void {
		unset( $suite );
		if ( null === $this->stream ) {
			return;
		}
		$end = $this->snapshot();
		if ( isset( $this->classes[ $this->depth ] ) ) {
			$frame = $this->classes[ $this->depth ];
			$this->write(
				array_merge(
					array(
						'type'  => 'class',
						'class' => $frame['class'],
					),
					$this->measure( $frame['start'], $end )
				)
			);
			unset( $this->classes[ $this->depth ] );
		}
		--$this->depth;
		if ( 0 === $this->depth ) {
			$this->write( array_merge( array( 'type' => 'run' ), $this->measure( $this->run, $end ) ) );
			$this->run = array();
		}
	}

	/**
	 * Check query-counter continuity before per-test setup.
	 *
	 * @param Test $test Starting test; never retained.
	 */
	public function startTest( Test $test ): void {
		unset( $test );
		if ( null !== $this->stream ) {
			$this->observe_queries();
		}
	}

	/**
	 * Accumulate PHPUnit's setup/body/teardown timer without retaining cases.
	 *
	 * @param Test  $test Completed test; never retained.
	 * @param float $time PHPUnit case duration in seconds.
	 */
	public function endTest( Test $test, float $time ): void {
		if ( null !== $this->stream ) {
			$this->tests        += count( $test );
			$this->case_seconds += $time;
			$this->observe_queries();
		}
	}

	/** Track valid counter segments without issuing or retaining SQL. */
	private function observe_queries(): void {
		global $wpdb;
		$current = array(
			'id'    => is_object( $wpdb ) ? spl_object_id( $wpdb ) : null,
			'count' => isset( $wpdb->num_queries ) ? $wpdb->num_queries : null,
		);
		if ( null !== $this->database ) {
			if ( null !== $current['count'] && null !== $this->database['count'] && $current['id'] === $this->database['id'] && $current['count'] >= $this->database['count'] ) {
				$this->queries += $current['count'] - $this->database['count'];
			} else {
				++$this->query_discontinuities;
			}
		}
		$this->database = $current;
	}

	/**
	 * Read native counters only; never trigger or reconfigure GC.
	 *
	 * @return array<string, mixed>
	 */
	private function snapshot(): array {
		$this->observe_queries();
		return array(
			'wall'                  => hrtime( true ) / 1e9,
			'cpu_self'              => $this->cpu_seconds( 0 ),
			'cpu_children'          => $this->cpu_seconds( 1 ),
			'gc'                    => gc_status(),
			'gc_enabled'            => gc_enabled(),
			'memory'                => memory_get_usage( true ),
			'tests'                 => $this->tests,
			'case_seconds'          => $this->case_seconds,
			'queries'               => $this->queries,
			'query_discontinuities' => $this->query_discontinuities,
		);
	}

	/**
	 * Read CPU consumed by this process or its reaped children, excluding MySQL.
	 *
	 * @param int $mode Zero for self, one for children.
	 * @return float|null Seconds, or null when unavailable.
	 */
	private function cpu_seconds( int $mode ): ?float {
		$usage = function_exists( 'getrusage' ) ? getrusage( $mode ) : false;
		return false === $usage ? null : $usage['ru_utime.tv_sec'] + $usage['ru_stime.tv_sec'] + ( $usage['ru_utime.tv_usec'] + $usage['ru_stime.tv_usec'] ) / 1e6;
	}

	/**
	 * Convert cumulative snapshots into interval measurements in seconds and counts.
	 *
	 * @param array<string, mixed> $start Interval start.
	 * @param array<string, mixed> $end Interval end.
	 * @return array<string, mixed>
	 */
	private function measure( array $start, array $end ): array {
		$wall  = $end['wall'] - $start['wall'];
		$cases = $end['case_seconds'] - $start['case_seconds'];
		$gaps  = $end['query_discontinuities'] - $start['query_discontinuities'];
		$count = $end['queries'] - $start['queries'];
		return array(
			'tests'                 => $end['tests'] - $start['tests'],
			'wall_seconds'          => $wall,
			'case_seconds'          => $cases,
			'outside_case_seconds'  => $wall - $cases,
			'cpu_self_seconds'      => null === $end['cpu_self'] || null === $start['cpu_self'] ? null : $end['cpu_self'] - $start['cpu_self'],
			'cpu_children_seconds'  => null === $end['cpu_children'] || null === $start['cpu_children'] ? null : $end['cpu_children'] - $start['cpu_children'],
			'queries'               => 0 === $gaps ? $count : null,
			'observed_queries'      => $count,
			'query_discontinuities' => $gaps,
			'gc_runs'               => $end['gc']['runs'] - $start['gc']['runs'],
			'gc_collected'          => $end['gc']['collected'] - $start['gc']['collected'],
			'gc_collector_seconds'  => isset( $end['gc']['collector_time'] ) ? $end['gc']['collector_time'] - $start['gc']['collector_time'] : null,
			'gc_roots_start'        => $start['gc']['roots'],
			'gc_roots_end'          => $end['gc']['roots'],
			'gc_threshold_start'    => $start['gc']['threshold'],
			'gc_threshold_end'      => $end['gc']['threshold'],
			'gc_enabled_start'      => $start['gc_enabled'],
			'gc_enabled_end'        => $end['gc_enabled'],
			'memory_bytes_start'    => $start['memory'],
			'memory_bytes_end'      => $end['memory'],
		);
	}

	/**
	 * Flush one JSON record, treating failed opt-in capture as an explicit error.
	 *
	 * @param array<string, mixed> $record Scalar measurements.
	 * @throws RuntimeException When a record cannot be written completely.
	 */
	private function write( array $record ): void {
		if ( null === $this->stream ) {
			return;
		}
		$line = json_encode( $record, JSON_THROW_ON_ERROR | JSON_UNESCAPED_SLASHES ) . "\n"; // phpcs:ignore WordPress.WP.AlternativeFunctions.json_encode_json_encode -- Native encoding throws on failure rather than repairing diagnostic data.
		if ( fwrite( $this->stream, $line ) !== strlen( $line ) || ! fflush( $this->stream ) ) { // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_fwrite -- Stream directly without WordPress filesystem hooks.
			throw new RuntimeException( 'Cannot write PHPUnit performance output.' );
		}
	}
}
