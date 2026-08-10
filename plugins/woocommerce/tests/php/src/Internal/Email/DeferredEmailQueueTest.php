<?php
declare( strict_types = 1 );

namespace Automattic\WooCommerce\Tests\Internal\Email;

use Automattic\WooCommerce\Internal\Email\DeferredEmailQueue;
use Automattic\WooCommerce\Internal\StockNotifications\Enums\NotificationStatus;
use Automattic\WooCommerce\Internal\StockNotifications\Notification as StockNotification;
use WC_Unit_Test_Case;

/**
 * Tests for the DeferredEmailQueue class.
 */
class DeferredEmailQueueTest extends WC_Unit_Test_Case {

	/**
	 * The System Under Test.
	 *
	 * @var DeferredEmailQueue
	 */
	private $sut;

	/**
	 * Stock notification IDs created by this test class.
	 *
	 * @var int[]
	 */
	private $created_stock_notification_ids = array();

	/**
	 * Set up test fixtures.
	 */
	public function setUp(): void {
		parent::setUp();
		$this->sut = new DeferredEmailQueue();
		$this->reset_queue_singleton();
		add_filter(
			'woocommerce_queue_class',
			function () {
				return \WC_Admin_Test_Action_Queue::class;
			}
		);
	}

	/**
	 * Tear down test fixtures.
	 */
	public function tearDown(): void {
		remove_all_filters( 'woocommerce_queue_class' );
		remove_all_filters( 'woocommerce_allow_send_queued_transactional_email' );
		remove_all_actions( 'woocommerce_send_queued_transactional_email' );
		remove_all_actions( 'woocommerce_deferred_email_test_unknown_object' );
		remove_all_actions( 'woocommerce_deferred_email_test_unknown_object_notification' );
		remove_all_actions( 'woocommerce_deferred_email_test_unsaved_product' );
		remove_all_actions( 'woocommerce_deferred_email_test_unsaved_product_notification' );
		$this->set_wc_emails_deferred_queue( null );
		$this->delete_stock_notifications();
		$this->reset_queue_singleton();
		parent::tearDown();
	}

	/**
	 * @testdox Push and dispatch schedules one AS action per email.
	 */
	public function test_push_and_dispatch_schedules_per_email(): void {
		$this->sut->push( 'woocommerce_order_status_completed', array( 123 ) );
		$this->sut->push( 'woocommerce_new_customer_note', array( 456, 'note' ) );

		$this->sut->dispatch();

		$queue = $this->get_test_queue();

		$this->assertCount( 2, $queue->actions, 'Should schedule one AS action per email' );
		$this->assertSame( 'woocommerce_send_queued_transactional_email', $queue->actions[0]['hook'] );
		$this->assertSame( 'woocommerce_send_queued_transactional_email', $queue->actions[1]['hook'] );
	}

	/**
	 * @testdox Dispatch does nothing when the queue is empty.
	 */
	public function test_dispatch_noop_when_empty(): void {
		$this->sut->dispatch();

		$queue = $this->get_test_queue();

		$this->assertEmpty( $queue->actions, 'Should not schedule any AS action when queue is empty' );
	}

	/**
	 * @testdox Dispatch clears the queue after scheduling so a second dispatch is a no-op.
	 */
	public function test_dispatch_clears_queue(): void {
		$this->sut->push( 'woocommerce_order_status_completed', array( 123 ) );
		$this->sut->dispatch();
		$this->sut->dispatch();

		$queue = $this->get_test_queue();

		$this->assertCount( 1, $queue->actions, 'Second dispatch should not schedule another action' );
	}

	/**
	 * @testdox Dispatch preserves the filter name and args for each queued email.
	 */
	public function test_dispatch_preserves_callback_data(): void {
		$this->sut->push( 'woocommerce_order_status_pending_to_processing', array( 42, 'extra' ) );
		$this->sut->dispatch();

		$queue  = $this->get_test_queue();
		$action = $queue->actions[0];

		$this->assertSame( 'woocommerce_order_status_pending_to_processing', $action['args'][0] );
		$this->assertSame( array( 42, 'extra' ), $action['args'][1] );
	}

	/**
	 * @testdox Processing preserves null arguments.
	 */
	public function test_send_queued_transactional_email_preserves_null_args(): void {
		$args = array(
			null,
			array(
				'nested' => null,
			),
		);

		$this->sut->push( 'woocommerce_order_status_pending_to_processing', $args );
		$this->sut->dispatch();

		$queue             = $this->get_test_queue();
		$round_tripped_arg = json_decode( wp_json_encode( $queue->actions[0]['args'] ), true );

		$this->assertIsArray( $round_tripped_arg );

		$sent = array();
		$this->capture_sent_queued_emails( $sent );

		$this->sut->send_queued_transactional_email(
			$round_tripped_arg[0],
			$round_tripped_arg[1]
		);

		$this->assertCount( 1, $sent, 'Should process the email callback' );
		$this->assertSame( $args, $sent[0]['args'] );
	}

	/**
	 * @testdox Push accepts scalar, array, and known WooCommerce object arguments.
	 */
	public function test_push_accepts_supported_args(): void {
		$product            = \WC_Helper_Product::create_simple_product();
		$order              = \WC_Helper_Order::create_order();
		$gateway            = $this->get_test_payment_gateway();
		$stock_notification = $this->create_test_stock_notification();

		$this->assertTrue(
			$this->sut->push(
				'woocommerce_low_stock',
				array(
					$product,
					array(
						'order'    => $order,
						'quantity' => 2,
					),
					$gateway,
					$stock_notification,
				)
			),
			'Known WooCommerce objects should be deferable'
		);
	}

	/**
	 * @testdox Push rejects unknown object arguments.
	 */
	public function test_push_rejects_unknown_object_args(): void {
		$this->assertFalse(
			$this->sut->push( 'woocommerce_low_stock', array( new \stdClass() ) ),
			'Unknown object args should not be deferable'
		);
		$this->assertFalse(
			$this->sut->push( 'woocommerce_low_stock', array( 'nested' => array( new \stdClass() ) ) ),
			'Nested unknown object args should not be deferable'
		);
	}

	/**
	 * @testdox Queue transactional email sends synchronously when args cannot be deferred.
	 */
	public function test_queue_transactional_email_sends_synchronously_when_args_cannot_be_deferred(): void {
		$object = new \stdClass();
		$sent   = array();

		$this->set_wc_emails_deferred_queue( $this->sut );

		// phpcs:disable WooCommerce.Commenting.CommentHooks.MissingHookComment,WooCommerce.Commenting.CommentHooks.MissingSinceComment -- Test-only hooks.
		add_action( 'woocommerce_deferred_email_test_unknown_object', array( \WC_Emails::class, 'queue_transactional_email' ) );
		add_action(
			'woocommerce_deferred_email_test_unknown_object_notification',
			function ( $arg ) use ( &$sent ) {
				$sent[] = $arg;
			}
		);

		do_action( 'woocommerce_deferred_email_test_unknown_object', $object );
		// phpcs:enable WooCommerce.Commenting.CommentHooks.MissingHookComment,WooCommerce.Commenting.CommentHooks.MissingSinceComment

		$this->sut->dispatch();

		$queue = $this->get_test_queue();

		$this->assertEmpty( $queue->actions, 'Unsupported object args should not be scheduled' );
		$this->assertSame( array( $object ), $sent, 'Unsupported object args should be sent synchronously' );
	}

	/**
	 * @testdox Queue transactional email sends synchronously when a supported object has no restorable ID.
	 */
	public function test_queue_transactional_email_sends_synchronously_when_supported_object_has_no_restorable_id(): void {
		$product = new \WC_Product_Simple();
		$sent    = array();

		$this->set_wc_emails_deferred_queue( $this->sut );

		// phpcs:disable WooCommerce.Commenting.CommentHooks.MissingHookComment,WooCommerce.Commenting.CommentHooks.MissingSinceComment -- Test-only hooks.
		add_action( 'woocommerce_deferred_email_test_unsaved_product', array( \WC_Emails::class, 'queue_transactional_email' ) );
		add_action(
			'woocommerce_deferred_email_test_unsaved_product_notification',
			function ( $arg ) use ( &$sent ) {
				$sent[] = $arg;
			}
		);

		do_action( 'woocommerce_deferred_email_test_unsaved_product', $product );
		// phpcs:enable WooCommerce.Commenting.CommentHooks.MissingHookComment,WooCommerce.Commenting.CommentHooks.MissingSinceComment

		$this->sut->dispatch();

		$queue = $this->get_test_queue();

		$this->assertSame( 0, $product->get_id() );
		$this->assertEmpty( $queue->actions, 'Supported object args with no restorable ID should not be scheduled' );
		$this->assertSame( array( $product ), $sent, 'Supported object args with no restorable ID should be sent synchronously' );
	}

	/**
	 * @testdox Push rejects items with object arguments that cannot be prepared for storage.
	 */
	public function test_push_rejects_items_with_unprepared_object_args(): void {
		$this->assertFalse( $this->sut->push( 'woocommerce_low_stock', array( new \stdClass() ) ) );
		$this->assertFalse( $this->sut->push( 'woocommerce_low_stock', array( new \WC_Product_Simple() ) ) );

		$this->sut->dispatch();

		$queue = $this->get_test_queue();

		$this->assertEmpty( $queue->actions, 'Unsupported object args should not be scheduled' );
	}

	/**
	 * @testdox Dispatch preserves product arguments after Action Scheduler JSON serialization.
	 */
	public function test_dispatch_preserves_product_args_after_action_scheduler_json_round_trip(): void {
		$product = \WC_Helper_Product::create_simple_product();

		$this->assert_object_args_round_trip(
			'woocommerce_low_stock',
			array( $product ),
			0,
			'product',
			\WC_Product::class,
			$product->get_id()
		);
	}

	/**
	 * @testdox Dispatch preserves order arguments after Action Scheduler JSON serialization.
	 */
	public function test_dispatch_preserves_order_args_after_action_scheduler_json_round_trip(): void {
		$order = \WC_Helper_Order::create_order();

		$this->assert_object_args_round_trip(
			'woocommerce_order_status_completed',
			array( $order->get_id(), $order ),
			1,
			'order',
			\WC_Order::class,
			$order->get_id()
		);
	}

	/**
	 * @testdox Dispatch preserves payment gateway arguments after Action Scheduler JSON serialization.
	 */
	public function test_dispatch_preserves_payment_gateway_args_after_action_scheduler_json_round_trip(): void {
		$gateway = $this->get_test_payment_gateway();

		$this->assert_object_args_round_trip(
			'woocommerce_payment_gateway_enabled',
			array( $gateway ),
			0,
			'payment_gateway',
			\WC_Payment_Gateway::class,
			$gateway->id
		);
	}

	/**
	 * @testdox Dispatch preserves stock notification arguments after Action Scheduler JSON serialization.
	 */
	public function test_dispatch_preserves_stock_notification_args_after_action_scheduler_json_round_trip(): void {
		$notification = $this->create_test_stock_notification();

		$this->assert_object_args_round_trip(
			'woocommerce_customer_stock_notification_verified',
			array( $notification ),
			0,
			'stock_notification',
			StockNotification::class,
			$notification->get_id()
		);
	}

	/**
	 * @testdox Dispatch skips email when a queued product reference can no longer be restored.
	 */
	public function test_dispatch_skips_email_when_queued_object_reference_cannot_be_restored(): void {
		$product = \WC_Helper_Product::create_simple_product();

		$this->sut->push( 'woocommerce_low_stock', array( $product ) );
		$this->sut->dispatch();

		$queue             = $this->get_test_queue();
		$round_tripped_arg = json_decode( wp_json_encode( $queue->actions[0]['args'] ), true );

		$this->assertIsArray( $round_tripped_arg );

		\WC_Helper_Product::delete_product( $product->get_id() );

		$sent = array();
		$this->capture_sent_queued_emails( $sent );

		$this->sut->send_queued_transactional_email(
			$round_tripped_arg[0],
			$round_tripped_arg[1]
		);

		$this->assertEmpty( $sent, 'Email should be skipped when a queued object reference cannot be restored' );
	}

	/**
	 * @testdox Dispatch assigns the woocommerce-emails group to scheduled actions.
	 */
	public function test_dispatch_uses_correct_group(): void {
		$this->sut->push( 'woocommerce_order_status_completed', array( 1 ) );
		$this->sut->dispatch();

		$queue = $this->get_test_queue();

		$this->assertSame( 'woocommerce-emails', $queue->actions[0]['group'] );
	}

	/**
	 * @testdox Processing calls WC_Emails::send_queued_transactional_email with the correct filter and args.
	 */
	public function test_send_queued_transactional_email_processes_callback(): void {
		$sent = array();

		add_filter(
			'woocommerce_allow_send_queued_transactional_email',
			function ( $allow, $filter, $args ) use ( &$sent ) {
				unset( $allow );
				$sent[] = array(
					'filter' => $filter,
					'args'   => $args,
				);
				return false;
			},
			10,
			3
		);

		$this->sut->send_queued_transactional_email( 'woocommerce_order_status_completed', array( 100 ) );

		$this->assertCount( 1, $sent, 'Should process the email callback' );
		$this->assertSame( 'woocommerce_order_status_completed', $sent[0]['filter'] );
		$this->assertSame( array( 100 ), $sent[0]['args'] );
	}

	/**
	 * @testdox Processing skips invalid input types gracefully.
	 */
	public function test_send_queued_transactional_email_skips_invalid_input(): void {
		$sent = array();

		add_filter(
			'woocommerce_allow_send_queued_transactional_email',
			function ( $allow, $filter ) use ( &$sent ) {
				unset( $allow );
				$sent[] = $filter;
				return false;
			},
			10,
			2
		);

		$this->sut->send_queued_transactional_email( 123, array() );
		$this->sut->send_queued_transactional_email( 'valid_hook', 'not-array' );

		$this->assertEmpty( $sent, 'Should not process callbacks with invalid types' );
	}

	/**
	 * @testdox Push can be called again after dispatch to queue new emails.
	 */
	public function test_push_after_dispatch_queues_new_emails(): void {
		$this->sut->push( 'woocommerce_order_status_completed', array( 1 ) );
		$this->sut->dispatch();

		$this->sut->push( 'woocommerce_new_customer_note', array( 2 ) );
		$this->sut->dispatch();

		$queue = $this->get_test_queue();

		$this->assertCount( 2, $queue->actions, 'Should schedule actions from both dispatch cycles' );
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
	 * Get the test action queue instance.
	 *
	 * @return \WC_Admin_Test_Action_Queue
	 */
	private function get_test_queue(): \WC_Admin_Test_Action_Queue {
		$queue = \WC_Queue::instance();
		$this->assertInstanceOf( \WC_Admin_Test_Action_Queue::class, $queue );
		return $queue;
	}

	/**
	 * Push an email, assert the wrapped object reference is scheduled, JSON
	 * round-trip the scheduled args, and assert the email callback receives
	 * the restored object back.
	 *
	 * @param string     $filter           Email hook name.
	 * @param array      $args             Args to push.
	 * @param int        $wrapped_position Index of the object argument inside $args.
	 * @param string     $expected_type    Expected object reference type.
	 * @param string     $expected_class   Expected restored object class.
	 * @param int|string $expected_id      Expected restored object ID.
	 */
	private function assert_object_args_round_trip(
		string $filter,
		array $args,
		int $wrapped_position,
		string $expected_type,
		string $expected_class,
		$expected_id
	): void {
		$this->sut->push( $filter, $args );
		$this->sut->dispatch();

		$scheduled_arg = $this->get_scheduled_email_action()['args'];
		$encoded_arg   = wp_json_encode( $scheduled_arg );

		$this->assert_queued_object_reference( $scheduled_arg[1][ $wrapped_position ], $expected_type, $expected_id );
		$this->assertIsString( $encoded_arg );

		$round_tripped_arg = json_decode( $encoded_arg, true );
		$this->assertIsArray( $round_tripped_arg );

		$sent = array();
		$this->capture_sent_queued_emails( $sent );

		$this->sut->send_queued_transactional_email(
			$round_tripped_arg[0],
			$round_tripped_arg[1]
		);

		$this->assertCount( 1, $sent, 'Should process the email callback' );
		$this->assertSame( $filter, $sent[0]['filter'] );
		$this->assertInstanceOf( $expected_class, $sent[0]['args'][ $wrapped_position ] );
		$this->assertSame( $expected_id, $this->get_restored_object_id( $sent[0]['args'][ $wrapped_position ] ) );
	}

	/**
	 * Get an ID from a restored queued object.
	 *
	 * @param object $restored_object Restored queued object.
	 * @return int|string
	 */
	private function get_restored_object_id( object $restored_object ) {
		if ( $restored_object instanceof \WC_Payment_Gateway ) {
			return $restored_object->id;
		}

		return $restored_object->get_id();
	}

	/**
	 * Register a filter that captures emails reaching send_queued_transactional_email
	 * into the given accumulator, short-circuiting actual sending.
	 *
	 * @param array $sent Accumulator for captured emails (passed by reference).
	 */
	private function capture_sent_queued_emails( array &$sent ): void {
		add_filter(
			'woocommerce_allow_send_queued_transactional_email',
			function ( $allow, $filter, $args ) use ( &$sent ) {
				unset( $allow );
				$sent[] = array(
					'filter' => $filter,
					'args'   => $args,
				);
				return false;
			},
			10,
			3
		);
	}

	/**
	 * Get the scheduled Action Scheduler email action from the test queue.
	 *
	 * Other fixture setup can enqueue unrelated actions before the email queue
	 * dispatches, so object round-trip assertions must target the email action.
	 *
	 * @return array{timestamp: int, hook: string, args: array, group: string}
	 */
	private function get_scheduled_email_action(): array {
		foreach ( $this->get_test_queue()->actions as $action ) {
			if ( 'woocommerce_send_queued_transactional_email' === $action['hook'] ) {
				return $action;
			}
		}

		$this->fail( 'Expected a queued transactional email action to be scheduled.' );
		return array(
			'timestamp' => 0,
			'hook'      => '',
			'args'      => array(),
			'group'     => '',
		);
	}

	/**
	 * Assert a queued object reference has the expected wrapper shape.
	 *
	 * @param array      $reference Queued object reference.
	 * @param string     $type      Expected object type.
	 * @param int|string $id        Expected object ID.
	 */
	private function assert_queued_object_reference( array $reference, string $type, $id ): void {
		$this->assertSame(
			array(
				'__woocommerce_deferred_email_object' => array(
					'type' => $type,
					'id'   => $id,
				),
			),
			$reference
		);
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

	/**
	 * Get a payment gateway available in the test environment.
	 *
	 * @return \WC_Payment_Gateway
	 */
	private function get_test_payment_gateway(): \WC_Payment_Gateway {
		$gateways = \WC()->payment_gateways()->payment_gateways();
		$gateway  = $gateways['bacs'] ?? reset( $gateways );

		if ( ! $gateway instanceof \WC_Payment_Gateway ) {
			$this->fail( 'Expected at least one payment gateway to be available' );
		}

		return $gateway;
	}

	/**
	 * Create a stock notification available in the test environment.
	 *
	 * @return StockNotification
	 */
	private function create_test_stock_notification(): StockNotification {
		$product      = \WC_Helper_Product::create_simple_product();
		$notification = new StockNotification();

		$notification->set_product_id( $product->get_id() );
		$notification->set_user_email( 'customer@example.com' );
		$notification->set_status( NotificationStatus::ACTIVE );
		$notification->save();

		$this->created_stock_notification_ids[] = (int) $notification->get_id();

		return $notification;
	}

	/**
	 * Delete stock notifications created by these tests.
	 */
	private function delete_stock_notifications(): void {
		global $wpdb;

		if ( empty( $this->created_stock_notification_ids ) ) {
			return;
		}

		$ids              = array_map( 'absint', $this->created_stock_notification_ids );
		$ids_placeholders = implode( ',', array_fill( 0, count( $ids ), '%d' ) );

		// phpcs:disable WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.PreparedSQLPlaceholders.UnfinishedPrepare -- $ids_placeholders contains %d placeholders for sanitized IDs.
		$wpdb->query(
			$wpdb->prepare(
				"DELETE FROM {$wpdb->prefix}wc_stock_notificationmeta WHERE notification_id IN ({$ids_placeholders})",
				...$ids
			)
		);
		$wpdb->query(
			$wpdb->prepare(
				"DELETE FROM {$wpdb->prefix}wc_stock_notifications WHERE id IN ({$ids_placeholders})",
				...$ids
			)
		);
		// phpcs:enable WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.PreparedSQLPlaceholders.UnfinishedPrepare

		$this->created_stock_notification_ids = array();
	}
}
