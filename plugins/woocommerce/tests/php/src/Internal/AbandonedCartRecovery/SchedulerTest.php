<?php
declare( strict_types = 1 );

namespace Automattic\WooCommerce\Tests\Internal\AbandonedCartRecovery;

use Automattic\WooCommerce\Enums\OrderStatus;
use Automattic\WooCommerce\Internal\AbandonedCartRecovery\Scheduler;
use Automattic\WooCommerce\RestApi\UnitTests\Helpers\OrderHelper;
use WC_Email_Customer_Abandoned_Cart_Recovery;
use WC_Order;
use WC_Unit_Test_Case;

/**
 * Scheduler test.
 *
 * @covers \Automattic\WooCommerce\Internal\AbandonedCartRecovery\Scheduler
 */
class SchedulerTest extends WC_Unit_Test_Case {

	/**
	 * The System Under Test.
	 *
	 * @var Scheduler
	 */
	private $sut;

	/**
	 * The email class instance — needed so the scheduler's `get_email()` lookup
	 * finds something in the mailer registry.
	 *
	 * @var WC_Email_Customer_Abandoned_Cart_Recovery
	 */
	private $email;

	/**
	 * Snapshot of `active_plugins` taken in setUp so tests that mock a known
	 * recovery handler can restore the original list in tearDown.
	 *
	 * @var array
	 */
	private $original_active_plugins = array();

	/**
	 * Enable the feature flag, force-include the email class, re-init the
	 * mailer so it picks up the registration, then resolve the SUT.
	 */
	public function setUp(): void {
		parent::setUp();

		update_option( 'woocommerce_feature_abandoned_cart_recovery_enabled', 'yes' );
		$this->original_active_plugins = (array) get_option( 'active_plugins', array() );

		$bootstrap = \WC_Unit_Tests_Bootstrap::instance();
		require_once $bootstrap->plugin_dir . '/includes/emails/class-wc-email.php';
		require_once $bootstrap->plugin_dir . '/includes/emails/class-wc-email-customer-abandoned-cart-recovery.php';

		WC()->mailer()->init();

		// Grab the mailer's registered instance — the Scheduler's `get_email()`
		// returns this same instance, so option updates from the test propagate
		// to the SUT instead of being applied to a parallel object.
		$emails      = WC()->mailer()->get_emails();
		$this->email = $emails['WC_Email_Customer_Abandoned_Cart_Recovery'];
		$this->email->update_option( 'enabled', 'yes' );
		$this->email->enabled = 'yes';
		$this->email->update_option( 'automated', 'yes' );

		$this->sut = wc_get_container()->get( Scheduler::class );

		add_action( Scheduler::ACTION_HOOK, array( $this->sut, 'handle_scheduled_send' ), 10, 1 );
	}

	/**
	 * Reset settings + cancel any leftover scheduled actions between tests.
	 */
	public function tearDown(): void {
		remove_action( Scheduler::ACTION_HOOK, array( $this->sut, 'handle_scheduled_send' ), 10 );

		delete_option( 'woocommerce_feature_abandoned_cart_recovery_enabled' );
		delete_option( 'woocommerce_customer_abandoned_cart_recovery_settings' );
		update_option( 'active_plugins', $this->original_active_plugins );

		as_unschedule_all_actions( Scheduler::ACTION_HOOK );

		parent::tearDown();
	}

	/**
	 * @testdox init() registers the new-order, status-changed, trash, delete, and AS-callback hooks so a fresh container resolve wires the schedule + cancel + dispatch listeners in one place.
	 */
	public function test_init_registers_hooks(): void {
		// setUp() pre-registers ACTION_HOOK so the dispatch test works without
		// init() (which would also wire woocommerce_new_order and auto-fire on
		// every OrderHelper::create_order). Tear that fixture shortcut down
		// here so this test asserts the production wiring rather than passing
		// on the setUp registration.
		remove_action( Scheduler::ACTION_HOOK, array( $this->sut, 'handle_scheduled_send' ), 10 );
		$this->assertFalse(
			has_action( Scheduler::ACTION_HOOK, array( $this->sut, 'handle_scheduled_send' ) ),
			'Fixture cleanup precondition: ACTION_HOOK must be unregistered before init() is asserted.'
		);

		// init() ran when the container first resolved Scheduler, but WP's
		// test framework has since restored `$wp_filter` past that point.
		// Re-invoke here so the assertions exercise the production wiring.
		$this->sut->init();

		$this->assertNotFalse( has_action( 'woocommerce_new_order', array( $this->sut, 'handle_new_order' ) ) );
		$this->assertNotFalse( has_action( 'woocommerce_order_status_changed', array( $this->sut, 'handle_status_changed' ) ) );
		$this->assertNotFalse( has_action( 'woocommerce_trash_order', array( $this->sut, 'handle_cancellation' ) ) );
		$this->assertNotFalse( has_action( 'woocommerce_before_delete_order', array( $this->sut, 'handle_cancellation' ) ) );
		$this->assertNotFalse( has_action( Scheduler::ACTION_HOOK, array( $this->sut, 'handle_scheduled_send' ) ) );
	}

	/**
	 * @testdox handle_scheduled_send() resolves the email lazily and dispatches the send — the path AS uses on its WP-Cron firing context where the email class isn't already loaded.
	 */
	public function test_handle_scheduled_send_dispatches_to_email(): void {
		$order = OrderHelper::create_order();
		$order->set_created_via( 'checkout' );
		$order->set_status( OrderStatus::PENDING );
		$order->save();
		$order->set_date_created( time() - WC_Email_Customer_Abandoned_Cart_Recovery::ABANDONMENT_THRESHOLD_SECONDS - MINUTE_IN_SECONDS );
		$order->save();
		$order = wc_get_order( $order->get_id() );

		$mailer = tests_retrieve_phpmailer_instance();
		$before = count( $mailer->mock_sent );

		$this->sut->handle_scheduled_send( $order->get_id() );

		$this->assertSame( $before + 1, count( $mailer->mock_sent ), 'AS-fired callback must dispatch one email.' );
		$fresh = wc_get_order( $order->get_id() );
		$this->assertNotEmpty(
			$fresh->get_meta( WC_Email_Customer_Abandoned_Cart_Recovery::META_KEY_SENT_AT ),
			'Successful send must record the sent_at meta so the dedup gate works on subsequent fires.'
		);
	}

	/**
	 * @testdox handle_scheduled_send() records a "sent automatically" order note when the send actually goes out, so the audit trail mirrors the manual-send path.
	 */
	public function test_handle_scheduled_send_records_order_note_on_success(): void {
		$order = OrderHelper::create_order();
		$order->set_created_via( 'checkout' );
		$order->set_status( OrderStatus::PENDING );
		$order->save();
		$order->set_date_created( time() - WC_Email_Customer_Abandoned_Cart_Recovery::ABANDONMENT_THRESHOLD_SECONDS - MINUTE_IN_SECONDS );
		$order->save();

		$this->sut->handle_scheduled_send( $order->get_id() );

		$notes        = wc_get_order_notes( array( 'order_id' => $order->get_id() ) );
		$note_strings = wp_list_pluck( $notes, 'content' );
		$this->assertNotEmpty(
			array_filter(
				$note_strings,
				static fn ( $note ) => false !== strpos( $note, 'sent automatically' )
			),
			'Successful auto-send must add a "sent automatically" order note.'
		);
	}

	/**
	 * @testdox handle_scheduled_send() does NOT add a note when trigger() bails (already sent, disabled, suppressed, unsubscribed) — no audit row for a non-event.
	 */
	public function test_handle_scheduled_send_skips_note_when_trigger_bails(): void {
		$order = OrderHelper::create_order();
		$order->set_status( OrderStatus::PENDING );
		// Mark the order as already sent so trigger() bails on the dedup gate.
		$order->update_meta_data( WC_Email_Customer_Abandoned_Cart_Recovery::META_KEY_SENT_AT, (string) ( time() - HOUR_IN_SECONDS ) );
		$order->save();
		$order->set_date_created( time() - WC_Email_Customer_Abandoned_Cart_Recovery::ABANDONMENT_THRESHOLD_SECONDS - MINUTE_IN_SECONDS );
		$order->save();

		$this->sut->handle_scheduled_send( $order->get_id() );

		$notes        = wc_get_order_notes( array( 'order_id' => $order->get_id() ) );
		$note_strings = wp_list_pluck( $notes, 'content' );
		$this->assertEmpty(
			array_filter(
				$note_strings,
				static fn ( $note ) => false !== strpos( $note, 'sent automatically' )
			),
			'Dedup-gated trigger() must not record a "sent automatically" order note.'
		);
	}

	/**
	 * @testdox do_action( Scheduler::ACTION_HOOK, $order_id ) reaches handle_scheduled_send so the production WP-Cron dispatch path is wired without the email class being instantiated up-front.
	 */
	public function test_action_dispatch_reaches_handle_scheduled_send(): void {
		$order = OrderHelper::create_order();
		$order->set_created_via( 'checkout' );
		$order->set_status( OrderStatus::PENDING );
		$order->save();
		$order->set_date_created( time() - WC_Email_Customer_Abandoned_Cart_Recovery::ABANDONMENT_THRESHOLD_SECONDS - MINUTE_IN_SECONDS );
		$order->save();

		$mailer = tests_retrieve_phpmailer_instance();
		$before = count( $mailer->mock_sent );

		/**
		 * Fires the Action Scheduler callback that dispatches the abandoned
		 * cart recovery email — simulated here so the test exercises the
		 * registered handler end-to-end.
		 *
		 * @since 11.0.0
		 *
		 * @param int $order_id The order to dispatch the recovery email for.
		 */
		do_action( Scheduler::ACTION_HOOK, $order->get_id() );

		$this->assertSame( $before + 1, count( $mailer->mock_sent ) );
	}

	/**
	 * @testdox handle_new_order() schedules the AS action and records the scheduled-at meta for a pending order when automated + enabled.
	 */
	public function test_handle_new_order_schedules_for_pending_order(): void {
		$order = OrderHelper::create_order();
		$order->set_created_via( 'checkout' );
		$order->set_status( OrderStatus::PENDING );
		$order->save();

		$this->sut->handle_new_order( $order->get_id() );

		$fresh = wc_get_order( $order->get_id() );
		$this->assertNotEmpty(
			$fresh->get_meta( Scheduler::SCHEDULED_META_KEY ),
			'Scheduled-at meta must be populated after handle_new_order() schedules the send.'
		);
		$this->assertNotFalse(
			as_next_scheduled_action( Scheduler::ACTION_HOOK, array( $order->get_id() ) ),
			'An AS action must be queued for the new pending order.'
		);
	}

	/**
	 * @testdox handle_new_order() is a no-op when the order is created in a non-abandoned status (e.g. processing).
	 */
	public function test_handle_new_order_skips_non_abandoned_status(): void {
		$order = OrderHelper::create_order();
		$order->set_created_via( 'checkout' );
		$order->set_status( OrderStatus::PROCESSING );
		$order->save();

		$this->sut->handle_new_order( $order->get_id() );

		$fresh = wc_get_order( $order->get_id() );
		$this->assertSame( '', $fresh->get_meta( Scheduler::SCHEDULED_META_KEY ) );
		$this->assertFalse( as_next_scheduled_action( Scheduler::ACTION_HOOK, array( $order->get_id() ) ) );
	}

	/**
	 * @testdox handle_new_order() is a no-op when the merchant has turned off automated scheduling — the email stays manual-send-only.
	 */
	public function test_handle_new_order_skips_when_not_automated(): void {
		$this->email->update_option( 'automated', 'no' );

		$order = OrderHelper::create_order();
		$order->set_created_via( 'checkout' );
		$order->set_status( OrderStatus::PENDING );
		$order->save();

		$this->sut->handle_new_order( $order->get_id() );

		$fresh = wc_get_order( $order->get_id() );
		$this->assertSame( '', $fresh->get_meta( Scheduler::SCHEDULED_META_KEY ) );
	}

	/**
	 * @testdox handle_new_order() is a no-op when the email itself is disabled, so the dropdown gate and the scheduler agree on what "off" means.
	 */
	public function test_handle_new_order_skips_when_email_disabled(): void {
		$this->email->update_option( 'enabled', 'no' );
		$this->email->enabled = 'no';

		$order = OrderHelper::create_order();
		$order->set_created_via( 'checkout' );
		$order->set_status( OrderStatus::PENDING );
		$order->save();

		$this->sut->handle_new_order( $order->get_id() );

		$fresh = wc_get_order( $order->get_id() );
		$this->assertSame( '', $fresh->get_meta( Scheduler::SCHEDULED_META_KEY ) );
	}

	/**
	 * @testdox handle_new_order() is a no-op when the suppress filter returns true, so partner plugins that handle recovery themselves don't see a duplicate send queued.
	 */
	public function test_handle_new_order_skips_when_suppressed(): void {
		$order = OrderHelper::create_order();
		$order->set_created_via( 'checkout' );
		$order->set_status( OrderStatus::PENDING );
		$order->save();

		add_filter( 'woocommerce_abandoned_cart_recovery_suppress', '__return_true' );
		try {
			$this->sut->handle_new_order( $order->get_id() );
		} finally {
			remove_filter( 'woocommerce_abandoned_cart_recovery_suppress', '__return_true' );
		}

		$fresh = wc_get_order( $order->get_id() );
		$this->assertSame( '', $fresh->get_meta( Scheduler::SCHEDULED_META_KEY ) );
	}

	/**
	 * @testdox handle_new_order() does not stack schedules: a second call for the same order id is a no-op once SCHEDULED_META_KEY is set.
	 */
	public function test_handle_new_order_is_idempotent(): void {
		$order = OrderHelper::create_order();
		$order->set_created_via( 'checkout' );
		$order->set_status( OrderStatus::PENDING );
		$order->save();

		$this->sut->handle_new_order( $order->get_id() );
		$fresh      = wc_get_order( $order->get_id() );
		$first_when = (string) $fresh->get_meta( Scheduler::SCHEDULED_META_KEY );

		$this->sut->handle_new_order( $order->get_id() );
		$fresh       = wc_get_order( $order->get_id() );
		$second_when = (string) $fresh->get_meta( Scheduler::SCHEDULED_META_KEY );

		$this->assertSame( $first_when, $second_when, 'Repeat new-order events must not reschedule the send.' );
	}

	/**
	 * @testdox handle_new_order() refuses to schedule when the order is already marked as sent — defense against re-creating a schedule for an order that already received the email.
	 */
	public function test_handle_new_order_skips_when_already_sent(): void {
		$order = OrderHelper::create_order();
		$order->set_created_via( 'checkout' );
		$order->set_status( OrderStatus::PENDING );
		$order->update_meta_data( WC_Email_Customer_Abandoned_Cart_Recovery::META_KEY_SENT_AT, (string) time() );
		$order->save();

		$this->sut->handle_new_order( $order->get_id() );

		$fresh = wc_get_order( $order->get_id() );
		$this->assertSame( '', $fresh->get_meta( Scheduler::SCHEDULED_META_KEY ) );
	}

	/**
	 * @testdox handle_new_order() is a no-op for pending orders not created by a customer checkout (admin invoices, REST API, renewals) — only abandoned checkouts get the automated nudge.
	 * @dataProvider provider_non_checkout_origins
	 *
	 * @param string $created_via Order-creation origin to test.
	 */
	public function test_handle_new_order_skips_non_checkout_origin( string $created_via ): void {
		$order = OrderHelper::create_order();
		$order->set_created_via( $created_via );
		$order->set_status( OrderStatus::PENDING );
		$order->save();

		$this->sut->handle_new_order( $order->get_id() );

		$fresh = wc_get_order( $order->get_id() );
		$this->assertSame( '', $fresh->get_meta( Scheduler::SCHEDULED_META_KEY ) );
		$this->assertFalse( as_next_scheduled_action( Scheduler::ACTION_HOOK, array( $order->get_id() ) ) );
	}

	/**
	 * Non-checkout order-creation origins.
	 *
	 * @return array<string, array<string>>
	 */
	public function provider_non_checkout_origins(): array {
		return array(
			'admin invoice'        => array( 'admin' ),
			'REST API'             => array( 'rest-api' ),
			'subscription renewal' => array( 'subscription' ),
		);
	}

	/**
	 * @testdox handle_new_order() schedules for a store-api (block checkout) order, so both checkout flows are covered by default.
	 */
	public function test_handle_new_order_schedules_for_store_api_origin(): void {
		$order = OrderHelper::create_order();
		$order->set_created_via( 'store-api' );
		$order->set_status( OrderStatus::PENDING );
		$order->save();

		$this->sut->handle_new_order( $order->get_id() );

		$fresh = wc_get_order( $order->get_id() );
		$this->assertNotEmpty( $fresh->get_meta( Scheduler::SCHEDULED_META_KEY ) );
		$this->assertNotFalse( as_next_scheduled_action( Scheduler::ACTION_HOOK, array( $order->get_id() ) ) );
	}

	/**
	 * @testdox handle_new_order() honors the woocommerce_abandoned_cart_recovery_eligible_statuses filter: an order created in a widened status (e.g. failed) is scheduled, matching the send/manual paths.
	 */
	public function test_handle_new_order_schedules_for_filter_widened_status(): void {
		$widen = static function ( $statuses ) {
			$statuses[] = OrderStatus::FAILED;
			return $statuses;
		};

		$order = OrderHelper::create_order();
		$order->set_created_via( 'checkout' );
		$order->set_status( OrderStatus::FAILED );
		$order->save();

		add_filter( 'woocommerce_abandoned_cart_recovery_eligible_statuses', $widen );
		try {
			$this->sut->handle_new_order( $order->get_id() );
		} finally {
			remove_filter( 'woocommerce_abandoned_cart_recovery_eligible_statuses', $widen );
		}

		$fresh = wc_get_order( $order->get_id() );
		$this->assertNotEmpty(
			$fresh->get_meta( Scheduler::SCHEDULED_META_KEY ),
			'A status added via the eligible-statuses filter must be scheduled like the default abandoned statuses.'
		);
		$this->assertNotFalse( as_next_scheduled_action( Scheduler::ACTION_HOOK, array( $order->get_id() ) ) );
	}

	/**
	 * @testdox handle_status_changed() keeps the send queued when the order moves between statuses inside the filter-widened eligible set (e.g. pending → failed with `failed` added).
	 */
	public function test_handle_status_changed_keeps_schedule_within_widened_set(): void {
		$order = $this->schedule_for_pending_order();

		$widen = static function ( $statuses ) {
			$statuses[] = OrderStatus::FAILED;
			return $statuses;
		};

		add_filter( 'woocommerce_abandoned_cart_recovery_eligible_statuses', $widen );
		try {
			$this->sut->handle_status_changed( $order->get_id(), OrderStatus::PENDING, OrderStatus::FAILED );
		} finally {
			remove_filter( 'woocommerce_abandoned_cart_recovery_eligible_statuses', $widen );
		}

		$fresh = wc_get_order( $order->get_id() );
		$this->assertNotEmpty(
			$fresh->get_meta( Scheduler::SCHEDULED_META_KEY ),
			'A transition inside the widened eligible set must not cancel the queued send.'
		);
		$this->assertNotFalse( as_next_scheduled_action( Scheduler::ACTION_HOOK, array( $order->get_id() ) ) );
	}

	/**
	 * @testdox handle_status_changed() still cancels when the order exits the filter-widened eligible set (e.g. failed → processing with `failed` added).
	 */
	public function test_handle_status_changed_cancels_on_exit_from_widened_set(): void {
		$order = $this->schedule_for_pending_order();

		$widen = static function ( $statuses ) {
			$statuses[] = OrderStatus::FAILED;
			return $statuses;
		};

		add_filter( 'woocommerce_abandoned_cart_recovery_eligible_statuses', $widen );
		try {
			$this->sut->handle_status_changed( $order->get_id(), OrderStatus::FAILED, OrderStatus::PROCESSING );
		} finally {
			remove_filter( 'woocommerce_abandoned_cart_recovery_eligible_statuses', $widen );
		}

		$fresh = wc_get_order( $order->get_id() );
		$this->assertSame( '', $fresh->get_meta( Scheduler::SCHEDULED_META_KEY ) );
		$this->assertFalse( as_next_scheduled_action( Scheduler::ACTION_HOOK, array( $order->get_id() ) ) );
	}

	/**
	 * @testdox handle_scheduled_send() is a no-op when the merchant disabled automation after the send was queued — the in-flight action must honor the current setting.
	 */
	public function test_handle_scheduled_send_skips_when_automation_disabled(): void {
		$order = OrderHelper::create_order();
		$order->set_created_via( 'checkout' );
		$order->set_status( OrderStatus::PENDING );
		$order->save();
		$order->set_date_created( time() - WC_Email_Customer_Abandoned_Cart_Recovery::ABANDONMENT_THRESHOLD_SECONDS - MINUTE_IN_SECONDS );
		$order->save();

		$this->email->update_option( 'automated', 'no' );

		$mailer = tests_retrieve_phpmailer_instance();
		$before = count( $mailer->mock_sent );

		$this->sut->handle_scheduled_send( $order->get_id() );

		$this->assertSame( $before, count( $mailer->mock_sent ), 'A queued send must not dispatch once automation is toggled off.' );
		$fresh = wc_get_order( $order->get_id() );
		$this->assertSame(
			'',
			$fresh->get_meta( WC_Email_Customer_Abandoned_Cart_Recovery::META_KEY_SENT_AT ),
			'A skipped send must not record the sent_at meta.'
		);
	}

	/**
	 * @testdox A store-api order is scheduled through the real production wiring when it exits checkout-draft: the data store re-fires woocommerce_new_order on the draft → pending transition.
	 */
	public function test_store_api_draft_to_pending_transition_schedules_via_hooks(): void {
		$order = OrderHelper::create_order();
		$order->set_created_via( 'store-api' );
		$order->set_status( OrderStatus::CHECKOUT_DRAFT );
		$order->save();

		// Register the production hooks; setUp() only wires ACTION_HOOK.
		$this->sut->init();

		$order->set_status( OrderStatus::PENDING );
		$order->save();

		$fresh = wc_get_order( $order->get_id() );
		$this->assertNotEmpty(
			$fresh->get_meta( Scheduler::SCHEDULED_META_KEY ),
			'The checkout-draft → pending transition must schedule the send via the re-fired woocommerce_new_order hook.'
		);
		$this->assertNotFalse( as_next_scheduled_action( Scheduler::ACTION_HOOK, array( $order->get_id() ) ) );
	}

	/**
	 * @testdox handle_status_changed() cancels the pending send when the order transitions out of the abandoned set (e.g. pending → processing).
	 */
	public function test_handle_status_changed_cancels_on_exit_from_abandoned_set(): void {
		$order = $this->schedule_for_pending_order();

		$this->sut->handle_status_changed( $order->get_id(), OrderStatus::PENDING, OrderStatus::PROCESSING );

		$fresh = wc_get_order( $order->get_id() );
		$this->assertSame( '', $fresh->get_meta( Scheduler::SCHEDULED_META_KEY ), 'Scheduled-at meta must be cleared once the order leaves the abandoned set.' );
		$this->assertFalse( as_next_scheduled_action( Scheduler::ACTION_HOOK, array( $order->get_id() ) ) );
	}

	/**
	 * @testdox handle_status_changed() does nothing when the previous status was already outside `pending` — nothing to cancel.
	 */
	public function test_handle_status_changed_noop_when_old_status_already_outside_set(): void {
		$order = OrderHelper::create_order();
		$order->set_status( OrderStatus::PROCESSING );
		$order->save();

		// No prior schedule → just assert this path doesn't blow up and the
		// meta stays empty.
		$this->sut->handle_status_changed( $order->get_id(), OrderStatus::PROCESSING, OrderStatus::COMPLETED );

		$fresh = wc_get_order( $order->get_id() );
		$this->assertSame( '', $fresh->get_meta( Scheduler::SCHEDULED_META_KEY ) );
	}

	/**
	 * @testdox handle_cancellation() unschedules and clears the meta for a trashed order so a deleted-then-restored order doesn't fire a stale send.
	 */
	public function test_handle_cancellation_clears_state(): void {
		$order = $this->schedule_for_pending_order();

		$this->sut->handle_cancellation( $order->get_id() );

		$fresh = wc_get_order( $order->get_id() );
		$this->assertSame( '', $fresh->get_meta( Scheduler::SCHEDULED_META_KEY ) );
		$this->assertFalse( as_next_scheduled_action( Scheduler::ACTION_HOOK, array( $order->get_id() ) ) );
	}

	/**
	 * Create a pending order and run it through handle_new_order() so the
	 * tests for the cancel/status-change paths start from a known scheduled
	 * state.
	 */
	private function schedule_for_pending_order(): WC_Order {
		$order = OrderHelper::create_order();
		$order->set_created_via( 'checkout' );
		$order->set_status( OrderStatus::PENDING );
		$order->save();

		$this->sut->handle_new_order( $order->get_id() );

		return wc_get_order( $order->get_id() );
	}
}
