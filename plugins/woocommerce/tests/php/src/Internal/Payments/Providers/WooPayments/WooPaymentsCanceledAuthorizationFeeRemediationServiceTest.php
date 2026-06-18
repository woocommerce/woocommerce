<?php
declare( strict_types = 1 );

namespace Automattic\WooCommerce\Tests\Internal\Payments\Providers\WooPayments;

use ActionScheduler_Store;
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
	 * The System Under Test.
	 *
	 * @var WooPaymentsCanceledAuthorizationFeeRemediationService
	 */
	private $sut;

	/**
	 * Set up test fixtures.
	 */
	public function setUp(): void {
		parent::setUp();
		$this->cleanup_state();
		$this->sut = $this->create_service( true );
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
	 * @testdox Preserved remediation hooks are registered when native owns runtime.
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
	 * @testdox A refunded WooPayments order with a canceled intent is affected even without fee metadata.
	 */
	public function test_has_affected_orders_detects_refunded_canceled_intent(): void {
		$this->create_order_fixture(
			array(
				'status'           => 'refunded',
				'with_fee_meta'    => false,
				'with_refund_meta' => false,
			)
		);

		$this->assertTrue( $this->sut->has_affected_orders(), 'Refunded canceled WooPayments orders should be affected.' );
	}

	/**
	 * @testdox A cancelled WooPayments order with a canceled intent and fee metadata is affected.
	 */
	public function test_has_affected_orders_detects_cancelled_canceled_intent_with_fee(): void {
		$order = $this->create_order_fixture( array( 'status' => 'cancelled' ) );

		$orders = $this->sut->get_affected_orders( 10 );

		$this->assertTrue( $this->sut->has_affected_orders(), 'Cancelled canceled WooPayments orders with fees should be affected.' );
		$this->assertCount( 1, $orders, 'Only the affected order should be hydrated.' );
		$this->assertSame( $order->get_id(), $orders[0]->get_id() );
	}

	/**
	 * @testdox Clean, non-canceled, and pre-bug-date orders are not affected.
	 */
	public function test_has_affected_orders_ignores_unaffected_orders(): void {
		$this->create_order_fixture(
			array(
				'status'           => 'processing',
				'intent_status'    => null,
				'with_fee_meta'    => false,
				'with_refund_meta' => false,
			)
		);
		$this->create_order_fixture( array( 'intent_status' => 'requires_capture' ) );
		$this->create_order_fixture( array( 'date_created' => '2023-03-31' ) );

		$this->assertFalse( $this->sut->has_affected_orders(), 'Unaffected orders should not trigger remediation.' );
	}

	/**
	 * @testdox Affected-order lookup preserves the reference query even when payment method metadata is nonstandard.
	 */
	public function test_has_affected_orders_does_not_filter_by_payment_method(): void {
		$order = $this->create_order_fixture(
			array(
				'payment_method' => 'woocommerce_payments_card',
				'status'         => 'cancelled',
			)
		);

		$orders = $this->sut->get_affected_orders( 10 );

		$this->assertCount( 1, $orders, 'The reference migration uses canceled-intent state rather than exact payment method metadata.' );
		$this->assertSame( $order->get_id(), $orders[0]->get_id() );
	}

	/**
	 * @testdox ensure_scheduled schedules the preserved main hook for affected stores.
	 */
	public function test_ensure_scheduled_schedules_main_hook_when_affected_orders_exist(): void {
		$this->create_order_fixture( array( 'status' => 'cancelled' ) );

		$result = $this->sut->ensure_scheduled();

		$this->assertSame( 'scheduled', $result );
		$this->assertSame( 'running', get_option( WooPaymentsCanceledAuthorizationFeeRemediationService::STATUS_OPTION_KEY ) );
		$this->assertSame( 'has_affected_orders', get_option( WooPaymentsCanceledAuthorizationFeeRemediationService::CHECK_STATE_OPTION_KEY ) );
		$this->assertNotFalse(
			as_has_scheduled_action(
				WooPaymentsCanceledAuthorizationFeeRemediationService::ACTION_HOOK,
				array(),
				WooPaymentsCanceledAuthorizationFeeRemediationService::ACTION_SCHEDULER_GROUP_ID
			),
			'The preserved remediation action should be scheduled in the WooPayments group.'
		);
	}

	/**
	 * @testdox ensure_scheduled returns completed without scheduling duplicate work.
	 */
	public function test_ensure_scheduled_returns_completed_when_remediation_is_complete(): void {
		$this->create_order_fixture( array( 'status' => 'cancelled' ) );
		update_option( WooPaymentsCanceledAuthorizationFeeRemediationService::STATUS_OPTION_KEY, 'completed' );

		$result = $this->sut->ensure_scheduled();

		$this->assertSame( 'completed', $result );
		$this->assertSame( 0, $this->get_scheduled_action_count( WooPaymentsCanceledAuthorizationFeeRemediationService::ACTION_HOOK ) );
	}

	/**
	 * @testdox ensure_scheduled returns already_scheduled without creating duplicate actions.
	 */
	public function test_ensure_scheduled_returns_already_scheduled_without_duplicate_action(): void {
		$this->create_order_fixture( array( 'status' => 'cancelled' ) );
		as_schedule_single_action(
			time() + MINUTE_IN_SECONDS,
			WooPaymentsCanceledAuthorizationFeeRemediationService::ACTION_HOOK,
			array(),
			WooPaymentsCanceledAuthorizationFeeRemediationService::ACTION_SCHEDULER_GROUP_ID
		);

		$result = $this->sut->ensure_scheduled();

		$this->assertSame( 'already_scheduled', $result );
		$this->assertSame( 1, $this->get_scheduled_action_count( WooPaymentsCanceledAuthorizationFeeRemediationService::ACTION_HOOK ) );
	}

	/**
	 * @testdox ensure_scheduled returns not_needed for clean stores.
	 */
	public function test_ensure_scheduled_returns_not_needed_when_no_affected_orders_exist(): void {
		$result = $this->sut->ensure_scheduled();

		$this->assertSame( 'not_needed', $result );
		$this->assertSame( 'no_affected_orders', get_option( WooPaymentsCanceledAuthorizationFeeRemediationService::CHECK_STATE_OPTION_KEY ) );
		$this->assertSame( 0, $this->get_scheduled_action_count( WooPaymentsCanceledAuthorizationFeeRemediationService::ACTION_HOOK ) );
		$this->assertNotSame( 'running', get_option( WooPaymentsCanceledAuthorizationFeeRemediationService::STATUS_OPTION_KEY ) );
	}

	/**
	 * @testdox ensure_scheduled returns unavailable when Action Scheduler cannot accept work.
	 */
	public function test_ensure_scheduled_returns_unavailable_when_action_scheduler_is_missing(): void {
		$service = new class() extends WooPaymentsCanceledAuthorizationFeeRemediationService {
			/**
			 * Simulate Action Scheduler being unavailable.
			 *
			 * @return bool
			 */
			protected function is_action_scheduler_available(): bool {
				return false;
			}
		};
		$service->init( new StaticNativeRuntimeArbiter( true ) );
		$this->create_order_fixture( array( 'status' => 'cancelled' ) );

		$result = $service->ensure_scheduled();

		$this->assertSame( 'unavailable', $result );
		$this->assertSame( 'has_affected_orders', get_option( WooPaymentsCanceledAuthorizationFeeRemediationService::CHECK_STATE_OPTION_KEY ) );
		$this->assertSame( 0, $this->get_scheduled_action_count( WooPaymentsCanceledAuthorizationFeeRemediationService::ACTION_HOOK ) );
		$this->assertNotSame( 'running', get_option( WooPaymentsCanceledAuthorizationFeeRemediationService::STATUS_OPTION_KEY ) );
	}

	/**
	 * @testdox Cutover preflight blocks affected stores when Action Scheduler cannot accept work.
	 */
	public function test_can_schedule_cutover_remediation_blocks_affected_store_without_action_scheduler(): void {
		$service = new class() extends WooPaymentsCanceledAuthorizationFeeRemediationService {
			/**
			 * Simulate Action Scheduler being unavailable.
			 *
			 * @return bool
			 */
			protected function is_action_scheduler_available(): bool {
				return false;
			}
		};
		$service->init( new StaticNativeRuntimeArbiter( true ) );
		$this->create_order_fixture( array( 'status' => 'cancelled' ) );

		$this->assertFalse( $service->can_schedule_cutover_remediation() );
	}

	/**
	 * @testdox ensure_scheduled replaces stale dry-runs with a live remediation job.
	 */
	public function test_ensure_scheduled_replaces_dry_run_with_live_job(): void {
		$this->create_order_fixture( array( 'status' => 'cancelled' ) );
		update_option(
			WooPaymentsCanceledAuthorizationFeeRemediationService::STATS_OPTION_KEY,
			array(
				'processed'  => 3,
				'remediated' => 3,
				'errors'     => 0,
			)
		);
		$this->sut->schedule_dry_run();

		$this->assertNotFalse(
			as_has_scheduled_action(
				WooPaymentsCanceledAuthorizationFeeRemediationService::DRY_RUN_ACTION_HOOK,
				array(),
				WooPaymentsCanceledAuthorizationFeeRemediationService::ACTION_SCHEDULER_GROUP_ID
			)
		);

		$result = $this->sut->ensure_scheduled();

		$this->assertSame( 'scheduled', $result );
		$this->assertFalse(
			as_has_scheduled_action(
				WooPaymentsCanceledAuthorizationFeeRemediationService::DRY_RUN_ACTION_HOOK,
				array(),
				WooPaymentsCanceledAuthorizationFeeRemediationService::ACTION_SCHEDULER_GROUP_ID
			),
			'Cutover should cancel stale dry-run jobs before scheduling live remediation.'
		);
		$this->assertNotFalse(
			as_has_scheduled_action(
				WooPaymentsCanceledAuthorizationFeeRemediationService::ACTION_HOOK,
				array(),
				WooPaymentsCanceledAuthorizationFeeRemediationService::ACTION_SCHEDULER_GROUP_ID
			)
		);
		$this->assertFalse( (bool) get_option( WooPaymentsCanceledAuthorizationFeeRemediationService::DRY_RUN_OPTION_KEY, false ) );
		$this->assertSame(
			array(
				'processed'  => 0,
				'remediated' => 0,
				'errors'     => 0,
			),
			$this->sut->get_stats()
		);
	}

	/**
	 * @testdox ensure_scheduled resets a completed dry-run cursor before checking for live work.
	 */
	public function test_ensure_scheduled_resets_completed_dry_run_cursor_before_live_lookup(): void {
		$this->create_order_fixture( array( 'status' => 'cancelled' ) );

		$this->sut->schedule_dry_run();
		$this->sut->process_batch_dry_run();
		$this->assertGreaterThan( 0, $this->sut->get_last_order_id(), 'Dry-run should advance the shared legacy cursor.' );

		$result = $this->sut->ensure_scheduled();

		$this->assertSame( 'scheduled', $result );
		$this->assertSame( 0, $this->sut->get_last_order_id(), 'Cutover should reset the dry-run cursor before live lookup.' );
		$this->assertNotFalse(
			as_has_scheduled_action(
				WooPaymentsCanceledAuthorizationFeeRemediationService::ACTION_HOOK,
				array(),
				WooPaymentsCanceledAuthorizationFeeRemediationService::ACTION_SCHEDULER_GROUP_ID
			),
			'Cutover must queue live remediation even when dry-run already scanned every affected order.'
		);
	}

	/**
	 * @testdox Remediation deletes only WooPayments refunds, removes stale metadata, and records a detailed note.
	 */
	public function test_remediate_order_deletes_only_wcpay_refunds_and_removes_fee_metadata(): void {
		$order         = $this->create_order_fixture( array( 'status' => 'cancelled' ) );
		$wcpay_refund  = $this->create_refund( $order, 'WooPayments refund', 're_wcpay' );
		$manual_refund = $this->create_refund( $order, 'Manual refund' );
		$order->set_status( 'refunded' );
		$order->save();

		$result = $this->sut->remediate_order( wc_get_order( $order->get_id() ) );
		$order  = wc_get_order( $order->get_id() );
		$notes  = $this->get_order_notes_content( $order );

		$this->assertTrue( $result );
		$this->assertSame( 'cancelled', $order->get_status() );
		$this->assertSame( '', $order->get_meta( '_wcpay_transaction_fee', true ) );
		$this->assertSame( '', $order->get_meta( '_wcpay_net', true ) );
		$this->assertSame( '', $order->get_meta( '_wcpay_refund_id', true ) );
		$this->assertSame( '', $order->get_meta( '_wcpay_refund_status', true ) );
		$this->assertFalse( (bool) wc_get_order( $wcpay_refund->get_id() ) );
		$this->assertInstanceOf( WC_Order_Refund::class, wc_get_order( $manual_refund->get_id() ) );
		$this->assertStringContainsString( 'Removed incorrect data from canceled authorization', $notes );
		$this->assertStringContainsString( 'No actual payment or refund occurred.', $notes );
	}

	/**
	 * @testdox Remediation deletes the stale WooCommerce Analytics stats row for deleted WooPayments refunds.
	 */
	public function test_remediate_order_deletes_refund_order_stats(): void {
		$order  = $this->create_order_fixture( array( 'status' => 'cancelled' ) );
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
		$order  = $this->create_order_fixture( array( 'status' => 'cancelled' ) );
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
		$failed_order = $this->create_order_fixture( array( 'status' => 'cancelled' ) );
		$next_order   = $this->create_order_fixture( array( 'status' => 'cancelled' ) );
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
	 * @testdox Dry-run remediation leaves orders, refunds, metadata, and notes untouched.
	 */
	public function test_remediate_order_dry_run_does_not_mutate_data(): void {
		$order  = $this->create_order_fixture( array( 'status' => 'cancelled' ) );
		$refund = $this->create_refund( $order, 'WooPayments refund', 're_wcpay' );
		$order->set_status( 'refunded' );
		$order->save();

		$this->sut->remediate_order( wc_get_order( $order->get_id() ), true );

		$order = wc_get_order( $order->get_id() );

		$this->assertSame( 'refunded', $order->get_status() );
		$this->assertSame( '1.50', $order->get_meta( '_wcpay_transaction_fee', true ) );
		$this->assertSame( '48.50', $order->get_meta( '_wcpay_net', true ) );
		$this->assertSame( 're_order', $order->get_meta( '_wcpay_refund_id', true ) );
		$this->assertInstanceOf( WC_Order_Refund::class, wc_get_order( $refund->get_id() ) );
		$this->assertStringNotContainsString( 'Removed incorrect data from canceled authorization', $this->get_order_notes_content( $order ) );
	}

	/**
	 * @testdox Processing a live batch updates stats and reschedules the next batch in the legacy hyphenated group.
	 */
	public function test_process_batch_reschedules_next_batch_with_legacy_group(): void {
		$this->create_order_fixture( array( 'status' => 'cancelled' ) );

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
	 * @testdox Processing an empty follow-up batch marks remediation complete and cleans processing options.
	 */
	public function test_process_batch_marks_complete_when_no_affected_orders_remain(): void {
		$this->create_order_fixture( array( 'status' => 'cancelled' ) );

		$this->sut->process_batch();
		$this->sut->process_batch();

		$this->assertSame( 'completed', get_option( WooPaymentsCanceledAuthorizationFeeRemediationService::STATUS_OPTION_KEY ) );
		$this->assertSame( 0, $this->sut->get_last_order_id() );
		$this->assertSame( WooPaymentsCanceledAuthorizationFeeRemediationService::INITIAL_BATCH_SIZE, $this->sut->get_batch_size() );
	}

	/**
	 * @testdox The affected-orders check caches when remediation work remains.
	 */
	public function test_check_and_cache_affected_orders_updates_state_option_for_affected_store(): void {
		$this->create_order_fixture( array( 'status' => 'cancelled' ) );

		$this->sut->check_and_cache_affected_orders();

		$this->assertSame( 'has_affected_orders', get_option( WooPaymentsCanceledAuthorizationFeeRemediationService::CHECK_STATE_OPTION_KEY ) );
	}

	/**
	 * @testdox The affected-orders check caches when no remediation work remains.
	 */
	public function test_check_and_cache_affected_orders_updates_state_option_for_clean_store(): void {
		$this->sut->check_and_cache_affected_orders();

		$this->assertSame( 'no_affected_orders', get_option( WooPaymentsCanceledAuthorizationFeeRemediationService::CHECK_STATE_OPTION_KEY ) );
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
	 * Create an order fixture.
	 *
	 * @param array<string,mixed> $args Fixture arguments.
	 * @return WC_Order
	 */
	private function create_order_fixture( array $args = array() ): WC_Order {
		$args = wp_parse_args(
			$args,
			array(
				'date_created'     => '2023-05-01',
				'intent_status'    => 'canceled',
				'payment_method'   => 'woocommerce_payments',
				'status'           => 'cancelled',
				'with_fee_meta'    => true,
				'with_refund_meta' => true,
			)
		);

		$order = WC_Helper_Order::create_order();
		$order->set_date_created( (string) $args['date_created'] );
		$order->set_payment_method( (string) $args['payment_method'] );
		$order->set_status( (string) $args['status'] );

		if ( null !== $args['intent_status'] ) {
			$order->update_meta_data( '_intention_status', (string) $args['intent_status'] );
		}

		if ( (bool) $args['with_fee_meta'] ) {
			$order->update_meta_data( '_wcpay_transaction_fee', '1.50' );
			$order->update_meta_data( '_wcpay_net', '48.50' );
		}

		if ( (bool) $args['with_refund_meta'] ) {
			$order->update_meta_data( '_wcpay_refund_id', 're_order' );
			$order->update_meta_data( '_wcpay_refund_status', 'succeeded' );
		}

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
	 * Get all order note content for assertions.
	 *
	 * @param WC_Order $order Order object.
	 * @return string Notes content.
	 */
	private function get_order_notes_content( WC_Order $order ): string {
		$content = array();
		$notes   = wc_get_order_notes( array( 'order_id' => $order->get_id() ) );

		foreach ( $notes as $note ) {
			$content[] = (string) $note->content;
		}

		return implode( "\n", $content );
	}

	/**
	 * Count pending scheduled actions for a hook.
	 *
	 * @param string $hook Hook name.
	 * @return int Scheduled action count.
	 */
	private function get_scheduled_action_count( string $hook ): int {
		if ( ! function_exists( 'as_get_scheduled_actions' ) ) {
			return 0;
		}

		return count(
			as_get_scheduled_actions(
				array(
					'hook'   => $hook,
					'group'  => WooPaymentsCanceledAuthorizationFeeRemediationService::ACTION_SCHEDULER_GROUP_ID,
					'status' => ActionScheduler_Store::STATUS_PENDING,
				)
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
