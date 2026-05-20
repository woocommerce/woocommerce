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
	 * @since 10.9.0
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
	 * @since 10.9.0
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
				unlink( $path );
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
		file_put_contents( $path, $content );
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
	 * @testdox A valid CSV row creates a fulfilled fulfillment.
	 */
	public function test_valid_row_creates_fulfillment(): void {
		$order = $this->make_order();
		$csv   = "order_number,tracking_number,shipment_provider\n{$order->get_id()},TRACK-1,ups\n";
		$file  = $this->make_csv( $csv );

		$sut     = new FulfillmentsCsvImporter( $file );
		$summary = $sut->run();

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
		$summary = $sut->run();

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
		$summary = $sut->run();

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
		$summary = $sut->run();

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
		$summary = $sut->run();

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
		$summary = $sut->run();

		$this->assertSame( 1, $summary['created'] );
		$this->assertSame( 1, $summary['skipped'] );
	}

	/**
	 * @testdox An existing fulfillment with the same tracking number is updated when update_existing is true.
	 */
	public function test_existing_fulfillment_with_same_tracking_is_updated(): void {
		$order = $this->make_order();
		$csv   = "order_number,tracking_number,shipment_provider\n{$order->get_id()},TRACK-UP,ups\n";
		$file  = $this->make_csv( $csv );

		// First import — creates the fulfillment.
		( new FulfillmentsCsvImporter( $file ) )->run();

		// Second import with the same tracking number — should update the existing record's provider.
		$csv2  = "order_number,tracking_number,shipment_provider\n{$order->get_id()},TRACK-UP,fedex\n";
		$file2 = $this->make_csv( $csv2 );

		$sut     = new FulfillmentsCsvImporter( $file2 );
		$summary = $sut->run();

		$this->assertSame( 0, $summary['created'] );
		$this->assertSame( 1, $summary['updated'] );

		/** @var FulfillmentsDataStore $store */
		$store        = WC_Data_Store::load( 'order-fulfillment' );
		$fulfillments = $store->read_fulfillments( WC_Order::class, (string) $order->get_id() );

		$this->assertCount( 1, $fulfillments );
		$this->assertSame( 'fedex', $fulfillments[0]->get_shipment_provider() );
	}

	/**
	 * @testdox When update_existing is false, an existing tracking number is skipped instead of updated.
	 */
	public function test_existing_fulfillment_is_skipped_when_update_disabled(): void {
		$order = $this->make_order();
		$csv   = "order_number,tracking_number,shipment_provider\n{$order->get_id()},TRACK-S,ups\n";
		( new FulfillmentsCsvImporter( $this->make_csv( $csv ) ) )->run();

		$csv2 = "order_number,tracking_number,shipment_provider\n{$order->get_id()},TRACK-S,fedex\n";
		$sut  = new FulfillmentsCsvImporter(
			$this->make_csv( $csv2 ),
			array( 'update_existing' => false )
		);

		$summary = $sut->run();

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
			$summary = $sut->run();
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
			$summary = $sut->run();
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
		$summary = $sut->run();

		$this->assertSame( 1, $summary['created'] );
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
		$sut->run();

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
		$summary = $sut->run();

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
		$summary = $sut->run();
		$this->assertSame( 1, $summary['failed'] );
		$this->assertSame( 'failed', $summary['rows'][0]['status'] );
	}

	/**
	 * @testdox Item IDs that don't belong to the order fail the row.
	 */
	public function test_items_unknown_item_id_fails(): void {
		$order = $this->make_order();
		$csv   = "order_number,tracking_number,shipment_provider,items\n{$order->get_id()},ITEM-UNKNOWN,ups,9999999:1\n";
		$file  = $this->make_csv( $csv );

		$sut     = new FulfillmentsCsvImporter( $file );
		$summary = $sut->run();
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
		$summary = $sut->run();

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
		$summary = $sut->run();

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
		$summary = $sut->run();

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

		$summary = ( new FulfillmentsCsvImporter( $file ) )->run();

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
		$summary = $sut->run();

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

		( new FulfillmentsCsvImporter( $file ) )->run();

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

		$summary = ( new FulfillmentsCsvImporter( $file ) )->run();

		$this->assertSame( 1, $summary['created'] );
		$this->assertSame( 0, $summary['failed'] );
	}

	/**
	 * @testdox Updating an existing fulfillment fires the fulfillment_updated_notification hook.
	 */
	public function test_update_existing_fires_updated_notification(): void {
		$order = $this->make_order();
		$csv   = "order_number,tracking_number,shipment_provider\n{$order->get_id()},UPDN-1,ups\n";
		( new FulfillmentsCsvImporter( $this->make_csv( $csv ), array( 'notify_customer' => true ) ) )->run();

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
			$csv2 = "order_number,tracking_number,shipment_provider\n{$order->get_id()},UPDN-1,fedex\n";
			$sut  = new FulfillmentsCsvImporter(
				$this->make_csv( $csv2 ),
				array( 'notify_customer' => true )
			);
			$summary = $sut->run();
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
		$summary = $sut->run();

		/** @var FulfillmentsDataStore $store */
		$store        = WC_Data_Store::load( 'order-fulfillment' );
		$fulfillments = $store->read_fulfillments( WC_Order::class, (string) $order->get_id() );

		$this->assertCount( 1, $fulfillments, 'Mixed-case tracking numbers should match the same fulfillment.' );
		$this->assertSame( 1, $summary['created'] );
		// The second row either updates (default) or is skipped, but must not create.
		$this->assertSame( 0, $summary['failed'] );
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

		$csv = "order_number,tracking_number,shipment_provider\n"
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
	 * @testdox Looping import_chunk yields the same counts as one-shot run() on the same fixture.
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
		$one_shot      = ( new FulfillmentsCsvImporter( $one_shot_file ) )->run();

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
	}

}
