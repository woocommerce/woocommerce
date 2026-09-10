<?php
declare( strict_types = 1 );

namespace Automattic\WooCommerce\Tests\Internal\DataStores\Orders;

use Automattic\WooCommerce\Caching\WPCacheEngine;
use Automattic\WooCommerce\Internal\DataStores\Orders\CustomOrdersTableController;
use Automattic\WooCommerce\Internal\DataStores\Orders\OrdersTableDataStore;
use Automattic\WooCommerce\Internal\DataStores\Orders\OrdersTableRefundDataStore;
use Automattic\WooCommerce\RestApi\UnitTests\HPOSToggleTrait;
use Automattic\WooCommerce\Utilities\OrderUtil;
use WC_Order;

/**
 * Tests for HPOS data cache cross-bleed prevention between data store subclasses.
 */
class OrdersTableDataStoreCacheCrossBleedTest extends \HposTestCase {
	use HPOSToggleTrait;

	/**
	 * The System Under Test.
	 *
	 * @var OrdersTableDataStore
	 */
	private $sut;

	/**
	 * The refund data store.
	 *
	 * @var OrdersTableRefundDataStore
	 */
	private $refund_sut;

	/**
	 * Whether COT was enabled before the test.
	 *
	 * @var bool
	 */
	private $cot_state;

	/**
	 * Set up test fixtures.
	 */
	public function setUp(): void {
		parent::setUp();

		add_filter( 'wc_allow_changing_orders_storage_while_sync_is_pending', '__return_true' );

		$this->setup_cot();
		$this->cot_state = OrderUtil::custom_orders_table_usage_is_enabled();
		$this->toggle_cot_feature_and_usage( true );
		update_option( CustomOrdersTableController::HPOS_DATASTORE_CACHING_ENABLED_OPTION, 'yes' );

		$container = wc_get_container();
		$container->reset_all_resolved();
		$this->sut        = $container->get( OrdersTableDataStore::class );
		$this->refund_sut = $container->get( OrdersTableRefundDataStore::class );
	}

	/**
	 * Tear down test fixtures.
	 */
	public function tearDown(): void {
		$this->toggle_cot_feature_and_usage( $this->cot_state );
		$this->clean_up_cot_setup();
		delete_option( CustomOrdersTableController::HPOS_DATASTORE_CACHING_ENABLED_OPTION );

		remove_all_filters( 'wc_allow_changing_orders_storage_while_sync_is_pending' );
		remove_all_filters( 'woocommerce_logging_class' );
		parent::tearDown();
	}

	/**
	 * @testdox Refund data store caches all base column properties from all table mappings.
	 */
	public function test_refund_data_store_caches_all_base_column_properties(): void {
		$order = \WC_Helper_Order::create_order();
		$order->set_status( 'completed' );
		$order->set_total( '50.00' );
		$order->save();

		$refund = wc_create_refund(
			array(
				'order_id' => $order->get_id(),
				'amount'   => '10.00',
				'reason'   => 'Test refund',
			)
		);
		$this->assertNotWPError( $refund, 'Refund creation should not return a WP_Error' );
		$refund_id = $refund->get_id();

		$this->refund_sut->clear_cached_data( array( $refund_id ) );
		wp_cache_flush();

		$call_protected = function ( $ids ) {
			return $this->get_order_data_for_ids( $ids );
		};

		$refund_data = $call_protected->call( $this->refund_sut, array( $refund_id ) );

		$this->assertArrayHasKey( $refund_id, $refund_data, 'Refund data should be returned' );

		$cached_object = $refund_data[ $refund_id ];

		foreach ( $this->get_all_base_named_properties() as $group => $properties ) {
			foreach ( $properties as $prop ) {
				$this->assertTrue(
					property_exists( $cached_object, $prop ),
					"Cached object should have '$group' property '$prop' even when loaded by refund data store"
				);
			}
		}
	}

	/**
	 * @testdox Order data store caches all base column properties from all table mappings.
	 */
	public function test_order_data_store_caches_all_base_column_properties(): void {
		$order = new WC_Order();
		$order->set_status( 'completed' );
		$order->set_recorded_sales( true );
		$order->set_transaction_id( 'txn_67890' );
		$order->set_cart_hash( 'hash456' );
		$order->save();
		$order_id = $order->get_id();

		$this->sut->clear_cached_data( array( $order_id ) );
		wp_cache_flush();

		$call_protected = function ( $ids ) {
			return $this->get_order_data_for_ids( $ids );
		};

		$order_data    = $call_protected->call( $this->sut, array( $order_id ) );
		$cached_object = $order_data[ $order_id ];

		foreach ( $this->get_all_base_named_properties() as $group => $properties ) {
			foreach ( $properties as $prop ) {
				$this->assertTrue(
					property_exists( $cached_object, $prop ),
					"Cached object should have '$group' property '$prop' when loaded by order data store"
				);
			}
		}
	}

	/**
	 * Return all named properties from the base class column mappings, grouped by table.
	 *
	 * @return array<string, string[]>
	 */
	private function get_all_base_named_properties(): array {
		return array(
			'orders'           => array(
				'id',
				'status',
				'type',
				'currency',
				'cart_tax',
				'total',
				'customer_id',
				'billing_email',
				'date_created',
				'date_modified',
				'parent_id',
				'payment_method',
				'payment_method_title',
				'customer_ip_address',
				'transaction_id',
				'customer_user_agent',
				'customer_note',
			),
			'billing_address'  => array(
				'billing_first_name',
				'billing_last_name',
				'billing_company',
				'billing_address_1',
				'billing_address_2',
				'billing_city',
				'billing_state',
				'billing_postcode',
				'billing_country',
				'billing_email',
				'billing_phone',
			),
			'shipping_address' => array(
				'shipping_first_name',
				'shipping_last_name',
				'shipping_company',
				'shipping_address_1',
				'shipping_address_2',
				'shipping_city',
				'shipping_state',
				'shipping_postcode',
				'shipping_country',
				'shipping_phone',
			),
			'operational_data' => array(
				'created_via',
				'version',
				'prices_include_tax',
				'recorded_coupon_usage_counts',
				'download_permissions_granted',
				'cart_hash',
				'new_order_email_sent',
				'order_key',
				'order_stock_reduced',
				'date_paid',
				'date_completed',
				'shipping_tax',
				'shipping_total',
				'discount_tax',
				'discount_total',
				'recorded_sales',
			),
		);
	}

	/**
	 * @testdox Order loaded via order data store retains correct values when cache was populated by refund data store.
	 */
	public function test_order_retains_values_when_cache_populated_by_refund_store(): void {
		$order = new WC_Order();
		$order->set_status( 'completed' );
		$order->set_total( '100.00' );
		$order->set_recorded_sales( true );
		$order->set_order_stock_reduced( true );
		$order->set_transaction_id( 'txn_cross_bleed_test' );
		$order->set_cart_hash( 'cross_bleed_hash' );
		$order->save();
		$order_id = $order->get_id();

		$refund = wc_create_refund(
			array(
				'order_id' => $order_id,
				'amount'   => '25.00',
				'reason'   => 'Cross-bleed regression test',
			)
		);
		$this->assertNotWPError( $refund, 'Refund creation should not return a WP_Error' );

		// Flush cache and reload the parent order via the refund data store to populate cache.
		$this->sut->clear_cached_data( array( $order_id ) );
		$this->refund_sut->clear_cached_data( array( $order_id ) );
		wp_cache_flush();

		$call_get_data = function ( $ids ) {
			return $this->get_order_data_for_ids( $ids );
		};
		$call_get_data->call( $this->refund_sut, array( $order_id ) );

		// Now load the order through the normal order data store, which should hit cache.
		$reloaded_order = wc_get_order( $order_id );

		$this->assertTrue( $reloaded_order->get_recorded_sales(), 'recorded_sales should be true, not reset to default' );
		$this->assertTrue( $reloaded_order->get_order_stock_reduced(), 'order_stock_reduced should be true, not reset to default' );
		$this->assertSame( 'txn_cross_bleed_test', $reloaded_order->get_transaction_id(), 'transaction_id should be preserved' );
		$this->assertSame( 'cross_bleed_hash', $reloaded_order->get_cart_hash(), 'cart_hash should be preserved' );
	}

	/**
	 * @testdox Debug logging is triggered when a property is missing from order data.
	 */
	public function test_debug_logging_on_missing_property(): void {
		$fake_logger = $this->create_fake_logger();
		add_filter(
			'woocommerce_logging_class',
			function () use ( $fake_logger ) {
				return $fake_logger;
			}
		);

		$container = wc_get_container();
		$container->reset_all_resolved();
		$sut = $container->get( OrdersTableDataStore::class );

		$order = new WC_Order();
		$order->save();
		$order_id = $order->get_id();

		$order_data     = new \stdClass();
		$order_data->id = $order_id;

		$call_protected = function ( $order, $order_data ) {
			$this->set_order_props_from_data( $order, $order_data );
		};

		$call_protected->call( $sut, $order, $order_data );

		$this->assertNotEmpty( $fake_logger->debug_calls, 'Debug log should fire when properties are missing from order data' );

		$found_hpos_source = false;
		foreach ( $fake_logger->debug_calls as $call ) {
			if ( isset( $call['context']['source'] ) && 'hpos-data-cache' === $call['context']['source'] ) {
				$found_hpos_source = true;
				break;
			}
		}
		$this->assertTrue( $found_hpos_source, 'Debug log entries should have source "hpos-data-cache"' );

		remove_all_filters( 'woocommerce_logging_class' );
	}

	/**
	 * @testdox A corrupt (non-object) order data cache entry is discarded, re-read from the database, and logged.
	 */
	public function test_corrupt_order_data_cache_entry_is_discarded_and_reread(): void {
		$fake_logger = $this->create_fake_logger();
		add_filter(
			'woocommerce_logging_class',
			function () use ( $fake_logger ) {
				return $fake_logger;
			}
		);

		$container = wc_get_container();
		$container->reset_all_resolved();
		$sut = $container->get( OrdersTableDataStore::class );

		$order = new WC_Order();
		$order->set_status( 'completed' );
		$order->set_total( '100.00' );
		$order->save();
		$order_id = $order->get_id();

		$sut->clear_cached_data( array( $order_id ) );
		wp_cache_flush();

		// Poison the order data cache with a non-object value, simulating a corrupt object cache entry.
		$cache_engine = $container->get( WPCacheEngine::class );
		$cache_engine->cache_objects( array( $order_id => 'corrupt-cache-entry' ), 0, 'orders_data' );

		$call_get_data = function ( $ids ) {
			return $this->get_order_data_for_ids( $ids );
		};
		$order_data    = $call_get_data->call( $sut, array( $order_id ) );

		$this->assertArrayHasKey( $order_id, $order_data, 'Order data should still be returned after the corrupt cache entry is discarded.' );
		$this->assertInstanceOf( \stdClass::class, $order_data[ $order_id ], 'The corrupt cache entry should be replaced by a fresh object read from the database.' );
		$this->assertSame( $order_id, (int) $order_data[ $order_id ]->id );

		// The corrupt entry should have been invalidated and re-cached with a valid object (self-heal).
		$recached = $cache_engine->get_cached_objects( array( $order_id ), 'orders_data' );
		$this->assertInstanceOf( \stdClass::class, $recached[ $order_id ], 'The corrupt entry should be replaced in cache by a valid object.' );

		$this->assert_hpos_cache_warning_logged( $fake_logger, 'corrupt HPOS order cache entry' );

		remove_all_filters( 'woocommerce_logging_class' );
	}

	/**
	 * @testdox A non-stdClass object order data cache entry is discarded, re-read from the database, and logged.
	 */
	public function test_corrupt_object_order_data_cache_entry_is_discarded_and_reread(): void {
		$fake_logger = $this->create_fake_logger();
		add_filter(
			'woocommerce_logging_class',
			function () use ( $fake_logger ) {
				return $fake_logger;
			}
		);

		$container = wc_get_container();
		$container->reset_all_resolved();
		$sut = $container->get( OrdersTableDataStore::class );

		$order = new WC_Order();
		$order->set_status( 'completed' );
		$order->save();
		$order_id = $order->get_id();

		$sut->clear_cached_data( array( $order_id ) );
		wp_cache_flush();

		// Poison the order data cache with a foreign object (not a plain stdClass), simulating a
		// cross-contaminated or unserialized-incomplete cache entry.
		$cache_engine = $container->get( WPCacheEngine::class );
		$cache_engine->cache_objects( array( $order_id => new WC_Order() ), 0, 'orders_data' );

		$call_get_data = function ( $ids ) {
			return $this->get_order_data_for_ids( $ids );
		};
		$order_data    = $call_get_data->call( $sut, array( $order_id ) );

		$this->assertArrayHasKey( $order_id, $order_data, 'Order data should still be returned after the corrupt object entry is discarded.' );
		$this->assertInstanceOf( \stdClass::class, $order_data[ $order_id ], 'The foreign-object cache entry should be replaced by a fresh stdClass read from the database.' );
		$this->assertSame( $order_id, (int) $order_data[ $order_id ]->id );

		$this->assert_hpos_cache_warning_logged( $fake_logger, 'corrupt HPOS order cache entry' );

		remove_all_filters( 'woocommerce_logging_class' );
	}

	/**
	 * @testdox A corrupt (non-array) meta cache entry is discarded, re-read from the database, and logged, without fataling the order read.
	 */
	public function test_corrupt_meta_cache_entry_is_discarded_and_reread(): void {
		$fake_logger = $this->create_fake_logger();
		add_filter(
			'woocommerce_logging_class',
			function () use ( $fake_logger ) {
				return $fake_logger;
			}
		);

		$container = wc_get_container();
		$container->reset_all_resolved();
		$sut = $container->get( OrdersTableDataStore::class );

		$order = new WC_Order();
		$order->add_meta_data( 'custom_meta_key', 'custom_value', true );
		$order->save();
		$order_id = $order->get_id();

		$sut->clear_cached_data( array( $order_id ) );
		wp_cache_flush();

		// Poison the meta cache with a non-array value, simulating a corrupt object cache entry.
		$cache_engine = $container->get( WPCacheEngine::class );
		$cache_engine->cache_objects( array( $order_id => 'corrupt-meta-entry' ), 0, 'orders_meta' );

		// Reading the order must not fatal in filter_raw_meta_data().
		$reloaded_order = wc_get_order( $order_id );

		$this->assertInstanceOf( WC_Order::class, $reloaded_order, 'The order should load instead of fataling on the corrupt meta cache entry.' );
		$this->assertSame( 'custom_value', $reloaded_order->get_meta( 'custom_meta_key' ), 'Meta should be re-read from the database after the corrupt cache entry is discarded.' );

		$this->assert_hpos_cache_warning_logged( $fake_logger, 'corrupt HPOS meta cache entry' );

		remove_all_filters( 'woocommerce_logging_class' );
	}

	/**
	 * @testdox A well-formed meta cache array whose elements are not meta rows does not fatal; the order self-heals on the next read.
	 */
	public function test_corrupt_meta_element_cache_entry_does_not_fatal_and_self_heals(): void {
		$fake_logger = $this->create_fake_logger();
		add_filter(
			'woocommerce_logging_class',
			function () use ( $fake_logger ) {
				return $fake_logger;
			}
		);

		$container = wc_get_container();
		$container->reset_all_resolved();
		$sut = $container->get( OrdersTableDataStore::class );

		$order = new WC_Order();
		$order->add_meta_data( 'custom_meta_key', 'custom_value', true );
		$order->save();
		$order_id = $order->get_id();

		$sut->clear_cached_data( array( $order_id ) );
		wp_cache_flush();

		/*
		 * Poison the meta cache with a well-formed array whose elements are scalars instead of
		 * meta-row objects. This passes a naive top-level is_array() check but is rejected by the
		 * full row-shape validation at the cache boundary, so it is re-read from the database in
		 * the same request rather than fataling in filter_raw_meta_data() ($meta->meta_key).
		 */
		$cache_engine = $container->get( WPCacheEngine::class );
		$cache_engine->cache_objects( array( $order_id => array( 'not-a-meta-row', 'another-string' ) ), 0, 'orders_meta' );

		// The read must not fatal and must self-heal in the same request from the database.
		$reloaded_order = wc_get_order( $order_id );
		$this->assertInstanceOf( WC_Order::class, $reloaded_order, 'The order should load instead of fataling on the corrupt meta elements.' );
		$this->assertSame( 'custom_value', $reloaded_order->get_meta( 'custom_meta_key' ), 'Meta should be re-read correctly from the database, not the corrupt scalar elements.' );
		$this->assert_hpos_cache_warning_logged( $fake_logger, 'corrupt HPOS meta cache entry' );

		remove_all_filters( 'woocommerce_logging_class' );
	}

	/**
	 * @testdox A meta row missing required fields (e.g. only meta_key) is treated as corrupt and re-read from the database.
	 */
	public function test_incomplete_meta_row_cache_entry_is_rejected_and_reread(): void {
		$fake_logger = $this->create_fake_logger();
		add_filter(
			'woocommerce_logging_class',
			function () use ( $fake_logger ) {
				return $fake_logger;
			}
		);

		$container = wc_get_container();
		$container->reset_all_resolved();
		$sut = $container->get( OrdersTableDataStore::class );

		$order = new WC_Order();
		$order->add_meta_data( 'custom_meta_key', 'custom_value', true );
		$order->save();
		$order_id = $order->get_id();

		$sut->clear_cached_data( array( $order_id ) );
		wp_cache_flush();

		// A row carrying only meta_key (no meta_id/meta_value) would otherwise load the real key
		// with a null value instead of the database value.
		$cache_engine = $container->get( WPCacheEngine::class );
		$cache_engine->cache_objects( array( $order_id => array( (object) array( 'meta_key' => 'custom_meta_key' ) ) ), 0, 'orders_meta' ); // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_key

		$reloaded_order = wc_get_order( $order_id );
		$this->assertInstanceOf( WC_Order::class, $reloaded_order, 'The order should load instead of using the incomplete cached row.' );
		$this->assertSame( 'custom_value', $reloaded_order->get_meta( 'custom_meta_key' ), 'The incomplete row should be discarded and the real value re-read from the database.' );
		$this->assert_hpos_cache_warning_logged( $fake_logger, 'corrupt HPOS meta cache entry' );

		remove_all_filters( 'woocommerce_logging_class' );
	}

	/**
	 * @testdox An order data cache entry whose id does not match the requested key (cross-bleed) is discarded and re-read.
	 */
	public function test_order_data_cache_entry_with_mismatched_id_is_discarded_and_reread(): void {
		$fake_logger = $this->create_fake_logger();
		add_filter(
			'woocommerce_logging_class',
			function () use ( $fake_logger ) {
				return $fake_logger;
			}
		);

		$container = wc_get_container();
		$container->reset_all_resolved();
		$sut = $container->get( OrdersTableDataStore::class );

		$order = new WC_Order();
		$order->set_status( 'completed' );
		$order->save();
		$order_id = $order->get_id();

		$sut->clear_cached_data( array( $order_id ) );
		wp_cache_flush();

		// Poison the cache with a stdClass whose id belongs to a different order (cross-bleed).
		$cross_bled     = new \stdClass();
		$cross_bled->id = $order_id + 999;
		$cache_engine   = $container->get( WPCacheEngine::class );
		$cache_engine->cache_objects( array( $order_id => $cross_bled ), 0, 'orders_data' );

		$call_get_data = function ( $ids ) {
			return $this->get_order_data_for_ids( $ids );
		};
		$order_data    = $call_get_data->call( $sut, array( $order_id ) );

		$this->assertArrayHasKey( $order_id, $order_data, 'Order data should still be returned after the cross-bled entry is discarded.' );
		$this->assertSame( $order_id, (int) $order_data[ $order_id ]->id, 'The mismatched-id entry should be replaced by the correct order read from the database.' );
		$this->assert_hpos_cache_warning_logged( $fake_logger, 'corrupt HPOS order cache entry' );

		remove_all_filters( 'woocommerce_logging_class' );
	}

	/**
	 * @testdox A cached order data record with the correct id but a missing mapped column is rejected and re-read.
	 */
	public function test_truncated_order_data_cache_entry_is_rejected_and_reread(): void {
		$fake_logger = $this->create_fake_logger();
		add_filter(
			'woocommerce_logging_class',
			function () use ( $fake_logger ) {
				return $fake_logger;
			}
		);

		$container = wc_get_container();
		$container->reset_all_resolved();
		$sut = $container->get( OrdersTableDataStore::class );

		$order = new WC_Order();
		$order->set_status( 'completed' );
		$order->set_total( '60.00' );
		$order->save();
		$order_id = $order->get_id();

		$sut->clear_cached_data( array( $order_id ) );
		wp_cache_flush();

		$call_get_data = function ( $ids ) {
			return $this->get_order_data_for_ids( $ids );
		};

		// Prime the cache with a complete record, then read it back.
		$call_get_data->call( $sut, array( $order_id ) );
		$cache_engine = $container->get( WPCacheEngine::class );
		$cached       = $cache_engine->get_cached_objects( array( $order_id ), 'orders_data' );
		$this->assertInstanceOf( \stdClass::class, $cached[ $order_id ] );
		$this->assertTrue( property_exists( $cached[ $order_id ], 'status' ), 'Sanity: the primed cache record should carry the status column.' );

		// Truncate the record: keep the correct id but drop a mapped column, then re-cache it.
		$truncated = $cached[ $order_id ];
		unset( $truncated->status );
		$cache_engine->cache_objects( array( $order_id => $truncated ), 0, 'orders_data' );

		$order_data = $call_get_data->call( $sut, array( $order_id ) );

		$this->assertArrayHasKey( $order_id, $order_data, 'Order data should still be returned after the truncated entry is discarded.' );
		$this->assertTrue( property_exists( $order_data[ $order_id ], 'status' ), 'The truncated entry should be replaced by a complete record read from the database.' );
		$this->assertSame( $order_id, (int) $order_data[ $order_id ]->id );
		$this->assert_hpos_cache_warning_logged( $fake_logger, 'corrupt HPOS order cache entry' );

		remove_all_filters( 'woocommerce_logging_class' );
	}

	/**
	 * @testdox filter_raw_meta_data() treats a non-array argument as empty meta instead of fataling.
	 */
	public function test_filter_raw_meta_data_tolerates_non_array_input(): void {
		$fake_logger = $this->create_fake_logger();
		add_filter(
			'woocommerce_logging_class',
			function () use ( $fake_logger ) {
				return $fake_logger;
			}
		);

		$container = wc_get_container();
		$container->reset_all_resolved();
		$sut = $container->get( OrdersTableDataStore::class );

		$order = new WC_Order();
		$order->save();

		$call_filter = function ( $order_object, $raw_meta_data ) {
			return $this->filter_raw_meta_data( $order_object, $raw_meta_data );
		};

		$result = $call_filter->call( $sut, $order, 'not-an-array' );

		$this->assertSame( array(), $result, 'A non-array meta argument should be treated as empty meta.' );
		$this->assert_hpos_cache_warning_logged( $fake_logger, 'Discarded malformed meta data' );

		remove_all_filters( 'woocommerce_logging_class' );
	}

	/**
	 * @testdox A corrupt legacy ('orders' group) meta cache entry is invalidated so the next read_meta_data() self-heals from the database.
	 */
	public function test_corrupt_legacy_meta_cache_entry_is_invalidated_and_self_heals(): void {
		$fake_logger = $this->create_fake_logger();
		add_filter(
			'woocommerce_logging_class',
			function () use ( $fake_logger ) {
				return $fake_logger;
			}
		);

		// Rebuild the data store so its injected logger (captured in init() via wc_get_logger())
		// is the fake logger added above, and orders created below use this instance.
		$container = wc_get_container();
		$container->reset_all_resolved();
		$container->get( OrdersTableDataStore::class );

		$order = new WC_Order();
		$order->add_meta_data( 'custom_meta_key', 'custom_value', true );
		$order->save();
		$order_id = $order->get_id();

		// Reload a fresh order object so its meta cache key reflects current cache prefixes.
		$order = wc_get_order( $order_id );

		/*
		 * Poison the legacy meta cache group that WC_Data::read_meta_data() reads
		 * (WC_Abstract_Order::$cache_group = 'orders') with a well-formed array whose elements are
		 * not meta rows. This passes the top-level is_array() check in read_meta_data(), so the
		 * corruption guard in filter_raw_meta_data() - not the HPOS 'orders_meta' boundary - is the
		 * layer that must invalidate it.
		 */
		$cache_key = $order->get_meta_cache_key();
		wp_cache_set( $cache_key, array( 'not-a-meta-row' ), 'orders' );
		$this->assertIsArray( wp_cache_get( $cache_key, 'orders' ), 'Sanity: the legacy meta cache entry should be primed.' );

		// Force the legacy read path directly (init_order_record() primes meta_data, so the normal
		// wc_get_order() flow does not re-enter read_meta_data()).
		$order->read_meta_data();

		$this->assert_hpos_cache_warning_logged( $fake_logger, 'Discarded malformed meta data' );

		// The corrupt legacy entry must have been invalidated - otherwise read_meta_data() would keep
		// loading it (it skips re-caching on a cache hit) and re-log on every read.
		$this->assertFalse(
			wp_cache_get( $cache_key, 'orders' ),
			'The corrupt legacy meta cache entry should be invalidated so the next read re-reads from the database.'
		);

		// The next read misses the cache, re-reads from the database, and recovers the real value.
		$order->read_meta_data();
		$this->assertSame(
			'custom_value',
			$order->get_meta( 'custom_meta_key' ),
			'Meta should self-heal from the database after the corrupt legacy cache entry is invalidated.'
		);

		// The self-healed read must not re-log: the warning fires once, not on every read.
		$this->assertSame(
			1,
			$this->count_hpos_cache_warnings( $fake_logger, 'Discarded malformed meta data' ),
			'The corruption warning should fire once and stop once the cache self-heals.'
		);

		remove_all_filters( 'woocommerce_logging_class' );
	}

	/**
	 * @testdox A legacy meta cache array containing an incomplete meta row (missing meta_value) is treated as corrupt, invalidated, and self-heals.
	 */
	public function test_incomplete_legacy_meta_row_is_invalidated_and_self_heals(): void {
		$fake_logger = $this->create_fake_logger();
		add_filter(
			'woocommerce_logging_class',
			function () use ( $fake_logger ) {
				return $fake_logger;
			}
		);

		// Rebuild the data store so its injected logger (captured in init() via wc_get_logger())
		// is the fake logger added above, and orders created below use this instance.
		$container = wc_get_container();
		$container->reset_all_resolved();
		$container->get( OrdersTableDataStore::class );

		$order = new WC_Order();
		$order->add_meta_data( 'custom_meta_key', 'custom_value', true );
		$order->save();
		$order_id = $order->get_id();

		$order = wc_get_order( $order_id );

		/*
		 * Poison the legacy 'orders' meta cache with an object row that has meta_key but is missing
		 * meta_value. This is a valid array of objects with meta_key, so a meta_key-only shape check
		 * would accept it and hydrate the real key with a null value. The completeness check
		 * (meta_key + meta_value) must treat the missing value as corrupt and self-heal instead.
		 */
		$cache_key = $order->get_meta_cache_key();
		wp_cache_set( $cache_key, array( (object) array( 'meta_key' => 'custom_meta_key' ) ), 'orders' ); // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_key

		$order->read_meta_data();

		$this->assert_hpos_cache_warning_logged( $fake_logger, 'Discarded malformed meta data' );
		$this->assertFalse(
			wp_cache_get( $cache_key, 'orders' ),
			'The incomplete legacy meta cache entry should be invalidated so the next read re-reads from the database.'
		);

		$order->read_meta_data();
		$this->assertSame(
			'custom_value',
			$order->get_meta( 'custom_meta_key' ),
			'The incomplete row should be discarded and the real value re-read from the database, not loaded as null.'
		);

		remove_all_filters( 'woocommerce_logging_class' );
	}

	/**
	 * @testdox A virtual meta row injected via the read-meta filter (meta_key/meta_value, no meta_id) is not treated as corrupt and does not churn the legacy meta cache.
	 */
	public function test_filter_injected_virtual_meta_is_not_treated_as_corrupt(): void {
		$fake_logger = $this->create_fake_logger();
		add_filter(
			'woocommerce_logging_class',
			function () use ( $fake_logger ) {
				return $fake_logger;
			}
		);

		// Rebuild the data store so its injected logger (captured in init() via wc_get_logger())
		// is the fake logger added above, and orders created below use this instance.
		$container = wc_get_container();
		$container->reset_all_resolved();
		$container->get( OrdersTableDataStore::class );

		$order = new WC_Order();
		$order->add_meta_data( 'custom_meta_key', 'custom_value', true );
		$order->save();
		$order_id = $order->get_id();

		// Reload a fresh order object so its meta cache key reflects current cache prefixes.
		$order = wc_get_order( $order_id );

		/*
		 * Simulate an extension that appends a virtual meta row on read. The row carries meta_key and
		 * meta_value but no meta_id - a shape the guard must accept, not flag as corrupt.
		 */
		$read_meta_filter = function ( $meta_data ) {
			$virtual             = new \stdClass();
			$virtual->meta_key   = '_virtual_meta';
			$virtual->meta_value = 'virtual_value';
			$meta_data[]         = $virtual;
			return $meta_data;
		};
		add_filter( 'woocommerce_data_store_wp_post_read_meta', $read_meta_filter );

		// Make sure the legacy meta cache starts empty so the first read is a cache miss.
		$cache_key = $order->get_meta_cache_key();
		wp_cache_delete( $cache_key, 'orders' );

		// First read: cache miss. Caches the post-filter output (including the injected virtual row)
		// in the legacy 'orders' group and hydrates the object's meta data.
		$order->read_meta_data();
		$this->assertSame( 'virtual_value', $order->get_meta( '_virtual_meta' ), 'The injected virtual meta should load without a meta_id.' );
		$this->assertIsArray( wp_cache_get( $cache_key, 'orders' ), 'The legacy meta cache should be primed after the first read.' );

		// Second read: cache hit. The guard re-validates the cached post-filter data, which now
		// includes the injected virtual row. It must not be reclassified as corrupt.
		$order->read_meta_data();

		$this->assertSame(
			0,
			$this->count_hpos_cache_warnings( $fake_logger, 'Discarded malformed meta data' ),
			'A filter-injected virtual meta row must not trigger the corruption guard.'
		);
		$this->assertIsArray(
			wp_cache_get( $cache_key, 'orders' ),
			'The legacy meta cache must stay warm - the guard should not purge it for a filter-injected virtual row.'
		);
		$this->assertSame( 'virtual_value', $order->get_meta( '_virtual_meta' ), 'The injected virtual meta should still resolve after a cache hit.' );
		$this->assertSame( 'custom_value', $order->get_meta( 'custom_meta_key' ), 'The real database meta should be unaffected.' );

		remove_filter( 'woocommerce_data_store_wp_post_read_meta', $read_meta_filter );
		remove_all_filters( 'woocommerce_logging_class' );
	}

	/**
	 * Count warnings with the "hpos-data-cache" source containing the given message fragment.
	 *
	 * @param object $fake_logger The fake logger capturing log calls.
	 * @param string $needle      A substring expected in the warning message.
	 *
	 * @return int Number of matching warnings.
	 */
	private function count_hpos_cache_warnings( object $fake_logger, string $needle ): int {
		$count = 0;
		foreach ( $fake_logger->warning_calls as $call ) {
			if ( 'hpos-data-cache' === ( $call['context']['source'] ?? '' ) && false !== strpos( $call['message'], $needle ) ) {
				++$count;
			}
		}

		return $count;
	}

	/**
	 * Assert that a warning with the "hpos-data-cache" source and the given message fragment was logged.
	 *
	 * @param object $fake_logger The fake logger capturing log calls.
	 * @param string $needle      A substring expected in the warning message.
	 */
	private function assert_hpos_cache_warning_logged( object $fake_logger, string $needle ): void {
		$found = false;
		foreach ( $fake_logger->warning_calls as $call ) {
			if ( 'hpos-data-cache' === ( $call['context']['source'] ?? '' ) && false !== strpos( $call['message'], $needle ) ) {
				$found = true;
				break;
			}
		}

		$this->assertTrue( $found, "Expected a 'hpos-data-cache' warning containing: $needle" );
	}

	/**
	 * Create a fake logger for testing.
	 *
	 * @return object Fake logger implementing WC_Logger_Interface.
	 */
	// phpcs:disable Squiz.Commenting
	private function create_fake_logger(): object {
		return new class() implements \WC_Logger_Interface {
			public array $debug_calls   = array();
			public array $info_calls    = array();
			public array $warning_calls = array();
			public array $error_calls   = array();

			public function add( $handle, $message, $level = \WC_Log_Levels::NOTICE ) {
				unset( $handle, $message, $level ); // Avoid parameter not used PHPCS errors.
				return true;
			}

			public function log( $level, $message, $context = array() ) {
				unset( $level, $message, $context ); // Avoid parameter not used PHPCS errors.
			}

			public function emergency( $message, $context = array() ) {
				unset( $message, $context ); // Avoid parameter not used PHPCS errors.
			}

			public function alert( $message, $context = array() ) {
				unset( $message, $context ); // Avoid parameter not used PHPCS errors.
			}

			public function critical( $message, $context = array() ) {
				unset( $message, $context ); // Avoid parameter not used PHPCS errors.
			}

			public function notice( $message, $context = array() ) {
				unset( $message, $context ); // Avoid parameter not used PHPCS errors.
			}

			public function debug( $message, $context = array() ) {
				$this->debug_calls[] = array(
					'message' => $message,
					'context' => $context,
				);
			}

			public function info( $message, $context = array() ) {
				$this->info_calls[] = array(
					'message' => $message,
					'context' => $context,
				);
			}

			public function warning( $message, $context = array() ) {
				$this->warning_calls[] = array(
					'message' => $message,
					'context' => $context,
				);
			}

			public function error( $message, $context = array() ) {
				$this->error_calls[] = array(
					'message' => $message,
					'context' => $context,
				);
			}
		};
	}
	// phpcs:enable Squiz.Commenting
}
