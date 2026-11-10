<?php

declare( strict_types = 1 );

namespace Automattic\WooCommerce\Tests\Internal\PushNotifications\Services;

use ActionScheduler_Action;
use ActionScheduler_Store;
use Automattic\WooCommerce\Internal\PushNotifications\DataStores\PushTokensDataStore;
use Automattic\WooCommerce\Internal\PushNotifications\Entities\PushToken;
use Automattic\WooCommerce\Internal\PushNotifications\Enums\CleanupOutcome;
use Automattic\WooCommerce\Internal\PushNotifications\PushNotifications;
use Automattic\WooCommerce\Internal\PushNotifications\Services\NotificationProcessor;
use Automattic\WooCommerce\Internal\PushNotifications\Services\NotificationStepLogger;
use Automattic\WooCommerce\Internal\PushNotifications\Services\StaleTokenCleanupService;
use Automattic\WooCommerce\RestApi\UnitTests\LoggerSpyTrait;
use Automattic\WooCommerce\Tests\Internal\PushNotifications\Helpers\PushNotificationsTestTrait;
use PHPUnit\Framework\MockObject\MockObject;
use RuntimeException;
use WC_Unit_Test_Case;
use WP_Post;

/**
 * Tests for the StaleTokenCleanupService class.
 *
 * @covers \Automattic\WooCommerce\Internal\PushNotifications\Services\StaleTokenCleanupService
 */
class StaleTokenCleanupServiceTest extends WC_Unit_Test_Case {

	use LoggerSpyTrait;
	use PushNotificationsTestTrait;

	/**
	 * The moment every test measures token age from.
	 */
	const NOW = '2026-10-23 12:00:00';

	/**
	 * The System Under Test.
	 *
	 * @var StaleTokenCleanupService
	 */
	private $sut;

	/**
	 * The push tokens data store.
	 *
	 * @var PushTokensDataStore
	 */
	private PushTokensDataStore $data_store;

	/**
	 * Whether the mocked push notifications feature reports itself enabled.
	 *
	 * @var bool
	 */
	private bool $push_enabled = true;

	/**
	 * Set up test fixtures.
	 */
	public function setUp(): void {
		parent::setUp();

		$this->data_store = new PushTokensDataStore();

		/**
		 * The feature class, enabled unless a test switches it off.
		 *
		 * @var PushNotifications|MockObject $push_notifications
		 */
		$push_notifications = $this->createMock( PushNotifications::class );
		$push_notifications->method( 'should_be_enabled' )->willReturnCallback( fn() => $this->push_enabled );

		$this->sut = new StaleTokenCleanupService();
		$this->sut->init( $this->data_store, new NotificationStepLogger(), $push_notifications );
	}

	/**
	 * @testdox Should delete a token from an app that checks in daily once it is past 90 days, and keep a recent one.
	 */
	public function test_deletes_check_in_tokens_past_the_retention_window(): void {
		$stale  = $this->create_token( '25.8', $this->days( 91 ) );
		$recent = $this->create_token( '25.8', $this->days( 89 ) );

		$summary = $this->sut->run( $this->now() );

		$this->assertNull( get_post( $stale ), 'A 25.8 token silent for 91 days should be deleted.' );
		$this->assertNotNull( get_post( $recent ), 'A 25.8 token seen 89 days ago should be kept.' );
		$this->assertSame( CleanupOutcome::COMPLETED, $summary['outcome'], 'The run should complete.' );
		$this->assertSame( 1, $summary['deleted_daily_check_in'], 'One token should be deleted under the 90-day rule.' );
	}

	/**
	 * @testdox Should treat app version $app_version from $origin as one that checks in daily.
	 * @testWith ["25.8", "com.automattic.woocommerce"]
	 *           ["25.8.0.0", "com.automattic.woocommerce"]
	 *           ["25.8.0.1", "com.automattic.woocommerce:dev"]
	 *           ["25.8-rc-1", "com.woocommerce.android"]
	 *           ["25.8.1", "com.woocommerce.android"]
	 *           ["25.10", "com.woocommerce.android:dev"]
	 *           ["26.0", "com.automattic.woocommerce"]
	 *
	 * @param string $app_version The app version the token was registered with.
	 * @param string $origin      The app the token was registered from.
	 */
	public function test_deletes_tokens_from_versions_that_check_in_daily( string $app_version, string $origin ): void {
		$token = $this->create_token( $app_version, $this->days( 91 ), $origin );

		$this->sut->run( $this->now() );

		$this->assertNull( get_post( $token ), "A {$app_version} token from {$origin} silent for 91 days should be deleted." );
	}

	/**
	 * @testdox Should keep a 91-day-old token with app version $app_version until the 180-day fallback.
	 * @testWith ["25.7"]
	 *           ["25.7.0.1"]
	 *           ["pr123-abc1234"]
	 *           [""]
	 *           [null]
	 *
	 * @param string|null $app_version The app version the token was registered with, or null for none.
	 */
	public function test_keeps_tokens_from_older_or_unknown_versions_inside_the_fallback_window( ?string $app_version ): void {
		$token = $this->create_token( $app_version, $this->days( 91 ) );

		$summary = $this->sut->run( $this->now() );

		$this->assertNotNull( get_post( $token ), 'An older or unknown app version should get the longer window.' );
		$this->assertSame( 1, $summary['kept_older_apps'], 'The kept token should be counted.' );
	}

	/**
	 * @testdox Should delete a $app_version token only when it is older than the cutoff by at least a second.
	 * @testWith ["25.8", 7776000, true]
	 *           ["25.8", 7776001, false]
	 *           ["25.7", 15552000, true]
	 *           ["25.7", 15552001, false]
	 *
	 * @param string $app_version The app version the token was registered with.
	 * @param int    $age         Seconds since the token last registered.
	 * @param bool   $kept        Whether the token should survive.
	 */
	public function test_deletes_only_tokens_strictly_older_than_the_cutoff( string $app_version, int $age, bool $kept ): void {
		$token = $this->create_token( $app_version, $age );

		$this->sut->run( $this->now() );

		$this->assertSame( $kept, get_post( $token ) instanceof WP_Post, "A {$app_version} token {$age}s old should " . ( $kept ? 'be kept.' : 'be deleted.' ) );
	}

	/**
	 * @testdox Should apply only the 180-day rule to a token whose origin is not a WooCommerce app.
	 */
	public function test_applies_the_fallback_to_tokens_from_other_origins(): void {
		$inside  = $this->create_token( '25.8', $this->days( 100 ) );
		$outside = $this->create_token( '25.8', $this->days( 181 ) );
		update_post_meta( $inside, 'origin', PushToken::ORIGIN_BROWSER );
		update_post_meta( $outside, 'origin', PushToken::ORIGIN_BROWSER );

		$this->sut->run( $this->now() );

		$this->assertNotNull( get_post( $inside ), 'A browser token seen 100 days ago should be kept.' );
		$this->assertNull( get_post( $outside ), 'A browser token silent for 181 days should be deleted.' );
	}

	/**
	 * @testdox Should spend the deletion limit on the oldest tokens, whichever rule they fall under.
	 */
	public function test_deletes_the_oldest_tokens_first_when_capped(): void {
		$fallback = array();
		for ( $i = 0; $i < 60; $i++ ) {
			$fallback[] = $this->create_token( '25.7', $this->days( 300 + $i ) );
		}

		$check_in = array();
		for ( $i = 0; $i < 60; $i++ ) {
			$check_in[] = $this->create_token( '25.8', $this->days( 100 + $i ) );
		}

		$first = $this->sut->run( $this->now() );

		$this->assertSame( CleanupOutcome::CAPPED, $first['outcome'], 'The first run should stop at the limit.' );
		$this->assertSame( 60, $first['deleted_fallback'], 'Every fallback token is older than every daily check-in token.' );
		$this->assertSame( 40, $first['deleted_daily_check_in'], 'The limit should be filled with the oldest daily check-in tokens.' );
		$this->assertSame( array_slice( $check_in, 0, 20 ), $this->existing( $check_in ), 'The 20 newest daily check-in tokens should remain.' );

		$second = $this->sut->run( $this->now() );

		$this->assertSame( CleanupOutcome::COMPLETED, $second['outcome'], 'The second run should finish.' );
		$this->assertSame( 20, $second['deleted_daily_check_in'], 'The second run should delete the rest.' );
	}

	/**
	 * @testdox Should report a completed run when it deletes exactly the limit.
	 */
	public function test_reports_completed_when_deleting_exactly_the_limit(): void {
		for ( $i = 0; $i < StaleTokenCleanupService::MAX_DELETIONS_PER_RUN; $i++ ) {
			$this->create_token( '25.8', $this->days( 91 ) );
		}
		$this->create_token( '25.7', $this->days( 100 ) );

		$summary = $this->sut->run( $this->now() );

		$this->assertSame( CleanupOutcome::COMPLETED, $summary['outcome'], 'Nothing deletable is left, so the run is not capped.' );
		$this->assertSame( StaleTokenCleanupService::MAX_DELETIONS_PER_RUN, $summary['deleted_daily_check_in'], 'Every eligible token should go.' );
	}

	/**
	 * @testdox Should finish across runs whatever old-app tokens come first, and do nothing on a repeat run.
	 */
	public function test_respects_the_deletion_limit_across_runs(): void {
		for ( $i = 0; $i < 3; $i++ ) {
			$this->create_token( '25.7', $this->days( 150 ) );
		}

		$deletable = array();
		for ( $i = 0; $i < StaleTokenCleanupService::MAX_DELETIONS_PER_RUN + 1; $i++ ) {
			$deletable[] = $this->create_token( '25.8', $this->days( 91 ) );
		}

		$first  = $this->sut->run( $this->now() );
		$second = $this->sut->run( $this->now() );
		$third  = $this->sut->run( $this->now() );

		$this->assertSame( CleanupOutcome::CAPPED, $first['outcome'], 'The first run should stop at the limit.' );
		$this->assertSame( StaleTokenCleanupService::MAX_DELETIONS_PER_RUN, $first['deleted_daily_check_in'], 'The first run should delete up to the limit.' );
		$this->assertSame( 1, $second['deleted_daily_check_in'], 'The second run should delete the one left.' );
		$this->assertSame( 0, $third['deleted_daily_check_in'], 'A repeat run should find nothing left to delete.' );
		$this->assertSame( array(), $this->existing( $deletable ), 'Every eligible token should be gone.' );
		$this->assertSame( 3, $this->data_store->count_tokens(), 'Only the old-app tokens inside the fallback window should remain.' );
	}

	/**
	 * @testdox Should report a token that cannot be deleted and carry on with the rest.
	 */
	public function test_reports_failures_and_carries_on(): void {
		$refused   = $this->create_token( '25.8', $this->days( 95 ) );
		$deletable = $this->create_token( '25.8', $this->days( 91 ) );
		$this->refuse_deletion_of( array( $refused ) );

		$summary = $this->sut->run( $this->now() );

		$this->assertSame( CleanupOutcome::COMPLETED, $summary['outcome'], 'A failed token should not stop the run.' );
		$this->assertSame( 1, $summary['failed'], 'The failure should be counted.' );
		$this->assertSame( array( $refused ), $summary['failed_token_ids'], 'The failed token should be named.' );
		$this->assertNull( get_post( $deletable ), 'The other token should still be deleted.' );
	}

	/**
	 * @testdox Should not let tokens that never delete use up the deletion limit.
	 */
	public function test_failures_do_not_count_towards_the_deletion_limit(): void {
		$refused = array();
		for ( $i = 0; $i < StaleTokenCleanupService::MAX_DELETIONS_PER_RUN + 1; $i++ ) {
			$refused[] = $this->create_token( '25.8', $this->days( 120 ) );
		}
		$deletable = $this->create_token( '25.8', $this->days( 91 ) );
		$this->refuse_deletion_of( $refused );

		$summary = $this->sut->run( $this->now() );

		$this->assertNull( get_post( $deletable ), 'A newer deletable token should be reached past the failing ones.' );
		$this->assertSame( StaleTokenCleanupService::MAX_DELETIONS_PER_RUN + 1, $summary['failed'], 'Every failure should be counted.' );
	}

	/**
	 * @testdox Should keep a $app_version token that re-registered after it was selected for deletion.
	 * @testWith ["25.8", 91]
	 *           ["25.7", 181]
	 *
	 * @param string $app_version The app version the token was registered with.
	 * @param int    $age_days    Days since the token last registered when the run starts.
	 */
	public function test_keeps_a_token_that_re_registers_during_the_run( string $app_version, int $age_days ): void {
		global $wpdb;

		$token = $this->create_token( $app_version, $this->days( $age_days ) );

		// Simulates the app checking in between the selection and the deletion.
		// 100 days is still past the 90-day cutoff, so only the 180-day cutoff
		// spares an old-app token.
		$refreshed_at = gmdate( 'Y-m-d H:i:s', $this->now() - ( '25.8' === $app_version ? 0 : $this->days( 100 ) ) );
		add_filter(
			'get_post_metadata',
			function ( $value, $object_id, $meta_key ) use ( $wpdb, $token, $refreshed_at ) {
				if ( $object_id === $token && 'origin' === $meta_key ) {
					$wpdb->update( $wpdb->posts, array( 'post_modified_gmt' => $refreshed_at ), array( 'ID' => $token ) );
				}
				return $value;
			},
			10,
			3
		);

		$this->sut->run( $this->now() );

		$this->assertNotNull( get_post( $token ), 'A token that checked in mid-run should be kept.' );
	}

	/**
	 * @testdox Should delete nothing while push notifications are off, and record that they are off.
	 */
	public function test_skips_and_records_a_pause_when_push_notifications_are_disabled(): void {
		$this->push_enabled = false;
		$token              = $this->create_token( '25.8', $this->days( 91 ) );

		$summary = $this->sut->run( $this->now() );

		$this->assertSame( CleanupOutcome::SKIPPED_DISABLED, $summary['outcome'], 'The run should be skipped.' );
		$this->assertNotNull( get_post( $token ), 'Nothing should be deleted while push notifications are off.' );
		$this->assertSame( $this->now(), (int) get_option( StaleTokenCleanupService::PAUSED_AT_OPTION ), 'The pause should be recorded.' );
	}

	/**
	 * @testdox Should not count the time push notifications were off, so a store back from 100 days off keeps its tokens until they miss 90 more days.
	 */
	public function test_waits_a_full_window_after_push_notifications_come_back_on(): void {
		$off_at    = $this->now();
		$check_in  = $this->create_token( '25.8', 0 );
		$older_app = $this->create_token( '25.7', 0 );

		$this->push_enabled = false;
		$this->sut->run( $off_at + $this->days( 1 ) );

		$this->push_enabled = true;
		$back_at            = $off_at + $this->days( 100 );
		$resumed            = $this->sut->run( $back_at );

		$this->assertSame( CleanupOutcome::SKIPPED_RECENTLY_RESUMED, $resumed['outcome'], 'The first run after coming back should delete nothing.' );
		$this->assertNotNull( get_post( $check_in ), 'A token silent only because push was off should be kept.' );
		$this->assertSame( $back_at, (int) get_option( StaleTokenCleanupService::RESUMED_AT_OPTION ), 'The resume should be recorded.' );
		$this->assertFalse( get_option( StaleTokenCleanupService::PAUSED_AT_OPTION ), 'The pause should be cleared.' );

		$this->sut->run( $back_at + $this->days( 89 ) );
		$this->assertNotNull( get_post( $check_in ), 'The 90-day rule should still wait 89 days after coming back.' );

		$after_90 = $this->sut->run( $back_at + $this->days( 91 ) );
		$this->assertNull( get_post( $check_in ), 'The 90-day rule should apply once 90 days have passed since coming back.' );
		$this->assertNotNull( get_post( $older_app ), 'The 180-day rule should still be waiting.' );
		$this->assertNull( $after_90['fallback_cutoff'], 'The summary should show the 180-day rule as paused.' );

		$this->sut->run( $back_at + $this->days( 181 ) );
		$this->assertNull( get_post( $older_app ), 'The 180-day rule should apply once 180 days have passed since coming back.' );
		$this->assertFalse( get_option( StaleTokenCleanupService::RESUMED_AT_OPTION ), 'The resume time should be dropped once it no longer matters.' );
	}

	/**
	 * @testdox Should delete nothing when the filter returns $returned.
	 * @testWith ["false"]
	 *           ["'false'"]
	 *           ["array"]
	 *           ["throw"]
	 *
	 * @param string $returned What the filter callback does.
	 */
	public function test_skips_when_the_filter_switches_cleanup_off( string $returned ): void {
		add_filter(
			'woocommerce_push_notification_stale_token_cleanup_enabled',
			function () use ( $returned ) {
				switch ( $returned ) {
					case 'throw':
						throw new RuntimeException( 'A broken callback.' );
					case 'array':
						return array( true );
					case "'false'":
						return 'false';
					default:
						return false;
				}
			}
		);
		$token = $this->create_token( '25.8', $this->days( 91 ) );

		$summary = $this->sut->run( $this->now() );

		$this->assertSame( CleanupOutcome::SKIPPED_FILTER, $summary['outcome'], 'The run should be skipped.' );
		$this->assertNotNull( get_post( $token ), 'Nothing should be deleted.' );
		$this->assertLogged( 'info', 'Stale token cleanup: skipped filter', array( 'source' => PushNotifications::FEATURE_NAME ) );
	}

	/**
	 * @testdox Should log the run's counts even when step logging is switched off.
	 */
	public function test_logs_a_summary_when_step_logging_is_off(): void {
		add_filter( 'woocommerce_push_notification_step_logging_enabled', '__return_false' );
		$token = $this->create_token( '25.8', $this->days( 91 ) );
		$this->create_token( '25.7', $this->days( 91 ) );

		$this->sut->run( $this->now() );

		$this->assertLogged(
			'info',
			'Stale token cleanup: completed',
			array(
				'source'                 => PushNotifications::FEATURE_NAME,
				'cutoff'                 => gmdate( 'Y-m-d H:i:s', $this->now() - $this->days( 90 ) ),
				'tokens_before'          => 2,
				'stale'                  => 2,
				'kept_older_apps'        => 1,
				'deleted_daily_check_in' => 1,
				'deleted_token_ids'      => array( $token ),
			)
		);
	}

	/**
	 * @testdox Should log a failed outcome, with the error, when selecting the stale tokens throws.
	 */
	public function test_logs_a_failed_outcome_when_selection_throws(): void {
		$data_store = $this->createMock( PushTokensDataStore::class );
		$data_store->method( 'get_stale_token_ids' )->willThrowException( new RuntimeException( 'Database gone.' ) );
		$push_notifications = $this->createMock( PushNotifications::class );
		$push_notifications->method( 'should_be_enabled' )->willReturn( true );
		$this->sut->init( $data_store, new NotificationStepLogger(), $push_notifications );

		$this->sut->handle_stale_token_cleanup();

		$this->assertLogged(
			'info',
			'Stale token cleanup: failed',
			array( 'error' => 'Database gone.' )
		);
	}

	/**
	 * @testdox Should keep one daily cleanup scheduled a day out while push notifications are on.
	 */
	public function test_ensure_recurring_actions_schedules_one_daily_cleanup(): void {
		$this->sut->handle_ensure_recurring_actions();
		$this->sut->handle_ensure_recurring_actions();

		$actions = as_get_scheduled_actions(
			array(
				'hook'   => StaleTokenCleanupService::CLEANUP_HOOK,
				'group'  => NotificationProcessor::ACTION_SCHEDULER_GROUP,
				'status' => ActionScheduler_Store::STATUS_PENDING,
			)
		);

		$this->assertCount( 1, $actions, 'Scheduling twice should leave one pending action.' );

		/**
		 * The pending cleanup.
		 *
		 * @var ActionScheduler_Action $action
		 */
		$action   = reset( $actions );
		$schedule = $action->get_schedule();
		$next     = $schedule->get_date()->getTimestamp();

		$this->assertTrue( $schedule->is_recurring(), 'The cleanup should recur.' );
		$this->assertSame( DAY_IN_SECONDS, (int) $schedule->get_recurrence(), 'The cleanup should run daily.' );
		$this->assertGreaterThan( time() + DAY_IN_SECONDS - MINUTE_IN_SECONDS, $next, 'The first run should wait a day.' );
		$this->assertLessThanOrEqual( time() + DAY_IN_SECONDS, $next, 'The first run should not wait longer than a day.' );
	}

	/**
	 * @testdox Should remove the cleanup and record a pause while push notifications are off.
	 */
	public function test_ensure_recurring_actions_unschedules_while_disabled(): void {
		$this->sut->handle_ensure_recurring_actions();
		$this->push_enabled = false;

		$this->sut->handle_ensure_recurring_actions();

		$this->assertFalse( as_next_scheduled_action( StaleTokenCleanupService::CLEANUP_HOOK ), 'The cleanup should be unscheduled.' );
		$this->assertNotFalse( get_option( StaleTokenCleanupService::PAUSED_AT_OPTION ), 'The pause should be recorded.' );
	}

	/**
	 * @testdox Should remove the cleanup and record a pause when WooCommerce is deactivated.
	 */
	public function test_plugin_deactivation_unschedules_and_records_a_pause(): void {
		$this->sut->handle_ensure_recurring_actions();

		$this->sut->handle_plugin_deactivation();

		$this->assertFalse( as_next_scheduled_action( StaleTokenCleanupService::CLEANUP_HOOK ), 'The cleanup should be unscheduled.' );
		$this->assertNotFalse( get_option( StaleTokenCleanupService::PAUSED_AT_OPTION ), 'The pause should be recorded.' );
	}

	/**
	 * @testdox Should register the cleanup, scheduling and deactivation callbacks.
	 */
	public function test_register_adds_the_callbacks(): void {
		$this->sut->register();

		$this->assertNotFalse( has_action( StaleTokenCleanupService::CLEANUP_HOOK, array( $this->sut, 'handle_stale_token_cleanup' ) ), 'The cleanup callback should be registered.' );
		$this->assertNotFalse( has_action( 'action_scheduler_ensure_recurring_actions', array( $this->sut, 'handle_ensure_recurring_actions' ) ), 'The scheduling callback should be registered.' );
		$this->assertNotFalse( has_action( 'deactivate_' . WC_PLUGIN_BASENAME, array( $this->sut, 'handle_plugin_deactivation' ) ), 'The deactivation callback should be registered.' );
	}

	/**
	 * Creates a token last registered a number of seconds before {@see self::NOW}.
	 *
	 * @param string|null $app_version The app version to register with, or null for none.
	 * @param int         $age         How many seconds ago the token last registered.
	 * @param string      $origin      The app the token is registered from.
	 * @return int The token's post ID.
	 */
	private function create_token( ?string $app_version, int $age, string $origin = PushToken::ORIGIN_WOOCOMMERCE_IOS ): int {
		$is_android = in_array( $origin, array( PushToken::ORIGIN_WOOCOMMERCE_ANDROID, PushToken::ORIGIN_WOOCOMMERCE_ANDROID_DEV ), true );

		$token = $this->data_store->create(
			array(
				'user_id'       => 1,
				'token'         => 'test_token_' . wp_rand(),
				'platform'      => $is_android ? PushToken::PLATFORM_ANDROID : PushToken::PLATFORM_APPLE,
				'device_uuid'   => 'test-device-uuid-' . wp_rand(),
				'origin'        => $origin,
				'device_locale' => 'en_US',
				'metadata'      => null === $app_version ? array() : array( 'app_version' => $app_version ),
			)
		);

		$modified = gmdate( 'Y-m-d H:i:s', $this->now() - $age );
		$this->set_token_dates( (int) $token->get_id(), $modified, $modified );

		return (int) $token->get_id();
	}

	/**
	 * Makes WordPress refuse to delete the given posts.
	 *
	 * @param int[] $ids The post IDs.
	 * @return void
	 */
	private function refuse_deletion_of( array $ids ): void {
		add_filter(
			'pre_delete_post',
			fn( $check, $post ) => in_array( $post->ID, $ids, true ) ? false : $check,
			10,
			2
		);
	}

	/**
	 * Returns the given post IDs that still exist, in the order given.
	 *
	 * @param int[] $ids The post IDs.
	 * @return int[]
	 */
	private function existing( array $ids ): array {
		return array_values( array_filter( $ids, fn( int $id ) => null !== get_post( $id ) ) );
	}

	/**
	 * Converts days to seconds.
	 *
	 * @param int $days The number of days.
	 * @return int
	 */
	private function days( int $days ): int {
		return $days * DAY_IN_SECONDS;
	}

	/**
	 * Returns {@see self::NOW} as a Unix timestamp.
	 *
	 * @return int
	 */
	private function now(): int {
		return (int) strtotime( self::NOW . ' UTC' );
	}
}
