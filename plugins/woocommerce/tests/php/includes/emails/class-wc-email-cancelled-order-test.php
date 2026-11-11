<?php
declare( strict_types = 1 );

use Automattic\WooCommerce\Internal\Email\DeferredEmailQueue;
use Automattic\WooCommerce\Internal\Orders\UnpaidOrderTimeoutMarker;

/**
 * WC_Email_Cancelled_Order test.
 *
 * @covers WC_Email_Cancelled_Order
 */
class WC_Email_Cancelled_Order_Test extends \WC_Unit_Test_Case {

	private const OPTION_KEY = 'woocommerce_cancelled_order_settings';

	private const NOTIFICATION_HOOKS = array(
		'woocommerce_order_status_pending_to_cancelled_notification',
		'woocommerce_order_status_processing_to_cancelled_notification',
		'woocommerce_order_status_on-hold_to_cancelled_notification',
	);

	/**
	 * Hook calls captured from woocommerce_email_skipped.
	 *
	 * @var array
	 */
	private $skipped = array();

	/**
	 * Hook calls captured from woocommerce_email_disabled.
	 *
	 * @var array
	 */
	private $disabled = array();

	/**
	 * Load the email classes, which are not loaded by default.
	 */
	public function setUp(): void {
		parent::setUp();

		$bootstrap = \WC_Unit_Tests_Bootstrap::instance();
		require_once $bootstrap->plugin_dir . '/includes/emails/class-wc-email.php';
		require_once $bootstrap->plugin_dir . '/includes/emails/class-wc-email-cancelled-order.php';

		WC()->mailer()->init();

		$this->skipped  = array();
		$this->disabled = array();
		add_action( 'woocommerce_email_skipped', array( $this, 'capture_skipped' ), 10, 2 );
		add_action( 'woocommerce_email_disabled', array( $this, 'capture_disabled' ) );
	}

	/**
	 * Clean up the option, hooks and deferred queue.
	 */
	public function tearDown(): void {
		delete_option( self::OPTION_KEY );
		remove_action( 'woocommerce_email_skipped', array( $this, 'capture_skipped' ), 10 );
		remove_action( 'woocommerce_email_disabled', array( $this, 'capture_disabled' ) );
		remove_all_filters( 'woocommerce_queue_class' );
		remove_action( 'woocommerce_order_status_pending_to_cancelled', array( \WC_Emails::class, 'queue_transactional_email' ), 10 );
		$this->set_wc_emails_deferred_queue( null );
		$this->reset_queue_singleton();
		parent::tearDown();
	}

	/**
	 * Capture woocommerce_email_skipped.
	 *
	 * @param string $reason   Skip reason.
	 * @param string $email_id Email ID.
	 */
	public function capture_skipped( $reason, $email_id ): void {
		$this->skipped[] = array( $reason, $email_id );
	}

	/**
	 * Capture woocommerce_email_disabled.
	 *
	 * @param string $email_id Email ID.
	 */
	public function capture_disabled( $email_id ): void {
		$this->disabled[] = $email_id;
	}

	/**
	 * @testdox The automatic cancellations setting is a checkbox right after Enable/Disable and defaults to yes.
	 */
	public function test_send_on_unpaid_timeout_defaults_to_yes(): void {
		update_option( self::OPTION_KEY, array( 'enabled' => 'yes' ) );
		$sut = new WC_Email_Cancelled_Order();

		$keys = array_keys( $sut->form_fields );

		$this->assertSame( 'yes', $sut->get_option( 'send_on_unpaid_timeout' ) );
		$this->assertSame( 'checkbox', $sut->form_fields['send_on_unpaid_timeout']['type'] );
		$this->assertSame( 'send_on_unpaid_timeout', $keys[ array_search( 'enabled', $keys, true ) + 1 ] );
	}

	/**
	 * @testdox Trigger with marked $marked and setting $setting sends: $expect_sent.
	 * @dataProvider provide_trigger_cases
	 *
	 * @param bool   $marked      Whether the unpaid order timer cancelled the order.
	 * @param string $setting     Value of the send_on_unpaid_timeout setting.
	 * @param bool   $expect_sent Whether the email is expected to be sent.
	 */
	public function test_trigger_respects_unpaid_timeout_setting( bool $marked, string $setting, bool $expect_sent ): void {
		$order = $this->create_cancelled_order( $marked );
		$sut   = $this->make_email( $setting );

		$mailer       = tests_retrieve_phpmailer_instance();
		$sent_before  = count( $mailer->mock_sent );
		$notes_before = $this->count_sent_notes( $order, $sut );

		$sut->trigger( $order->get_id(), $order );

		$this->assertSame( $expect_sent ? 1 : 0, count( $mailer->mock_sent ) - $sent_before );
		$this->assertSame( $expect_sent ? 1 : 0, $this->count_sent_notes( $order, $sut ) - $notes_before );
		$this->assertSame(
			$expect_sent ? array() : array( array( WC_Email::SKIP_REASON_UNPAID_TIMEOUT, 'cancelled_order' ) ),
			$this->skipped
		);
	}

	/**
	 * Cases for the trigger test.
	 *
	 * @return array
	 */
	public function provide_trigger_cases(): array {
		return array(
			'timer cancel, setting off' => array( true, 'no', false ),
			'timer cancel, setting on'  => array( true, 'yes', true ),
			'other cancel, setting off' => array( false, 'no', true ),
			'other cancel, setting on'  => array( false, 'yes', true ),
		);
	}

	/**
	 * @testdox A disabled email fires the disabled hook, not the skipped hook.
	 */
	public function test_disabled_email_fires_disabled_not_skipped(): void {
		$order = $this->create_cancelled_order( true );
		$sut   = $this->make_email( 'no', 'no' );

		$mailer      = tests_retrieve_phpmailer_instance();
		$sent_before = count( $mailer->mock_sent );

		$sut->trigger( $order->get_id(), $order );

		$this->assertSame( array( 'cancelled_order' ), $this->disabled );
		$this->assertSame( array(), $this->skipped );
		$this->assertSame( $sent_before, count( $mailer->mock_sent ) );
	}

	/**
	 * @testdox An order the timer cancelled, then reopened ($intermediate) and cancelled by hand, sends the email.
	 * @dataProvider provide_intermediate_statuses
	 *
	 * @param string $intermediate Status the order passes through before the manual cancellation.
	 */
	public function test_order_cancelled_again_by_hand_sends_with_setting_off( string $intermediate ): void {
		$order = $this->create_cancelled_order( true );
		$sut   = $this->make_email( 'no' );

		$order->update_status( $intermediate );
		$order->update_status( 'cancelled' );
		wc_get_container()->get( \Automattic\WooCommerce\Caches\OrderCache::class )->remove( $order->get_id() );

		$mailer      = tests_retrieve_phpmailer_instance();
		$sent_before = count( $mailer->mock_sent );

		$sut->trigger( $order->get_id() );

		$this->assertSame( 1, count( $mailer->mock_sent ) - $sent_before );
		$this->assertSame( array(), $this->skipped );
	}

	/**
	 * Intermediate statuses.
	 *
	 * @return array
	 */
	public function provide_intermediate_statuses(): array {
		return array(
			'pending'    => array( 'pending' ),
			'processing' => array( 'processing' ),
			'on-hold'    => array( 'on-hold' ),
		);
	}

	/**
	 * @testdox A deferred email reads the marker from the reloaded order (setting $setting, reopened: $reopened).
	 * @dataProvider provide_deferred_cases
	 *
	 * @param string $setting     Value of the send_on_unpaid_timeout setting.
	 * @param bool   $reopened    Whether the order goes back to pending before the queued email runs.
	 * @param bool   $expect_sent Whether the email is expected to be sent.
	 */
	public function test_deferred_email_reads_marker_after_reload( string $setting, bool $reopened, bool $expect_sent ): void {
		$this->reset_queue_singleton();
		add_filter(
			'woocommerce_queue_class',
			function () {
				return \WC_Admin_Test_Action_Queue::class;
			}
		);
		$queue = new DeferredEmailQueue();
		$this->set_wc_emails_deferred_queue( $queue );
		remove_action( 'woocommerce_order_status_pending_to_cancelled', array( \WC_Emails::class, 'send_transactional_email' ), 10 );
		add_action( 'woocommerce_order_status_pending_to_cancelled', array( \WC_Emails::class, 'queue_transactional_email' ), 10, 10 );

		// The real status change pushes the email onto the deferred queue instead of sending it.
		$order = $this->create_cancelled_order( true );
		$sut   = $this->make_email( $setting );
		$queue->dispatch();

		if ( $reopened ) {
			$order->set_status( 'pending' );
			$order->save();
		}
		wc_get_container()->get( \Automattic\WooCommerce\Caches\OrderCache::class )->remove( $order->get_id() );

		$action = null;
		foreach ( \WC_Queue::instance()->actions as $candidate ) {
			if ( 'woocommerce_send_queued_transactional_email' === $candidate['hook'] && 'woocommerce_order_status_pending_to_cancelled' === $candidate['args'][0] ) {
				$action = $candidate;
			}
		}
		$this->assertNotNull( $action, 'The cancelled email should be queued.' );

		$notes_before = $this->count_sent_notes( $order, $sut );
		$round_trip   = json_decode( wp_json_encode( $action['args'] ), true );
		$queue->send_queued_transactional_email( ...$round_trip );

		$this->assertSame( $expect_sent ? 1 : 0, $this->count_sent_notes( $order, $sut ) - $notes_before );
	}

	/**
	 * Deferred cases.
	 *
	 * @return array
	 */
	public function provide_deferred_cases(): array {
		return array(
			'setting off'                 => array( 'no', false, false ),
			'setting on'                  => array( 'yes', false, true ),
			'setting off, order reopened' => array( 'no', true, false ),
		);
	}

	/**
	 * Detach the cancelled email listeners so real status changes do not send emails.
	 */
	private function detach_notification_hooks(): void {
		foreach ( self::NOTIFICATION_HOOKS as $hook ) {
			remove_all_actions( $hook );
		}
	}

	/**
	 * Create the email with the given settings.
	 *
	 * @param string $send_on_timeout Value of send_on_unpaid_timeout.
	 * @param string $enabled         Value of enabled.
	 * @return WC_Email_Cancelled_Order
	 */
	private function make_email( string $send_on_timeout, string $enabled = 'yes' ): WC_Email_Cancelled_Order {
		update_option(
			self::OPTION_KEY,
			array(
				'enabled'                => $enabled,
				'recipient'              => 'admin@example.com',
				'send_on_unpaid_timeout' => $send_on_timeout,
			)
		);
		return new WC_Email_Cancelled_Order();
	}

	/**
	 * Cancel a pending order, through the timer when $by_timer is true, with the email listeners detached.
	 *
	 * @param bool $by_timer Whether the unpaid order timer cancels it.
	 * @return WC_Order
	 */
	private function create_cancelled_order( bool $by_timer ): WC_Order {
		$order = WC_Helper_Order::create_order();
		$order->set_status( 'pending' );
		$order->save();

		$this->detach_notification_hooks();

		if ( $by_timer ) {
			wc_get_container()->get( UnpaidOrderTimeoutMarker::class )->cancel_unpaid_order( $order, 'Unpaid order cancelled - time limit reached.' );
		} else {
			$order->update_status( 'cancelled' );
		}

		return $order;
	}

	/**
	 * Count the "email sent" notes of an email on an order.
	 *
	 * @param WC_Order                 $order Order.
	 * @param WC_Email_Cancelled_Order $email Email.
	 * @return int
	 */
	private function count_sent_notes( WC_Order $order, WC_Email_Cancelled_Order $email ): int {
		$expected = sprintf( 'Email "%s" sent.', $email->get_title() );
		$count    = 0;
		foreach ( wc_get_order_notes( array( 'order_id' => $order->get_id() ) ) as $note ) {
			if ( $expected === $note->content ) {
				++$count;
			}
		}
		return $count;
	}

	/**
	 * Reset the WC_Queue singleton so the test queue filter takes effect.
	 */
	private function reset_queue_singleton(): void {
		$reflection = new \ReflectionClass( \WC_Queue::class );
		$instance   = $reflection->getProperty( 'instance' );
		$instance->setAccessible( true );
		$instance->setValue( null, null );
	}

	/**
	 * Set the deferred queue used by WC_Emails.
	 *
	 * @param DeferredEmailQueue|null $queue Deferred email queue instance.
	 */
	private function set_wc_emails_deferred_queue( ?DeferredEmailQueue $queue ): void {
		$reflection     = new \ReflectionClass( \WC_Emails::class );
		$deferred_queue = $reflection->getProperty( 'deferred_queue' );
		$deferred_queue->setAccessible( true );
		$deferred_queue->setValue( null, $queue );
	}
}
