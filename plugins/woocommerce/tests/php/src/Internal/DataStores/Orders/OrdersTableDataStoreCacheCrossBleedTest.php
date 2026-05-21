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
