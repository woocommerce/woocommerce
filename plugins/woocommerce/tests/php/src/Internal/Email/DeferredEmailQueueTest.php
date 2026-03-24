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
		remove_all_actions( 'woocommerce_send_queued_transactional_emails' );
		$this->reset_queue_singleton();
		parent::tearDown();
	}

	/**
	 * @testdox Push collects email callbacks and dispatch schedules a single AS action for the batch.
	 */
	public function test_push_and_dispatch_schedules_batch(): void {
		$this->sut->push( 'woocommerce_order_status_completed', array( 123 ) );
		$this->sut->push( 'woocommerce_new_customer_note', array( 456, 'note' ) );

		$this->sut->dispatch();

		$queue = $this->get_test_queue();

		$this->assertCount( 1, $queue->actions, 'Should schedule exactly one AS action for the batch' );
		$this->assertSame( 'woocommerce_send_queued_transactional_emails', $queue->actions[0]['hook'] );
		$this->assertCount( 2, $queue->actions[0]['args'][0], 'Batch should contain two email callbacks' );
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

		$queue = $this->get_test_queue();
		$batch = $queue->actions[0]['args'][0];

		$this->assertSame( 'woocommerce_order_status_pending_to_processing', $batch[0]['filter'] );
		$this->assertSame( array( 42, 'extra' ), $batch[0]['args'] );
	}

	/**
	 * @testdox Dispatch assigns the woocommerce-emails group to the scheduled action.
	 */
	public function test_dispatch_uses_correct_group(): void {
		$this->sut->push( 'woocommerce_order_status_completed', array( 1 ) );
		$this->sut->dispatch();

		$queue = $this->get_test_queue();

		$this->assertSame( 'woocommerce-emails', $queue->actions[0]['group'] );
	}

	/**
	 * @testdox Processing a batch calls send_queued_transactional_email for each valid callback.
	 */
	public function test_send_queued_transactional_emails_processes_valid_callbacks(): void {
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

		$batch = array(
			array(
				'filter' => 'woocommerce_order_status_completed',
				'args'   => array( 100 ),
			),
			array(
				'filter' => 'woocommerce_new_customer_note',
				'args'   => array( 200 ),
			),
		);

		$this->sut->send_queued_transactional_emails( $batch );

		$this->assertCount( 2, $sent, 'Should process both email callbacks' );
		$this->assertSame( 'woocommerce_order_status_completed', $sent[0]['filter'] );
		$this->assertSame( array( 100 ), $sent[0]['args'] );
		$this->assertSame( 'woocommerce_new_customer_note', $sent[1]['filter'] );
		$this->assertSame( array( 200 ), $sent[1]['args'] );
	}

	/**
	 * @testdox Processing skips malformed callbacks in the batch.
	 */
	public function test_send_queued_transactional_emails_skips_malformed(): void {
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

		$batch = array(
			'not-an-array',
			array( 'missing_filter_key' => true ),
			array(
				'filter' => 123,
				'args'   => array(),
			),
			array(
				'filter' => 'valid_hook',
				'args'   => 'not-array',
			),
			array(
				'filter' => 'woocommerce_order_status_completed',
				'args'   => array( 1 ),
			),
		);

		$this->sut->send_queued_transactional_emails( $batch );

		$this->assertCount( 1, $sent, 'Should only process the one valid callback' );
		$this->assertSame( 'woocommerce_order_status_completed', $sent[0] );
	}

	/**
	 * @testdox Processing handles non-array input gracefully without errors.
	 */
	public function test_send_queued_transactional_emails_handles_non_array(): void {
		$this->sut->send_queued_transactional_emails( 'not-an-array' );
		$this->sut->send_queued_transactional_emails( null );
		$this->sut->send_queued_transactional_emails( 42 );

		$this->assertTrue( true, 'Should not throw for non-array input' );
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
