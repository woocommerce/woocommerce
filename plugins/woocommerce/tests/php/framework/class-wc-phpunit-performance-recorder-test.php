<?php
/**
 * Tests for the opt-in PHPUnit performance recorder.
 *
 * @package WooCommerce\Tests
 */

declare( strict_types = 1 );

use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\TestSuite;

require_once __DIR__ . '/../../legacy/framework/class-wc-phpunit-performance-recorder.php';

/** Verifies recorder boundaries, counter validity and object ownership. */
class WC_PHPUnit_Performance_Recorder_Test extends TestCase {

	/** @var string Temporary capture file. */
	private $path;

	/** Create an isolated output file. */
	protected function setUp(): void {
		parent::setUp();
		$this->path = tempnam( sys_get_temp_dir(), 'wc-profile-' );
	}

	/** Remove the capture after each test. */
	protected function tearDown(): void {
		wp_delete_file( $this->path );
		parent::tearDown();
	}

	/** Remains inert when explicitly disabled, even during a profiled run. */
	public function test_disabled_recorder_does_not_sample_or_write(): void {
		global $wpdb;
		$before   = $wpdb->num_queries;
		$recorder = new WC_PHPUnit_Performance_Recorder( '' );
		$suite    = $this->named_suite( self::class );
		$recorder->startTestSuite( $suite );
		$recorder->startTest( $this );
		$recorder->endTest( $this, 0.1 );
		$recorder->endTestSuite( $suite );
		$this->assertSame( $before, $wpdb->num_queries );
		$this->assertSame( 0, filesize( $this->path ) );
	}

	/** Streams class records, ignoring provider suites, before the run completes. */
	public function test_records_class_hooks_cases_and_queries_without_provider_duplicates(): void {
		global $wpdb;
		$recorder = new WC_PHPUnit_Performance_Recorder( $this->path );
		$root     = $this->named_suite( 'root' );
		$class    = $this->named_suite( self::class );
		$provider = $this->named_suite( self::class . '::example' );
		$recorder->startTestSuite( $root );
		$recorder->startTestSuite( $class );
		$wpdb->get_var( 'SELECT 1' );
		$recorder->startTestSuite( $provider );
		$recorder->startTest( $this );
		$wpdb->get_var( 'SELECT 1' );
		$recorder->endTest( $this, 0.125 );
		$recorder->startTest( $this );
		$recorder->endTest( $this, 0.25 );
		$recorder->endTestSuite( $provider );
		$wpdb->get_var( 'SELECT 1' );
		$recorder->endTestSuite( $class );
		$partial = $this->records();
		$this->assertCount( 2, $partial, 'Metadata and the completed class survive an interrupted run.' );
		$row = $partial[1];
		$this->assertSame( 'class', $row['type'] );
		$this->assertSame( self::class, $row['class'] );
		$this->assertSame( 2, $row['tests'] );
		$this->assertSame( 0.375, $row['case_seconds'] );
		$this->assertSame( 3, $row['queries'] );
		$this->assertSame( 0, $row['query_discontinuities'] );
		$this->assertEqualsWithDelta( $row['wall_seconds'] - 0.375, $row['outside_case_seconds'], 0.000001 );
		$this->assertGreaterThanOrEqual( 0, $row['cpu_self_seconds'] );
		$this->assertGreaterThanOrEqual( 0, $row['gc_runs'] );
		$this->assertArrayHasKey( 'gc_collector_seconds', $row );
		$recorder->endTestSuite( $root );
		$records = $this->records();
		$this->assertCount( 3, $records );
		$this->assertSame( 'run', $records[2]['type'] );
		$this->assertSame( 2, $records[2]['tests'] );
		$this->assertSame( 3, $records[2]['queries'] );
	}

	/** Never presents a reset query counter as a complete count. */
	public function test_marks_query_counter_resets_incomplete(): void {
		global $wpdb;
		$before   = $wpdb->num_queries;
		$recorder = new WC_PHPUnit_Performance_Recorder( $this->path );
		$suite    = $this->named_suite( self::class );
		try {
			$recorder->startTestSuite( $suite );
			$recorder->startTest( $this );
			$wpdb->num_queries = 0;
			$recorder->endTest( $this, 0 );
			$recorder->endTestSuite( $suite );
		} finally {
			$wpdb->num_queries = $before;
		}
		$row = $this->records()[1];
		$this->assertNull( $row['queries'] );
		$this->assertSame( 1, $row['query_discontinuities'] );
		$this->assertSame( 0, $row['observed_queries'] );
	}

	/** Does not keep the completed suite or test alive. */
	public function test_does_not_retain_test_or_suite_objects(): void {
		$recorder  = new WC_PHPUnit_Performance_Recorder( $this->path );
		$suite     = $this->named_suite( self::class );
		$test      = new self( 'test_disabled_recorder_does_not_sample_or_write' );
		$suite_ref = WeakReference::create( $suite );
		$test_ref  = WeakReference::create( $test );
		$recorder->startTestSuite( $suite );
		$recorder->startTest( $test );
		$recorder->endTest( $test, 0 );
		unset( $test, $suite );
		$this->assertNull( $suite_ref->get() );
		$this->assertNull( $test_ref->get() );
	}

	/** A replacement with a larger counter is still a discontinuity. */
	public function test_marks_replaced_database_incomplete(): void {
		$original = $GLOBALS['wpdb'];
		$recorder = new WC_PHPUnit_Performance_Recorder( $this->path );
		$suite    = $this->named_suite( self::class );
		try {
			$recorder->startTestSuite( $suite );
			$GLOBALS['wpdb'] = (object) array( 'num_queries' => $original->num_queries + 10 );
			$recorder->endTest( $this, 0 );
		} finally {
			$GLOBALS['wpdb'] = $original;
		}
		$recorder->endTestSuite( $suite );
		$row = $this->records()[1];
		$this->assertNull( $row['queries'] );
		$this->assertSame( 2, $row['query_discontinuities'] );
	}

	/** Records actual class hooks and preserves inner failures/skips. */
	public function test_real_lifecycle_preserves_results_and_counts_class_fixture_queries(): void {
		require_once __DIR__ . '/../helpers/class-wc-phpunit-performance-fixture.php';
		$recorder = new WC_PHPUnit_Performance_Recorder( $this->path );
		$result   = new PHPUnit\Framework\TestResult();
		$result->addListener( $recorder );
		$enabled = gc_enabled();
		( new TestSuite( WC_PHPUnit_Performance_Fixture::class ) )->run( $result );
		$this->assertSame( 3, $result->count() );
		$this->assertSame( 1, $result->failureCount() );
		$this->assertSame( 1, $result->skippedCount() );
		$this->assertSame( 0, $result->errorCount() );
		$this->assertSame( $enabled, gc_enabled() );
		$records = $this->records();
		$this->assertCount( 3, $records );
		$this->assertSame( 3, $records[1]['tests'] );
		$this->assertSame( 9, $records[1]['queries'], 'Both class hooks, six case hooks and one body query.' );
		$this->assertGreaterThan( 0, $records[1]['case_seconds'] );
		$this->assertGreaterThanOrEqual( $records[1]['case_seconds'], $records[1]['wall_seconds'] );
		$this->assertSame( PHP_VERSION_ID >= 80300, is_int( $records[1]['gc_collector_seconds'] ) || is_float( $records[1]['gc_collector_seconds'] ) );
	}

	/** Class setup skips report an entire provider suite in one endTest event. */
	public function test_counts_datasets_when_class_setup_skips_a_provider_suite(): void {
		$recorder = new WC_PHPUnit_Performance_Recorder( $this->path );
		$class    = $this->named_suite( self::class );
		$provider = $this->named_suite( self::class . '::provider' );
		$provider->addTest( new self( 'test_disabled_recorder_does_not_sample_or_write' ) );
		$provider->addTest( new self( 'test_disabled_recorder_does_not_sample_or_write' ) );
		$recorder->startTestSuite( $class );
		$recorder->startTest( $provider );
		$recorder->endTest( $provider, 0 );
		$recorder->endTestSuite( $class );
		$this->assertSame( 2, $this->records()[1]['tests'] );
	}

	/**
	 * Make a named boundary without discovering or running extra tests.
	 *
	 * @param string $name Suite name.
	 * @return TestSuite
	 */
	private function named_suite( string $name ): TestSuite {
		$suite = new TestSuite();
		$suite->setName( $name );
		return $suite;
	}

	/**
	 * Read the records already flushed to disk.
	 *
	 * @return array<array<string, mixed>>
	 */
	private function records(): array {
		$lines = file( $this->path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES );
		$this->assertIsArray( $lines );
		return array_map(
			static function ( string $line ): array {
				return json_decode( $line, true, 512, JSON_THROW_ON_ERROR );
			},
			$lines
		);
	}
}
