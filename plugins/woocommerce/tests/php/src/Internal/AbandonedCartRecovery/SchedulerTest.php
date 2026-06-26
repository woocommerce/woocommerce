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
	 * @testdox init() registers the new-order, update-order, status-changed, trash, delete, and AS-callback hooks so a fresh container resolve wires the schedule + cancel + dispatch listeners in one place.
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
		$this->assertNotFalse( has_action( 'woocommerce_update_order', array( $this->sut, 'handle_order_update' ) ) );
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
		 * @since 10.9.0
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
		$order->set_status( OrderStatus::PENDING );
		$order->update_meta_data( WC_Email_Customer_Abandoned_Cart_Recovery::META_KEY_SENT_AT, (string) time() );
		$order->save();

		$this->sut->handle_new_order( $order->get_id() );

		$fresh = wc_get_order( $order->get_id() );
		$this->assertSame( '', $fresh->get_meta( Scheduler::SCHEDULED_META_KEY ) );
	}

	/**
	 * @testdox handle_order_update() schedules the AS action and records the scheduled-at meta for a checkout-draft order — the Blocks Store API path, where woocommerce_new_order never fires.
	 */
	public function test_handle_order_update_schedules_for_checkout_draft_order(): void {
		$order = OrderHelper::create_order();
		$order->set_status( OrderStatus::CHECKOUT_DRAFT );
		$order->save();

		$this->sut->handle_order_update( $order->get_id() );

		$fresh = wc_get_order( $order->get_id() );
		$this->assertNotEmpty(
			$fresh->get_meta( Scheduler::SCHEDULED_META_KEY ),
			'Scheduled-at meta must be populated after handle_order_update() schedules a checkout-draft send.'
		);
		$this->assertNotFalse(
			as_next_scheduled_action( Scheduler::ACTION_HOOK, array( $order->get_id() ) ),
			'An AS action must be queued for the checkout-draft order.'
		);
	}

	/**
	 * @testdox handle_order_update() is a no-op for a non-draft order (e.g. pending) — those are scheduled by handle_new_order, so the update path must not also handle them.
	 */
	public function test_handle_order_update_skips_non_draft_status(): void {
		$order = OrderHelper::create_order();
		$order->set_status( OrderStatus::PENDING );
		$order->save();

		$this->sut->handle_order_update( $order->get_id() );

		$fresh = wc_get_order( $order->get_id() );
		$this->assertSame( '', $fresh->get_meta( Scheduler::SCHEDULED_META_KEY ) );
		$this->assertFalse( as_next_scheduled_action( Scheduler::ACTION_HOOK, array( $order->get_id() ) ) );
	}

	/**
	 * @testdox handle_order_update() does not schedule a checkout-draft order that has no billing email yet — avoids queuing an Action Scheduler job that would only bail at trigger time for lack of a recipient.
	 */
	public function test_handle_order_update_skips_checkout_draft_without_recipient(): void {
		$order = OrderHelper::create_order();
		$order->set_status( OrderStatus::CHECKOUT_DRAFT );
		$order->set_billing_email( '' );
		$order->save();

		$this->sut->handle_order_update( $order->get_id() );

		$fresh = wc_get_order( $order->get_id() );
		$this->assertSame( '', $fresh->get_meta( Scheduler::SCHEDULED_META_KEY ) );
		$this->assertFalse( as_next_scheduled_action( Scheduler::ACTION_HOOK, array( $order->get_id() ) ) );
	}

	/**
	 * @testdox handle_order_update() does not stack schedules: the Store API re-saves a draft many times, but the SCHEDULED_META_KEY guard leaves exactly one queued send.
	 */
	public function test_handle_order_update_is_idempotent(): void {
		$order = OrderHelper::create_order();
		$order->set_status( OrderStatus::CHECKOUT_DRAFT );
		$order->save();

		// Simulate the Store API firing woocommerce_update_order several times as
		// the customer works through checkout.
		$this->sut->handle_order_update( $order->get_id() );
		$first_when = (string) wc_get_order( $order->get_id() )->get_meta( Scheduler::SCHEDULED_META_KEY );

		$this->sut->handle_order_update( $order->get_id() );
		$this->sut->handle_order_update( $order->get_id() );
		$second_when = (string) wc_get_order( $order->get_id() )->get_meta( Scheduler::SCHEDULED_META_KEY );

		$this->assertSame( $first_when, $second_when, 'Repeated draft updates must not reschedule the send.' );

		$scheduled = as_get_scheduled_actions(
			array(
				'hook' => Scheduler::ACTION_HOOK,
				'args' => array( $order->get_id() ),
			),
			'ids'
		);
		$this->assertCount( 1, $scheduled, 'Repeated draft updates must leave exactly one scheduled action, not one per fire.' );
	}

	/**
	 * @testdox A checkout-draft order scheduled via handle_order_update() is not re-scheduled when it later becomes pending and fires handle_new_order() — the two scheduling hooks share the SCHEDULED_META_KEY guard, so a draft promoted at the payment step keeps its single send.
	 */
	public function test_draft_then_pending_does_not_double_schedule(): void {
		$order = OrderHelper::create_order();
		$order->set_status( OrderStatus::CHECKOUT_DRAFT );
		$order->save();

		$this->sut->handle_order_update( $order->get_id() );
		$draft_when = (string) wc_get_order( $order->get_id() )->get_meta( Scheduler::SCHEDULED_META_KEY );
		$this->assertNotEmpty( $draft_when, 'Precondition: the checkout-draft order must be scheduled.' );

		// Customer proceeds to payment: the draft is promoted to pending, which
		// fires woocommerce_new_order → handle_new_order.
		$order->set_status( OrderStatus::PENDING );
		$order->save();
		$this->sut->handle_new_order( $order->get_id() );

		$pending_when = (string) wc_get_order( $order->get_id() )->get_meta( Scheduler::SCHEDULED_META_KEY );
		$this->assertSame( $draft_when, $pending_when, 'The pending transition must not reschedule an already-scheduled draft.' );

		$scheduled = as_get_scheduled_actions(
			array(
				'hook' => Scheduler::ACTION_HOOK,
				'args' => array( $order->get_id() ),
			),
			'ids'
		);
		$this->assertCount( 1, $scheduled, 'A draft promoted to pending must still have exactly one scheduled send.' );
	}

	/**
	 * @testdox A checkout-draft order re-saved through the live woocommerce_update_order path is scheduled — the wiring-level companion proving the Store API re-save (not just a direct method call) reaches the scheduler.
	 */
	public function test_checkout_draft_order_is_scheduled_through_live_woocommerce_update_order(): void {
		// Park an order in checkout-draft the way the Blocks Store API does. Do
		// this before wiring init() so the setup saves don't fire the scheduler.
		$order = OrderHelper::create_order();
		$order->set_status( OrderStatus::CHECKOUT_DRAFT );
		$order->save();

		$this->assertSame(
			'',
			wc_get_order( $order->get_id() )->get_meta( Scheduler::SCHEDULED_META_KEY ),
			'Sanity: the draft must not be scheduled before the live hooks are wired.'
		);

		// Wire the production hooks, then reproduce the Store API re-saving the
		// draft as the customer fills in checkout — a real save that fires
		// woocommerce_update_order while the order is still checkout-draft.
		$this->sut->init();
		$order->set_billing_email( 'shopper@example.com' );
		$order->save();

		$fresh = wc_get_order( $order->get_id() );
		$this->assertNotEmpty(
			$fresh->get_meta( Scheduler::SCHEDULED_META_KEY ),
			'A checkout-draft order re-saved through the live woocommerce_update_order path must be scheduled.'
		);
		$this->assertNotFalse(
			as_next_scheduled_action( Scheduler::ACTION_HOOK, array( $order->get_id() ) ),
			'The live update path must queue an AS action for the checkout-draft order.'
		);
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
	 * @testdox handle_status_changed() cancels the queued send when a checkout-draft order leaves the abandoned set (checkout-draft → processing).
	 */
	public function test_handle_status_changed_cancels_when_checkout_draft_completes(): void {
		$order = OrderHelper::create_order();
		$order->set_status( OrderStatus::CHECKOUT_DRAFT );
		$order->save();
		$this->sut->handle_order_update( $order->get_id() );
		$this->assertNotEmpty(
			wc_get_order( $order->get_id() )->get_meta( Scheduler::SCHEDULED_META_KEY ),
			'Precondition: the checkout-draft order must be scheduled before the transition.'
		);

		$this->sut->handle_status_changed( $order->get_id(), OrderStatus::CHECKOUT_DRAFT, OrderStatus::PROCESSING );

		$fresh = wc_get_order( $order->get_id() );
		$this->assertSame( '', $fresh->get_meta( Scheduler::SCHEDULED_META_KEY ) );
		$this->assertFalse( as_next_scheduled_action( Scheduler::ACTION_HOOK, array( $order->get_id() ) ) );
	}

	/**
	 * @testdox handle_status_changed() leaves the schedule alone on a transition within the abandoned set (pending → checkout-draft) so moving between classic and Blocks checkout keeps the queued nudge.
	 */
	public function test_handle_status_changed_leaves_schedule_within_abandoned_set(): void {
		$order = $this->schedule_for_pending_order();

		$this->sut->handle_status_changed( $order->get_id(), OrderStatus::PENDING, OrderStatus::CHECKOUT_DRAFT );

		$fresh = wc_get_order( $order->get_id() );
		$this->assertNotEmpty(
			$fresh->get_meta( Scheduler::SCHEDULED_META_KEY ),
			'In-set transitions must not cancel the queued send.'
		);
	}

	/**
	 * @testdox handle_status_changed() does nothing when the previous status was already outside the abandoned set — nothing to cancel.
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
		$order->set_status( OrderStatus::PENDING );
		$order->save();

		$this->sut->handle_new_order( $order->get_id() );

		return wc_get_order( $order->get_id() );
	}
}
