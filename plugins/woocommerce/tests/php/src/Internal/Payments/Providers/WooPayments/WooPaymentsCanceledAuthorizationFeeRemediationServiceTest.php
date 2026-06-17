<?php
declare( strict_types = 1 );

namespace Automattic\WooCommerce\Tests\Internal\Payments\Providers\WooPayments;

use Automattic\WooCommerce\Internal\Payments\Providers\WooPayments\WooPaymentsCanceledAuthorizationFeeRemediationService;
use Automattic\WooCommerce\Tests\Internal\Payments\StaticNativeRuntimeArbiter;
use WC_Helper_Order;
use WC_Order;
use WC_Order_Refund;
use WC_Unit_Test_Case;

/**
 * Tests for the WooPaymentsCanceledAuthorizationFeeRemediationService class.
 */
class WooPaymentsCanceledAuthorizationFeeRemediationServiceTest extends WC_Unit_Test_Case {

	/**
	 * System under test.
	 *
	 * @var WooPaymentsCanceledAuthorizationFeeRemediationService
	 */
	private $sut;

	/**
	 * Set up test fixtures.
	 */
	public function setUp(): void {
		parent::setUp();
		$this->sut = $this->create_service( true );
		$this->cleanup_state();
	}

	/**
	 * Tear down test fixtures.
	 */
	public function tearDown(): void {
		$this->remove_hooks( $this->sut );
		$this->cleanup_state();
		parent::tearDown();
	}

	/**
	 * @testdox Preserved remediation hooks are registered only when native owns runtime.
	 */
	public function test_registers_preserved_actions_when_native_owns_runtime(): void {
		$this->sut->register();

		$this->assertSame( 10, has_action( WooPaymentsCanceledAuthorizationFeeRemediationService::ACTION_HOOK, array( $this->sut, 'process_batch' ) ) );
		$this->assertSame( 10, has_action( WooPaymentsCanceledAuthorizationFeeRemediationService::DRY_RUN_ACTION_HOOK, array( $this->sut, 'process_batch_dry_run' ) ) );
		$this->assertSame( 10, has_action( WooPaymentsCanceledAuthorizationFeeRemediationService::CHECK_AFFECTED_ORDERS_HOOK, array( $this->sut, 'check_and_cache_affected_orders' ) ) );
	}

	/**
	 * @testdox Remediation hooks are not registered while the standalone plugin owns runtime.
	 */
	public function test_registers_no_actions_when_plugin_owns_runtime(): void {
		$service = $this->create_service( false );

		$service->register();

		$this->assertFalse( has_action( WooPaymentsCanceledAuthorizationFeeRemediationService::ACTION_HOOK, array( $service, 'process_batch' ) ) );
		$this->assertFalse( has_action( WooPaymentsCanceledAuthorizationFeeRemediationService::DRY_RUN_ACTION_HOOK, array( $service, 'process_batch_dry_run' ) ) );
		$this->assertFalse( has_action( WooPaymentsCanceledAuthorizationFeeRemediationService::CHECK_AFFECTED_ORDERS_HOOK, array( $service, 'check_and_cache_affected_orders' ) ) );
	}

	/**
	 * @testdox Affected order lookup preserves the WooPayments canceled-authorization query semantics.
	 */
	public function test_get_affected_orders_finds_canceled_orders_with_fees(): void {
		$order = $this->create_affected_order( 'cancelled' );

		$orders = $this->sut->get_affected_orders( 10 );

		$this->assertCount( 1, $orders );
		$this->assertSame( $order->get_id(), $orders[0]->get_id() );
	}

	/**
	 * @testdox Remediation deletes only WooPayments refunds and removes stale fee metadata.
	 */
	public function test_remediate_order_deletes_only_wcpay_refunds_and_removes_fee_metadata(): void {
		$order = $this->create_affected_order( 'cancelled' );

		$this->create_refund( $order, 'WooPayments refund', 're_wcpay' );
		$this->create_refund( $order, 'Manual refund' );

		$this->sut->remediate_order( wc_get_order( $order->get_id() ) );

		$order   = wc_get_order( $order->get_id() );
		$refunds = $order->get_refunds();

		$this->assertSame( 'cancelled', $order->get_status() );
		$this->assertSame( '', $order->get_meta( '_wcpay_transaction_fee', true ) );
		$this->assertSame( '', $order->get_meta( '_wcpay_net', true ) );
		$this->assertCount( 1, $refunds );
		$this->assertSame( 'Manual refund', $refunds[0]->get_reason() );
	}

	/**
	 * @testdox Remediation deletes the stale WooCommerce Analytics stats row for deleted WooPayments refunds.
	 */
	public function test_remediate_order_deletes_refund_order_stats(): void {
		$order  = $this->create_affected_order( 'cancelled' );
		$refund = $this->create_refund( $order, 'WooPayments refund', 're_wcpay' );

		$this->create_order_stats_row( $refund->get_id(), $order->get_id() );

		$this->assertSame( 1, $this->get_order_stats_row_count( $refund->get_id() ) );

		$this->sut->remediate_order( wc_get_order( $order->get_id() ) );

		$this->assertSame( 0, $this->get_order_stats_row_count( $refund->get_id() ) );
	}

	/**
	 * @testdox Remediation aborts without cleanup when refund deletion is vetoed.
	 */
	public function test_remediate_order_aborts_when_refund_deletion_is_vetoed(): void {
		$order  = $this->create_affected_order( 'cancelled' );
		$refund = $this->create_refund( $order, 'WooPayments refund', 're_wcpay' );
		$filter = static function ( $check, $data_object ) use ( $refund ) {
			if ( $data_object instanceof WC_Order_Refund && $refund->get_id() === $data_object->get_id() ) {
				return false;
			}

			return $check;
		};

		add_filter( 'woocommerce_pre_delete_order_refund', $filter, 10, 2 );
		try {
			$result = $this->sut->remediate_order( wc_get_order( $order->get_id() ) );
		} finally {
			remove_filter( 'woocommerce_pre_delete_order_refund', $filter, 10 );
		}

		$order = wc_get_order( $order->get_id() );

		$this->assertFalse( $result );
		$this->assertSame( '1.50', $order->get_meta( '_wcpay_transaction_fee', true ) );
		$this->assertSame( '48.50', $order->get_meta( '_wcpay_net', true ) );
		$this->assertCount( 1, $order->get_refunds() );
	}

	/**
	 * @testdox A failed live remediation batch does not advance the cursor past the failed order.
	 */
	public function test_process_batch_does_not_advance_cursor_after_refund_deletion_failure(): void {
		$failed_order = $this->create_affected_order( 'cancelled' );
		$next_order   = $this->create_affected_order( 'cancelled' );
		$refund       = $this->create_refund( $failed_order, 'WooPayments refund', 're_wcpay' );
		$filter       = static function ( $check, $data_object ) use ( $refund ) {
			if ( $data_object instanceof WC_Order_Refund && $refund->get_id() === $data_object->get_id() ) {
				return false;
			}

			return $check;
		};

		add_filter( 'woocommerce_pre_delete_order_refund', $filter, 10, 2 );
		try {
			$this->sut->process_batch();
		} finally {
			remove_filter( 'woocommerce_pre_delete_order_refund', $filter, 10 );
		}

		$stats = $this->sut->get_stats();

		$this->assertSame( 1, $stats['processed'] );
		$this->assertSame( 0, $stats['remediated'] );
		$this->assertSame( 1, $stats['errors'] );
		$this->assertSame( 0, $this->sut->get_last_order_id() );
		$this->assertSame( '1.50', wc_get_order( $next_order->get_id() )->get_meta( '_wcpay_transaction_fee', true ) );
	}

	/**
	 * @testdox Remediation corrects refunded canceled authorizations back to cancelled.
	 */
	public function test_remediate_order_changes_refunded_status_to_cancelled(): void {
		$order = $this->create_affected_order( 'refunded' );

		$this->sut->remediate_order( wc_get_order( $order->get_id() ) );

		$order = wc_get_order( $order->get_id() );

		$this->assertSame( 'cancelled', $order->get_status() );
	}

	/**
	 * @testdox Dry-run remediation leaves orders, refunds, and metadata untouched.
	 */
	public function test_remediate_order_dry_run_does_not_mutate_data(): void {
		$order = $this->create_affected_order( 'cancelled' );

		$this->create_refund( $order, 'WooPayments refund', 're_wcpay' );

		$this->sut->remediate_order( wc_get_order( $order->get_id() ), true );

		$order = wc_get_order( $order->get_id() );

		$this->assertSame( 'cancelled', $order->get_status() );
		$this->assertSame( '1.50', $order->get_meta( '_wcpay_transaction_fee', true ) );
		$this->assertSame( '48.50', $order->get_meta( '_wcpay_net', true ) );
		$this->assertCount( 1, $order->get_refunds() );
	}

	/**
	 * @testdox Processing a live batch updates stats and reschedules the next batch in the legacy hyphenated group.
	 */
	public function test_process_batch_reschedules_next_batch_with_legacy_group(): void {
		$this->create_affected_order( 'cancelled' );

		$this->sut->process_batch();

		$stats = $this->sut->get_stats();
		$this->assertSame( 1, $stats['processed'] );
		$this->assertSame( 1, $stats['remediated'] );
		$this->assertNotFalse(
			as_has_scheduled_action(
				WooPaymentsCanceledAuthorizationFeeRemediationService::ACTION_HOOK,
				array(),
				WooPaymentsCanceledAuthorizationFeeRemediationService::ACTION_SCHEDULER_GROUP_ID
			)
		);
	}

	/**
	 * @testdox The affected-orders check caches whether remediation work remains.
	 */
	public function test_check_and_cache_affected_orders_updates_state_option(): void {
		$this->create_affected_order( 'cancelled' );

		$this->sut->check_and_cache_affected_orders();

		$this->assertSame( 'has_affected_orders', get_option( WooPaymentsCanceledAuthorizationFeeRemediationService::CHECK_STATE_OPTION_KEY ) );
	}

	/**
	 * Create a remediation service.
	 *
	 * @param bool $native Whether native owns runtime.
	 * @return WooPaymentsCanceledAuthorizationFeeRemediationService
	 */
	private function create_service( bool $native ): WooPaymentsCanceledAuthorizationFeeRemediationService {
		$service = new WooPaymentsCanceledAuthorizationFeeRemediationService();
		$service->init( new StaticNativeRuntimeArbiter( $native ) );

		return $service;
	}

	/**
	 * Create an affected WooPayments order.
	 *
	 * @param string $status Order status without the wc- prefix.
	 * @return WC_Order
	 */
	private function create_affected_order( string $status ): WC_Order {
		$order = WC_Helper_Order::create_order();
		$order->set_date_created( '2023-05-01' );
		$order->set_status( $status );
		$order->update_meta_data( '_intention_status', 'canceled' );
		$order->update_meta_data( '_wcpay_transaction_fee', '1.50' );
		$order->update_meta_data( '_wcpay_net', '48.50' );
		$order->update_meta_data( '_wcpay_refund_id', 're_order' );
		$order->update_meta_data( '_wcpay_refund_status', 'succeeded' );
		$order->save();

		return $order;
	}

	/**
	 * Create a refund fixture for an order.
	 *
	 * @param WC_Order $order           Parent order.
	 * @param string   $reason          Refund reason.
	 * @param string   $wcpay_refund_id Optional WooPayments refund ID.
	 * @return WC_Order_Refund
	 */
	private function create_refund( WC_Order $order, string $reason, string $wcpay_refund_id = '' ): WC_Order_Refund {
		$refund = wc_create_refund(
			array(
				'order_id'       => $order->get_id(),
				'amount'         => 5,
				'reason'         => $reason,
				'refund_payment' => false,
			)
		);

		if ( ! $refund instanceof WC_Order_Refund ) {
			$this->fail( 'Expected refund fixture creation to return a WC_Order_Refund.' );
		}

		if ( '' !== $wcpay_refund_id ) {
			$refund->update_meta_data( '_wcpay_refund_id', $wcpay_refund_id );
			$refund->save_meta_data();
		}

		return $refund;
	}

	/**
	 * Create an analytics order stats fixture.
	 *
	 * @param int $order_id  Order or refund ID.
	 * @param int $parent_id Parent order ID.
	 */
	private function create_order_stats_row( int $order_id, int $parent_id = 0 ): void {
		global $wpdb;

		$wpdb->insert(
			$wpdb->prefix . 'wc_order_stats',
			array(
				'order_id'         => $order_id,
				'parent_id'        => $parent_id,
				'date_created'     => '2023-05-01 00:00:00',
				'date_created_gmt' => '2023-05-01 00:00:00',
				'date_paid'        => '2023-05-01 00:00:00',
				'date_completed'   => '2023-05-01 00:00:00',
				'num_items_sold'   => 0,
				'total_sales'      => -5,
				'tax_total'        => 0,
				'shipping_total'   => 0,
				'net_total'        => -5,
				'status'           => 'wc-completed',
				'customer_id'      => 0,
			)
		);
	}

	/**
	 * Count analytics stats rows for an order or refund ID.
	 *
	 * @param int $order_id Order or refund ID.
	 * @return int
	 */
	private function get_order_stats_row_count( int $order_id ): int {
		global $wpdb;

		return (int) $wpdb->get_var(
			$wpdb->prepare(
				"SELECT COUNT(*) FROM {$wpdb->prefix}wc_order_stats WHERE order_id = %d",
				$order_id
			)
		);
	}

	/**
	 * Remove hooks registered by a service instance.
	 *
	 * @param WooPaymentsCanceledAuthorizationFeeRemediationService $service Service instance.
	 */
	private function remove_hooks( WooPaymentsCanceledAuthorizationFeeRemediationService $service ): void {
		remove_action( WooPaymentsCanceledAuthorizationFeeRemediationService::ACTION_HOOK, array( $service, 'process_batch' ) );
		remove_action( WooPaymentsCanceledAuthorizationFeeRemediationService::DRY_RUN_ACTION_HOOK, array( $service, 'process_batch_dry_run' ) );
		remove_action( WooPaymentsCanceledAuthorizationFeeRemediationService::CHECK_AFFECTED_ORDERS_HOOK, array( $service, 'check_and_cache_affected_orders' ) );
	}

	/**
	 * Clean state shared by remediation tests.
	 */
	private function cleanup_state(): void {
		delete_option( WooPaymentsCanceledAuthorizationFeeRemediationService::STATUS_OPTION_KEY );
		delete_option( WooPaymentsCanceledAuthorizationFeeRemediationService::LAST_ORDER_ID_OPTION_KEY );
		delete_option( WooPaymentsCanceledAuthorizationFeeRemediationService::BATCH_SIZE_OPTION_KEY );
		delete_option( WooPaymentsCanceledAuthorizationFeeRemediationService::STATS_OPTION_KEY );
		delete_option( WooPaymentsCanceledAuthorizationFeeRemediationService::DRY_RUN_OPTION_KEY );
		delete_option( WooPaymentsCanceledAuthorizationFeeRemediationService::CHECK_STATE_OPTION_KEY );

		if ( function_exists( 'as_unschedule_all_actions' ) ) {
			as_unschedule_all_actions( WooPaymentsCanceledAuthorizationFeeRemediationService::ACTION_HOOK, null, WooPaymentsCanceledAuthorizationFeeRemediationService::ACTION_SCHEDULER_GROUP_ID );
			as_unschedule_all_actions( WooPaymentsCanceledAuthorizationFeeRemediationService::DRY_RUN_ACTION_HOOK, null, WooPaymentsCanceledAuthorizationFeeRemediationService::ACTION_SCHEDULER_GROUP_ID );
			as_unschedule_all_actions( WooPaymentsCanceledAuthorizationFeeRemediationService::CHECK_AFFECTED_ORDERS_HOOK, null, WooPaymentsCanceledAuthorizationFeeRemediationService::ACTION_SCHEDULER_GROUP_ID );
		}
	}
}
