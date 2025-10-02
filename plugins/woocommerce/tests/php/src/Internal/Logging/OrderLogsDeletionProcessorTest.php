<?php

declare( strict_types=1 );

namespace Automattic\WooCommerce\Tests\Internal\Logging;

use Automattic\WooCommerce\Internal\DataStores\Orders\DataSynchronizer;
use Automattic\WooCommerce\Internal\Logging\OrderLogsDeletionProcessor;
use Automattic\WooCommerce\Proxies\LegacyProxy;
use Automattic\WooCommerce\RestApi\UnitTests\Helpers\OrderHelper;
use Automattic\WooCommerce\RestApi\UnitTests\HPOSToggleTrait;
use Automattic\WooCommerce\Testing\Tools\TestingContainer;

/**
 * Tests for the OrderLogsDeletionProcessor class.
 */
class OrderLogsDeletionProcessorTest extends \WC_Unit_Test_Case {
	use HPOSToggleTrait;

	/**
	 * Fake logger class.
	 *
	 * @var object
	 */
	private static $fake_logger;

	/**
	 * The instance of the LegacyProxy object to use.
	 *
	 * @var LegacyProxy
	 */
	private $legacy_proxy;

	/**
	 * The System Under Test.
	 *
	 * @var OrderLogsDeletionProcessor
	 */
	private OrderLogsDeletionProcessor $sut;

	/**
	 * The DI container to use.
	 *
	 * @var TestingContainer
	 */
	private TestingContainer $container;

	/**
	 * Temporary storage of the target for the data store class filter.
	 *
	 * @var callable
	 */
	private $data_store_filter_callback = null;

	// phpcs:disable WordPress.DB.SlowDBQuery

	/**
	 * Runs before all the tests in the class.
	 */
	public static function setUpBeforeClass(): void {
		parent::setUpBeforeClass();

		// phpcs:disable Squiz.Commenting
		$fake_logger = new class() {
			public array $sources_cleared = array();

			public function clear( $source = '', $quiet = false ) {
				$this->sources_cleared[] = $source;
			}

			public function reset() {
				$this->sources_cleared = array();
			}
		};
		// phpcs:enable Squiz.Commenting

		self::$fake_logger = $fake_logger;
	}

	/**
	 * Runs before each test.
	 */
	public function setUp(): void {
		parent::setUp();

		$this->container = wc_get_container();
		$this->container->reset_all_resolved();

		global $wpdb;
		$wpdb->delete( $wpdb->prefix . 'wc_orders_meta', array( 'meta_key' => '_debug_log_source_pending_deletion' ) );
		$wpdb->delete( $wpdb->postmeta, array( 'meta_key' => '_debug_log_source_pending_deletion' ) );

		$this->sut = $this->container->get( OrderLogsDeletionProcessor::class );

		$this->register_legacy_proxy_function_mocks(
			array(
				'wc_get_logger' => function () {
					return self::$fake_logger;
				},
			)
		);
		self::$fake_logger->reset();
	}

	/**
	 * Runs after each test.
	 */
	public function tearDown(): void {
		parent::tearDown();
		if ( $this->data_store_filter_callback ) {
				remove_filter( 'woocommerce_order_data_store', $this->data_store_filter_callback, 99999 );
				$this->data_store_filter_callback = null;
		}
	}

	/**
	 * @testdox get_total_pending_count returns the correct total count of items pending to process.
	 *
	 * @testWith  [true]
	 *            [false]
	 *
	 * @param bool $with_hpos Test with HPOS active or not.
	 */
	public function test_get_total_pending_count( bool $with_hpos ) {
		$this->setup_hpos_and_reset_container( $with_hpos );

		$this->create_orders_with_logs( 3 );
		$this->assertEquals( 3, $this->sut->get_total_pending_count() );
	}

	/**
	 * @testdox test_get_next_batch_to_process returns the correct set of items pending to process.
	 *
	 * @testWith  [true]
	 *            [false]
	 *
	 * @param bool $with_hpos Test with HPOS active or not.
	 */
	public function test_get_next_batch_to_process( bool $with_hpos ) {
		$this->setup_hpos_and_reset_container( $with_hpos );

		$order_ids = $this->create_orders_with_logs( 5 );

		$actual_meta_ids = $this->get_meta_ids( $with_hpos, 3 );
		$expected_batch  = array(
			array(
				'order_id'   => $order_ids[0],
				'meta_id'    => $actual_meta_ids[0],
				'meta_value' => 'place-order-debug-0',
			),
			array(
				'order_id'   => $order_ids[1],
				'meta_id'    => $actual_meta_ids[1],
				'meta_value' => 'place-order-debug-1',
			),
			array(
				'order_id'   => $order_ids[2],
				'meta_id'    => $actual_meta_ids[2],
				'meta_value' => 'place-order-debug-2',
			),
		);

		$actual_batch = $this->sut->get_next_batch_to_process( 3 );
		$this->assertEquals( $expected_batch, $actual_batch );
	}

	/**
	 * @testdox process_batch correctly processes the supplied batch of items.
	 *
	 * @testWith  [true]
	 *            [false]
	 *
	 * @param bool $with_hpos Test with HPOS active or not.
	 */
	public function test_process_batch( bool $with_hpos ) {
		$this->setup_hpos_and_reset_container( $with_hpos );

		$order_ids = $this->create_orders_with_logs( 5 );

		$batch = $this->sut->get_next_batch_to_process( 3 );
		$this->sut->process_batch( $batch );

		$this->assertEquals( 2, $this->sut->get_total_pending_count() );

		$actual_meta_ids = $this->get_meta_ids( $with_hpos, 3 );
		$expected_batch  = array(
			array(
				'order_id'   => $order_ids[3],
				'meta_id'    => $actual_meta_ids[0],
				'meta_value' => 'place-order-debug-3',
			),
			array(
				'order_id'   => $order_ids[4],
				'meta_id'    => $actual_meta_ids[1],
				'meta_value' => 'place-order-debug-4',
			),
		);

		$actual_batch = $this->sut->get_next_batch_to_process( 3 );
		$this->assertEquals( $expected_batch, $actual_batch );
	}

	/**
	 * process_batch correctly processes the supplied batch of items when HPOS data sync is enabled.
	 *
	 * @testWith [true]
	 *           [false]
	 *
	 * @param bool $with_hpos Test with HPOS active or not.
	 */
	public function test_process_batch_with_sync_enabled( bool $with_hpos ) {
		// phpcs:disable WordPress.DB.PreparedSQL.InterpolatedNotPrepared

		global $wpdb;

		$this->setup_hpos_and_reset_container( true );
		$order_ids = $this->create_orders_with_logs( 5 );

		// Force a manual sync and verify that the meta entries
		// have been replicated in the backup table.

		$data_synchronizer = wc_get_container()->get( DataSynchronizer::class );
		$batch             = $data_synchronizer->get_next_batch_to_process( 5 );
		$data_synchronizer->process_batch( $batch );

		$order_ids_string = implode( ',', $batch );

		$table_name    = $with_hpos ? $wpdb->postmeta : "{$wpdb->prefix}wc_orders_meta";
		$id_field_name = $with_hpos ? 'post_id' : 'order_id';
		$count         =
			$wpdb->get_var(
				$wpdb->prepare(
					"select count(*) from {$table_name} where {$id_field_name} in ({$order_ids_string}) and meta_key=%s",
					'_debug_log_source_pending_deletion'
				)
			);
		$this->assertEquals( 5, $count );

		// Process a batch of logs pending deletion and verify that:
		// 1. The processed meta entries have been removed from the backup table.
		// 2. The meta entries not yet processed are still present in the backup table.

		$previous_data_sync_option = get_option( DataSynchronizer::ORDERS_DATA_SYNC_ENABLED_OPTION );
		update_option( DataSynchronizer::ORDERS_DATA_SYNC_ENABLED_OPTION, 'yes' );

		$batch = $this->sut->get_next_batch_to_process( 3 );
		$this->sut->process_batch( $batch );

		$batch                     = $this->sut->get_next_batch_to_process( 3 );
		$actual_order_ids_in_batch = array_map( fn( $item ) => $item['order_id'], $batch );
		$expected_order_ids        = array( $order_ids[3], $order_ids[4] );
		$this->assertEquals( $expected_order_ids, $actual_order_ids_in_batch );

		$order_ids_in_backup_table =
			$wpdb->get_results(
				$wpdb->prepare(
					"select {$id_field_name} from {$table_name} where {$id_field_name} in ({$order_ids_string}) and meta_key=%s",
					'_debug_log_source_pending_deletion'
				),
				ARRAY_N
			);
		$order_ids_in_backup_table = array_map( fn( $item ) => absint( $item[0] ), $order_ids_in_backup_table );
		$this->assertEquals( $expected_order_ids, $order_ids_in_backup_table );

		if ( false === $previous_data_sync_option ) {
			delete_option( DataSynchronizer::ORDERS_DATA_SYNC_ENABLED_OPTION );
		} else {
			update_option( DataSynchronizer::ORDERS_DATA_SYNC_ENABLED_OPTION, $previous_data_sync_option );
		}

		// phpcs:enable WordPress.DB.PreparedSQL.InterpolatedNotPrepared
	}

	/**
	 * @testdox process_batch throws an exception if an invalid batch is supplied.
	 *
	 * @testWith [[null]]
	 *           [[34]]
	 *           [[{"meta_id": 34}]]
	 *           [[{"meta_value": "MSX"}]]
	 *           [[{"order_id": 34}]]
	 *
	 * @param mixed $batch Batch to try to process.
	 */
	public function test_process_invalid_batch( $batch ) {
		$this->expectExceptionMessage( "\$batch must be an array of arrays, each having a 'meta_id' key, a 'meta_value' key and an 'order_id' key" );
		$this->sut->process_batch( $batch );
	}

	/**
	 * @testdox Public methods throw "doing it wrong" when an unknown orders data store is in use.
	 *
	 * @testWith ["get_next_batch_to_process", 5]
	 *           ["process_batch", {"foo": "bar"}]
	 *           ["get_total_pending_count", null]
	 *
	 * @param string $method_name Method to run.
	 * @param mixed  $argument Argument to pass to the method, null to pass none.
	 */
	public function test_unknown_data_source( string $method_name, $argument ) {
		$data_store                       = new class() extends \WC_Order_Data_Store_CPT {};
		$this->data_store_filter_callback = function () use ( $data_store ) {
			return $data_store;
		};
		add_filter( 'woocommerce_order_data_store', $this->data_store_filter_callback, 99999, 0 );

		$this->setup_hpos_and_reset_container( false );

		$actual_function_name = null;
		$actual_message       = null;

		// phpcs:disable Generic.CodeAnalysis.UnusedFunctionParameter.FoundAfterLastUsed
		$this->register_legacy_proxy_function_mocks(
			array(
				'wc_doing_it_wrong' => function ( $function_name, $message, $version ) use ( &$actual_function_name, &$actual_message ) {
					$actual_function_name = $function_name;
					$actual_message = $message;
				},
			)
		);
		// phpcs:enable Generic.CodeAnalysis.UnusedFunctionParameter.FoundAfterLastUsed

		if ( is_null( $argument ) ) {
			$this->sut->$method_name();
		} else {
			$this->sut->$method_name( $argument );
		}

		$this->assertEquals( 'OrderLogsDeletionProcessor::' . $method_name, $actual_function_name );
		$this->assertEquals( "This processor shouldn't be enqueued when the orders data store in use is neither the HPOS one nor the CPT one. Just delete the order debug logs directly.", $actual_message );
	}

	// phpcs:enable WordPress.DB.SlowDBQuery

	/**
	 * Create a set of orders with debug logs.
	 *
	 * @param int $count How many orders to create.
	 * @return array Ids of the created orders.
	 */
	private function create_orders_with_logs( int $count ): array {
		$order_ids = array();

		for ( $i = 0; $i < $count; $i++ ) {
			$order = OrderHelper::create_order();
			$order->add_meta_data( '_debug_log_source_pending_deletion', 'place-order-debug-' . $i );
			$order->save();
			$order_ids[] = $order->get_id();
		}

		return $order_ids;
	}

	/**
	 * Get the ids of order debug meta entries, sorted by meta id.
	 *
	 * @param bool $with_hpos Test with HPOS active or not.
	 * @param int  $limit Maximum count of ids to return.
	 * @return array
	 */
	private function get_meta_ids( bool $with_hpos, int $limit ): array {
		global $wpdb;

		// phpcs:disable WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		$meta_ids =
			$with_hpos ?
				$wpdb->get_results( $wpdb->prepare( "select id from {$wpdb->prefix}wc_orders_meta where meta_key=%s order by order_id limit {$limit}", '_debug_log_source_pending_deletion' ), ARRAY_N ) :
				$wpdb->get_results( $wpdb->prepare( "select meta_id from {$wpdb->postmeta} where meta_key=%s order by post_id limit {$limit}", '_debug_log_source_pending_deletion' ), ARRAY_N );
		// phpcs:enable WordPress.DB.PreparedSQL.InterpolatedNotPrepared

		return array_map( fn( $item ) => $item[0], $meta_ids );
	}

	/**
	 * Initialize HPOS and reset the DI container resolutions
	 * (resetting the container is needed because the tested class checks for HPOS activation
	 * only once when the DI container first retrieves it).
	 *
	 * @param bool $enable_hpos Test with HPOS active or not.
	 */
	private function setup_hpos_and_reset_container( bool $enable_hpos ) {
		$this->toggle_cot_feature_and_usage( $enable_hpos );
		$this->container->reset_all_resolved();
		$this->sut = $this->container->get( OrderLogsDeletionProcessor::class );
	}
}
