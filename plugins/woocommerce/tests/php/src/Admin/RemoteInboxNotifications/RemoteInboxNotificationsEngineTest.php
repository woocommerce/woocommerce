<?php
declare( strict_types = 1 );

namespace Automattic\WooCommerce\Tests\Admin\RemoteInboxNotifications;

use ActionScheduler;
use ActionScheduler_Store;
use Automattic\WooCommerce\Admin\RemoteInboxNotifications\RemoteInboxNotificationsEngine;
use WC_Queue;
use WC_Queue_Interface;
use WC_Unit_Test_Case;

/**
 * Tests for upgrade-triggered remote inbox scheduling.
 *
 * @covers \Automattic\WooCommerce\Admin\RemoteInboxNotifications\RemoteInboxNotificationsEngine
 */
class RemoteInboxNotificationsEngineTest extends WC_Unit_Test_Case {

	/**
	 * The upgrade action hook.
	 */
	private const HOOK = 'woocommerce_run_on_woocommerce_admin_updated';

	/**
	 * The engine's action group.
	 */
	private const GROUP = 'woocommerce-remote-inbox-engine';

	/**
	 * Initialize only the engine's update listener. The parent restores hooks and database fixtures.
	 */
	public function setUp(): void {
		parent::setUp();
		remove_all_actions( 'woocommerce_updated' );
		RemoteInboxNotificationsEngine::init();
	}

	/**
	 * @testdox Repeated updates do not add pending copies while an upgrade action is running.
	 * @dataProvider running_action_provider
	 *
	 * @param int $pending_count Existing pending copies alongside the running action.
	 */
	public function test_running_action_prevents_accumulation( int $pending_count ): void {
		$running_id = as_schedule_single_action( time() - 60, self::HOOK, array(), self::GROUP );
		ActionScheduler::store()->log_execution( $running_id );
		for ( $i = 0; $i < $pending_count; $i++ ) {
			as_schedule_single_action( time() - 30, self::HOOK, array(), self::GROUP );
		}
		for ( $i = 0; $i < 10; $i++ ) {
			as_schedule_single_action( time() - 120, 'unrelated_upgrade_backlog', array( $i ), self::GROUP );
		}

		$this->assertSame( ActionScheduler_Store::STATUS_RUNNING, ActionScheduler::store()->get_status( $running_id ), 'The stored action must be running.' );
		$this->assertTrue( as_next_scheduled_action( self::HOOK, array(), self::GROUP ), 'A running action masks pending dates in the Action Scheduler lookup.' );
		$this->assertNull( WC()->queue()->get_next( self::HOOK, array(), self::GROUP ), 'The date-based queue lookup cannot represent running work.' );

		$this->fire_updates();

		$this->assertCount( $pending_count, $this->pending_actions(), 'Repeated updates must not grow the pending backlog while an action runs.' );
	}

	/**
	 * Existing backlog sizes for a running action.
	 *
	 * @return array<string, array{int}>
	 */
	public function running_action_provider(): array {
		return array(
			'running alone'        => array( 0 ),
			'running with backlog' => array( 3 ),
		);
	}

	/**
	 * @testdox A pending async upgrade action prevents additional scheduled copies.
	 */
	public function test_pending_async_action_prevents_accumulation(): void {
		$action_id = as_enqueue_async_action( self::HOOK, array(), self::GROUP );
		$this->assertInstanceOf( \ActionScheduler_NullSchedule::class, ActionScheduler::store()->fetch_action( $action_id )->get_schedule() );
		$this->assertTrue( as_next_scheduled_action( self::HOOK, array(), self::GROUP ) );
		$this->assertNull( WC()->queue()->get_next( self::HOOK, array(), self::GROUP ) );

		$this->fire_updates();

		$this->assertSame( array( $action_id ), $this->pending_actions(), 'Pending async work must prevent another copy even without a scheduled date.' );
	}

	/**
	 * @testdox Repeated updates schedule one action when there is no active upgrade work.
	 * @dataProvider finished_action_provider
	 *
	 * @param string|null $status Existing finished action status, or null for an empty queue.
	 */
	public function test_schedules_after_finished_work( ?string $status ): void {
		if ( null !== $status ) {
			$action_id = as_schedule_single_action( time() - 60, self::HOOK, array(), self::GROUP );
			$store     = ActionScheduler::store();
			if ( ActionScheduler_Store::STATUS_COMPLETE === $status ) {
				$store->mark_complete( $action_id );
			} elseif ( ActionScheduler_Store::STATUS_FAILED === $status ) {
				$store->mark_failure( $action_id );
			} else {
				$store->cancel_action( $action_id );
			}
		}

		$this->fire_updates();

		$this->assertCount( 1, $this->pending_actions(), 'There should be exactly one fresh action after repeated updates.' );
	}

	/**
	 * Inactive queue states that allow a fresh action.
	 *
	 * @return array<string, array{string|null}>
	 */
	public function finished_action_provider(): array {
		return array(
			'empty'     => array( null ),
			'completed' => array( ActionScheduler_Store::STATUS_COMPLETE ),
			'failed'    => array( ActionScheduler_Store::STATUS_FAILED ),
			'canceled'  => array( ActionScheduler_Store::STATUS_CANCELED ),
		);
	}

	/**
	 * @testdox An ordinary pending action prevents duplicate upgrade work.
	 */
	public function test_pending_scheduled_action_prevents_accumulation(): void {
		$action_id = as_schedule_single_action( time() + 60, self::HOOK, array(), self::GROUP );

		$this->fire_updates();

		$this->assertSame( array( $action_id ), $this->pending_actions(), 'The existing pending action should be reused.' );
	}

	/**
	 * @testdox Pending and running work with a different hook, arguments or group does not suppress upgrade work.
	 * @dataProvider unrelated_action_provider
	 *
	 * @param string $hook  The unrelated action's hook.
	 * @param array  $args  The unrelated action's arguments.
	 * @param string $group The unrelated action's group.
	 */
	public function test_unrelated_work_does_not_suppress_scheduling( string $hook, array $args, string $group ): void {
		$action_id = as_schedule_single_action( time() - 60, $hook, $args, $group );
		ActionScheduler::store()->log_execution( $action_id );
		as_schedule_single_action( time() - 30, $hook, $args, $group );

		$this->fire_updates();

		$this->assertCount( 1, $this->pending_actions(), 'Only work matching the exact hook, arguments and group should suppress scheduling.' );
	}

	/**
	 * Actions differing in exactly one part of the engine's scope.
	 *
	 * @return array<string, array{string, array, string}>
	 */
	public function unrelated_action_provider(): array {
		return array(
			'different hook'  => array( 'unrelated_upgrade_action', array(), self::GROUP ),
			'different args'  => array( self::HOOK, array( 'unrelated' ), self::GROUP ),
			'different group' => array( self::HOOK, array(), 'unrelated-upgrade-group' ),
		);
	}

	/**
	 * @testdox Replacement queues control scheduling through the existing queue interface.
	 * @dataProvider replacement_queue_provider
	 *
	 * @param string|null $active_status Existing active work, or null for an empty queue.
	 */
	public function test_replacement_queue_controls_scheduling( ?string $active_status ): void {
		$queue         = $this->createMock( WC_Queue_Interface::class );
		$should_create = null === $active_status;
		$queue->expects( $this->never() )->method( 'get_next' );
		$queue->method( 'search' )->willReturnCallback(
			function ( array $args, string $return_format ) use ( &$active_status ): array {
				$this->assertContains( $args['status'], array( ActionScheduler_Store::STATUS_PENDING, ActionScheduler_Store::STATUS_RUNNING ), 'Replacement queues receive a scalar status.' );
				$this->assertSame( self::HOOK, $args['hook'] );
				$this->assertSame( array(), $args['args'] );
				$this->assertSame( self::GROUP, $args['group'] );
				$this->assertSame( 1, $args['per_page'] );
				$this->assertSame( 'ids', $return_format );
				return $active_status === $args['status'] ? array( 123 ) : array();
			}
		);
		$queue->expects( $should_create ? $this->once() : $this->never() )
			->method( 'schedule_single' )
			->with( $this->isType( 'int' ), self::HOOK, array(), self::GROUP )
			->willReturnCallback(
				function () use ( &$active_status ): int {
					$active_status = ActionScheduler_Store::STATUS_PENDING;
					return 123;
				}
			);
		$original_queue = WC()->queue();
		$instance       = new \ReflectionProperty( WC_Queue::class, 'instance' );
		$instance->setAccessible( true );
		$instance->setValue( null, $queue );
		try {
			$this->fire_updates();
		} finally {
			$instance->setValue( null, $original_queue );
		}
	}

	/**
	 * Active states exposed by a replacement queue.
	 *
	 * @return array<string, array{string|null}>
	 */
	public function replacement_queue_provider(): array {
		return array(
			'empty'   => array( null ),
			'pending' => array( ActionScheduler_Store::STATUS_PENDING ),
			'running' => array( ActionScheduler_Store::STATUS_RUNNING ),
		);
	}

	/**
	 * Fire repeated upgrade events while the queue is not being drained.
	 */
	private function fire_updates(): void {
		for ( $i = 0; $i < 10; $i++ ) {
			do_action( 'woocommerce_updated' );
		}
	}

	/**
	 * Get all pending upgrade actions in the engine's exact scope.
	 *
	 * @return array<int>
	 */
	private function pending_actions(): array {
		$action_ids = as_get_scheduled_actions(
			array(
				'hook'     => self::HOOK,
				'args'     => array(),
				'group'    => self::GROUP,
				'status'   => ActionScheduler_Store::STATUS_PENDING,
				'per_page' => -1,
			),
			'ids'
		);
		return array_map( 'intval', $action_ids );
	}
}
