<?php declare( strict_types=1 );

namespace Automattic\WooCommerce\Tests\Admin\Features\Fulfillments\Importer;

use Automattic\WooCommerce\Admin\Features\Fulfillments\DataStore\FulfillmentsDataStore;
use Automattic\WooCommerce\Admin\Features\Fulfillments\Fulfillment;
use Automattic\WooCommerce\Admin\Features\Fulfillments\FulfillmentsController;
use Automattic\WooCommerce\Admin\Features\Fulfillments\Importer\FulfillmentsCsvImporter;
use Automattic\WooCommerce\RestApi\UnitTests\Helpers\OrderHelper;
use WC_Data_Store;
use WC_Order;

/**
 * Tests for the Fulfillments CSV importer service.
 */
class FulfillmentsCsvImporterTest extends \WC_Unit_Test_Case {

	/**
	 * Original value of the fulfillments feature flag.
	 *
	 * @var mixed
	 */
	private static $original_fulfillments_flag;

	/**
	 * Paths to temporary CSV files created by tests; cleaned up in tearDown.
	 *
	 * @var array<int, string>
	 */
	private array $temp_files = array();

	/**
	 * Bootstrap the fulfillments feature for the test run.
	 *
	 * @since 11.2.0
	 */
	public static function setUpBeforeClass(): void {
		parent::setUpBeforeClass();
		self::$original_fulfillments_flag = get_option( 'woocommerce_feature_fulfillments_enabled' );
		update_option( 'woocommerce_feature_fulfillments_enabled', 'yes' );
		$controller = wc_get_container()->get( FulfillmentsController::class );
		$controller->register();
		$controller->initialize_fulfillments();
	}

	/**
	 * Restore the original feature flag value.
	 *
	 * @since 11.2.0
	 */
	public static function tearDownAfterClass(): void {
		if ( false === self::$original_fulfillments_flag ) {
			delete_option( 'woocommerce_feature_fulfillments_enabled' );
		} else {
			update_option( 'woocommerce_feature_fulfillments_enabled', self::$original_fulfillments_flag );
		}
		parent::tearDownAfterClass();
	}

	/**
	 * Clean up any temp files between tests.
	 */
	public function tearDown(): void {
		foreach ( $this->temp_files as $path ) {
			if ( file_exists( $path ) ) {
				wp_delete_file( $path );
			}
		}
		$this->temp_files = array();
		parent::tearDown();
	}

	/**
	 * Write the given CSV content to a temp file and return its path.
	 *
	 * @param string $content CSV content.
	 * @return string
	 */
	private function make_csv( string $content ): string {
		$path = wp_tempnam( 'wc-fulfillments-import-' );
		file_put_contents( $path, $content ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_file_put_contents -- Test fixture write.
		$this->temp_files[] = $path;
		return $path;
	}

	/**
	 * Create a simple paid order suitable for fulfillment.
	 *
	 * @return WC_Order
	 */
	private function make_order(): WC_Order {
		return OrderHelper::create_order();
	}

	/**
	 * Drive a full import by looping import_chunk() with the auto-detected mapping,
	 * mirroring how the REST controller consumes the importer.
	 *
	 * @param FulfillmentsCsvImporter $importer Importer under test.
	 * @param int                     $limit    Chunk size.
	 * @return array{created:int, updated:int, skipped:int, failed:int, notified:int, rows:array<int, array<string, mixed>>}
	 */
	private function run_import( FulfillmentsCsvImporter $importer, int $limit = FulfillmentsCsvImporter::DEFAULT_CHUNK_SIZE ): array {
		$summary = array(
			'created'  => 0,
			'updated'  => 0,
			'skipped'  => 0,
			'failed'   => 0,
			'notified' => 0,
			'rows'     => array(),
		);

		$parsed = $importer->parse_headers();
		if ( isset( $parsed['error'] ) ) {
			$summary['rows'][] = array(
				'row'     => 0,
				'status'  => 'failed',
				'code'    => (string) $parsed['error']['code'],
				'message' => (string) $parsed['error']['message'],
			);
			++$summary['failed'];
			return $summary;
		}

		$mapping     = is_array( $parsed['detected_mapping'] ?? null ) ? $parsed['detected_mapping'] : array();
		$total       = (int) ( $parsed['total'] ?? 0 );
		$seen        = array();
		$offset      = 0;
		$byte_offset = 0;

		do {
			$result = $importer->import_chunk(
				$offset,
				$limit,
				$mapping,
				array(
					'seen_tracking_pairs' => $seen,
					'byte_offset'         => $byte_offset,
				)
			);

			foreach ( array( 'created', 'updated', 'skipped', 'failed', 'notified' ) as $key ) {
				$summary[ $key ] += (int) ( $result['counts'][ $key ] ?? 0 );
			}
			$summary['rows'] = array_merge( $summary['rows'], (array) $result['rows'] );
			$seen            = (array) $result['seen_tracking_pairs'];
			$byte_offset     = (int) $result['byte_offset'];

			if ( ! empty( $result['aborted'] ) || ! empty( $result['eof'] ) ) {
				break;
			}

			$offset += $limit;
		} while ( $offset < $total );

		return $summary;
	}

	/**
	 * @testdox A valid CSV row creates a fulfilled fulfillment.
	 */
	public function test_valid_row_creates_fulfillment(): void {
		$order = $this->make_order();
		$csv   = "order_number,tracking_number,shipment_provider\n{$order->get_id()},TRACK-1,ups\n";
		$file  = $this->make_csv( $csv );

		$sut     = new FulfillmentsCsvImporter( $file );
		$summary = $this->run_import( $sut );

		$this->assertSame( 1, $summary['created'] );
		$this->assertSame( 0, $summary['failed'] );
		$this->assertSame( 0, $summary['updated'] );
		$this->assertCount( 1, $summary['rows'] );
		$this->assertSame( 'created', $summary['rows'][0]['status'] );

		/** @var FulfillmentsDataStore $store */
		$store        = WC_Data_Store::load( 'order-fulfillment' );
		$fulfillments = $store->read_fulfillments( WC_Order::class, (string) $order->get_id() );

		$this->assertCount( 1, $fulfillments );
		/** @var Fulfillment $fulfillment */
		$fulfillment = $fulfillments[0];
		$this->assertTrue( $fulfillment->get_is_fulfilled() );
		$this->assertSame( 'TRACK-1', $fulfillment->get_tracking_number() );
		$this->assertSame( 'ups', $fulfillment->get_shipment_provider() );
	}

	/**
	 * @testdox Unknown order numbers produce a failed row without aborting the import.
	 */
	public function test_unknown_order_number_fails_row(): void {
		$order = $this->make_order();
		$csv   = "order_number,tracking_number,shipment_provider\n99999999,TRACK-X,ups\n{$order->get_id()},TRACK-Y,fedex\n";
		$file  = $this->make_csv( $csv );

		$sut     = new FulfillmentsCsvImporter( $file );
		$summary = $this->run_import( $sut );

		$this->assertSame( 1, $summary['failed'] );
		$this->assertSame( 1, $summary['created'] );
		$this->assertSame( 'failed', $summary['rows'][0]['status'] );
		$this->assertSame( 'created', $summary['rows'][1]['status'] );
	}

	/**
	 * @testdox Missing required columns fails the entire import early.
	 */
	public function test_missing_required_columns_fails_early(): void {
		$csv  = "order_number,tracking_number\n1,TRACK-1\n";
		$file = $this->make_csv( $csv );

		$sut     = new FulfillmentsCsvImporter( $file );
		$summary = $this->run_import( $sut );

		$this->assertSame( 1, $summary['failed'] );
		$this->assertSame( 0, $summary['created'] );
		$this->assertArrayHasKey( 'message', $summary['rows'][0] );
		$this->assertStringContainsString( 'shipment_provider', $summary['rows'][0]['message'] );
	}

	/**
	 * @testdox An empty CSV is reported as a failure.
	 */
	public function test_empty_csv_is_reported(): void {
		$file = $this->make_csv( '' );

		$sut     = new FulfillmentsCsvImporter( $file );
		$summary = $this->run_import( $sut );

		$this->assertSame( 1, $summary['failed'] );
		$this->assertSame( 0, $summary['created'] );
	}

	/**
	 * @testdox Missing tracking number fails the row.
	 */
	public function test_missing_tracking_number_fails_row(): void {
		$order = $this->make_order();
		$csv   = "order_number,tracking_number,shipment_provider\n{$order->get_id()},,ups\n";
		$file  = $this->make_csv( $csv );

		$sut     = new FulfillmentsCsvImporter( $file );
		$summary = $this->run_import( $sut );

		$this->assertSame( 1, $summary['failed'] );
		$this->assertSame( 'failed', $summary['rows'][0]['status'] );
		$this->assertStringContainsString( 'tracking', strtolower( $summary['rows'][0]['message'] ) );
	}

	/**
	 * @testdox A second row with the same order/tracking pair is skipped (in-file dedupe).
	 */
	public function test_duplicate_row_in_file_is_skipped(): void {
		$order = $this->make_order();
		$csv   = "order_number,tracking_number,shipment_provider\n{$order->get_id()},DUP-1,ups\n{$order->get_id()},DUP-1,ups\n";
		$file  = $this->make_csv( $csv );

		$sut     = new FulfillmentsCsvImporter( $file );
		$summary = $this->run_import( $sut );

		$this->assertSame( 1, $summary['created'] );
		$this->assertSame( 1, $summary['skipped'] );
	}

	/**
	 * @testdox A failed row does not mark its order/tracking pair as seen, so an identical valid row later still imports.
	 */
	public function test_failed_row_does_not_block_identical_valid_row(): void {
		$order = $this->make_order();
		$csv   = "order_number,tracking_number,shipment_provider,items\n"
			. "{$order->get_id()},RETRY-1,ups,999999:1\n"
			. "{$order->get_id()},RETRY-1,ups,\n";
		$file  = $this->make_csv( $csv );

		$sut     = new FulfillmentsCsvImporter( $file );
		$summary = $this->run_import( $sut );

		$this->assertSame( 1, $summary['failed'] );
		$this->assertSame( 1, $summary['created'] );
		$this->assertSame( 0, $summary['skipped'] );
		$this->assertSame( 'failed', $summary['rows'][0]['status'] );
		$this->assertSame( 'created', $summary['rows'][1]['status'], 'The valid retry row must not be treated as a duplicate of the failed row' );
	}

	/**
	 * @testdox An existing fulfillment with the same tracking number is updated when update_existing is true.
	 */
	public function test_existing_fulfillment_with_same_tracking_is_updated(): void {
		$order = $this->make_order();
		$csv   = "order_number,tracking_number,shipment_provider\n{$order->get_id()},TRACK-UP,ups\n";
		$file  = $this->make_csv( $csv );

		// First import creates the fulfillment.
		$this->run_import( new FulfillmentsCsvImporter( $file ) );

		// Second import with the same tracking number should update the existing record's provider.
		$csv2  = "order_number,tracking_number,shipment_provider\n{$order->get_id()},TRACK-UP,fedex\n";
		$file2 = $this->make_csv( $csv2 );

		$sut     = new FulfillmentsCsvImporter( $file2 );
		$summary = $this->run_import( $sut );

		$this->assertSame( 0, $summary['created'] );
		$this->assertSame( 1, $summary['updated'] );

		/** @var FulfillmentsDataStore $store */
		$store        = WC_Data_Store::load( 'order-fulfillment' );
		$fulfillments = $store->read_fulfillments( WC_Order::class, (string) $order->get_id() );

		$this->assertCount( 1, $fulfillments );
		$this->assertSame( 'fedex', $fulfillments[0]->get_shipment_provider() );
	}

	/**
	 * @testdox A blank items cell on an update row preserves the existing partial items.
	 */
	public function test_blank_items_on_update_preserves_existing_items(): void {
		$order      = $this->make_order();
		$line_items = $order->get_items( 'line_item' );
		$first_item = current( $line_items );
		$item_id    = (int) $first_item->get_id();
		$this->assertGreaterThanOrEqual( 2, (int) $first_item->get_quantity() );

		// Partial fulfillment: only 1 unit of the first line item.
		$csv     = "order_number,tracking_number,shipment_provider,items\n{$order->get_id()},PARTIAL-1,ups,{$item_id}:1\n";
		$summary = $this->run_import( new FulfillmentsCsvImporter( $this->make_csv( $csv ) ) );
		$this->assertSame( 1, $summary['created'] );

		// Re-import the same tracking number to fix the carrier, leaving items blank.
		$csv2    = "order_number,tracking_number,shipment_provider,items\n{$order->get_id()},PARTIAL-1,fedex,\n";
		$summary = $this->run_import( new FulfillmentsCsvImporter( $this->make_csv( $csv2 ) ) );
		$this->assertSame( 1, $summary['updated'] );

		/** @var FulfillmentsDataStore $store */
		$store        = WC_Data_Store::load( 'order-fulfillment' );
		$fulfillments = $store->read_fulfillments( WC_Order::class, (string) $order->get_id() );
		$this->assertCount( 1, $fulfillments );
		$this->assertSame( 'fedex', $fulfillments[0]->get_shipment_provider() );

		$items = $fulfillments[0]->get_items();
		$this->assertCount( 1, $items, 'A blank items cell must not expand the fulfillment to all order items' );
		$this->assertSame( $item_id, (int) $items[0]['item_id'] );
		$this->assertSame( 1, (int) $items[0]['qty'] );
	}

	/**
	 * @testdox When update_existing is false, an existing tracking number is skipped instead of updated.
	 */
	public function test_existing_fulfillment_is_skipped_when_update_disabled(): void {
		$order = $this->make_order();
		$csv   = "order_number,tracking_number,shipment_provider\n{$order->get_id()},TRACK-S,ups\n";
		$this->run_import( new FulfillmentsCsvImporter( $this->make_csv( $csv ) ) );

		$csv2 = "order_number,tracking_number,shipment_provider\n{$order->get_id()},TRACK-S,fedex\n";
		$sut  = new FulfillmentsCsvImporter(
			$this->make_csv( $csv2 ),
			array( 'update_existing' => false )
		);

		$summary = $this->run_import( $sut );

		$this->assertSame( 0, $summary['updated'] );
		$this->assertSame( 1, $summary['skipped'] );
	}

	/**
	 * @testdox The notify_customer option triggers the documented WooCommerce hook on creation.
	 */
	public function test_notify_customer_fires_hook(): void {
		$order      = $this->make_order();
		$csv        = "order_number,tracking_number,shipment_provider\n{$order->get_id()},NOTIFY-1,ups\n";
		$file       = $this->make_csv( $csv );
		$hook_count = 0;
		$listener   = function () use ( &$hook_count ) {
			++$hook_count;
		};
		add_action( 'woocommerce_fulfillment_created_notification', $listener );

		try {
			$sut     = new FulfillmentsCsvImporter( $file, array( 'notify_customer' => true ) );
			$summary = $this->run_import( $sut );
		} finally {
			remove_action( 'woocommerce_fulfillment_created_notification', $listener );
		}

		$this->assertSame( 1, $hook_count );
		$this->assertSame( 1, $summary['notified'] );
	}

	/**
	 * @testdox Without notify_customer, the customer-facing hook does not fire.
	 */
	public function test_no_notification_when_flag_off(): void {
		$order      = $this->make_order();
		$csv        = "order_number,tracking_number,shipment_provider\n{$order->get_id()},NOTIFY-2,ups\n";
		$file       = $this->make_csv( $csv );
		$hook_count = 0;
		$listener   = function () use ( &$hook_count ) {
			++$hook_count;
		};
		add_action( 'woocommerce_fulfillment_created_notification', $listener );

		try {
			$sut     = new FulfillmentsCsvImporter( $file );
			$summary = $this->run_import( $sut );
		} finally {
			remove_action( 'woocommerce_fulfillment_created_notification', $listener );
		}

		$this->assertSame( 0, $hook_count );
		$this->assertSame( 0, $summary['notified'] );
		$this->assertSame( 1, $summary['created'] );
	}

	/**
	 * @testdox Header aliases (e.g. "Tracking" / "Carrier") are normalized to the canonical columns.
	 */
	public function test_header_aliases_are_accepted(): void {
		$order = $this->make_order();
		$csv   = "Order,Tracking,Carrier\n{$order->get_id()},ALIAS-1,ups\n";
		$file  = $this->make_csv( $csv );

		$sut     = new FulfillmentsCsvImporter( $file );
		$summary = $this->run_import( $sut );

		$this->assertSame( 1, $summary['created'] );
		$this->assertSame( 0, $summary['failed'] );
	}

	/**
	 * @testdox The resolve-order filter can map a non-numeric order number onto an order.
	 */
	public function test_resolve_order_filter_handles_custom_order_numbers(): void {
		$order  = $this->make_order();
		$filter = function ( $resolved, $order_number ) use ( $order ) {
			return 'INV-100' === $order_number ? $order : $resolved;
		};
		add_filter( 'woocommerce_fulfillments_csv_importer_resolve_order', $filter, 10, 2 );

		try {
			$csv     = "order_number,tracking_number,shipment_provider\nINV-100,CUSTOM-1,ups\n";
			$summary = $this->run_import( new FulfillmentsCsvImporter( $this->make_csv( $csv ) ) );
		} finally {
			remove_filter( 'woocommerce_fulfillments_csv_importer_resolve_order', $filter, 10 );
		}

		$this->assertSame( 1, $summary['created'] );
		$this->assertSame( $order->get_id(), $summary['rows'][0]['order_id'] );
	}

	/**
	 * @testdox The resolve-order filter still runs for rows whose order was loaded by chunk priming.
	 */
	public function test_resolve_order_filter_runs_for_primed_orders(): void {
		$first  = $this->make_order();
		$second = $this->make_order();
		$seen   = array();
		$filter = function ( $resolved, $order_number ) use ( &$seen, $second ) {
			$seen[] = $order_number;
			// Redirect every row onto the second order to prove the filter wins over priming.
			return $second;
		};
		add_filter( 'woocommerce_fulfillments_csv_importer_resolve_order', $filter, 10, 2 );

		try {
			$csv     = "order_number,tracking_number,shipment_provider\n{$first->get_id()},PRIMED-1,ups\n";
			$summary = $this->run_import( new FulfillmentsCsvImporter( $this->make_csv( $csv ) ) );
		} finally {
			remove_filter( 'woocommerce_fulfillments_csv_importer_resolve_order', $filter, 10 );
		}

		$this->assertSame( array( (string) $first->get_id() ), $seen, 'The filter must run for a primed order number' );
		$this->assertSame( 1, $summary['created'] );
		$this->assertSame( $second->get_id(), $summary['rows'][0]['order_id'] );
	}

	/**
	 * @testdox A resolve-order filter returning a non-order fails the row instead of fataling.
	 */
	public function test_resolve_order_filter_junk_return_fails_the_row(): void {
		$order  = $this->make_order();
		$filter = fn() => 'not-an-order';
		add_filter( 'woocommerce_fulfillments_csv_importer_resolve_order', $filter );

		try {
			$csv     = "order_number,tracking_number,shipment_provider\n{$order->get_id()},JUNK-1,ups\n";
			$summary = $this->run_import( new FulfillmentsCsvImporter( $this->make_csv( $csv ) ) );
		} finally {
			remove_filter( 'woocommerce_fulfillments_csv_importer_resolve_order', $filter );
		}

		$this->assertSame( 0, $summary['created'] );
		$this->assertSame( 1, $summary['failed'] );
		$this->assertSame( 'order_not_found', $summary['rows'][0]['code'] );
	}

	/**
	 * @testdox Chunk priming loads the orders the rows name, not the most recent ones.
	 */
	public function test_chunk_priming_targets_the_rows_own_orders(): void {
		$target = $this->make_order();
		// Newer orders would win a query that ignored the ID filter.
		$this->make_order();
		$this->make_order();

		$csv     = "order_number,tracking_number,shipment_provider\n{$target->get_id()},PRIME-TARGET,ups\n";
		$summary = $this->run_import( new FulfillmentsCsvImporter( $this->make_csv( $csv ) ) );

		$this->assertSame( 1, $summary['created'] );
		$this->assertSame( $target->get_id(), $summary['rows'][0]['order_id'] );
	}

	/**
	 * @testdox Canonical keys the importer does not know are dropped from the column-aliases filter.
	 */
	public function test_alias_filter_ignores_unknown_canonical_keys(): void {
		$order  = $this->make_order();
		$filter = function ( $aliases ) {
			$aliases['warehouse_bay'] = array( 'bay' );
			return $aliases;
		};
		add_filter( 'woocommerce_fulfillments_csv_importer_column_aliases', $filter );

		try {
			$csv    = "order_number,tracking_number,shipment_provider,bay\n{$order->get_id()},UNKNOWN-KEY,ups,A1\n";
			$sut    = new FulfillmentsCsvImporter( $this->make_csv( $csv ) );
			$parsed = $sut->parse_headers();
		} finally {
			remove_filter( 'woocommerce_fulfillments_csv_importer_column_aliases', $filter );
		}

		// An unknown key would be auto-detected here and then rejected by the /run schema.
		$this->assertNotContains( 'warehouse_bay', $parsed['detected_mapping'] );
	}

	/**
	 * @testdox Malformed values from the column-aliases filter are dropped instead of fataling the import.
	 */
	public function test_malformed_alias_filter_output_is_tolerated(): void {
		$order  = $this->make_order();
		$filter = function ( $aliases ) {
			// A misbehaving callback: non-array alias list, non-string entries, junk key.
			$aliases[ FulfillmentsCsvImporter::COL_TRACKING_URL ] = 'not-an-array';
			$aliases[ FulfillmentsCsvImporter::COL_ITEMS ]        = array( 42, null, 'items' );
			$aliases[0] = array( 'zero' );
			return $aliases;
		};
		add_filter( 'woocommerce_fulfillments_csv_importer_column_aliases', $filter );

		try {
			$csv     = "order_number,tracking_number,shipment_provider,items\n{$order->get_id()},ALIAS-BAD,ups,\n";
			$summary = $this->run_import( new FulfillmentsCsvImporter( $this->make_csv( $csv ) ) );
		} finally {
			remove_filter( 'woocommerce_fulfillments_csv_importer_column_aliases', $filter );
		}

		$this->assertSame( 1, $summary['created'], 'Valid alias entries must keep working when the filter returns junk for others' );
		$this->assertSame( 0, $summary['failed'] );
	}

	/**
	 * @testdox When no items column is provided, all order line items are included at full ordered qty.
	 */
	public function test_items_default_to_full_order(): void {
		$order = $this->make_order();
		$csv   = "order_number,tracking_number,shipment_provider\n{$order->get_id()},DEFAULT-ITEMS,ups\n";
		$file  = $this->make_csv( $csv );

		$sut = new FulfillmentsCsvImporter( $file );
		$this->run_import( $sut );

		/** @var FulfillmentsDataStore $store */
		$store        = WC_Data_Store::load( 'order-fulfillment' );
		$fulfillments = $store->read_fulfillments( WC_Order::class, (string) $order->get_id() );
		$this->assertCount( 1, $fulfillments );
		$items = $fulfillments[0]->get_items();
		$this->assertNotEmpty( $items, 'Default items should be derived from the order.' );

		$expected_total = 0;
		foreach ( $order->get_items( 'line_item' ) as $line_item ) {
			$expected_total += (int) $line_item->get_quantity();
		}
		$this->assertSame( $expected_total, $fulfillments[0]->get_item_count() );
	}

	/**
	 * @testdox An items entry referencing an order item ID is honored, with qty respected.
	 */
	public function test_items_column_with_specific_item_id(): void {
		$order      = $this->make_order();
		$line_items = $order->get_items( 'line_item' );
		$this->assertNotEmpty( $line_items );
		$first_item = current( $line_items );
		$item_id    = (int) $first_item->get_id();

		$csv  = "order_number,tracking_number,shipment_provider,items\n{$order->get_id()},ITEM-1,ups,{$item_id}:1\n";
		$file = $this->make_csv( $csv );

		$sut     = new FulfillmentsCsvImporter( $file );
		$summary = $this->run_import( $sut );

		$this->assertSame( 1, $summary['created'] );
		/** @var FulfillmentsDataStore $store */
		$store        = WC_Data_Store::load( 'order-fulfillment' );
		$fulfillments = $store->read_fulfillments( WC_Order::class, (string) $order->get_id() );
		$items        = $fulfillments[0]->get_items();
		$this->assertCount( 1, $items );
		$this->assertSame( $item_id, $items[0]['item_id'] );
		$this->assertSame( 1, (int) $items[0]['qty'] );
	}

	/**
	 * @testdox Quantity larger than what the order has fails the row.
	 */
	public function test_items_quantity_above_ordered_fails(): void {
		$order      = $this->make_order();
		$line_items = $order->get_items( 'line_item' );
		$first_item = current( $line_items );
		$item_id    = (int) $first_item->get_id();
		$too_many   = (int) $first_item->get_quantity() + 100;

		$csv  = "order_number,tracking_number,shipment_provider,items\n{$order->get_id()},ITEM-OVER,ups,{$item_id}:{$too_many}\n";
		$file = $this->make_csv( $csv );

		$sut     = new FulfillmentsCsvImporter( $file );
		$summary = $this->run_import( $sut );
		$this->assertSame( 1, $summary['failed'] );
		$this->assertSame( 'failed', $summary['rows'][0]['status'] );
	}

	/**
	 * @testdox Repeated item entries whose combined quantity exceeds the ordered quantity fail the row.
	 */
	public function test_items_duplicate_entries_exceeding_ordered_quantity_fail(): void {
		$order      = $this->make_order();
		$line_items = $order->get_items( 'line_item' );
		$first_item = current( $line_items );
		$item_id    = (int) $first_item->get_id();
		$ordered    = (int) $first_item->get_quantity();

		$csv  = "order_number,tracking_number,shipment_provider,items\n{$order->get_id()},ITEM-DUP-OVER,ups,{$item_id}:{$ordered}|{$item_id}:1\n";
		$file = $this->make_csv( $csv );

		$sut     = new FulfillmentsCsvImporter( $file );
		$summary = $this->run_import( $sut );

		$this->assertSame( 1, $summary['failed'], 'Duplicate entries must be summed before the ordered-quantity check' );
		$this->assertSame( 'failed', $summary['rows'][0]['status'] );
	}

	/**
	 * @testdox Repeated item entries within the ordered quantity are summed into one item.
	 */
	public function test_items_duplicate_entries_are_aggregated(): void {
		$order      = $this->make_order();
		$line_items = $order->get_items( 'line_item' );
		$first_item = current( $line_items );
		$item_id    = (int) $first_item->get_id();
		$this->assertGreaterThanOrEqual( 2, (int) $first_item->get_quantity() );

		$csv  = "order_number,tracking_number,shipment_provider,items\n{$order->get_id()},ITEM-DUP-SUM,ups,{$item_id}:1|{$item_id}:1\n";
		$file = $this->make_csv( $csv );

		$sut     = new FulfillmentsCsvImporter( $file );
		$summary = $this->run_import( $sut );

		$this->assertSame( 1, $summary['created'] );
		/** @var FulfillmentsDataStore $store */
		$store        = WC_Data_Store::load( 'order-fulfillment' );
		$fulfillments = $store->read_fulfillments( WC_Order::class, (string) $order->get_id() );
		$items        = $fulfillments[0]->get_items();
		$this->assertCount( 1, $items );
		$this->assertSame( $item_id, $items[0]['item_id'] );
		$this->assertSame( 2, (int) $items[0]['qty'] );
	}

	/**
	 * @testdox Item IDs that don't belong to the order fail the row.
	 */
	public function test_items_unknown_item_id_fails(): void {
		$order = $this->make_order();
		$csv   = "order_number,tracking_number,shipment_provider,items\n{$order->get_id()},ITEM-UNKNOWN,ups,9999999:1\n";
		$file  = $this->make_csv( $csv );

		$sut     = new FulfillmentsCsvImporter( $file );
		$summary = $this->run_import( $sut );
		$this->assertSame( 1, $summary['failed'] );
	}

	/**
	 * @testdox Fractional item quantities are rejected rather than silently truncated.
	 */
	public function test_items_fractional_quantity_fails(): void {
		$order      = $this->make_order();
		$line_items = $order->get_items( 'line_item' );
		$first_item = current( $line_items );
		$item_id    = (int) $first_item->get_id();

		$csv  = "order_number,tracking_number,shipment_provider,items\n{$order->get_id()},ITEM-FRAC,ups,{$item_id}:0.5\n";
		$file = $this->make_csv( $csv );

		$sut     = new FulfillmentsCsvImporter( $file );
		$summary = $this->run_import( $sut );

		$this->assertSame( 1, $summary['failed'] );
		$this->assertSame( 'failed', $summary['rows'][0]['status'] );
	}

	/**
	 * @testdox Blank lines between data rows are silently skipped.
	 */
	public function test_blank_lines_are_skipped(): void {
		$order = $this->make_order();
		$csv   = "order_number,tracking_number,shipment_provider\n\n{$order->get_id()},BLANK-1,ups\n\n";
		$file  = $this->make_csv( $csv );

		$sut     = new FulfillmentsCsvImporter( $file );
		$summary = $this->run_import( $sut );

		$this->assertSame( 1, $summary['created'] );
		$this->assertSame( 0, $summary['failed'] );
	}

	/**
	 * @testdox The summary contains per-row entries with row numbers, status, and message.
	 */
	public function test_summary_structure(): void {
		$order = $this->make_order();
		$csv   = "order_number,tracking_number,shipment_provider\n{$order->get_id()},S-1,ups\n,,\n";
		$file  = $this->make_csv( $csv );

		$sut     = new FulfillmentsCsvImporter( $file );
		$summary = $this->run_import( $sut );

		$this->assertArrayHasKey( 'created', $summary );
		$this->assertArrayHasKey( 'updated', $summary );
		$this->assertArrayHasKey( 'skipped', $summary );
		$this->assertArrayHasKey( 'failed', $summary );
		$this->assertArrayHasKey( 'rows', $summary );
		$this->assertIsArray( $summary['rows'] );

		foreach ( $summary['rows'] as $row ) {
			$this->assertArrayHasKey( 'row', $row );
			$this->assertArrayHasKey( 'status', $row );
			$this->assertArrayHasKey( 'message', $row );
		}
	}

	/**
	 * @testdox A UTF-8 BOM at the start of the file does not break header detection.
	 */
	public function test_utf8_bom_is_stripped(): void {
		$order = $this->make_order();
		$csv   = "\xEF\xBB\xBForder_number,tracking_number,shipment_provider\n{$order->get_id()},BOM-1,ups\n";
		$file  = $this->make_csv( $csv );

		$summary = $this->run_import( new FulfillmentsCsvImporter( $file ) );

		$this->assertSame( 1, $summary['created'] );
		$this->assertSame( 0, $summary['failed'] );
	}

	/**
	 * @testdox A custom delimiter option is honored when parsing rows.
	 */
	public function test_custom_delimiter_is_used(): void {
		$order = $this->make_order();
		$csv   = "order_number;tracking_number;shipment_provider\n{$order->get_id()};DEL-1;ups\n";
		$file  = $this->make_csv( $csv );

		$sut     = new FulfillmentsCsvImporter( $file, array( 'delimiter' => ';' ) );
		$summary = $this->run_import( $sut );

		$this->assertSame( 1, $summary['created'] );
	}

	/**
	 * @testdox The optional tracking_url column is persisted on the fulfillment.
	 */
	public function test_tracking_url_is_stored(): void {
		$order = $this->make_order();
		$csv   = "order_number,tracking_number,shipment_provider,tracking_url\n"
			. "{$order->get_id()},URL-1,ups,https://example.test/track/URL-1\n";
		$file  = $this->make_csv( $csv );

		$this->run_import( new FulfillmentsCsvImporter( $file ) );

		/** @var FulfillmentsDataStore $store */
		$store        = WC_Data_Store::load( 'order-fulfillment' );
		$fulfillments = $store->read_fulfillments( WC_Order::class, (string) $order->get_id() );

		$this->assertCount( 1, $fulfillments );
		$this->assertSame( 'https://example.test/track/URL-1', $fulfillments[0]->get_tracking_url() );
	}

	/**
	 * @testdox An items entry in the form "sku:<sku>:<qty>" resolves against the order line items.
	 */
	public function test_items_sku_resolution(): void {
		$order      = $this->make_order();
		$line_items = $order->get_items( 'line_item' );
		$this->assertNotEmpty( $line_items, 'Test order is expected to have at least one line item.' );

		$first_item = reset( $line_items );
		$product    = $first_item->get_product();
		$sku        = is_object( $product ) ? $product->get_sku() : '';

		if ( '' === $sku ) {
			$this->markTestSkipped( 'Order helper did not produce a line item with a SKU.' );
		}

		$csv  = "order_number,tracking_number,shipment_provider,items\n"
			. "{$order->get_id()},SKU-1,ups,sku:{$sku}:1\n";
		$file = $this->make_csv( $csv );

		$summary = $this->run_import( new FulfillmentsCsvImporter( $file ) );

		$this->assertSame( 1, $summary['created'] );
		$this->assertSame( 0, $summary['failed'] );

		/** @var FulfillmentsDataStore $store */
		$store        = WC_Data_Store::load( 'order-fulfillment' );
		$fulfillments = $store->read_fulfillments( WC_Order::class, (string) $order->get_id() );
		$this->assertCount( 1, $fulfillments );
		$this->assertSame(
			array(
				array(
					'item_id' => (int) $first_item->get_id(),
					'qty'     => 1,
				),
			),
			array_map(
				static fn( $item ) => array(
					'item_id' => (int) $item['item_id'],
					'qty'     => (int) $item['qty'],
				),
				$fulfillments[0]->get_items()
			),
			'The SKU entry must resolve to exactly the matching order item with the requested quantity'
		);
	}

	/**
	 * @testdox Updating an existing fulfillment fires the fulfillment_updated_notification hook.
	 */
	public function test_update_existing_fires_updated_notification(): void {
		$order = $this->make_order();
		$csv   = "order_number,tracking_number,shipment_provider\n{$order->get_id()},UPDN-1,ups\n";
		$this->run_import( new FulfillmentsCsvImporter( $this->make_csv( $csv ), array( 'notify_customer' => true ) ) );

		$updated_hits = 0;
		$created_hits = 0;
		$on_updated   = function () use ( &$updated_hits ) {
			++$updated_hits;
		};
		$on_created   = function () use ( &$created_hits ) {
			++$created_hits;
		};
		add_action( 'woocommerce_fulfillment_updated_notification', $on_updated );
		add_action( 'woocommerce_fulfillment_created_notification', $on_created );

		try {
			$csv2    = "order_number,tracking_number,shipment_provider\n{$order->get_id()},UPDN-1,fedex\n";
			$sut     = new FulfillmentsCsvImporter(
				$this->make_csv( $csv2 ),
				array( 'notify_customer' => true )
			);
			$summary = $this->run_import( $sut );
		} finally {
			remove_action( 'woocommerce_fulfillment_updated_notification', $on_updated );
			remove_action( 'woocommerce_fulfillment_created_notification', $on_created );
		}

		$this->assertSame( 1, $summary['updated'] );
		$this->assertSame( 1, $updated_hits, 'Expected fulfillment_updated_notification to fire exactly once.' );
		$this->assertSame( 0, $created_hits, 'Did not expect fulfillment_created_notification when updating an existing fulfillment.' );
	}

	/**
	 * @testdox Different case spellings of the same tracking number do not create a duplicate fulfillment.
	 */
	public function test_case_insensitive_tracking_match_prevents_duplicate(): void {
		$order = $this->make_order();
		$csv   = "order_number,tracking_number,shipment_provider\n"
			. "{$order->get_id()},TRK-CASE,ups\n"
			. "{$order->get_id()},trk-case,fedex\n";
		$file  = $this->make_csv( $csv );

		$sut     = new FulfillmentsCsvImporter( $file );
		$summary = $this->run_import( $sut );

		/** @var FulfillmentsDataStore $store */
		$store        = WC_Data_Store::load( 'order-fulfillment' );
		$fulfillments = $store->read_fulfillments( WC_Order::class, (string) $order->get_id() );

		$this->assertCount( 1, $fulfillments, 'Mixed-case tracking numbers should match the same fulfillment.' );
		$this->assertSame( 1, $summary['created'] );
		// The second row either updates (default) or is skipped, but must not create.
		$this->assertSame( 0, $summary['failed'] );
	}

	/**
	 * @testdox A stored fulfillment is matched case-insensitively by a later import.
	 */
	public function test_case_insensitive_match_updates_stored_fulfillment(): void {
		$order = $this->make_order();

		$first = $this->run_import(
			new FulfillmentsCsvImporter(
				$this->make_csv( "order_number,tracking_number,shipment_provider\n{$order->get_id()},TRK-STORED,ups\n" )
			)
		);
		$this->assertSame( 1, $first['created'] );

		// A separate import run, so the match must come from the store, not the in-file dedupe.
		$second = $this->run_import(
			new FulfillmentsCsvImporter(
				$this->make_csv( "order_number,tracking_number,shipment_provider\n{$order->get_id()},trk-stored,fedex\n" )
			)
		);

		$this->assertSame( 1, $second['updated'], 'The lowercase spelling must update the stored fulfillment' );
		$this->assertSame( 0, $second['created'] );

		/** @var FulfillmentsDataStore $store */
		$store = WC_Data_Store::load( 'order-fulfillment' );
		$this->assertCount( 1, $store->read_fulfillments( WC_Order::class, (string) $order->get_id() ) );
	}

	/**
	 * @testdox Quoted fields with embedded delimiters and newlines survive chunked reads.
	 */
	public function test_quoted_fields_with_embedded_delimiters_and_newlines(): void {
		$o1 = $this->make_order();
		$o2 = $this->make_order();

		$csv  = "order_number,tracking_number,shipment_provider\n"
			. "{$o1->get_id()},\"QTD,1\",\"ups\nground\"\n"
			. "{$o2->get_id()},QTD-2,\"fedex, express\"\n";
		$file = $this->make_csv( $csv );

		$mapping = array(
			0 => FulfillmentsCsvImporter::COL_ORDER_NUMBER,
			1 => FulfillmentsCsvImporter::COL_TRACKING_NUMBER,
			2 => FulfillmentsCsvImporter::COL_PROVIDER,
		);

		$sut = new FulfillmentsCsvImporter( $file );

		$first = $sut->import_chunk( 0, 1, $mapping );
		$this->assertSame( 1, $first['counts']['created'] );
		$this->assertFalse( $first['eof'] );

		$second = $sut->import_chunk( 1, 1, $mapping, array( 'byte_offset' => $first['byte_offset'] ) );
		$this->assertSame( 1, $second['counts']['created'], 'The byte offset must land on the record boundary after a multi-line quoted cell' );

		/** @var FulfillmentsDataStore $store */
		$store = WC_Data_Store::load( 'order-fulfillment' );

		$first_stored = $store->read_fulfillments( WC_Order::class, (string) $o1->get_id() );
		$this->assertCount( 1, $first_stored );
		$this->assertSame( 'QTD,1', $first_stored[0]->get_tracking_number() );
		$this->assertSame( "ups\nground", $first_stored[0]->get_shipment_provider() );

		$second_stored = $store->read_fulfillments( WC_Order::class, (string) $o2->get_id() );
		$this->assertCount( 1, $second_stored );
		$this->assertSame( 'fedex, express', $second_stored[0]->get_shipment_provider() );
	}

	/**
	 * @testdox A header-only CSV imports zero rows without failing.
	 */
	public function test_header_only_csv_yields_empty_summary(): void {
		$file = $this->make_csv( "order_number,tracking_number,shipment_provider\n" );

		$summary = $this->run_import( new FulfillmentsCsvImporter( $file ) );

		$this->assertSame( 0, $summary['created'] );
		$this->assertSame( 0, $summary['updated'] );
		$this->assertSame( 0, $summary['skipped'] );
		$this->assertSame( 0, $summary['failed'] );
		$this->assertSame( array(), $summary['rows'] );
	}

	/**
	 * @testdox resolve_chunk_size clamps the filtered value into the valid range.
	 */
	public function test_resolve_chunk_size_clamps_filtered_values(): void {
		$forced_value = 0;
		$filter       = function () use ( &$forced_value ) {
			return $forced_value;
		};
		add_filter( 'woocommerce_fulfillments_csv_importer_chunk_size', $filter );

		try {
			$forced_value = 0;
			$this->assertSame( FulfillmentsCsvImporter::DEFAULT_CHUNK_SIZE, FulfillmentsCsvImporter::resolve_chunk_size(), 'Non-positive filter values must fall back to the default' );

			$forced_value = 5000;
			$this->assertSame( FulfillmentsCsvImporter::MAX_CHUNK_SIZE, FulfillmentsCsvImporter::resolve_chunk_size(), 'The filtered value must be capped at the hard ceiling' );

			$forced_value = 50;
			$this->assertSame( 50, FulfillmentsCsvImporter::resolve_chunk_size() );
		} finally {
			remove_filter( 'woocommerce_fulfillments_csv_importer_chunk_size', $filter );
		}
	}

	/**
	 * @testdox parse_headers returns headers, sample row, total and auto-detected mapping.
	 */
	public function test_parse_headers_returns_metadata_and_mapping(): void {
		$csv  = "Order ID,Tracking,Carrier,URL,Items\n"
			. "12345,1Z999AA,UPS,https://example.com,SKU-A:1\n"
			. "67890,1Z000AA,UPS,https://example.com,SKU-B:1\n";
		$file = $this->make_csv( $csv );

		$sut    = new FulfillmentsCsvImporter( $file );
		$parsed = $sut->parse_headers();

		$this->assertArrayNotHasKey( 'error', $parsed );
		$this->assertSame( array( 'Order ID', 'Tracking', 'Carrier', 'URL', 'Items' ), $parsed['headers'] );
		$this->assertSame( array( '12345', '1Z999AA', 'UPS', 'https://example.com', 'SKU-A:1' ), $parsed['sample'] );
		$this->assertSame( 2, $parsed['total'] );
		$this->assertSame( ',', $parsed['delimiter'] );

		$mapping = $parsed['detected_mapping'];
		$this->assertSame( FulfillmentsCsvImporter::COL_ORDER_NUMBER, $mapping[0] );
		$this->assertSame( FulfillmentsCsvImporter::COL_TRACKING_NUMBER, $mapping[1] );
		$this->assertSame( FulfillmentsCsvImporter::COL_PROVIDER, $mapping[2] );
		$this->assertSame( FulfillmentsCsvImporter::COL_TRACKING_URL, $mapping[3] );
		$this->assertSame( FulfillmentsCsvImporter::COL_ITEMS, $mapping[4] );
	}

	/**
	 * @testdox parse_headers honors an explicit non-comma delimiter.
	 */
	public function test_parse_headers_honors_explicit_delimiter(): void {
		$csv  = "order_number;tracking_number;shipment_provider\n1;TRACK-1;ups\n";
		$file = $this->make_csv( $csv );

		$parsed = ( new FulfillmentsCsvImporter( $file, array( 'delimiter' => ';' ) ) )->parse_headers();

		$this->assertArrayNotHasKey( 'error', $parsed );
		$this->assertSame( ';', $parsed['delimiter'] );
		$this->assertSame( array( 'order_number', 'tracking_number', 'shipment_provider' ), $parsed['headers'] );
	}

	/**
	 * @testdox An empty delimiter option falls back to comma.
	 */
	public function test_empty_delimiter_falls_back_to_comma(): void {
		$csv  = "order_number,tracking_number,shipment_provider\n1,TRACK-1,ups\n";
		$file = $this->make_csv( $csv );

		$parsed = ( new FulfillmentsCsvImporter( $file, array( 'delimiter' => '' ) ) )->parse_headers();

		$this->assertArrayNotHasKey( 'error', $parsed );
		$this->assertSame( ',', $parsed['delimiter'] );
	}

	/**
	 * @testdox A multi-character delimiter is clamped to its first byte so fgetcsv() does not throw.
	 */
	public function test_multi_character_delimiter_is_clamped_to_single_byte(): void {
		$csv  = "order_number;tracking_number;shipment_provider\n1;TRACK-1;ups\n";
		$file = $this->make_csv( $csv );

		$parsed = ( new FulfillmentsCsvImporter( $file, array( 'delimiter' => ';;' ) ) )->parse_headers();

		$this->assertArrayNotHasKey( 'error', $parsed );
		$this->assertSame( ';', $parsed['delimiter'] );
	}

	/**
	 * @testdox A multibyte delimiter falls back to comma instead of a truncated byte fragment.
	 */
	public function test_multibyte_delimiter_falls_back_to_comma(): void {
		$this->assertSame( ',', FulfillmentsCsvImporter::normalize_delimiter( '—' ) );
		$this->assertSame( ',', FulfillmentsCsvImporter::normalize_delimiter( '“' ) );
		$this->assertSame( "\t", FulfillmentsCsvImporter::normalize_delimiter( "\t" ), 'ASCII control delimiters like tab must be preserved' );
		$this->assertSame( '|', FulfillmentsCsvImporter::normalize_delimiter( '|' ) );
	}

	/**
	 * @testdox parse_headers reports an error when the file is empty.
	 */
	public function test_parse_headers_reports_empty_csv(): void {
		$file   = $this->make_csv( '' );
		$parsed = ( new FulfillmentsCsvImporter( $file ) )->parse_headers( ',' );

		$this->assertArrayHasKey( 'error', $parsed );
		$this->assertSame( 'empty_csv', $parsed['error']['code'] );
	}

	/**
	 * @testdox parse_headers reports an error when the file is missing.
	 */
	public function test_parse_headers_reports_missing_file(): void {
		$parsed = ( new FulfillmentsCsvImporter( '/nonexistent/path/missing.csv' ) )->parse_headers( ',' );

		$this->assertArrayHasKey( 'error', $parsed );
		$this->assertSame( 'file_not_readable', $parsed['error']['code'] );
	}

	/**
	 * @testdox import_chunk only touches the rows in the requested offset+limit range.
	 */
	public function test_import_chunk_only_processes_requested_range(): void {
		$o1 = $this->make_order();
		$o2 = $this->make_order();
		$o3 = $this->make_order();
		$o4 = $this->make_order();

		$csv  = "order_number,tracking_number,shipment_provider\n"
			. "{$o1->get_id()},T-1,ups\n"
			. "{$o2->get_id()},T-2,ups\n"
			. "{$o3->get_id()},T-3,ups\n"
			. "{$o4->get_id()},T-4,ups\n";
		$file = $this->make_csv( $csv );

		$sut    = new FulfillmentsCsvImporter( $file );
		$result = $sut->import_chunk(
			1,
			2,
			array(
				0 => FulfillmentsCsvImporter::COL_ORDER_NUMBER,
				1 => FulfillmentsCsvImporter::COL_TRACKING_NUMBER,
				2 => FulfillmentsCsvImporter::COL_PROVIDER,
			)
		);

		$this->assertSame( 2, $result['counts']['created'] );
		$this->assertCount( 2, $result['rows'] );

		/** @var FulfillmentsDataStore $store */
		$store = WC_Data_Store::load( 'order-fulfillment' );
		$this->assertCount( 0, $store->read_fulfillments( WC_Order::class, (string) $o1->get_id() ) );
		$this->assertCount( 1, $store->read_fulfillments( WC_Order::class, (string) $o2->get_id() ) );
		$this->assertCount( 1, $store->read_fulfillments( WC_Order::class, (string) $o3->get_id() ) );
		$this->assertCount( 0, $store->read_fulfillments( WC_Order::class, (string) $o4->get_id() ) );
	}

	/**
	 * @testdox import_chunk reports missing required columns when the mapping omits them.
	 */
	public function test_import_chunk_reports_missing_required_columns(): void {
		$csv  = "order_number,tracking_number,shipment_provider\n1,T-1,ups\n";
		$file = $this->make_csv( $csv );

		$result = ( new FulfillmentsCsvImporter( $file ) )->import_chunk(
			0,
			10,
			array(
				0 => FulfillmentsCsvImporter::COL_ORDER_NUMBER,
				1 => FulfillmentsCsvImporter::COL_TRACKING_NUMBER,
			)
		);

		$this->assertSame( 1, $result['counts']['failed'] );
		$this->assertCount( 1, $result['rows'] );
		$this->assertStringContainsString( FulfillmentsCsvImporter::COL_PROVIDER, $result['rows'][0]['message'] );
	}

	/**
	 * @testdox Looping import_chunk with a small limit matches a full single-pass import.
	 */
	public function test_chunked_and_one_shot_produce_equivalent_counts(): void {
		$orders = array();
		$csv    = "order_number,tracking_number,shipment_provider\n";
		for ( $i = 0; $i < 10; $i++ ) {
			$order    = $this->make_order();
			$orders[] = $order;
			$csv     .= "{$order->get_id()},TRK-{$i},ups\n";
		}
		// Add a row with an unknown order to exercise the failed bucket too.
		$csv .= "99999999,TRK-MISS,ups\n";

		$one_shot_file = $this->make_csv( $csv );
		$one_shot      = $this->run_import( new FulfillmentsCsvImporter( $one_shot_file ) );

		// Reset for the chunked variant by creating fresh orders + a fresh file.
		$orders = array();
		$csv    = "order_number,tracking_number,shipment_provider\n";
		for ( $i = 0; $i < 10; $i++ ) {
			$order    = $this->make_order();
			$orders[] = $order;
			$csv     .= "{$order->get_id()},TRKB-{$i},ups\n";
		}
		$csv .= "99999998,TRKB-MISS,ups\n";

		$chunk_file = $this->make_csv( $csv );
		$sut        = new FulfillmentsCsvImporter( $chunk_file );
		$parsed     = $sut->parse_headers();
		$mapping    = $parsed['detected_mapping'];

		$counts = array(
			'created'  => 0,
			'updated'  => 0,
			'skipped'  => 0,
			'failed'   => 0,
			'notified' => 0,
		);
		$rows   = array();
		$seen   = array();
		$total  = $parsed['total'];
		$limit  = 3;

		for ( $offset = 0; $offset < $total; $offset += $limit ) {
			$result = $sut->import_chunk( $offset, $limit, $mapping, array( 'seen_tracking_pairs' => $seen ) );
			foreach ( array_keys( $counts ) as $key ) {
				$counts[ $key ] += (int) $result['counts'][ $key ];
			}
			$rows = array_merge( $rows, $result['rows'] );
			$seen = $result['seen_tracking_pairs'];
		}

		$this->assertSame( $one_shot['created'], $counts['created'] );
		$this->assertSame( $one_shot['updated'], $counts['updated'] );
		$this->assertSame( $one_shot['skipped'], $counts['skipped'] );
		$this->assertSame( $one_shot['failed'], $counts['failed'] );
		$this->assertCount( count( $one_shot['rows'] ), $rows );
		$this->assertSame(
			array_column( $one_shot['rows'], 'status' ),
			array_column( $rows, 'status' ),
			'Chunked processing must visit rows in the same order with the same outcome'
		);
	}

	/**
	 * @testdox Rows with a scheme-less tracking URL fail instead of storing the value.
	 *
	 * @testWith ["//example.test/track"]
	 *           ["/wp-admin/index.php"]
	 *
	 * @param string $tracking_url Tracking URL cell under test.
	 */
	public function test_tracking_url_without_http_scheme_fails_row( string $tracking_url ): void {
		$order = $this->make_order();
		$csv   = "order_number,tracking_number,shipment_provider,tracking_url\n"
			. "{$order->get_id()},TRK-NOSCHEME,ups,{$tracking_url}\n";

		$summary = $this->run_import( new FulfillmentsCsvImporter( $this->make_csv( $csv ) ) );

		$this->assertSame( 0, $summary['created'] );
		$this->assertSame( 1, $summary['failed'] );
		$this->assertSame( 'invalid_tracking_url', $summary['rows'][0]['code'] );
	}

	/**
	 * @testdox Rows with a non-http tracking URL fail instead of storing the value.
	 */
	public function test_tracking_url_with_disallowed_scheme_fails_row(): void {
		$order = $this->make_order();
		$csv   = "order_number,tracking_number,shipment_provider,tracking_url\n"
			. "{$order->get_id()},TRK-XSS,ups,javascript:alert(1)\n";
		$file  = $this->make_csv( $csv );

		$sut     = new FulfillmentsCsvImporter( $file );
		$summary = $this->run_import( $sut );

		$this->assertSame( 0, $summary['created'] );
		$this->assertSame( 1, $summary['failed'] );
		$this->assertSame( 'invalid_tracking_url', $summary['rows'][0]['code'] );

		/** @var FulfillmentsDataStore $store */
		$store = WC_Data_Store::load( 'order-fulfillment' );
		$this->assertCount( 0, $store->read_fulfillments( WC_Order::class, (string) $order->get_id() ) );
	}

	/**
	 * @testdox import_chunk reports eof only when the file is exhausted.
	 */
	public function test_import_chunk_signals_eof_only_at_end_of_file(): void {
		$o1 = $this->make_order();
		$o2 = $this->make_order();
		$o3 = $this->make_order();

		$csv  = "order_number,tracking_number,shipment_provider\n"
			. "{$o1->get_id()},E-1,ups\n"
			. "{$o2->get_id()},E-2,ups\n"
			. "{$o3->get_id()},E-3,ups\n";
		$file = $this->make_csv( $csv );

		$mapping = array(
			0 => FulfillmentsCsvImporter::COL_ORDER_NUMBER,
			1 => FulfillmentsCsvImporter::COL_TRACKING_NUMBER,
			2 => FulfillmentsCsvImporter::COL_PROVIDER,
		);

		$sut = new FulfillmentsCsvImporter( $file );

		$first = $sut->import_chunk( 0, 2, $mapping );
		$this->assertFalse( $first['eof'], 'A chunk that fills its limit must not report eof' );
		$this->assertFalse( $first['aborted'] );
		$this->assertSame( 2, $first['consumed'] );

		$second = $sut->import_chunk( 2, 2, $mapping, array( 'byte_offset' => $first['byte_offset'] ) );
		$this->assertTrue( $second['eof'], 'The chunk that drains the file must report eof' );
		$this->assertFalse( $second['aborted'] );
		$this->assertSame( 1, $second['consumed'] );
	}

	/**
	 * @testdox import_chunk aborts instead of reporting eof when the file cannot be read.
	 */
	public function test_import_chunk_aborts_when_file_missing(): void {
		$sut = new FulfillmentsCsvImporter( '/path/that/does/not/exist.csv' );

		$result = $sut->import_chunk(
			0,
			10,
			array(
				0 => FulfillmentsCsvImporter::COL_ORDER_NUMBER,
				1 => FulfillmentsCsvImporter::COL_TRACKING_NUMBER,
				2 => FulfillmentsCsvImporter::COL_PROVIDER,
			)
		);

		$this->assertTrue( $result['aborted'], 'An unreadable file must abort the chunk' );
		$this->assertFalse( $result['eof'] );
		$this->assertSame( 0, $result['consumed'] );
		$this->assertSame( 1, $result['counts']['failed'] );
		$this->assertSame( 'file_not_readable', $result['rows'][0]['code'] );
	}
}
