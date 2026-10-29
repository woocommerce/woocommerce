<?php
declare( strict_types = 1 );

namespace Automattic\WooCommerce\Tests\Admin\RemoteInboxNotifications;

use ActionScheduler;
use ActionScheduler_Store;
use Automattic\WooCommerce\Admin\Notes\Note;
use Automattic\WooCommerce\Admin\RemoteInboxNotifications\RemoteInboxNotificationsDataSourcePoller;
use Automattic\WooCommerce\Admin\RemoteInboxNotifications\RemoteInboxNotificationsEngine;
use Automattic\WooCommerce\Admin\RemoteSpecs\DataSourcePoller;
use WC_Action_Queue;
use WC_Queue;
use WC_Queue_Interface;
use WC_Unit_Test_Case;
use WP_Error;

/**
 * Tests for upgrade-triggered remote inbox scheduling and withdrawn alert reconciliation.
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
	 * Transient holding the latest complete feed read.
	 */
	private const SNAPSHOT_TRANSIENT = 'woocommerce_admin_remote_inbox_notifications_published_snapshot';

	/**
	 * Initialize only the engine's update listener. The parent restores hooks and database fixtures.
	 */
	public function setUp(): void {
		parent::setUp();
		// An upgrade during test bootstrap can leave work outside the test transaction.
		$store      = ActionScheduler::store();
		$action_ids = $store->query_actions(
			array(
				'hook'     => self::HOOK,
				'group'    => self::GROUP,
				'per_page' => -1,
			)
		);
		foreach ( $action_ids as $action_id ) {
			$store->delete_action( $action_id );
		}
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
	 * @param string      $queue_class   Queue interface or class to replace.
	 * @param string|null $active_status Existing active work, or null for an empty queue.
	 */
	public function test_replacement_queue_controls_scheduling( string $queue_class, ?string $active_status ): void {
		$queue         = $this->createMock( $queue_class );
		$should_create = null === $active_status;
		if ( $should_create ) {
			as_schedule_single_action( time() + 60, self::HOOK, array(), self::GROUP );
		}
		$this->assertSame( $should_create, as_has_scheduled_action( self::HOOK, array(), self::GROUP ), 'Action Scheduler must disagree with the replacement queue so bypassing its search would fail this test.' );

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
	 * @return array<string, array{string, string|null}>
	 */
	public function replacement_queue_provider(): array {
		$cases = array();
		foreach ( array( WC_Queue_Interface::class, WC_Action_Queue::class ) as $queue_class ) {
			$cases[ $queue_class . ' empty' ]   = array( $queue_class, null );
			$cases[ $queue_class . ' pending' ] = array( $queue_class, ActionScheduler_Store::STATUS_PENDING );
			$cases[ $queue_class . ' running' ] = array( $queue_class, ActionScheduler_Store::STATUS_RUNNING );
		}
		return $cases;
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

	/**
	 * @testdox A complete feed read hides only unactioned WooCommerce.com alerts it no longer contains.
	 */
	public function test_run_hides_only_withdrawn_remote_alerts(): void {
		$published   = $this->create_note( 'remote-alert-published' );
		$withdrawn   = $this->create_note( 'remote-alert-withdrawn' );
		$error       = $this->create_note( 'remote-error-withdrawn', 'woocommerce.com', Note::E_WC_ADMIN_NOTE_ERROR );
		$local       = $this->create_note( 'local-alert', 'woocommerce' );
		$information = $this->create_note( 'remote-information', 'woocommerce.com', Note::E_WC_ADMIN_NOTE_INFORMATIONAL );
		$actioned    = $this->create_note( 'remote-alert-actioned', 'woocommerce.com', Note::E_WC_ADMIN_NOTE_UPDATE, Note::E_WC_ADMIN_NOTE_ACTIONED );
		$snoozed     = $this->create_note( 'remote-alert-snoozed', 'woocommerce.com', Note::E_WC_ADMIN_NOTE_UPDATE, Note::E_WC_ADMIN_NOTE_SNOOZED );
		$deleted     = $this->create_note( 'remote-alert-deleted' );
		$deleted->set_is_deleted( true );
		$deleted->save();
		$this->arrange_feed( array( $this->create_spec( 'remote-alert-published' ) ), time() );

		RemoteInboxNotificationsEngine::run();

		$this->assertSame( Note::E_WC_ADMIN_NOTE_PENDING, $this->get_status( $withdrawn ), 'A withdrawn update alert must be hidden.' );
		$this->assertSame( Note::E_WC_ADMIN_NOTE_PENDING, $this->get_status( $error ), 'A withdrawn error alert must be hidden.' );
		$this->assertSame( Note::E_WC_ADMIN_NOTE_UNACTIONED, $this->get_status( $published ), 'A published alert must stay visible.' );
		$this->assertSame( Note::E_WC_ADMIN_NOTE_UNACTIONED, $this->get_status( $local ), 'Notes from other sources must not change.' );
		$this->assertSame( Note::E_WC_ADMIN_NOTE_UNACTIONED, $this->get_status( $information ), 'Inbox notes are outside the Store Alerts scope.' );
		$this->assertSame( Note::E_WC_ADMIN_NOTE_ACTIONED, $this->get_status( $actioned ), 'A merchant action must be preserved.' );
		$this->assertSame( Note::E_WC_ADMIN_NOTE_SNOOZED, $this->get_status( $snoozed ), 'A snoozed alert must keep its reminder.' );
		$this->assertSame( Note::E_WC_ADMIN_NOTE_UNACTIONED, $this->get_status( $deleted ), 'A deleted note must not be rewritten.' );
	}

	/**
	 * @testdox Without a complete feed read, no alert is hidden.
	 */
	public function test_run_without_published_snapshot_hides_nothing(): void {
		$withdrawn = $this->create_note( 'remote-alert-withdrawn' );
		$this->arrange_feed( array( $this->create_spec( 'remote-alert-published' ) ), null );

		RemoteInboxNotificationsEngine::run();

		$this->assertSame( Note::E_WC_ADMIN_NOTE_UNACTIONED, $this->get_status( $withdrawn ) );
	}

	/**
	 * @testdox An alert that became visible after, or shortly before, the complete read is not hidden by it.
	 */
	public function test_run_does_not_hide_alerts_newer_than_the_published_snapshot(): void {
		$fetched_at       = time() - 2 * HOUR_IN_SECONDS;
		$after_read       = $this->create_note( 'remote-alert-after-read', 'woocommerce.com', Note::E_WC_ADMIN_NOTE_UPDATE, Note::E_WC_ADMIN_NOTE_UNACTIONED, $fetched_at + 60 );
		$shortly_before   = $this->create_note( 'remote-alert-shortly-before-read', 'woocommerce.com', Note::E_WC_ADMIN_NOTE_UPDATE, Note::E_WC_ADMIN_NOTE_UNACTIONED, $fetched_at - 30 * MINUTE_IN_SECONDS );
		$well_before_read = $this->create_note( 'remote-alert-well-before-read', 'woocommerce.com', Note::E_WC_ADMIN_NOTE_UPDATE, Note::E_WC_ADMIN_NOTE_UNACTIONED, $fetched_at - 2 * HOUR_IN_SECONDS );
		$this->arrange_feed( array( $this->create_spec( 'remote-alert-published' ) ), $fetched_at );

		RemoteInboxNotificationsEngine::run();

		$this->assertSame( Note::E_WC_ADMIN_NOTE_UNACTIONED, $this->get_status( $after_read ), 'An older read, for example from another locale, must not hide a newer alert.' );
		$this->assertSame( Note::E_WC_ADMIN_NOTE_UNACTIONED, $this->get_status( $shortly_before ), 'The read may have been served from a cache.' );
		$this->assertSame( Note::E_WC_ADMIN_NOTE_PENDING, $this->get_status( $well_before_read ) );
	}

	/**
	 * @testdox An alert whose last status change is unknown is not hidden.
	 */
	public function test_run_does_not_hide_alerts_with_an_unknown_date(): void {
		global $wpdb;
		$undated = $this->create_note( 'remote-alert-undated' );
		$wpdb->update( $wpdb->prefix . 'wc_admin_notes', array( 'date_created' => '0000-00-00 00:00:00' ), array( 'note_id' => $undated->get_id() ) );
		$this->arrange_feed( array( $this->create_spec( 'remote-alert-published' ) ), time() );

		RemoteInboxNotificationsEngine::run();

		$this->assertSame( Note::E_WC_ADMIN_NOTE_UNACTIONED, $this->get_status( $undated ) );
	}

	/**
	 * @testdox A filtered spec with a non-scalar slug does not stop reconciliation.
	 */
	public function test_run_ignores_non_scalar_slugs_from_filtered_specs(): void {
		$withdrawn = $this->create_note( 'remote-alert-withdrawn' );
		$this->arrange_feed( array( $this->create_spec( 'remote-alert-published' ) ), time() );
		$malformed       = $this->create_spec( 'remote-alert-malformed' );
		$malformed->slug = (object) array( 'name' => 'remote-alert-malformed' );
		add_filter(
			DataSourcePoller::FILTER_NAME_SPECS,
			fn( $specs, $poller_id ) => RemoteInboxNotificationsDataSourcePoller::ID === $poller_id ? array_merge( array_values( $specs ), array( $malformed ) ) : $specs,
			10,
			2
		);
		$this->setExpectedIncorrectUsage( 'wpdb::prepare' );

		RemoteInboxNotificationsEngine::run();

		$this->assertSame( Note::E_WC_ADMIN_NOTE_PENDING, $this->get_status( $withdrawn ) );
	}

	/**
	 * @testdox Specs added or removed by the specs filter keep their alerts visible.
	 */
	public function test_run_preserves_alerts_for_filtered_specs(): void {
		$added   = $this->create_note( 'remote-alert-added-by-filter' );
		$removed = $this->create_note( 'remote-alert-removed-by-filter' );
		$this->arrange_feed(
			array( $this->create_spec( 'remote-alert-published' ), $this->create_spec( 'remote-alert-removed-by-filter' ) ),
			time()
		);
		add_filter(
			DataSourcePoller::FILTER_NAME_SPECS,
			fn( $specs, $poller_id ) => RemoteInboxNotificationsDataSourcePoller::ID === $poller_id
				? array( $this->create_spec( 'remote-alert-published' ), $this->create_spec( 'remote-alert-added-by-filter', null ) )
				: $specs,
			10,
			2
		);

		RemoteInboxNotificationsEngine::run();

		$this->assertSame( Note::E_WC_ADMIN_NOTE_UNACTIONED, $this->get_status( $added ), 'A spec added by a filter is published for this site.' );
		$this->assertSame( Note::E_WC_ADMIN_NOTE_UNACTIONED, $this->get_status( $removed ), 'A spec removed by a filter is still in the feed.' );
	}

	/**
	 * @testdox A republished alert becomes visible again, while one the merchant dismissed stays dismissed.
	 */
	public function test_republished_alert_returns_without_undoing_merchant_actions(): void {
		$withdrawn = $this->create_note( 'remote-alert-withdrawn' );
		$dismissed = $this->create_note( 'remote-alert-dismissed', 'woocommerce.com', Note::E_WC_ADMIN_NOTE_UPDATE, Note::E_WC_ADMIN_NOTE_ACTIONED );
		$this->arrange_feed( array( $this->create_spec( 'remote-alert-published' ) ), time() );
		RemoteInboxNotificationsEngine::run();
		$this->assertSame( Note::E_WC_ADMIN_NOTE_PENDING, $this->get_status( $withdrawn ) );

		$republished = array( $this->create_spec( 'remote-alert-withdrawn' ), $this->create_spec( 'remote-alert-dismissed' ) );
		foreach ( $republished as $spec ) {
			$spec->rules = array( (object) array( 'type' => 'pass' ) );
		}
		$this->arrange_feed( $republished, time() );
		RemoteInboxNotificationsEngine::run();

		$this->assertSame( Note::E_WC_ADMIN_NOTE_UNACTIONED, $this->get_status( $withdrawn ), 'A republished alert must be shown again.' );
		$this->assertSame( Note::E_WC_ADMIN_NOTE_ACTIONED, $this->get_status( $dismissed ), 'Republishing must not undo a dismissal.' );
	}


	/**
	 * @testdox Canonical identities reconcile alerts despite invalid specs or an auxiliary outage.
	 * @testWith ["valid", false]
	 *           ["missing_type", false]
	 *           ["missing_locale", false]
	 *           ["invalid_rule", false]
	 *           ["missing_type", true]
	 *
	 * @param string $shape        Shape of the published spec.
	 * @param bool   $cached_specs Whether an older executable cache exists.
	 */
	public function test_run_reconciles_canonical_identities( string $shape, bool $cached_specs ): void {
		$published = $this->create_note( 'remote-alert-published' );
		$withdrawn = $this->create_note( 'remote-alert-withdrawn' );
		$cached    = $this->create_note( 'remote-alert-cached' );
		$spec      = $this->create_spec( 'remote-alert-published' );
		if ( 'missing_type' === $shape ) {
			unset( $spec->type );
		} elseif ( 'missing_locale' === $shape ) {
			$spec->locales = array();
		} elseif ( 'invalid_rule' === $shape ) {
			$spec->rules = array( (object) array( 'type' => 'publish_before_time' ) );
		}

		$poller = RemoteInboxNotificationsDataSourcePoller::get_instance();
		$this->arrange_feed( array( $this->create_spec( 'remote-alert-cached' ) ), null );
		if ( ! $cached_specs ) {
			$poller->delete_specs_transient();
		}
		$canonical_url = RemoteInboxNotificationsDataSourcePoller::get_data_sources()[0];
		add_filter(
			DataSourcePoller::FILTER_NAME,
			fn( $sources, $id ) => RemoteInboxNotificationsDataSourcePoller::ID === $id ? array( $canonical_url, 'https://example.com/unavailable-feed' ) : $sources,
			10,
			2
		);
		add_filter(
			'pre_http_request',
			static function ( $preempt, $args, $url ) use ( $canonical_url, $spec ) {
				unset( $args );
				if ( 0 !== strpos( $url, $canonical_url ) ) {
					return $preempt;
				}
				return array(
					'body'     => wp_json_encode( array( $spec ) ),
					'headers'  => array(),
					'response' => array( 'code' => 200 ),
				);
			},
			20,
			3
		);

		$poller->read_specs_from_data_sources();
		RemoteInboxNotificationsEngine::run();

		$this->assertSame( Note::E_WC_ADMIN_NOTE_UNACTIONED, $this->get_status( $published ), 'Published identities are protected even when their specs cannot run.' );
		$this->assertSame( Note::E_WC_ADMIN_NOTE_PENDING, $this->get_status( $withdrawn ), 'Canonical evidence must not depend on an auxiliary feed or runnable specs.' );
		$this->assertSame( $cached_specs ? Note::E_WC_ADMIN_NOTE_UNACTIONED : Note::E_WC_ADMIN_NOTE_PENDING, $this->get_status( $cached ), 'Previously cached specs remain protected when processed.' );
	}

	/**
	 * @testdox A malformed specs filter result does not authorize withdrawal.
	 * @testWith [null]
	 *           [123]
	 *           ["invalid"]
	 *
	 * @param mixed $filtered_specs Malformed filtered specs.
	 */
	public function test_run_ignores_invalid_filtered_specs( $filtered_specs ): void {
		$withdrawn = $this->create_note( 'remote-alert-withdrawn' );
		$this->arrange_feed( array( $this->create_spec( 'remote-alert-published' ) ), time() );
		add_filter(
			DataSourcePoller::FILTER_NAME_SPECS,
			fn( $specs, $id ) => RemoteInboxNotificationsDataSourcePoller::ID === $id ? $filtered_specs : $specs,
			10,
			2
		);

		RemoteInboxNotificationsEngine::run();

		$this->assertSame( Note::E_WC_ADMIN_NOTE_UNACTIONED, $this->get_status( $withdrawn ) );
	}

	/**
	 * @testdox A withdrawal logs its persisted transition once, without logging preserved alerts.
	 */
	public function test_run_logs_withdrawal_once(): void {
		$withdrawn = $this->create_note( 'remote-alert-withdrawn' );
		$this->create_note( 'remote-alert-published' );
		$fetched_at = time();
		$this->arrange_feed( array( $this->create_spec( 'remote-alert-published' ) ), $fetched_at );
		$logger = $this->createMock( \WC_Logger_Interface::class );
		$logger->expects( $this->once() )->method( 'info' )->with(
			$this->isType( 'string' ),
			$this->callback(
				function ( $context ) use ( $withdrawn, $fetched_at ) {
					$this->assertSame( Note::E_WC_ADMIN_NOTE_PENDING, $this->get_status( $withdrawn ), 'Only a persisted transition should be logged.' );
					$this->assertSame( 'remote-inbox-notifications', $context['source'] );
					$this->assertSame( $withdrawn->get_id(), $context['note_id'] );
					$this->assertSame( 'remote-alert-withdrawn', $context['slug'] );
					$this->assertSame( $fetched_at, $context['fetched_at'] );
					return true;
				}
			)
		);
		add_filter( 'woocommerce_logging_class', static fn() => $logger );

		RemoteInboxNotificationsEngine::run();
		RemoteInboxNotificationsEngine::run();

		$this->assertSame( Note::E_WC_ADMIN_NOTE_PENDING, $this->get_status( $withdrawn ) );
	}

	/**
	 * Cache the given specs for the current locale and record a matching published snapshot.
	 *
	 * @param object[] $specs      Specs the feed returned.
	 * @param int|null $fetched_at Time of the complete read, or null when there is none.
	 */
	private function arrange_feed( array $specs, ?int $fetched_at ): void {
		$keyed_specs = array_column( $specs, null, 'slug' );
		set_transient( 'woocommerce_admin_' . RemoteInboxNotificationsDataSourcePoller::ID . '_specs', array( get_user_locale() => $keyed_specs ), DAY_IN_SECONDS );

		if ( null === $fetched_at ) {
			delete_transient( self::SNAPSHOT_TRANSIENT );
		} else {
			set_transient(
				self::SNAPSHOT_TRANSIENT,
				array(
					'fetched_at' => $fetched_at,
					'slugs'      => array_keys( $keyed_specs ),
				),
				DAY_IN_SECONDS
			);
		}

		update_option( RemoteInboxNotificationsEngine::STORED_STATE_OPTION_NAME, new \stdClass(), false );
		add_filter( 'pre_http_request', array( $this, 'block_http_request' ) );
	}

	/**
	 * Keep the engine from reaching the network.
	 *
	 * @return WP_Error Blocked request.
	 */
	public function block_http_request(): WP_Error {
		return new WP_Error( 'http_request_blocked', 'Remote inbox tests must not reach the network.' );
	}

	/**
	 * Create a note that last changed status at the given time.
	 *
	 * @param string $name         Note name.
	 * @param string $source       Note source.
	 * @param string $type         Note type.
	 * @param string $status       Note status.
	 * @param int    $date_created Time of the last status change; defaults to a day ago.
	 * @return Note Saved note.
	 */
	private function create_note(
		string $name,
		string $source = 'woocommerce.com',
		string $type = Note::E_WC_ADMIN_NOTE_UPDATE,
		string $status = Note::E_WC_ADMIN_NOTE_UNACTIONED,
		int $date_created = 0
	): Note {
		$note = new Note();
		$note->set_name( $name );
		$note->set_title( 'Remote alert' );
		$note->set_content( 'Remote alert content.' );
		$note->set_source( $source );
		$note->set_type( $type );
		$note->set_status( $status );
		$note->save();
		// Creating a note always stamps the current time; only an update keeps an earlier one.
		$note->set_date_created( $date_created ? $date_created : time() - DAY_IN_SECONDS );
		$note->save();

		return $note;
	}

	/**
	 * Read a note's stored status.
	 *
	 * @param Note $note Note to read.
	 * @return string Stored status.
	 */
	private function get_status( Note $note ): string {
		return ( new Note( $note->get_id() ) )->get_status();
	}

	/**
	 * Create a minimal remote alert spec.
	 *
	 * @param string      $slug   Spec slug.
	 * @param string|null $source Spec source, or null to omit it.
	 * @return object Remote spec.
	 */
	private function create_spec( string $slug, ?string $source = 'woocommerce.com' ): object {
		$spec = (object) array(
			'slug'    => $slug,
			'status'  => Note::E_WC_ADMIN_NOTE_UNACTIONED,
			'type'    => Note::E_WC_ADMIN_NOTE_UPDATE,
			'locales' => array(
				(object) array(
					'locale'  => 'en_US',
					'title'   => 'Remote alert',
					'content' => 'Remote alert content.',
				),
			),
		);
		if ( null !== $source ) {
			$spec->source = $source;
		}

		return $spec;
	}
}
