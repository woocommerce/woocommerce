<?php
/**
 * Tests for the Analytics report exporter.
 *
 * @package WooCommerce\Tests\Admin
 */

declare( strict_types = 1 );

namespace Automattic\WooCommerce\Tests\Admin;

use Automattic\WooCommerce\Admin\ReportCSVExporter;
use Automattic\WooCommerce\Admin\ReportExporter;
use Automattic\WooCommerce\Internal\Admin\Schedulers\OrdersScheduler;
use WC_Helper_Order;
use WC_Helper_Queue;
use WC_Unit_Test_Case;

/**
 * Tests for retained report exports.
 */
class ReportExporterTest extends WC_Unit_Test_Case {

	/**
	 * Paths written by a test, removed in tear down.
	 *
	 * @var string[]
	 */
	private $paths = array();

	/**
	 * Remove files that the database transaction does not roll back.
	 */
	public function tearDown(): void {
		foreach ( $this->paths as $path ) {
			if ( file_exists( $path ) ) {
				wp_delete_file( $path );
			}
		}
		$this->paths = array();

		parent::tearDown();
	}

	/**
	 * Write an export to the reports directory.
	 *
	 * @param string $filename Filename without the extension.
	 * @param string $body     Export body.
	 * @param int    $age      How long ago the export was written, in seconds.
	 * @return string Resolved filename, as the download handler and cleanup see it.
	 */
	private function create_export( string $filename, string $body = "1,2\n", int $age = 0 ): string {
		ReportCSVExporter::maybe_create_directory();

		$exporter = new ReportCSVExporter();
		$exporter->set_filename( $filename );
		$resolved = $exporter->get_filename();
		$path     = ReportCSVExporter::get_reports_directory() . $resolved;

		$this->write_file( $path, $body, $age );
		$this->write_file( $path . '.headers', "id,total\n", $age );

		return $resolved;
	}

	/**
	 * Write one file and backdate it.
	 *
	 * @param string $path     File path.
	 * @param string $contents File contents.
	 * @param int    $age      How long ago the file was written, in seconds.
	 * @return void
	 */
	private function write_file( string $path, string $contents, int $age ): void {
		file_put_contents( $path, $contents ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_file_put_contents
		touch( $path, time() - $age ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_touch

		$this->paths[] = $path;
	}

	/**
	 * @testdox Downloading an export streams it without consuming it, so the link can be reused.
	 */
	public function test_streaming_an_export_leaves_it_in_place(): void {
		$filename = $this->create_export( 'wc-orders-report-export-repeatable', "42,7\n" );
		$path     = ReportCSVExporter::get_reports_directory() . $filename;

		$exporter = new ReportCSVExporter();
		$exporter->set_filename( $filename );

		ob_start();
		$exporter->stream_export_file();
		$first = ob_get_clean();

		$this->assertStringContainsString( '42,7', $first, 'The export body should be streamed to the client.' );
		$this->assertStringContainsString( 'id,total', $first, 'The stored header row should be streamed ahead of the body.' );
		$this->assertFileExists( $path, 'Serving an export should not delete it.' );

		ob_start();
		$exporter->stream_export_file();
		$second = ob_get_clean();

		$this->assertSame( $first, $second, 'A second download should return the same export.' );
	}

	/**
	 * @testdox An export that is gone reports itself as missing instead of serving an empty report.
	 */
	public function test_missing_export_is_reported_as_missing(): void {
		$filename = $this->create_export( 'wc-orders-report-export-missing' );
		$exporter = new ReportCSVExporter();
		$exporter->set_filename( $filename );

		$this->assertTrue( $exporter->export_file_exists(), 'A generated export should be reported as available.' );

		wp_delete_file( ReportCSVExporter::get_reports_directory() . $filename );

		$this->assertFalse( $exporter->export_file_exists(), 'A deleted export should be reported as unavailable.' );
	}

	/**
	 * @testdox An export that never finished writing reports itself as missing.
	 */
	public function test_incomplete_export_is_reported_as_missing(): void {
		$filename = $this->create_export( 'wc-orders-report-export-incomplete' );
		$exporter = new ReportCSVExporter();
		$exporter->set_filename( $filename );

		// A body with no `.headers` companion is an export that stopped before reaching 100%.
		wp_delete_file( ReportCSVExporter::get_reports_directory() . $filename . '.headers' );

		$this->assertFalse( $exporter->export_file_exists(), 'An unfinished export should be reported as unavailable.' );
	}

	/**
	 * @testdox Daily cleanup deletes only exports past the retention period.
	 */
	public function test_cleanup_deletes_only_expired_exports(): void {
		$reports_dir = ReportCSVExporter::get_reports_directory();
		$expired     = $this->create_export( 'wc-orders-report-export-expired', "1,2\n", ReportExporter::EXPORT_RETENTION_PERIOD + HOUR_IN_SECONDS );
		$fresh       = $this->create_export( 'wc-orders-report-export-fresh', "3,4\n", ReportExporter::EXPORT_RETENTION_PERIOD - HOUR_IN_SECONDS );

		ReportExporter::delete_expired_exports();

		$this->assertFileDoesNotExist( $reports_dir . $expired, 'An expired export should be deleted.' );
		$this->assertFileDoesNotExist( $reports_dir . $expired . '.headers', 'An expired export header row should be deleted too.' );
		$this->assertFileExists( $reports_dir . $fresh, 'An export inside the retention period should be kept.' );
		$this->assertFileExists( $reports_dir . $fresh . '.headers', 'An export header row inside the retention period should be kept.' );
		$this->assertFileExists( $reports_dir . '.htaccess', 'Cleanup should not touch the directory guards.' );
		$this->assertFileExists( $reports_dir . 'index.html', 'Cleanup should not touch the directory guards.' );
	}

	/**
	 * @testdox A report's date range is read from the arguments it was exported with.
	 *
	 * @testWith ["2025-06-01T00:00:00", "2025-06-30T23:59:59", "2025-06-01", "2025-06-30"]
	 *           ["2025-06-01", "2025-06-01", "2025-06-01", "2025-06-01"]
	 *           ["2024-02-01T00:00:00", "2024-02-29T23:59:59", "2024-02-01", "2024-02-29"]
	 *
	 * @param string $after           The export's `after` argument.
	 * @param string $before          The export's `before` argument.
	 * @param string $expected_after  Expected first day of the range.
	 * @param string $expected_before Expected last day of the range.
	 */
	public function test_date_range_is_read_from_report_args( string $after, string $before, string $expected_after, string $expected_before ): void {
		$this->assertSame(
			array(
				'after'  => $expected_after,
				'before' => $expected_before,
			),
			ReportExporter::get_export_date_range(
				array(
					'after'  => $after,
					'before' => $before,
				)
			),
			'The range should be the dates the report was run for, as written.'
		);
	}

	/**
	 * Create orders and import them into the analytics tables.
	 *
	 * @param int      $count   Number of orders.
	 * @param int|null $created Creation timestamp, defaults to now.
	 * @return int[] Order IDs.
	 */
	private function create_reported_orders( int $count, ?int $created = null ): array {
		$ids = array();

		for ( $i = 0; $i < $count; $i++ ) {
			$order = WC_Helper_Order::create_order();
			$order->set_status( 'completed' );
			if ( null !== $created ) {
				// Analytics may report by created, paid or completed date, so move all three.
				$order->set_date_created( $created );
				$order->set_date_paid( $created );
				$order->set_date_completed( $created );
			}
			$order->save();

			// The queue runner is not running during a test, so import directly rather than through it.
			OrdersScheduler::import( $order->get_id() );
			$ids[] = $order->get_id();
		}

		return $ids;
	}

	/**
	 * Queue an emailed orders export, two orders per batch, and track its files for tear down.
	 *
	 * @return string Export ID.
	 */
	private function queue_orders_export(): string {
		add_filter( 'woocommerce_admin_orders_report_export_batch_limit', array( $this, 'two_rows_per_batch' ) );

		$export_id = (string) time();
		$args      = array(
			'after'  => '2000-01-01T00:00:00',
			'before' => '2100-01-01T00:00:00',
		);

		ReportExporter::queue_report_export( $export_id, 'orders', $args, true );
		ReportExporter::update_export_percentage_complete( 'orders', $export_id, 0 );

		$path          = ReportCSVExporter::get_reports_directory() . "wc-orders-report-export-{$export_id}.csv";
		$this->paths[] = $path;
		$this->paths[] = $path . '.headers';

		return $export_id;
	}

	/**
	 * Batch limit filter callback.
	 *
	 * @return int
	 */
	public function two_rows_per_batch(): int {
		return 2;
	}

	/**
	 * Get this export's queued batch actions, keyed by action ID in page order.
	 *
	 * @param string $export_id Export ID.
	 * @return \ActionScheduler_Action[]
	 */
	private function get_batch_actions( string $export_id ): array {
		return WC()->queue()->search(
			array(
				'hook'     => ReportExporter::get_action( 'export_report' ),
				'search'   => '"' . $export_id . '"',
				'group'    => ReportExporter::$group,
				'per_page' => -1,
				'orderby'  => 'action_id',
				'order'    => 'ASC',
			)
		);
	}

	/**
	 * @testdox Arguments without a usable date range produce no range.
	 *
	 * A date that does not exist counts as unusable. Left alone it would roll over, so an export
	 * run for June 31 would be labelled and named July 1.
	 *
	 * @testWith [{}]
	 *           [{"after": "2025-06-01T00:00:00"}]
	 *           [{"after": "2025-06-01T00:00:00", "before": ""}]
	 *           [{"after": "2025-06-01T00:00:00", "before": "last month"}]
	 *           [{"after": "2025-06-01T00:00:00", "before": ["2025-06-30"]}]
	 *           [{"after": "2025-06-31T00:00:00", "before": "2025-06-30T23:59:59"}]
	 *           [{"after": "2025-06-01T00:00:00", "before": "2025-13-45T00:00:00"}]
	 *           [{"after": "2025-02-29T00:00:00", "before": "2025-03-01T00:00:00"}]
	 *
	 * @param array $report_args Report parameters the export was queued with.
	 */
	public function test_report_args_without_a_date_range( array $report_args ): void {
		$this->assertSame(
			array(),
			ReportExporter::get_export_date_range( $report_args ),
			'A report that is not limited to a period should report no date range.'
		);
	}

	/**
	 * @testdox The date range is labelled in the store's date format.
	 */
	public function test_date_range_label_uses_the_store_date_format(): void {
		update_option( 'date_format', 'F j, Y' );

		$this->assertSame(
			'June 1, 2025 - June 30, 2025',
			ReportExporter::get_export_date_range_label(
				array(
					'after'  => '2025-06-01T00:00:00',
					'before' => '2025-06-30T23:59:59',
				)
			),
			'The label should read as the merchant picked the range.'
		);
	}

	/**
	 * @testdox The date range is labelled in the store's timezone, not in UTC.
	 *
	 * A date format that names the timezone should name the merchant's own, and the date itself
	 * should read the same whichever timezone the store keeps.
	 *
	 * @testWith ["Europe/Sofia", "Y-m-d T", "2025-06-01 EEST"]
	 *           ["America/Los_Angeles", "Y-m-d T", "2025-06-01 PDT"]
	 *           ["Pacific/Kiritimati", "F j, Y", "June 1, 2025"]
	 *           ["Pacific/Midway", "F j, Y", "June 1, 2025"]
	 *
	 * @param string $timezone Store timezone.
	 * @param string $format   Store date format.
	 * @param string $expected Expected label for a one day report.
	 */
	public function test_date_range_label_uses_the_store_timezone( string $timezone, string $format, string $expected ): void {
		update_option( 'timezone_string', $timezone );
		update_option( 'date_format', $format );

		$this->assertSame(
			$expected,
			ReportExporter::get_export_date_range_label(
				array(
					'after'  => '2025-06-01T00:00:00',
					'before' => '2025-06-01T23:59:59',
				)
			),
			'The label should read in the store timezone rather than UTC.'
		);
	}

	/**
	 * @testdox The date range label goes through the WooCommerce date format, so a store can filter it.
	 */
	public function test_date_range_label_uses_the_woocommerce_date_format(): void {
		update_option( 'date_format', 'F j, Y' );
		add_filter( 'woocommerce_date_format', fn() => 'd/m/Y' );

		$this->assertSame(
			'01/06/2025 - 30/06/2025',
			ReportExporter::get_export_date_range_label(
				array(
					'after'  => '2025-06-01T00:00:00',
					'before' => '2025-06-30T23:59:59',
				)
			),
			'The label should honour woocommerce_date_format like the rest of WooCommerce date output.'
		);
	}

	/**
	 * @testdox A single day range is labelled as one date rather than a range.
	 */
	public function test_single_day_date_range_label(): void {
		update_option( 'date_format', 'F j, Y' );

		$this->assertSame(
			'June 1, 2025',
			ReportExporter::get_export_date_range_label(
				array(
					'after'  => '2025-06-01T00:00:00',
					'before' => '2025-06-01T23:59:59',
				)
			),
			'A one day report should not repeat the same date twice.'
		);
	}

	/**
	 * @testdox An export is downloaded under a name that says which period it covers.
	 */
	public function test_download_is_named_after_the_period_it_covers(): void {
		$filename = $this->create_export( 'wc-products-report-export-1234567890' );

		$exporter = new ReportCSVExporter();
		$exporter->set_filename( $filename );
		$exporter->set_download_suffix( '2025-06-01-to-2025-06-30' );

		$this->assertSame(
			'wc-products-report-export-1234567890-2025-06-01-to-2025-06-30.csv',
			$exporter->get_download_filename(),
			'The download should be named after the period the report covers.'
		);
		$this->assertSame(
			$filename,
			$exporter->get_filename(),
			'Naming the download should leave the stored export name alone.'
		);
		$this->assertTrue(
			$exporter->export_file_exists(),
			'The stored export should still be found under the name it was written with.'
		);
	}

	/**
	 * @testdox A download link that names a date range is served under a name that says so.
	 */
	public function test_download_request_names_the_download_after_the_date_range(): void {
		$this->act_as_reports_user();

		$exporter = $this->request_export( $this->download_request( array( 'date_range' => '2025-06-01-to-2025-06-30' ) ) );

		$this->assertNotNull( $exporter, 'A valid download request should be served.' );
		$this->assertSame(
			'wc-products-report-export-1234567890-2025-06-01-to-2025-06-30.csv',
			$exporter->get_download_filename(),
			'The date range on the link should reach the name the export is downloaded as.'
		);
		$this->assertSame(
			'wc-products-report-export-1234567890.csv',
			$exporter->get_filename(),
			'The date range on the link should never change the name the export is stored under.'
		);
	}

	/**
	 * @testdox A download link without a date range keeps the stored export name.
	 */
	public function test_download_request_without_a_date_range(): void {
		$this->act_as_reports_user();

		$exporter = $this->request_export( $this->download_request() );

		$this->assertNotNull( $exporter, 'A valid download request should be served.' );
		$this->assertSame(
			'wc-products-report-export-1234567890.csv',
			$exporter->get_download_filename(),
			'A link that names no period should download under the stored name, as it did before.'
		);
	}

	/**
	 * @testdox A hostile date range cannot escape the download name or the reports directory.
	 *
	 * @testWith ["../../../../etc/passwd", "wc-products-report-export-1234567890-etcpasswd.csv"]
	 *           ["a\r\nX-Injected: 1", "wc-products-report-export-1234567890-a-X-Injected-1.csv"]
	 *           ["setup.bat", "wc-products-report-export-1234567890-setup.bat_.csv"]
	 *
	 * @param string $date_range Date range as it arrives on the link.
	 * @param string $expected   Expected download name.
	 */
	public function test_download_request_sanitises_the_date_range( string $date_range, string $expected ): void {
		$this->act_as_reports_user();

		$exporter = $this->request_export( $this->download_request( array( 'date_range' => $date_range ) ) );

		$this->assertNotNull( $exporter, 'A valid download request should be served.' );
		$this->assertSame(
			$expected,
			$exporter->get_download_filename(),
			'The date range only names the download, so it must not carry separators or a second extension.'
		);
		$this->assertSame(
			'wc-products-report-export-1234567890.csv',
			$exporter->get_filename(),
			'The date range must never reach the path the export is read from.'
		);
	}

	/**
	 * @testdox A download request is refused without the reports capability.
	 */
	public function test_download_request_requires_the_reports_capability(): void {
		wp_set_current_user( $this->factory->user->create( array( 'role' => 'subscriber' ) ) );

		$this->assertNull(
			$this->request_export( $this->download_request() ),
			'A user who cannot view reports should not be served an export.'
		);
	}

	/**
	 * @testdox Requests that do not ask for an export are left alone.
	 *
	 * @testWith [{}]
	 *           [{"action": "edit", "filename": "wc-products-report-export-1234567890"}]
	 *           [{"action": "woocommerce_admin_download_report_csv"}]
	 *           [{"action": "woocommerce_admin_download_report_csv", "filename": ""}]
	 *           [{"action": "woocommerce_admin_download_report_csv", "filename": ["x"]}]
	 *
	 * @param array $request Request parameters, as the download handler reads them.
	 */
	public function test_requests_that_do_not_ask_for_an_export( array $request ): void {
		$this->act_as_reports_user();

		$this->assertNull(
			$this->request_export( $request ),
			'The download handler should leave requests that are not report downloads alone.'
		);
	}

	/**
	 * Sign in as a user allowed to view reports.
	 *
	 * @return void
	 */
	private function act_as_reports_user(): void {
		wp_set_current_user( $this->factory->user->create( array( 'role' => 'administrator' ) ) );
	}

	/**
	 * Build the parameters of a valid download link.
	 *
	 * @param array $extra Parameters to add to the request.
	 * @return array
	 */
	private function download_request( array $extra = array() ): array {
		return array_merge(
			array(
				'action'   => ReportExporter::DOWNLOAD_EXPORT_ACTION,
				'filename' => 'wc-products-report-export-1234567890',
			),
			$extra
		);
	}

	/**
	 * Build the exporter that a download request would be served by.
	 *
	 * Reaches the handler's own reading of the request, so that dropping the date range on the way
	 * from the link to the download's name fails a test.
	 *
	 * @param array $request Request parameters, as the download handler reads them from `$_GET`.
	 * @return ReportCSVExporter|null
	 */
	private function request_export( array $request ) {
		$method = new \ReflectionMethod( ReportExporter::class, 'get_requested_export' );
		$method->setAccessible( true );

		return $method->invoke( null, $request );
	}

	/**
	 * @testdox The emailed download link names the period the export covers.
	 */
	public function test_emailed_link_carries_the_date_range(): void {
		$sent = $this->email_completed_export(
			array(
				array(
					'after'  => '2025-06-01T00:00:00',
					'before' => '2025-06-30T23:59:59',
				),
			)
		);

		$this->assertStringContainsString(
			'date_range=2025-06-01-to-2025-06-30',
			$sent['body'],
			'The emailed link should name the period the export covers.'
		);
	}

	/**
	 * Run one queued action.
	 *
	 * @param int $action_id Action ID.
	 * @return void
	 */
	private function run_action( int $action_id ): void {
		( new \ActionScheduler_QueueRunner() )->process_action( $action_id );
	}

	/**
	 * Subjects of the emails sent so far.
	 *
	 * @return string[]
	 */
	private function get_sent_subjects(): array {
		return array_column( tests_retrieve_phpmailer_instance()->mock_sent, 'subject' );
	}

	/**
	 * Install a logger that records error calls.
	 *
	 * @return object Fake logger with a public `$errors` array.
	 */
	private function capture_log_errors(): object {
		// phpcs:disable Squiz.Commenting, Squiz.Classes.ClassFileName.NoMatch
		$logger = new class() implements \WC_Logger_Interface {
			public array $errors = array();

			public function add( $handle, $message, $level = \WC_Log_Levels::NOTICE ) {
				unset( $handle, $message, $level );
				return true;
			}
			public function log( $level, $message, $context = array() ) {
				unset( $level, $message, $context );
			}
			public function emergency( $message, $context = array() ) {
				unset( $message, $context );
			}
			public function alert( $message, $context = array() ) {
				unset( $message, $context );
			}
			public function critical( $message, $context = array() ) {
				unset( $message, $context );
			}
			public function error( $message, $context = array() ) {
				unset( $context );
				$this->errors[] = $message;
			}
			public function warning( $message, $context = array() ) {
				unset( $message, $context );
			}
			public function notice( $message, $context = array() ) {
				unset( $message, $context );
			}
			public function info( $message, $context = array() ) {
				unset( $message, $context );
			}
			public function debug( $message, $context = array() ) {
				unset( $message, $context );
			}
		};
		// phpcs:enable

		add_filter(
			'woocommerce_logging_class',
			static function () use ( $logger ) {
				return $logger;
			}
		);

		return $logger;
	}

	/**
	 * @testdox An export covers the orders that existed when it was requested, not ones placed while it ran.
	 */
	public function test_export_is_a_snapshot_of_the_request_time(): void {
		reset_phpmailer_instance();
		wp_set_current_user( 1 );
		$this->create_reported_orders( 3, time() - HOUR_IN_SECONDS );
		$export_id = $this->queue_orders_export();
		$batches   = $this->get_batch_actions( $export_id );

		$this->assertCount( 2, $batches, 'Three orders at two per batch should queue two pages.' );
		$this->assertLessThanOrEqual( time(), strtotime( reset( $batches )->get_args()[3]['before'] ), 'The queued period should end at the request time.' );

		$this->run_action( array_key_first( $batches ) );
		$this->create_reported_orders( 2, time() + HOUR_IN_SECONDS );
		WC_Helper_Queue::run_all_pending( ReportExporter::$group );

		$exporter = new ReportCSVExporter();
		$exporter->set_filename( "wc-orders-report-export-{$export_id}" );
		$path = ReportCSVExporter::get_reports_directory() . $exporter->get_filename();

		$this->assertSame( 100, ReportExporter::get_export_percentage_complete( 'orders', $export_id ), 'Every queued page ran, so the export is complete.' );
		$this->assertTrue( $exporter->export_file_exists(), 'The header row should be written once every queued page ran.' );
		$this->assertCount( 3, file( $path ), 'Orders placed after the request should not be in the export.' );
		$this->assertCount( 1, preg_grep( '/is ready/', $this->get_sent_subjects() ), 'The download link should be emailed.' );
	}

	/**
	 * @testdox An export whose row count shrank while it ran is still finished and emailed.
	 */
	public function test_export_completes_when_orders_drop_out_mid_export(): void {
		reset_phpmailer_instance();
		wp_set_current_user( 1 );
		$order_ids = $this->create_reported_orders( 4, time() - HOUR_IN_SECONDS );
		$export_id = $this->queue_orders_export();
		$batches   = $this->get_batch_actions( $export_id );

		$this->assertCount( 2, $batches, 'Four orders at two per batch should queue two pages.' );

		$this->run_action( array_key_first( $batches ) );
		foreach ( array_slice( $order_ids, 0, 3 ) as $order_id ) {
			$order = wc_get_order( $order_id );
			$order->set_status( 'cancelled' );
			$order->save();
			OrdersScheduler::import( $order_id );
		}
		WC_Helper_Queue::run_all_pending( ReportExporter::$group );

		$exporter = new ReportCSVExporter();
		$exporter->set_filename( "wc-orders-report-export-{$export_id}" );

		$this->assertSame( 100, ReportExporter::get_export_percentage_complete( 'orders', $export_id ), 'Every queued page ran, so the export is complete.' );
		$this->assertTrue( $exporter->export_file_exists(), 'The header row should be written once every queued page ran.' );
		$this->assertCount( 1, preg_grep( '/is ready/', $this->get_sent_subjects() ), 'The download link should be emailed.' );
	}

	/**
	 * @testdox The report period is capped at the request time only when it runs into the future.
	 */
	public function test_report_period_is_capped_at_the_request_time(): void {
		$past   = ReportExporter::freeze_report_period( 'orders', array( 'before' => '2020-01-01T00:00:00' ) );
		$future = ReportExporter::freeze_report_period( 'orders', array( 'before' => '2100-01-01T00:00:00' ) );
		$open   = ReportExporter::freeze_report_period( 'orders', array( 'after' => '2020-01-01T00:00:00' ) );
		$stock  = ReportExporter::freeze_report_period( 'stock', array( 'after' => '2020-01-01T00:00:00' ) );

		$this->assertSame( '2020-01-01T00:00:00', $past['before'], 'A period that already ended is left alone.' );
		$this->assertLessThanOrEqual( time(), strtotime( $future['before'] ), 'A period running into the future ends at the request time.' );
		$this->assertLessThanOrEqual( time(), strtotime( $open['before'] ), 'A period with no end ends at the request time.' );
		$this->assertArrayNotHasKey( 'before', $stock, 'A report without a period is left alone.' );
	}

	/**
	 * @testdox The cap is the store's local time on a store set to a manual UTC offset.
	 */
	public function test_report_period_cap_uses_the_store_utc_offset(): void {
		update_option( 'timezone_string', '' );
		update_option( 'gmt_offset', 10 );

		$capped = ReportExporter::freeze_report_period( 'orders', array( 'after' => '2020-01-01T00:00:00' ) );

		$this->assertStringEndsWith( '+10:00', $capped['before'], 'The cap should spell out the store offset.' );
		$this->assertEqualsWithDelta(
			time(),
			( new \DateTime( $capped['before'], new \DateTimeZone( wc_timezone_string() ) ) )->getTimestamp(),
			5,
			'Read as the report data store reads it, the cap should be the request time, not the UTC clock.'
		);
	}

	/**
	 * @testdox A failed batch is logged and emailed as a failure instead of being silently dropped.
	 */
	public function test_failed_batch_is_reported(): void {
		reset_phpmailer_instance();
		wp_set_current_user( 1 );
		$logger = $this->capture_log_errors();
		$this->create_reported_orders( 3 );
		$export_id = $this->queue_orders_export();

		add_action(
			ReportExporter::get_action( 'export_report' ),
			static function ( $page ) {
				if ( 2 === (int) $page ) {
					throw new \RuntimeException( 'database went away' );
				}
			},
			1
		);
		WC_Helper_Queue::run_all_pending( ReportExporter::$group );

		$exporter = new ReportCSVExporter();
		$exporter->set_filename( "wc-orders-report-export-{$export_id}" );

		$this->assertFalse( $exporter->export_file_exists(), 'An export with a missing page must not be served as complete.' );
		$this->assertCount( 0, preg_grep( '/is ready/', $this->get_sent_subjects() ), 'No download link should be emailed for a broken export.' );
		$this->assertCount( 1, preg_grep( '/did not complete/', $this->get_sent_subjects() ), 'The user should be told the export failed.' );
		$this->assertCount( 1, $logger->errors, 'The failure should be logged.' );
		$this->assertStringContainsString( 'database went away', $logger->errors[0], 'The log should carry the batch error.' );
	}

	/**
	 * @testdox The email waits for a batch that another runner is still executing.
	 */
	public function test_email_waits_for_a_running_batch(): void {
		reset_phpmailer_instance();
		wp_set_current_user( 1 );
		$this->create_reported_orders( 3 );
		$export_id = $this->queue_orders_export();
		$batches   = $this->get_batch_actions( $export_id );

		$this->run_action( array_key_first( $batches ) );
		\ActionScheduler::store()->log_execution( array_key_last( $batches ) );

		$this->assertInstanceOf(
			\ActionScheduler_Action::class,
			ReportExporter::get_next_blocking_job( 'email_report_download_link' ),
			'A batch that is in progress should block the email action like a pending one.'
		);

		$email_hook = ReportExporter::get_action( 'email_report_download_link' );
		$pending    = count(
			WC()->queue()->search(
				array(
					'hook'     => $email_hook,
					'status'   => 'pending',
					'per_page' => -1,
				)
			)
		);

		ReportExporter::email_report_download_link( 1, $export_id, 'orders' );

		$this->assertCount( 0, preg_grep( '/Orders Report/', $this->get_sent_subjects() ), 'Nothing should be emailed while a page is still being written.' );
		$this->assertSame(
			$pending + 1,
			count(
				WC()->queue()->search(
					array(
						'hook'     => $email_hook,
						'status'   => 'pending',
						'per_page' => -1,
					)
				)
			),
			'The email should be re-queued to check again later.'
		);
	}

	/**
	 * @testdox An export queued before the date range was added still emails a working link.
	 */
	public function test_emailed_link_for_an_export_queued_without_report_args(): void {
		// Exports queued by an earlier release carry three arguments, not four.
		$sent = $this->email_completed_export( array() );

		$this->assertStringContainsString(
			'Your Products Report download is ready',
			$sent['subject'],
			'An export queued without report arguments should keep the original subject.'
		);
		$this->assertStringContainsString(
			'action=woocommerce_admin_download_report_csv',
			$sent['body'],
			'An export queued without report arguments should still be emailed a download link.'
		);
		$this->assertStringNotContainsString(
			'date_range=',
			$sent['body'],
			'An export with no known date range should not claim one.'
		);
	}

	/**
	 * Email the download link for a finished export and return the message that went out.
	 *
	 * Dispatched through the hook Action Scheduler fires, so the number of arguments a queued
	 * action carries is what decides how the callback is reached.
	 *
	 * @param array $queued_args Arguments the queued action carries after the report type.
	 * @return array The sent message.
	 */
	private function email_completed_export( array $queued_args ): array {
		$user_id   = $this->factory->user->create( array( 'role' => 'administrator' ) );
		$export_id = (string) microtime( true );
		$hook      = ReportExporter::get_action( 'email_report_download_link' );
		$mailer    = tests_retrieve_phpmailer_instance();

		ReportExporter::update_export_percentage_complete( 'products', $export_id, 100 );

		$this->assertNotFalse( has_action( $hook ), 'The export email action should be registered.' );

		do_action_ref_array( $hook, array_merge( array( $user_id, $export_id, 'products' ), $queued_args ) );

		$sent = end( $mailer->mock_sent );

		$this->assertIsArray( $sent, 'A finished export should be emailed to the user who asked for it.' );

		return $sent;
	}

	/**
	 * @testdox Progress is counted in queued pages and is unknown when no pages were queued.
	 */
	public function test_progress_is_read_from_the_queue(): void {
		$this->create_reported_orders( 3 );
		$export_id = $this->queue_orders_export();

		$this->run_action( array_key_first( $this->get_batch_actions( $export_id ) ) );
		$progress = ReportExporter::get_export_progress( $export_id );

		$this->assertSame( 2, $progress['pages'], 'Two pages were queued.' );
		$this->assertSame( 1, $progress['complete'], 'One page has run.' );
		$this->assertSame( 1, $progress['unfinished'], 'One page is still queued.' );
		$this->assertSame( 0, $progress['failed'], 'No page failed.' );
		$this->assertSame( 50, ReportExporter::get_export_percentage_complete( 'orders', $export_id ), 'Half of the queued pages ran.' );
		$this->assertNull( ReportExporter::get_export_progress( 'never-queued' ), 'An export without queued pages has no progress to read.' );
	}

	/**
	 * @testdox Cleanup runs from the daily WooCommerce Admin event.
	 */
	public function test_cleanup_is_hooked_to_the_daily_event(): void {
		$this->assertSame(
			10,
			has_action( 'wc_admin_daily', array( ReportExporter::class, 'delete_expired_exports' ) ),
			'Expired exports should be cleaned up by the daily event rather than on each request.'
		);
		$this->assertSame(
			10,
			has_action( 'admin_init', array( ReportExporter::class, 'download_export_file' ) ),
			'The download handler should be registered.'
		);
	}
}
