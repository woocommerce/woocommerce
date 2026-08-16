<?php
declare( strict_types = 1 );

namespace Automattic\WooCommerce\Tests\Admin\API\Reports\Orders\Stats;

use Automattic\WooCommerce\Admin\API\Reports\Orders\Stats\DataStore as OrdersStatsDataStore;
use Automattic\WooCommerce\Caches\OrderCache;
use Automattic\WooCommerce\Internal\Admin\Schedulers\OrdersScheduler;
use Automattic\WooCommerce\Utilities\OrderUtil;
use WC_Helper_Order;
use WC_Unit_Test_Case;
use WP_Error;

/**
 * Tests for Orders Stats DataStore.
 */
class DataStoreTest extends WC_Unit_Test_Case {

	/**
	 * Previous woocommerce_db_version for restore.
	 *
	 * @var mixed
	 */
	private $previous_db_version;

	/**
	 * Previous woocommerce_analytics_uses_old_full_refund_data for restore.
	 *
	 * @var mixed
	 */
	private $previous_old_full_refund_flag;

	/**
	 * Set up test fixtures.
	 */
	public function setUp(): void {
		parent::setUp();
		$this->previous_db_version           = get_option( 'woocommerce_db_version' );
		$this->previous_old_full_refund_flag = get_option( 'woocommerce_analytics_uses_old_full_refund_data' );
	}

	/**
	 * Tear down test fixtures.
	 */
	public function tearDown(): void {
		if ( false !== $this->previous_db_version ) {
			update_option( 'woocommerce_db_version', $this->previous_db_version );
		} else {
			delete_option( 'woocommerce_db_version' );
		}
		if ( false !== $this->previous_old_full_refund_flag ) {
			update_option( 'woocommerce_analytics_uses_old_full_refund_data', $this->previous_old_full_refund_flag );
		} else {
			delete_option( 'woocommerce_analytics_uses_old_full_refund_data' );
		}
		parent::tearDown();
	}

	/**
	 * @testdox Lump-sum full refund without _refund_type stores parent product net in order stats.
	 */
	public function test_lump_sum_full_refund_without_refund_type_uses_parent_net_total(): void {
		update_option( 'woocommerce_db_version', '10.2.0' );
		update_option( 'woocommerce_analytics_uses_old_full_refund_data', 'no' );

		$order = WC_Helper_Order::create_order();
		// Add cart tax so we assert tax and shipping are both stripped from net, not only shipping.
		$order->set_cart_tax( 5.00 );
		$order->set_total( 55.00 );
		$order->save();
		$order->update_status( 'completed' );

		$remaining = (float) wc_format_decimal( $order->get_total() - $order->get_total_refunded() );
		$refund    = wc_create_refund(
			array(
				'order_id'   => $order->get_id(),
				'amount'     => $remaining,
				'line_items' => array(),
			)
		);

		$this->assertNotInstanceOf( WP_Error::class, $refund );

		global $wpdb;
		if ( OrderUtil::custom_orders_table_usage_is_enabled() ) {
			$wpdb->delete(
				$wpdb->prefix . 'wc_orders_meta',
				array(
					'order_id' => $refund->get_id(),
					'meta_key' => '_refund_type', // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_key -- Test fixture: clear refund type meta.
				),
				array( '%d', '%s' )
			);
		} else {
			delete_post_meta( $refund->get_id(), '_refund_type' );
		}

		if ( OrderUtil::orders_cache_usage_is_enabled() ) {
			wc_get_container()->get( OrderCache::class )->remove( $refund->get_id() );
		}

		$refund_after_clear = wc_get_order( $refund->get_id() );
		$this->assertInstanceOf( \WC_Order_Refund::class, $refund_after_clear );
		$this->assertEmpty( $refund_after_clear->get_meta( '_refund_type', true ) );

		OrdersStatsDataStore::sync_order( $refund->get_id() );

		$net_total = $wpdb->get_var(
			$wpdb->prepare(
				"SELECT net_total FROM {$wpdb->prefix}wc_order_stats WHERE order_id = %d",
				$refund->get_id()
			)
		);

		$expected_net = -1 * ( $order->get_total() - $order->get_total_tax() - $order->get_shipping_total() );

		$this->assertEqualsWithDelta( $expected_net, $net_total, 0.02 );

		WC_Helper_Order::delete_order( $order->get_id() );
	}

	/**
	 * @testdox A partial refund followed by a full refund does not double-count the returns amount.
	 *
	 * Regression test for https://github.com/woocommerce/woocommerce/issues/66217: the full-refund
	 * row used to store the whole parent order total again, ignoring the amount an earlier partial
	 * refund had already recorded, so the Revenue report over-counted returns.
	 */
	public function test_partial_then_full_refund_does_not_double_count_returns(): void {
		update_option( 'woocommerce_db_version', '10.2.0' );
		update_option( 'woocommerce_analytics_uses_old_full_refund_data', 'no' );

		// Order: net 40 (4 x $10 product) + tax 5 + shipping 10 = 55 gross.
		$order = WC_Helper_Order::create_order();
		$order->set_cart_tax( 5.00 );
		$order->set_total( 55.00 );
		$order->save();
		$order->update_status( 'completed' );

		$product_item_id = array_key_first( $order->get_items() );

		// Partial refund: 2 of the 4 product units ($20) with a real line-item breakdown.
		$partial = wc_create_refund(
			array(
				'order_id'   => $order->get_id(),
				'amount'     => 20.00,
				'line_items' => array(
					$product_item_id => array(
						'qty'          => 2,
						'refund_total' => 20.00,
					),
				),
			)
		);
		$this->assertNotInstanceOf( WP_Error::class, $partial );

		// Full refund of the remainder as a lump sum (no line items), flagged as a full refund.
		$remaining = (float) wc_format_decimal( $order->get_total() - $order->get_total_refunded() );
		$full      = wc_create_refund(
			array(
				'order_id'   => $order->get_id(),
				'amount'     => $remaining,
				'line_items' => array(),
			)
		);
		$this->assertNotInstanceOf( WP_Error::class, $full );
		$full->update_meta_data( '_refund_type', 'full' );
		$full->save_meta_data();
		if ( OrderUtil::orders_cache_usage_is_enabled() ) {
			wc_get_container()->get( OrderCache::class )->remove( $full->get_id() );
		}

		OrdersStatsDataStore::sync_order( $order->get_id() );
		OrdersStatsDataStore::sync_order( $partial->get_id() );
		OrdersStatsDataStore::sync_order( $full->get_id() );

		global $wpdb;

		// Reported returns = absolute gross (net + tax + shipping) summed over the refund rows.
		$returns = (float) $wpdb->get_var(
			$wpdb->prepare(
				"SELECT ABS( SUM( net_total + tax_total + shipping_total ) ) FROM {$wpdb->prefix}wc_order_stats WHERE parent_id = %d",
				$order->get_id()
			)
		);
		// Once (the order gross), not the partial refund counted twice ($75 before the fix).
		$this->assertEqualsWithDelta( 55.00, $returns, 0.02 );

		// The net portion across refunds should reconstruct the order net exactly once.
		$refunded_net = (float) $wpdb->get_var(
			$wpdb->prepare(
				"SELECT SUM( net_total ) FROM {$wpdb->prefix}wc_order_stats WHERE parent_id = %d",
				$order->get_id()
			)
		);
		$this->assertEqualsWithDelta( -40.00, $refunded_net, 0.02 );

		WC_Helper_Order::delete_order( $order->get_id() );
	}

	/**
	 * @testdox Deleting a refund removes its analytics rows while keeping the parent order's rows.
	 *
	 * Regression test for HPOS refund deletion leaving orphaned analytics rows:
	 * OrdersTableRefundDataStore::delete() fired no hooks, so the refund's
	 * wc_order_stats and wc_order_product_lookup rows survived the deletion and
	 * permanently skewed Revenue, Orders and Products reports.
	 *
	 * @see https://github.com/woocommerce/woocommerce/issues/48955
	 */
	public function test_deleting_refund_removes_analytics_rows(): void {
		global $wpdb;

		$order = WC_Helper_Order::create_order();
		$order->update_status( 'completed' );
		$order_id = $order->get_id();

		$items  = array_values( $order->get_items() );
		$refund = wc_create_refund(
			array(
				'order_id'   => $order_id,
				'amount'     => 10,
				'line_items' => array(
					$items[0]->get_id() => array(
						'qty'          => 1,
						'refund_total' => 10,
					),
				),
			)
		);
		$this->assertNotInstanceOf( WP_Error::class, $refund );
		$refund_id = $refund->get_id();

		// Import both records, as the woocommerce_update_order and
		// woocommerce_refund_created flows would.
		OrdersScheduler::import( $order_id );
		OrdersScheduler::import( $refund_id );

		$stats_rows  = static function ( $id ) use ( $wpdb ) {
			return (int) $wpdb->get_var(
				$wpdb->prepare( "SELECT COUNT(*) FROM {$wpdb->prefix}wc_order_stats WHERE order_id = %d", $id )
			);
		};
		$lookup_rows = static function ( $id ) use ( $wpdb ) {
			return (int) $wpdb->get_var(
				$wpdb->prepare( "SELECT COUNT(*) FROM {$wpdb->prefix}wc_order_product_lookup WHERE order_id = %d", $id )
			);
		};

		$this->assertSame( 1, $stats_rows( $order_id ), 'Parent order should have a stats row after import.' );
		$this->assertSame( 1, $stats_rows( $refund_id ), 'Refund should have a stats row after import.' );
		$this->assertGreaterThan( 0, $lookup_rows( $refund_id ), 'Refund should have product lookup rows after import.' );

		// Delete the refund the way the admin UI and REST API do.
		$refund->delete( true );

		$this->assertSame( 0, $stats_rows( $refund_id ), 'Deleting a refund should remove its stats row.' );
		$this->assertSame( 0, $lookup_rows( $refund_id ), 'Deleting a refund should remove its product lookup rows.' );
		$this->assertSame( 1, $stats_rows( $order_id ), 'Deleting a refund should keep the parent order stats row.' );

		WC_Helper_Order::delete_order( $order_id );
	}

	/**
	 * @testdox delete_refund still runs the cleanup cascade when no stats row exists.
	 *
	 * Imports are not atomic, so lookup rows can outlive the stats row. Gating on it
	 * would orphan them, so the cascade runs regardless, with a customer ID of 0.
	 */
	public function test_delete_refund_runs_cascade_when_no_stats_row_exists(): void {
		if ( ! OrderUtil::custom_orders_table_usage_is_enabled() ) {
			$this->markTestSkipped( 'delete_refund() only runs when HPOS is authoritative; CPT is cleaned via delete_post.' );
		}

		$fired         = 0;
		$seen_ids      = array();
		$seen_customer = null;
		$callback      = function ( $order_id, $customer_id ) use ( &$fired, &$seen_ids, &$seen_customer ) {
			++$fired;
			$seen_ids[]    = $order_id;
			$seen_customer = $customer_id;
		};
		add_action( 'woocommerce_analytics_delete_order_stats', $callback, 10, 2 );

		OrdersStatsDataStore::delete_refund( 987654321 );

		remove_action( 'woocommerce_analytics_delete_order_stats', $callback, 10 );

		$this->assertSame( 1, $fired, 'delete_refund should fire the delete-stats cascade even when no stats row exists.' );
		$this->assertSame( array( 987654321 ), $seen_ids, 'The cascade should carry the refund ID.' );
		$this->assertSame( 0, $seen_customer, 'A missing stats row should yield customer ID 0.' );
	}

	/**
	 * @testdox delete_refund removes orphaned product lookup rows left by a partial import.
	 *
	 * Stats and product lookups sync in separate, non-transactional steps, so the
	 * lookup rows can outlive a failed stats sync.
	 */
	public function test_delete_refund_removes_orphaned_lookup_rows_without_stats_row(): void {
		global $wpdb;

		if ( ! OrderUtil::custom_orders_table_usage_is_enabled() ) {
			$this->markTestSkipped( 'delete_refund() only runs when HPOS is authoritative; the CPT equivalent is covered below.' );
		}

		$refund_id = 987654322;

		// Simulate a partial import: product lookup row present, stats row absent.
		$wpdb->insert(
			$wpdb->prefix . 'wc_order_product_lookup',
			array(
				'order_item_id'         => 987654322,
				'order_id'              => $refund_id,
				'product_id'            => 1,
				'variation_id'          => 0,
				'customer_id'           => 0,
				'date_created'          => '2026-01-01 00:00:00',
				'product_qty'           => -1,
				'product_net_revenue'   => -10,
				'product_gross_revenue' => -10,
			)
		);

		$lookup_rows = static function ( $id ) use ( $wpdb ) {
			return (int) $wpdb->get_var(
				$wpdb->prepare( "SELECT COUNT(*) FROM {$wpdb->prefix}wc_order_product_lookup WHERE order_id = %d", $id )
			);
		};
		$stats_rows  = static function ( $id ) use ( $wpdb ) {
			return (int) $wpdb->get_var(
				$wpdb->prepare( "SELECT COUNT(*) FROM {$wpdb->prefix}wc_order_stats WHERE order_id = %d", $id )
			);
		};

		$this->assertSame( 1, $lookup_rows( $refund_id ), 'Fixture should leave a product lookup row.' );
		$this->assertSame( 0, $stats_rows( $refund_id ), 'Fixture should leave no stats row.' );

		OrdersStatsDataStore::delete_refund( $refund_id );

		$this->assertSame( 0, $lookup_rows( $refund_id ), 'Deleting a refund should clear orphaned product lookup rows.' );
	}

	/**
	 * @testdox Deleting a CPT refund fires the analytics delete cascade exactly once.
	 *
	 * The CPT store deletes the post — running the cascade via delete_post — before
	 * firing woocommerce_delete_order_refund, so delete_refund() must stand down or
	 * listeners see two events for one deletion.
	 */
	public function test_deleting_cpt_refund_fires_delete_cascade_once(): void {
		if ( OrderUtil::custom_orders_table_usage_is_enabled() ) {
			$this->markTestSkipped( 'Test requires CPT to be the authoritative store.' );
		}

		$order = WC_Helper_Order::create_order();
		$order->update_status( 'completed' );
		$order_id = $order->get_id();

		$items  = array_values( $order->get_items() );
		$refund = wc_create_refund(
			array(
				'order_id'   => $order_id,
				'amount'     => 10,
				'line_items' => array(
					$items[0]->get_id() => array(
						'qty'          => 1,
						'refund_total' => 10,
					),
				),
			)
		);
		$this->assertNotInstanceOf( WP_Error::class, $refund );
		$refund_id = $refund->get_id();

		OrdersScheduler::import( $order_id );
		OrdersScheduler::import( $refund_id );

		$fired    = 0;
		$callback = function ( $deleted_id ) use ( &$fired, $refund_id ) {
			if ( (int) $deleted_id === $refund_id ) {
				++$fired;
			}
		};
		add_action( 'woocommerce_analytics_delete_order_stats', $callback );

		$refund->delete( true );

		remove_action( 'woocommerce_analytics_delete_order_stats', $callback );

		$this->assertSame( 1, $fired, 'The delete cascade should fire exactly once per CPT refund deletion.' );

		WC_Helper_Order::delete_order( $order_id );
	}

	/**
	 * @testdox Deleting a CPT refund clears orphaned lookup rows left by a partial import.
	 *
	 * This is what makes the HPOS gate safe: delete_order() has no stats-row guard, so
	 * it clears the lookup rows whether or not a stats row survived.
	 */
	public function test_deleting_cpt_refund_clears_orphaned_lookup_rows(): void {
		global $wpdb;

		if ( OrderUtil::custom_orders_table_usage_is_enabled() ) {
			$this->markTestSkipped( 'Test requires CPT to be the authoritative store.' );
		}

		$order = WC_Helper_Order::create_order();
		$order->update_status( 'completed' );
		$order_id = $order->get_id();

		$items  = array_values( $order->get_items() );
		$refund = wc_create_refund(
			array(
				'order_id'   => $order_id,
				'amount'     => 10,
				'line_items' => array(
					$items[0]->get_id() => array(
						'qty'          => 1,
						'refund_total' => 10,
					),
				),
			)
		);
		$this->assertNotInstanceOf( WP_Error::class, $refund );
		$refund_id = $refund->get_id();

		OrdersScheduler::import( $order_id );
		OrdersScheduler::import( $refund_id );

		// Simulate a partial import: drop the stats row but leave the lookup rows.
		$wpdb->delete( $wpdb->prefix . 'wc_order_stats', array( 'order_id' => $refund_id ) );

		$lookup_rows = static function ( $id ) use ( $wpdb ) {
			return (int) $wpdb->get_var(
				$wpdb->prepare( "SELECT COUNT(*) FROM {$wpdb->prefix}wc_order_product_lookup WHERE order_id = %d", $id )
			);
		};
		$this->assertGreaterThan( 0, $lookup_rows( $refund_id ), 'Fixture should leave orphaned product lookup rows.' );

		$refund->delete( true );

		$this->assertSame(
			0,
			$lookup_rows( $refund_id ),
			'delete_post -> delete_order() should clear orphaned lookup rows under CPT, so gating delete_refund() to HPOS loses nothing.'
		);

		WC_Helper_Order::delete_order( $order_id );
	}

	/**
	 * @testdox Changing the first order to an excluded custom status longer than the 20-char storage limit reassigns the first-order role and marks the customer as returning.
	 */
	public function test_returning_customer_recalculated_for_long_excluded_status(): void {
		global $wpdb;

		$long_status = 'competition-completed';
		register_post_status( 'wc-' . $long_status, array( 'public' => true ) );
		$add_status = function ( $statuses ) use ( $long_status ) {
			$statuses[ 'wc-' . $long_status ] = 'Competition Completed';
			return $statuses;
		};
		add_filter( 'wc_order_statuses', $add_status );
		update_option( 'woocommerce_excluded_report_order_statuses', array( 'pending', 'failed', 'cancelled', $long_status ) );

		$customer = \WC_Helper_Customer::create_customer( 'cust_long_status', 'pwd', 'long_status_customer@mail.com' );

		$order_1 = WC_Helper_Order::create_order( $customer->get_id() );
		$order_1->set_date_created( time() - 2 * HOUR_IN_SECONDS );
		$order_1->set_status( 'processing' );
		$order_1->save();

		$order_2 = WC_Helper_Order::create_order( $customer->get_id() );
		$order_2->set_date_created( time() - HOUR_IN_SECONDS );
		$order_2->set_status( 'processing' );
		$order_2->save();

		OrdersStatsDataStore::sync_order( $order_1->get_id() );
		OrdersStatsDataStore::sync_order( $order_2->get_id() );

		$returning_flag = static function ( $id ) use ( $wpdb ) {
			return $wpdb->get_var(
				$wpdb->prepare( "SELECT returning_customer FROM {$wpdb->prefix}wc_order_stats WHERE order_id = %d", $id )
			);
		};
		$this->assertSame( '0', $returning_flag( $order_1->get_id() ), 'Oldest order should start as the non-returning first order.' );
		$this->assertSame( '1', $returning_flag( $order_2->get_id() ), 'Second order should start as returning.' );

		// Core warns when saving a status longer than the 20-char column; that
		// truncated storage is the exact scenario under test.
		$this->setExpectedIncorrectUsage( 'Abstract_WC_Order_Data_Store_CPT::get_post_status' );
		$order_1->set_status( $long_status );
		$order_1->save();

		// Reload so the order reports the truncated status actually stored in the database.
		$order_1 = wc_get_order( $order_1->get_id() );

		$this->assertTrue(
			OrdersStatsDataStore::is_returning_customer( $order_1 ),
			'An order moved to an excluded long custom status should be reported as returning.'
		);
		$this->assertSame(
			'0',
			$returning_flag( $order_2->get_id() ),
			'The next oldest order should be reassigned as the customer\'s first order.'
		);

		remove_filter( 'wc_order_statuses', $add_status );
		unset( $GLOBALS['wp_post_statuses'][ 'wc-' . $long_status ] );
	}
}
