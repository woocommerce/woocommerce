<?php
/**
 * Integration tests for the DueDispatcher (find, lock, notify).
 *
 * @package Automattic\WooCommerce\SubscriptionsEngine
 */

declare( strict_types=1 );

namespace Automattic\WooCommerce\SubscriptionsEngine\Tests\Integration\Integration\Dispatch;

use DateTimeImmutable;
use DateTimeZone;
use EngineIntegrationTestCase;
use RuntimeException;
use Automattic\WooCommerce\SubscriptionsEngine\Api\ContractLock;
use Automattic\WooCommerce\SubscriptionsEngine\Api\ContractLockedException;
use Automattic\WooCommerce\SubscriptionsEngine\Api\Contracts;
use Automattic\WooCommerce\SubscriptionsEngine\Api\View\ContractView;
use Automattic\WooCommerce\SubscriptionsEngine\Core\Entity\ContractStatus;
use Automattic\WooCommerce\SubscriptionsEngine\Integration\Dispatch\DueDispatcher;
use Automattic\WooCommerce\SubscriptionsEngine\Integration\Ownership\ConsumerRegistry;
use Automattic\WooCommerce\SubscriptionsEngine\Integration\Storage\ContractRepository;
use Automattic\WooCommerce\SubscriptionsEngine\Integration\Storage\SchemaInstaller;

/**
 * @covers \Automattic\WooCommerce\SubscriptionsEngine\Integration\Dispatch\DueDispatcher
 */
class DueDispatcherTest extends EngineIntegrationTestCase {

	private const OWNER = 'engine-tests';

	private const SCHEDULE_CHECK_OPTION = 'woocommerce_subscriptions_engine_due_dispatch_scheduled_check';

	/**
	 * The System Under Test.
	 *
	 * @var DueDispatcher
	 */
	private $sut;

	/**
	 * Calls the recording handler received, in order.
	 *
	 * @var array<int, array{contract: ContractView, context: array<string, mixed>}>
	 */
	private $calls = array();

	public function set_up(): void {
		parent::set_up();
		ConsumerRegistry::reset();
		$this->calls = array();
		$this->sut   = new DueDispatcher();
	}

	public function tear_down(): void {
		as_unschedule_all_actions( DueDispatcher::HOOK, array(), DueDispatcher::GROUP );
		ConsumerRegistry::reset();
		parent::tear_down();
	}

	/**
	 * @testdox A tick that overlaps a running notification does not notify the same contract again.
	 */
	public function test_overlapping_ticks_notify_once(): void {
		$nested = null;
		ConsumerRegistry::register(
			self::OWNER,
			array(
				'contract_due_handler' => function ( ContractView $contract, array $context ) use ( &$nested ): void {
					$this->calls[] = array(
						'contract' => $contract,
						'context'  => $context,
					);
					if ( null === $nested ) {
						$nested = ( new DueDispatcher() )->run_batch( $this->now() );
					}
				},
			)
		);
		$this->create_due_contract();

		$this->assertSame( 1, $this->sut->run_batch( $this->now() ) );
		$this->assertSame( 0, $nested, 'The overlapping tick finds the contract locked.' );
		$this->assertCount( 1, $this->calls );
	}

	/**
	 * @testdox A contract locked before the scan is not selected.
	 */
	public function test_a_contract_locked_before_the_scan_is_not_notified(): void {
		$this->register_recording_handler();
		$id = $this->create_due_contract();

		$this->take_lock( $id, 'hold' );

		$this->assertSame( 0, $this->sut->run_batch( $this->now() ) );
		$this->assertSame( array(), $this->calls );
		$this->assertSame( 'hold', $this->get_lock_holder( $id )['operation'], 'The holder keeps its lock.' );
	}

	/**
	 * @testdox A contract whose due moment moves, or that another operation locks, after the scan is skipped.
	 */
	public function test_a_lock_lost_after_the_scan_is_skipped(): void {
		$first  = $this->create_due_contract( '2026-02-13 00:00:00' );
		$second = $this->create_due_contract( '2026-02-14 00:00:00' );
		$third  = $this->create_due_contract( '2026-02-15 00:00:00' );
		ConsumerRegistry::register(
			self::OWNER,
			array(
				'contract_due_handler' => function ( ContractView $contract, array $context ) use ( $first, $second, $third ): void {
					$this->calls[] = array(
						'contract' => $contract,
						'context'  => $context,
					);
					if ( $first === $contract->get_id() ) {
						// Other writers act on the next contracts after the scan read them.
						Contracts::update( $second, array( 'next_payment_gmt' => '2026-02-14 12:00:00' ) );
						$this->take_lock( $third, 'cancel' );
					}
				},
			)
		);

		$this->assertSame( 1, $this->sut->run_batch( $this->now() ), 'Only the first contract is notified.' );
		$this->assertCount( 1, $this->calls );
		$this->assertSame( $first, $this->calls[0]['contract']->get_id() );
		$this->assertNull( $this->get_lock_holder( $second )['operation'], 'The moved contract was not locked.' );
		$this->assertSame( 'cancel', $this->get_lock_holder( $third )['operation'], 'The other operation keeps its lock.' );
	}

	/**
	 * @testdox A handler that leaves the due moment in place is notified again on the next tick.
	 */
	public function test_an_idle_handler_is_renotified_on_the_next_tick(): void {
		$this->register_recording_handler();
		$id = $this->create_due_contract();

		$this->sut->run_batch( $this->now() );
		$this->assertNull( $this->get_lock_holder( $id )['operation'], 'The lock is released when the handler returns.' );

		$this->sut->run_batch( $this->now() );
		$this->assertCount( 2, $this->calls );
	}

	/**
	 * @testdox A handler that moves the due moment with its lock is not notified again until the new moment.
	 */
	public function test_moving_the_due_moment_defers_the_next_notification(): void {
		ConsumerRegistry::register(
			self::OWNER,
			array(
				'contract_due_handler' => function ( ContractView $contract, array $context ): void {
					$this->calls[] = array(
						'contract' => $contract,
						'context'  => $context,
					);
					Contracts::update( $contract->get_id(), array( 'next_payment_gmt' => '2026-02-16 00:00:00' ), $context['lock'] );
				},
			)
		);
		$this->create_due_contract();

		$this->sut->run_batch( $this->now() );
		$this->sut->run_batch( $this->now() );
		$this->assertCount( 1, $this->calls, 'Not due again before the new moment.' );

		$this->sut->run_batch( $this->now()->modify( '+1 day' ) );
		$this->assertCount( 2, $this->calls );
		$this->assertEquals( new DateTimeImmutable( '2026-02-16 00:00:00', new DateTimeZone( 'UTC' ) ), $this->calls[1]['context']['due_at'] );
	}

	/**
	 * @testdox A handler that clears the due moment is never notified again.
	 */
	public function test_clearing_the_due_moment_stops_notifications(): void {
		ConsumerRegistry::register(
			self::OWNER,
			array(
				'contract_due_handler' => function ( ContractView $contract, array $context ): void {
					$this->calls[] = array(
						'contract' => $contract,
						'context'  => $context,
					);
					Contracts::update( $contract->get_id(), array( 'next_payment_gmt' => null ), $context['lock'] );
				},
			)
		);
		$this->create_due_contract();

		$this->sut->run_batch( $this->now() );
		$this->sut->run_batch( $this->now()->modify( '+1 day' ) );

		$this->assertCount( 1, $this->calls );
	}

	/**
	 * @testdox The handler gets a live lock for its contract: writes with it land, unlocked writes are refused.
	 */
	public function test_the_handler_writes_with_the_dispatcher_lock(): void {
		$refused = null;
		ConsumerRegistry::register(
			self::OWNER,
			array(
				'contract_due_handler' => function ( ContractView $contract, array $context ) use ( &$refused ): void {
					$lock = $context['lock'];
					$this->assertInstanceOf( ContractLock::class, $lock );
					$this->assertSame( $contract->get_id(), $lock->get_contract_id() );
					$this->assertSame( DueDispatcher::LOCK_OPERATION, $lock->get_operation() );

					try {
						Contracts::update( $contract->get_id(), array( 'status' => ContractStatus::CANCELLED ) );
					} catch ( ContractLockedException $e ) {
						$refused = $e->get_operation();
					}
					Contracts::update( $contract->get_id(), array( 'status' => ContractStatus::ON_HOLD ), $lock );
				},
			)
		);
		$id = $this->create_due_contract();

		$this->assertSame( 1, $this->sut->run_batch( $this->now() ) );
		$this->assertSame( DueDispatcher::LOCK_OPERATION, $refused, 'The unlocked write inside the handler is refused.' );
		$view = Contracts::get( $id );
		$this->assertInstanceOf( ContractView::class, $view );
		$this->assertSame( ContractStatus::ON_HOLD, $view->get_status() );
	}

	/**
	 * @testdox A handler that releases the lock early does not break the dispatcher's release.
	 */
	public function test_a_handler_may_release_early(): void {
		ConsumerRegistry::register(
			self::OWNER,
			array(
				'contract_due_handler' => function ( ContractView $contract, array $context ): void {
					$context['lock']->release();
					$this->take_lock( $contract->get_id(), 'follow-up' );
				},
			)
		);
		$id = $this->create_due_contract();

		$this->assertSame( 1, $this->sut->run_batch( $this->now() ) );
		$this->assertSame( 'follow-up', $this->get_lock_holder( $id )['operation'], 'The dispatcher never clears a lock it no longer holds.' );
	}

	/**
	 * @testdox A throwing handler is logged, keeps its lock until it expires and does not stop the batch.
	 */
	public function test_a_throwing_handler_is_logged_and_the_batch_continues(): void {
		$failing = $this->create_due_contract( '2026-02-14 00:00:00' );
		$healthy = $this->create_due_contract( '2026-02-15 00:00:00' );
		ConsumerRegistry::register(
			self::OWNER,
			array(
				'contract_due_handler' => function ( ContractView $contract, array $context ) use ( $failing ): void {
					$this->calls[] = array(
						'contract' => $contract,
						'context'  => $context,
					);
					if ( $failing === $contract->get_id() ) {
						throw new RuntimeException( 'Handler failure.' );
					}
				},
			)
		);

		$this->pin_db_clock( '2026-02-15 00:00:00' );
		$errors = $this->capture_engine_log(
			'error',
			array( 'contract_id' => $failing ),
			function (): void {
				$this->assertSame( 1, $this->sut->run_batch( $this->now() ), 'Only the healthy contract counts as notified.' );
			}
		);

		$this->assertNotSame( array(), $errors, 'The failure is logged with the contract id.' );
		$this->assertCount( 2, $this->calls );
		$this->assertSame( DueDispatcher::LOCK_OPERATION, $this->get_lock_holder( $failing )['operation'], 'The failing contract keeps its lock (back-off).' );

		$this->pin_db_clock( '2026-02-15 00:09:59' );
		$this->sut->run_batch( $this->now() );
		$this->assertSame( array( $failing, $healthy, $healthy ), $this->notified_ids(), 'Not re-notified while the lock lives.' );

		$this->pin_db_clock( '2026-02-15 00:10:00' );
		$this->sut->run_batch( $this->now() );
		$this->assertContains( $failing, array_slice( $this->notified_ids(), 3 ), 'Re-notified once the lock expired on the database clock.' );
	}

	/**
	 * @testdox A failed release after the handler is logged as a release failure and does not stop the batch.
	 */
	public function test_a_failed_release_is_logged_and_the_batch_continues(): void {
		$this->register_recording_handler();
		$first  = $this->create_due_contract( '2026-02-14 00:00:00' );
		$second = $this->create_due_contract( '2026-02-15 00:00:00' );

		$this->fail_next_query( '/^\s*UPDATE\s+\S*contracts\s+SET\s+lock_token\s*=\s*NULL/i' );
		$errors = $this->capture_engine_log(
			'error',
			array( 'contract_id' => $first ),
			function (): void {
				$this->assertSame( 2, $this->sut->run_batch( $this->now() ), 'A failed release still counts as notified.' );
			}
		);

		$this->assertNotSame( array(), $errors );
		foreach ( $errors as $error ) {
			$this->assertSame( 'release', $error['stage'] ?? null, 'Logged as a release failure, not a handler failure.' );
		}
		$this->assertSame( array( $first, $second ), $this->notified_ids() );
		$this->assertNotNull( $this->get_lock_holder( $first )['operation'], 'The unreleased lock expires on its own.' );
		$this->assertNull( $this->get_lock_holder( $second )['operation'] );
	}

	/**
	 * @testdox A failed contract read after the lock is logged per contract; the lock is released and the batch continues.
	 */
	public function test_a_failed_read_after_the_lock_is_caught_per_contract(): void {
		$this->register_recording_handler();
		$first  = $this->create_due_contract( '2026-02-14 00:00:00' );
		$second = $this->create_due_contract( '2026-02-15 00:00:00' );

		$this->fail_next_query( '/^\s*SELECT\s+\*,\s*\(\s*lock_token/i' );
		$errors = $this->capture_engine_log(
			'error',
			array( 'contract_id' => $first ),
			function (): void {
				$this->assertSame( 1, $this->sut->run_batch( $this->now() ) );
			}
		);

		$this->assertNotSame( array(), $errors );
		$this->assertSame( array( $second ), $this->notified_ids() );
		$this->assertNull( $this->get_lock_holder( $first )['operation'], 'Nothing ran, so the lock is released.' );
	}

	/**
	 * @testdox An owner registered without a handler is not dispatched and its contracts stay untouched.
	 */
	public function test_an_owner_without_a_handler_is_not_dispatched(): void {
		ConsumerRegistry::register( self::OWNER );
		$id = $this->create_due_contract();

		$this->assertSame( 0, $this->sut->run_batch( $this->now() ) );
		$this->assertNull( $this->get_lock_holder( $id )['operation'], 'No lock is taken on a contract nobody handles.' );
	}

	/**
	 * @testdox Contracts of an unregistered owner, or with no owner, are not dispatched.
	 */
	public function test_unregistered_and_missing_owners_are_not_dispatched(): void {
		global $wpdb;

		$this->register_recording_handler();
		$foreign  = $this->create_due_contract( '2026-02-15 00:00:00', array( 'extension_slug' => 'other-ext' ) );
		$no_owner = $this->create_due_contract();
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching
		$wpdb->update( SchemaInstaller::get_table_name( SchemaInstaller::TABLE_CONTRACTS ), array( 'extension_slug' => null ), array( 'id' => $no_owner ) );

		$this->assertSame( 0, $this->sut->run_batch( $this->now() ) );
		$this->assertSame( array(), $this->calls );
		$this->assertNull( $this->get_lock_holder( $foreign )['operation'] );
	}

	/**
	 * @testdox Each due contract goes to its own owner's handler when several owners have one.
	 */
	public function test_each_contract_goes_to_its_owner_s_handler(): void {
		$received = array(
			'owner-a' => array(),
			'owner-b' => array(),
		);
		foreach ( array_keys( $received ) as $owner ) {
			ConsumerRegistry::register(
				$owner,
				array(
					'contract_due_handler' => function ( ContractView $contract ) use ( &$received, $owner ): void {
						$received[ $owner ][] = $contract->get_id();
					},
				)
			);
		}
		$a = $this->create_due_contract( '2026-02-15 00:00:00', array( 'extension_slug' => 'owner-a' ) );
		$b = $this->create_due_contract( '2026-02-15 00:00:00', array( 'extension_slug' => 'owner-b' ) );

		$this->assertSame( 2, $this->sut->run_batch( $this->now() ) );
		$this->assertSame( array( $a ), $received['owner-a'] );
		$this->assertSame( array( $b ), $received['owner-b'] );
	}

	/**
	 * @testdox A contract whose schedule a gateway or extension keeps is not dispatched.
	 */
	public function test_a_contract_with_another_schedule_source_is_not_dispatched(): void {
		$this->register_recording_handler();
		$id = $this->create_due_contract( '2026-02-15 00:00:00', array( 'schedule_source' => 'my_gateway' ) );

		$this->assertSame( 0, $this->sut->run_batch( $this->now() ) );
		$this->assertSame( array(), $this->calls );
		$this->assertNull( $this->get_lock_holder( $id )['operation'], 'No lock is taken on a contract the engine does not schedule.' );
	}

	/**
	 * @testdox The handler receives the contract view with items and the stored due moment in UTC.
	 */
	public function test_the_handler_receives_the_contract_view_and_due_at(): void {
		$this->register_recording_handler();
		$id = $this->create_due_contract();

		$this->sut->run_batch( new DateTimeImmutable( '2026-02-15 03:00:00', new DateTimeZone( 'Europe/Berlin' ) ) );

		$this->assertCount( 1, $this->calls );
		$contract = $this->calls[0]['contract'];
		$this->assertSame( $id, $contract->get_id() );
		$this->assertCount( 1, $contract->get_items() ?? array(), 'The view carries the contract items.' );

		$due_at = $this->calls[0]['context']['due_at'];
		$this->assertInstanceOf( DateTimeImmutable::class, $due_at );
		$this->assertSame( '2026-02-15 00:00:00', $due_at->format( 'Y-m-d H:i:s' ) );
		$this->assertSame( 'UTC', $due_at->getTimezone()->getName() );
	}

	/**
	 * @testdox The dispatcher is status-blind: an on-hold contract with a due moment is notified.
	 */
	public function test_an_on_hold_contract_with_a_due_moment_is_notified(): void {
		$this->register_recording_handler();
		$this->create_due_contract( '2026-02-15 00:00:00', array( 'status' => ContractStatus::ON_HOLD ) );

		$this->assertSame( 1, $this->sut->run_batch( $this->now() ) );
		$this->assertSame( ContractStatus::ON_HOLD, $this->calls[0]['contract']->get_status() );
	}

	/**
	 * @testdox The batch limit caps notifications per tick and a non-positive limit is a no-op.
	 */
	public function test_batch_limit(): void {
		$this->register_recording_handler();
		$first = $this->create_due_contract( '2026-02-13 00:00:00' );
		$this->create_due_contract( '2026-02-14 00:00:00' );
		$this->create_due_contract( '2026-02-15 00:00:00' );

		$this->assertSame( 0, $this->sut->run_batch( $this->now(), 0 ) );
		$this->assertSame( 0, $this->sut->run_batch( $this->now(), -1 ) );
		$this->assertNull( $this->get_lock_holder( $first )['operation'], 'A no-op tick locks nothing.' );

		$this->assertSame( 1, $this->sut->run_batch( $this->now(), 1 ) );
		$this->assertSame( $first, $this->calls[0]['contract']->get_id(), 'The oldest-due contract goes first.' );
		$this->assertSame( 3, $this->sut->run_batch( $this->now() ), 'Idle handlers are notified again on the next tick.' );
	}

	/**
	 * @testdox The handler receives the dispatcher's lock in its context.
	 */
	public function test_the_handler_context_carries_the_lock(): void {
		$this->register_recording_handler();
		$this->create_due_contract();

		$this->sut->run_batch( $this->now() );

		$this->assertInstanceOf( ContractLock::class, $this->calls[0]['context']['lock'] );
		$this->assertTrue( $this->lock_state( $this->calls[0]['context']['lock'] )->is_released(), 'Released once the handler returned.' );
	}

	/**
	 * @testdox Contracts whose handler left the due moment in place sort behind a freshly due contract.
	 */
	public function test_repeat_notifications_do_not_starve_a_fresh_due_contract(): void {
		global $wpdb;

		$this->register_recording_handler();
		$stuck = array();
		for ( $i = 0; $i < 3; $i++ ) {
			$stuck[] = $this->create_due_contract( '2026-01-15 00:00:00' );
		}
		$fresh = $this->create_due_contract( '2026-02-15 00:00:00' );

		// Every stuck contract was notified before; its lock has since been released.
		$table = SchemaInstaller::get_table_name( SchemaInstaller::TABLE_CONTRACTS );
		foreach ( $stuck as $id ) {
			// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching
			$wpdb->update( $table, array( 'locked_until_gmt' => '2026-01-15 00:15:00' ), array( 'id' => $id ) );
		}

		// A batch smaller than the stuck backlog: the fresh contract still makes it in.
		$this->assertSame( 2, $this->sut->run_batch( $this->now(), 2 ) );
		$this->assertSame( $fresh, $this->calls[0]['contract']->get_id(), 'The never-notified contract goes first despite its later due moment.' );
	}

	/**
	 * @testdox Among notified contracts, the least recently locked goes first, so stuck older ones do not starve a later-due retry.
	 */
	public function test_stuck_contracts_do_not_starve_a_later_due_retry(): void {
		global $wpdb;

		$this->register_recording_handler();
		$stuck = array();
		for ( $i = 0; $i < 3; $i++ ) {
			$stuck[] = $this->create_due_contract( '2026-01-15 00:00:00' );
		}
		$retry = $this->create_due_contract( '2026-02-10 00:00:00' );

		// The stuck contracts were re-notified recently; the retry's lock expired a while ago.
		$table = SchemaInstaller::get_table_name( SchemaInstaller::TABLE_CONTRACTS );
		foreach ( $stuck as $id ) {
			// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching
			$wpdb->update( $table, array( 'locked_until_gmt' => '2026-02-14 00:15:00' ), array( 'id' => $id ) );
		}
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching
		$wpdb->update( $table, array( 'locked_until_gmt' => '2026-02-10 00:15:00' ), array( 'id' => $retry ) );

		// A batch smaller than the stuck backlog: the retry still makes it in.
		$this->assertSame( 2, $this->sut->run_batch( $this->now(), 2 ) );
		$this->assertSame( $retry, $this->calls[0]['contract']->get_id(), 'The least recently locked contract goes first despite its later due moment.' );
	}

	/**
	 * @testdox A due handler registered after the boot-time check (which found none) schedules the scan itself.
	 */
	public function test_a_handler_registered_after_the_boot_check_schedules_the_scan(): void {
		$this->assertGreaterThan( 0, did_action( 'wp_loaded' ), 'The test runs after wp_loaded, like a late registration.' );
		delete_option( self::SCHEDULE_CHECK_OPTION );

		DueDispatcher::ensure_scheduled();
		$this->assertFalse( as_next_scheduled_action( DueDispatcher::HOOK, array(), DueDispatcher::GROUP ), 'The boot check found no handler.' );

		$this->register_recording_handler();

		$this->assertNotFalse( as_next_scheduled_action( DueDispatcher::HOOK, array(), DueDispatcher::GROUP ), 'The late registration scheduled the scan.' );
	}

	/**
	 * @testdox ensure_scheduled schedules only when a handler exists, is idempotent, and unschedules when the last handler-owner leaves.
	 */
	public function test_ensure_scheduled_follows_the_handler_owners(): void {
		delete_option( self::SCHEDULE_CHECK_OPTION );

		ConsumerRegistry::register( self::OWNER );
		DueDispatcher::ensure_scheduled();
		$this->assertFalse( as_next_scheduled_action( DueDispatcher::HOOK, array(), DueDispatcher::GROUP ), 'No scan without a handler.' );

		ConsumerRegistry::reset();
		$this->register_recording_handler();
		DueDispatcher::ensure_scheduled();
		$this->assertNotFalse( as_next_scheduled_action( DueDispatcher::HOOK, array(), DueDispatcher::GROUP ) );

		delete_option( self::SCHEDULE_CHECK_OPTION );
		DueDispatcher::ensure_scheduled();
		$this->assertCount( 1, as_get_scheduled_actions( array( 'hook' => DueDispatcher::HOOK, 'group' => DueDispatcher::GROUP, 'status' => 'pending' ), 'ids' ), 'A second call does not schedule a duplicate.' ); // phpcs:ignore WordPress.Arrays.ArrayDeclarationSpacing.AssociativeArrayFound

		ConsumerRegistry::reset();
		DueDispatcher::ensure_scheduled();
		$this->assertFalse( as_next_scheduled_action( DueDispatcher::HOOK, array(), DueDispatcher::GROUP ), 'The scan is removed once no owner has a handler.' );
		$this->assertFalse( get_option( self::SCHEDULE_CHECK_OPTION, false ) );
	}

	/**
	 * @testdox Unregistering the last owner with a due handler removes the scan right away.
	 */
	public function test_unregistering_the_last_handler_owner_removes_the_scan(): void {
		delete_option( self::SCHEDULE_CHECK_OPTION );
		$this->register_recording_handler();
		DueDispatcher::ensure_scheduled();
		$this->assertNotFalse( as_next_scheduled_action( DueDispatcher::HOOK, array(), DueDispatcher::GROUP ) );

		ConsumerRegistry::unregister( self::OWNER );

		$this->assertFalse( as_next_scheduled_action( DueDispatcher::HOOK, array(), DueDispatcher::GROUP ), 'No owner has a handler, so the scan is gone.' );
	}

	/**
	 * @testdox handle_tick runs a batch at the current moment.
	 */
	public function test_handle_tick_routes_to_run_batch(): void {
		$this->register_recording_handler();
		$this->create_due_contract();

		// The booted engine registers its own dispatcher; route the tick only to the SUT.
		remove_all_actions( DueDispatcher::HOOK );
		$this->sut->register_hooks();
		do_action( DueDispatcher::HOOK ); // phpcs:ignore WooCommerce.Commenting.CommentHooks.MissingHookComment

		$this->assertCount( 1, $this->calls );
	}

	/**
	 * The scan moment the fixtures are due against.
	 */
	private function now(): DateTimeImmutable {
		return new DateTimeImmutable( '2026-02-15 00:00:00', new DateTimeZone( 'UTC' ) );
	}

	/**
	 * Register the test owner with a handler that records each call.
	 */
	private function register_recording_handler(): void {
		ConsumerRegistry::register(
			self::OWNER,
			array(
				'contract_due_handler' => function ( ContractView $contract, array $context ): void {
					$this->calls[] = array(
						'contract' => $contract,
						'context'  => $context,
					);
				},
			)
		);
	}

	/**
	 * Create an active contract with one item, due at `$next_payment_gmt`.
	 *
	 * @param string               $next_payment_gmt Due moment (UTC).
	 * @param array<string, mixed> $overrides        `Contracts::create()` fields to replace.
	 * @return int The contract id.
	 */
	private function create_due_contract( string $next_payment_gmt = '2026-02-15 00:00:00', array $overrides = array() ): int {
		$contract = Contracts::create(
			array_merge(
				array(
					'extension_slug'   => self::OWNER,
					'status'           => ContractStatus::ACTIVE,
					'customer_id'      => 1,
					'currency'         => 'USD',
					'start_gmt'        => '2026-01-15 00:00:00',
					'next_payment_gmt' => $next_payment_gmt,
					'billing_total'    => '19.99',
					'items'            => array(
						array(
							'item_name'  => 'Coffee bag',
							'item_type'  => 'line_item',
							'product_id' => 200,
							'quantity'   => '1',
							'subtotal'   => '19.99',
							'total'      => '19.99',
						),
					),
				),
				$overrides
			)
		);

		return $contract->get_id();
	}

	/**
	 * The contract's live lock holder.
	 *
	 * @param int $id Contract id.
	 * @return array{exists: bool, operation: ?string, locked_until_gmt: ?string}
	 */
	private function get_lock_holder( int $id ): array {
		return ( new ContractRepository() )->get_lock_holder( $id );
	}

	/**
	 * The ids of the notified contracts, in call order.
	 *
	 * @return array<int, int>
	 */
	private function notified_ids(): array {
		return array_map(
			static function ( array $call ): int {
				return $call['contract']->get_id();
			},
			$this->calls
		);
	}
}
