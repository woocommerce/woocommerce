<?php
declare( strict_types = 1 );

namespace Automattic\WooCommerce\Tests\Internal\Email;

use Automattic\WooCommerce\Internal\Email\DeferredEmailQueue;
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
}
