<?php
declare( strict_types = 1 );

namespace Automattic\WooCommerce\Tests\Internal\StockNotifications\Migration\Report;

use Automattic\WooCommerce\Internal\StockNotifications\Migration\Migrators\NotificationsMigrator;
use Automattic\WooCommerce\Internal\StockNotifications\Migration\Report\Reporter;
use Automattic\WooCommerce\Internal\StockNotifications\Migration\Writers\Writer;
use Automattic\WooCommerce\Tests\Internal\StockNotifications\Migration\Helpers\LegacyStore;
use WC_Unit_Test_Case;

/**
 * Tests for what the migration writes to the log and how it reports itself.
 *
 * The migration touches a store's entire subscriber list, so the load-bearing assertion
 * here is that no log line ever carries an email address or any other row content: these
 * files get pasted into support tickets.
 */
class ReporterTests extends WC_Unit_Test_Case {

	/**
	 * Log lines captured for the run, as `severity => [messages]`.
	 *
	 * @var array<string,array<int,string>>
	 */
	private array $logged = array();

	/**
	 * Capture everything written through the WooCommerce logger.
	 */
	public function setUp(): void {
		parent::setUp();

		$this->logged = array();

		LegacyStore::create_tables();
		LegacyStore::truncate_all();
		delete_option( 'wc_bis_migration_has_legacy_links' );
		delete_option( 'wc_bis_migration_has_migrated_rows' );

		add_filter( 'woocommerce_logger_log_message', array( $this, 'capture_log_message' ), 10, 3 );
	}

	/**
	 * Stop capturing and clean up.
	 */
	public function tearDown(): void {
		remove_filter( 'woocommerce_logger_log_message', array( $this, 'capture_log_message' ), 10 );

		LegacyStore::drop_tables();
		delete_option( 'wc_bis_migration_has_legacy_links' );
		delete_option( 'wc_bis_migration_has_migrated_rows' );

		parent::tearDown();
	}

	/**
	 * Record a log message written during a test.
	 *
	 * @param string $message The message.
	 * @param string $level   Severity.
	 * @param array  $context Log context.
	 * @return string The unmodified message.
	 */
	public function capture_log_message( $message, $level, $context ) {
		// Every log call made during the test comes through here, not just the reporter's, and
		// `WC_Logger::log()` does not type its context.
		if ( is_array( $context ) && 'bis-migration' === ( $context['source'] ?? '' ) ) {
			$this->logged[ $level ][] = (string) $message;
		}

		return $message;
	}

	/**
	 * @testdox no log line should ever carry row content, at any severity.
	 */
	public function test_no_log_line_carries_row_content(): void {
		$product = new \WC_Product_Simple();
		$product->save();

		$migrated = LegacyStore::add_notification(
			array(
				'product_id' => $product->get_id(),
				'user_email' => 'migrated@example.com',
			)
		);

		$failing = LegacyStore::add_notification(
			array(
				'product_id' => $product->get_id(),
				'user_email' => 'failing@example.com',
			)
		);

		// The failing row adopts a pre-existing Core row, so its write happens inside the
		// per-row try/catch rather than in the batch's bulk insert.
		LegacyStore::add_core_notification(
			array(
				'product_id' => $product->get_id(),
				'user_email' => 'failing@example.com',
			)
		);

		$migrator = new NotificationsMigrator( new Reporter() );
		$batch    = $migrator->get_batch( 0, 10 );

		$thrower = static function ( $query ) {
			if ( false !== strpos( $query, '_wc_bis_legacy_adopted' ) ) {
				throw new \RuntimeException( 'forced row failure' );
			}

			return $query;
		};

		add_filter( 'query', $thrower );
		$migrator->migrate_batch( $batch, wc_get_container()->get( Writer::class ) );
		remove_filter( 'query', $thrower );

		$this->assertNotEmpty( $this->logged, 'The run should have logged something.' );

		foreach ( $this->logged as $level => $messages ) {
			foreach ( $messages as $message ) {
				$this->assertStringNotContainsString( '@example.com', $message, "An address leaked into a {$level} line." );
			}
		}

		$this->assertContains( "section=notifications id={$failing} outcome=failed", $this->logged['error'] ?? array() );
		$this->assertNotContains( "section=notifications id={$migrated} outcome=failed", $this->logged['error'] ?? array() );
	}

	/**
	 * @testdox only an error-severity outcome should mark the run as failed.
	 */
	public function test_only_error_severity_marks_the_run_as_failed(): void {
		$reporter = new Reporter();

		$reporter->record( 'notifications', Reporter::OUTCOME_MIGRATED, 1 );
		$reporter->record( 'notifications', Reporter::OUTCOME_PRODUCT_MISSING, 2 );

		$this->assertFalse( $reporter->has_errors(), 'A skip is a warning, not an error.' );

		$reporter->record( 'notifications', Reporter::OUTCOME_FAILED, 3 );

		$this->assertTrue( $reporter->has_errors() );
	}

	/**
	 * @testdox a batch-level exception should be reported as an error.
	 */
	public function test_batch_exception_is_reported_as_an_error(): void {
		$reporter = new Reporter();

		$reporter->report_exception( 'notifications', new \RuntimeException( 'lost connection' ) );

		$this->assertTrue( $reporter->has_errors() );
		$this->assertContains( 'section=notifications batch failed: lost connection', $this->logged['error'] ?? array() );
	}

	/**
	 * @testdox the outcome table should carry one row per section and outcome.
	 */
	public function test_table_has_one_row_per_section_and_outcome(): void {
		$reporter = new Reporter();

		$reporter->record( 'notifications', Reporter::OUTCOME_MIGRATED, 1 );
		$reporter->record( 'notifications', Reporter::OUTCOME_MIGRATED, 2 );
		$reporter->record( 'product-meta', Reporter::OUTCOME_PRODUCT_MISSING, 1 );

		$table = $reporter->get_table();

		$this->assertContains(
			array(
				'section' => 'notifications',
				'outcome' => Reporter::OUTCOME_MIGRATED,
				'count'   => 2,
			),
			$table
		);
		$this->assertContains(
			array(
				'section' => 'product-meta',
				'outcome' => Reporter::OUTCOME_PRODUCT_MISSING,
				'count'   => 1,
			),
			$table
		);
	}

	/**
	 * @testdox timestamps should render in site-local time, not UTC.
	 */
	public function test_timestamps_render_in_site_local_time(): void {
		$original_offset = get_option( 'gmt_offset' );
		$original_zone   = get_option( 'timezone_string' );

		update_option( 'timezone_string', '' );
		update_option( 'gmt_offset', 5 );

		$timestamp = 1600000000;
		$format    = get_option( 'date_format' ) . ' ' . get_option( 'time_format' );
		$rendered  = ( new Reporter() )->format_site_time( $timestamp );

		$this->assertSame( wp_date( $format, $timestamp ), $rendered );
		$this->assertNotSame( gmdate( $format, $timestamp ), $rendered, 'A five-hour offset must show.' );

		update_option( 'gmt_offset', $original_offset );
		update_option( 'timezone_string', $original_zone );
	}

	/**
	 * @testdox a cached count should be rendered with the time it was computed.
	 */
	public function test_cached_count_carries_its_timestamp(): void {
		$reporter = new Reporter();
		$rendered = $reporter->format_cached_count( 42, 1600000000 );

		$this->assertStringContainsString( '42', $rendered );
		$this->assertStringContainsString( $reporter->format_site_time( 1600000000 ), $rendered );
	}
}
