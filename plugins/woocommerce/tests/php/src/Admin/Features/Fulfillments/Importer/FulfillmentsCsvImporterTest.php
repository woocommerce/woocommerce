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
	 * A valid CSV row creates a fulfilled fulfillment.
	 */
	public function test_valid_row_creates_fulfillment(): void {
		$order = $this->make_order();
		$csv   = "order_number,tracking_number,shipment_provider\n{$order->get_id()},TRACK-1,ups\n";
		$file  = $this->make_csv( $csv );

		$summary = ( new FulfillmentsCsvImporter( $file ) )->run();

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
	 * Unknown order numbers produce a failed row without aborting the import.
	 */
	public function test_unknown_order_number_fails_row(): void {
		$order = $this->make_order();
		$csv   = "order_number,tracking_number,shipment_provider\n99999999,TRACK-X,ups\n{$order->get_id()},TRACK-Y,fedex\n";
		$file  = $this->make_csv( $csv );

		$summary = ( new FulfillmentsCsvImporter( $file ) )->run();

		$this->assertSame( 1, $summary['failed'] );
		$this->assertSame( 1, $summary['created'] );
		$this->assertSame( 'failed', $summary['rows'][0]['status'] );
		$this->assertSame( 'created', $summary['rows'][1]['status'] );
	}

	/**
	 * Missing required columns fails the entire import early.
	 */
	public function test_missing_required_columns_fails_early(): void {
		$csv  = "order_number,tracking_number\n1,TRACK-1\n";
		$file = $this->make_csv( $csv );

		$summary = ( new FulfillmentsCsvImporter( $file ) )->run();

		$this->assertSame( 1, $summary['failed'] );
		$this->assertSame( 0, $summary['created'] );
		$this->assertArrayHasKey( 'message', $summary['rows'][0] );
		$this->assertStringContainsString( 'shipment_provider', $summary['rows'][0]['message'] );
	}

	/**
	 * An empty CSV is reported as a failure.
	 */
	public function test_empty_csv_is_reported(): void {
		$file = $this->make_csv( '' );

		$summary = ( new FulfillmentsCsvImporter( $file ) )->run();

		$this->assertSame( 1, $summary['failed'] );
		$this->assertSame( 0, $summary['created'] );
	}

	/**
	 * Missing tracking number fails the row.
	 */
	public function test_missing_tracking_number_fails_row(): void {
		$order = $this->make_order();
		$csv   = "order_number,tracking_number,shipment_provider\n{$order->get_id()},,ups\n";
		$file  = $this->make_csv( $csv );

		$summary = ( new FulfillmentsCsvImporter( $file ) )->run();

		$this->assertSame( 1, $summary['failed'] );
		$this->assertSame( 'failed', $summary['rows'][0]['status'] );
		$this->assertStringContainsString( 'tracking', strtolower( $summary['rows'][0]['message'] ) );
	}

	/**
	 * A second row with the same order/tracking pair is skipped (in-file dedupe).
	 */
	public function test_duplicate_row_in_file_is_skipped(): void {
		$order = $this->make_order();
		$csv   = "order_number,tracking_number,shipment_provider\n{$order->get_id()},DUP-1,ups\n{$order->get_id()},DUP-1,ups\n";
		$file  = $this->make_csv( $csv );

		$summary = ( new FulfillmentsCsvImporter( $file ) )->run();

		$this->assertSame( 1, $summary['created'] );
		$this->assertSame( 1, $summary['skipped'] );
	}

	/**
	 * An existing fulfillment with the same tracking number is updated when update_existing is true.
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

		$summary = ( new FulfillmentsCsvImporter( $file2 ) )->run();

		$this->assertSame( 0, $summary['created'] );
		$this->assertSame( 1, $summary['updated'] );

		/** @var FulfillmentsDataStore $store */
		$store        = WC_Data_Store::load( 'order-fulfillment' );
		$fulfillments = $store->read_fulfillments( WC_Order::class, (string) $order->get_id() );

		$this->assertCount( 1, $fulfillments );
		$this->assertSame( 'fedex', $fulfillments[0]->get_shipment_provider() );
	}

	/**
	 * When update_existing is false, an existing tracking number is skipped instead of updated.
	 */
	public function test_existing_fulfillment_is_skipped_when_update_disabled(): void {
		$order = $this->make_order();
		$csv   = "order_number,tracking_number,shipment_provider\n{$order->get_id()},TRACK-S,ups\n";
		( new FulfillmentsCsvImporter( $this->make_csv( $csv ) ) )->run();

		$csv2    = "order_number,tracking_number,shipment_provider\n{$order->get_id()},TRACK-S,fedex\n";
		$summary = ( new FulfillmentsCsvImporter(
			$this->make_csv( $csv2 ),
			array( 'update_existing' => false )
		) )->run();

		$this->assertSame( 0, $summary['updated'] );
		$this->assertSame( 1, $summary['skipped'] );
	}

	/**
	 * The notify_customer option triggers the documented WooCommerce hook on creation.
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
			$summary = ( new FulfillmentsCsvImporter( $file, array( 'notify_customer' => true ) ) )->run();
		} finally {
			remove_action( 'woocommerce_fulfillment_created_notification', $listener );
		}

		$this->assertSame( 1, $hook_count );
		$this->assertSame( 1, $summary['notified'] );
	}

	/**
	 * Without notify_customer, the customer-facing hook does not fire.
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
			$summary = ( new FulfillmentsCsvImporter( $file ) )->run();
		} finally {
			remove_action( 'woocommerce_fulfillment_created_notification', $listener );
		}

		$this->assertSame( 0, $hook_count );
		$this->assertSame( 0, $summary['notified'] );
		$this->assertSame( 1, $summary['created'] );
	}

	/**
	 * Header aliases (e.g. "Tracking" / "Carrier") are normalized to the canonical columns.
	 */
	public function test_header_aliases_are_accepted(): void {
		$order = $this->make_order();
		$csv   = "Order,Tracking,Carrier\n{$order->get_id()},ALIAS-1,ups\n";
		$file  = $this->make_csv( $csv );

		$summary = ( new FulfillmentsCsvImporter( $file ) )->run();

		$this->assertSame( 1, $summary['created'] );
		$this->assertSame( 0, $summary['failed'] );
	}

	/**
	 * When no items column is provided, all order line items are included at full ordered qty.
	 */
	public function test_items_default_to_full_order(): void {
		$order = $this->make_order();
		$csv   = "order_number,tracking_number,shipment_provider\n{$order->get_id()},DEFAULT-ITEMS,ups\n";
		$file  = $this->make_csv( $csv );

		( new FulfillmentsCsvImporter( $file ) )->run();

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
	 * An items entry referencing an order item ID is honored, with qty respected.
	 */
	public function test_items_column_with_specific_item_id(): void {
		$order      = $this->make_order();
		$line_items = $order->get_items( 'line_item' );
		$this->assertNotEmpty( $line_items );
		$first_item = current( $line_items );
		$item_id    = (int) $first_item->get_id();

		$csv  = "order_number,tracking_number,shipment_provider,items\n{$order->get_id()},ITEM-1,ups,{$item_id}:1\n";
		$file = $this->make_csv( $csv );

		$summary = ( new FulfillmentsCsvImporter( $file ) )->run();

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
	 * Quantity larger than what the order has fails the row.
	 */
	public function test_items_quantity_above_ordered_fails(): void {
		$order      = $this->make_order();
		$line_items = $order->get_items( 'line_item' );
		$first_item = current( $line_items );
		$item_id    = (int) $first_item->get_id();
		$too_many   = (int) $first_item->get_quantity() + 100;

		$csv  = "order_number,tracking_number,shipment_provider,items\n{$order->get_id()},ITEM-OVER,ups,{$item_id}:{$too_many}\n";
		$file = $this->make_csv( $csv );

		$summary = ( new FulfillmentsCsvImporter( $file ) )->run();
		$this->assertSame( 1, $summary['failed'] );
		$this->assertSame( 'failed', $summary['rows'][0]['status'] );
	}

	/**
	 * Item IDs that don't belong to the order fail the row.
	 */
	public function test_items_unknown_item_id_fails(): void {
		$order = $this->make_order();
		$csv   = "order_number,tracking_number,shipment_provider,items\n{$order->get_id()},ITEM-UNKNOWN,ups,9999999:1\n";
		$file  = $this->make_csv( $csv );

		$summary = ( new FulfillmentsCsvImporter( $file ) )->run();
		$this->assertSame( 1, $summary['failed'] );
	}

	/**
	 * Blank lines between data rows are silently skipped.
	 */
	public function test_blank_lines_are_skipped(): void {
		$order = $this->make_order();
		$csv   = "order_number,tracking_number,shipment_provider\n\n{$order->get_id()},BLANK-1,ups\n\n";
		$file  = $this->make_csv( $csv );

		$summary = ( new FulfillmentsCsvImporter( $file ) )->run();

		$this->assertSame( 1, $summary['created'] );
		$this->assertSame( 0, $summary['failed'] );
	}

	/**
	 * The summary contains per-row entries with row numbers, status, and message.
	 */
	public function test_summary_structure(): void {
		$order = $this->make_order();
		$csv   = "order_number,tracking_number,shipment_provider\n{$order->get_id()},S-1,ups\n,,\n";
		$file  = $this->make_csv( $csv );

		$summary = ( new FulfillmentsCsvImporter( $file ) )->run();

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
}
