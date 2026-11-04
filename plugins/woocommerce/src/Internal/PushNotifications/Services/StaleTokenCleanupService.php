<?php

declare( strict_types = 1 );

namespace Automattic\WooCommerce\Internal\PushNotifications\Services;

defined( 'ABSPATH' ) || exit;

use Automattic\WooCommerce\Internal\PushNotifications\DataStores\PushTokensDataStore;
use Automattic\WooCommerce\Internal\PushNotifications\Entities\PushToken;
use Automattic\WooCommerce\Internal\PushNotifications\Enums\CleanupOutcome;
use Automattic\WooCommerce\Internal\PushNotifications\PushNotifications;
use Throwable;

/**
 * Deletes push tokens that no app has re-registered for a long time.
 *
 * Apps from 25.8 check in daily while used, so their tokens go after
 * {@see self::RETENTION_DAYS} days of silence; any other token after
 * {@see self::FALLBACK_RETENTION_DAYS}. See ROADMAP.md for the reasoning.
 *
 * @since 11.4.0
 *
 * @internal
 */
class StaleTokenCleanupService {
	/**
	 * Action Scheduler hook for the daily cleanup.
	 */
	const CLEANUP_HOOK = 'wc_push_notification_stale_token_cleanup';

	/**
	 * Days a token from an app that checks in daily is kept without a registration.
	 */
	const RETENTION_DAYS = 90;

	/**
	 * Days any other token is kept without a registration.
	 */
	const FALLBACK_RETENTION_DAYS = 180;

	/**
	 * The first app version that checks in daily.
	 */
	const MIN_CHECK_IN_APP_VERSION = '25.8';

	/**
	 * Origins of the apps that check in daily.
	 */
	const CHECK_IN_ORIGINS = array(
		PushToken::ORIGIN_WOOCOMMERCE_ANDROID,
		PushToken::ORIGIN_WOOCOMMERCE_ANDROID_DEV,
		PushToken::ORIGIN_WOOCOMMERCE_IOS,
		PushToken::ORIGIN_WOOCOMMERCE_IOS_DEV,
	);

	/**
	 * How many stale tokens to load and classify at once.
	 */
	const CHUNK_SIZE = 100;

	/**
	 * The most tokens one run deletes, so a bug cannot empty a store at once.
	 */
	const MAX_DELETIONS_PER_RUN = 100;

	/**
	 * The most deletions one run attempts, counting failures, so tokens that
	 * never delete cannot make a run endless.
	 */
	const MAX_ATTEMPTS_PER_RUN = 500;

	/**
	 * Option set while push notifications are off, as a Unix timestamp.
	 */
	const PAUSED_AT_OPTION = 'woocommerce_push_notification_cleanup_paused_at';

	/**
	 * Option holding when push notifications came back on, as a Unix timestamp.
	 *
	 * Apps cannot re-register while push notifications are off, so silence from
	 * before this moment does not count towards a token's age.
	 */
	const RESUMED_AT_OPTION = 'woocommerce_push_notification_cleanup_resumed_at';

	/**
	 * Step name for the run's log line.
	 */
	const LOG_STEP = 'stale_token_cleanup';

	/**
	 * The push tokens data store.
	 *
	 * @var PushTokensDataStore
	 */
	private PushTokensDataStore $data_store;

	/**
	 * The step logger.
	 *
	 * @var NotificationStepLogger
	 */
	private NotificationStepLogger $step_logger;

	/**
	 * The push notifications feature class.
	 *
	 * @var PushNotifications
	 */
	private PushNotifications $push_notifications;

	/**
	 * Initialize dependencies.
	 *
	 * @since 11.4.0
	 *
	 * @internal
	 *
	 * @param PushTokensDataStore    $data_store         The push tokens data store.
	 * @param NotificationStepLogger $step_logger        The step logger.
	 * @param PushNotifications      $push_notifications The push notifications feature class.
	 */
	final public function init(
		PushTokensDataStore $data_store,
		NotificationStepLogger $step_logger,
		PushNotifications $push_notifications
	): void {
		$this->data_store         = $data_store;
		$this->step_logger        = $step_logger;
		$this->push_notifications = $push_notifications;
	}

	/**
	 * Registers the cleanup, scheduling and deactivation callbacks.
	 *
	 * Called whether or not push notifications are enabled, so the schedule
	 * follows the store's state and an action left pending never lacks a callback.
	 *
	 * @since 11.4.0
	 *
	 * @return void
	 */
	public function register(): void {
		add_action( self::CLEANUP_HOOK, array( $this, 'handle_stale_token_cleanup' ) );
		add_action( 'action_scheduler_ensure_recurring_actions', array( $this, 'handle_ensure_recurring_actions' ) );

		if ( defined( 'WC_PLUGIN_BASENAME' ) ) {
			add_action( 'deactivate_' . WC_PLUGIN_BASENAME, array( $this, 'handle_plugin_deactivation' ) );
		}
	}

	/**
	 * Handles the daily action_scheduler_ensure_recurring_actions hook.
	 *
	 * Keeps the cleanup scheduled while push notifications are on, and removes
	 * it while they are off.
	 *
	 * @since 11.4.0
	 *
	 * @internal
	 *
	 * @return void
	 */
	public function handle_ensure_recurring_actions(): void {
		if ( ! $this->push_notifications->should_be_enabled() ) {
			$this->pause();
			return;
		}

		$this->note_resumed( time() );

		// The first run waits a day, so a store's first cleanup never follows
		// straight on from the update or switch that scheduled it.
		as_schedule_recurring_action(
			time() + DAY_IN_SECONDS,
			DAY_IN_SECONDS,
			self::CLEANUP_HOOK,
			array(),
			NotificationProcessor::ACTION_SCHEDULER_GROUP,
			true
		);
	}

	/**
	 * Handles WooCommerce being deactivated.
	 *
	 * @since 11.4.0
	 *
	 * @internal
	 *
	 * @return void
	 */
	public function handle_plugin_deactivation(): void {
		$this->pause();
	}

	/**
	 * Handles the daily cleanup action.
	 *
	 * @since 11.4.0
	 *
	 * @internal
	 *
	 * @return void
	 */
	public function handle_stale_token_cleanup(): void {
		try {
			$this->run();
		} catch ( Throwable $e ) {
			$this->step_logger->log_unattributed_failure(
				self::LOG_STEP,
				CleanupOutcome::FAILED,
				'error',
				sprintf( 'Stale push token cleanup failed: %s', $e->getMessage() )
			);
		}
	}

	/**
	 * Deletes up to {@see self::MAX_DELETIONS_PER_RUN} stale tokens, oldest
	 * first, and logs what it did.
	 *
	 * @since 11.4.0
	 *
	 * @param int|null $now Unix timestamp to measure the retention from. Defaults to now.
	 * @return array The summary that was logged, including the outcome.
	 */
	public function run( ?int $now = null ): array {
		$now = $now ?? time();

		if ( ! $this->push_notifications->should_be_enabled() ) {
			$this->mark_paused( $now );
			return $this->log_summary( CleanupOutcome::SKIPPED_DISABLED, array() );
		}

		if ( ! $this->is_enabled_by_filter() ) {
			return $this->log_summary( CleanupOutcome::SKIPPED_FILTER, array() );
		}

		$resumed_at      = $this->note_resumed( $now );
		$check_in_cutoff = $this->get_cutoff( $now, self::RETENTION_DAYS, $resumed_at );
		$cutoffs         = array(
			'check_in' => $check_in_cutoff,
			'fallback' => $this->get_cutoff( $now, self::FALLBACK_RETENTION_DAYS, $resumed_at ),
		);

		if ( null === $check_in_cutoff ) {
			return $this->log_summary( CleanupOutcome::SKIPPED_RECENTLY_RESUMED, array( 'resumed_at' => gmdate( 'Y-m-d H:i:s', (int) $resumed_at ) ) );
		}

		$summary = array(
			'cutoff'                 => $cutoffs['check_in'],
			'fallback_cutoff'        => $cutoffs['fallback'],
			'resumed_at'             => null === $resumed_at ? null : gmdate( 'Y-m-d H:i:s', $resumed_at ),
			'tokens_before'          => $this->data_store->count_tokens(),
			'stale'                  => 0,
			'kept_older_apps'        => 0,
			'deleted_daily_check_in' => 0,
			'deleted_fallback'       => 0,
			'failed'                 => 0,
			'deleted_token_ids'      => array(),
			'failed_token_ids'       => array(),
		);
		$outcome = CleanupOutcome::COMPLETED;

		try {
			$stale_ids          = $this->data_store->get_stale_token_ids( $check_in_cutoff );
			$summary['stale']   = count( $stale_ids );
			$attempts_remaining = self::MAX_ATTEMPTS_PER_RUN;

			foreach ( array_chunk( $stale_ids, self::CHUNK_SIZE ) as $chunk ) {
				$deletions_remaining = self::MAX_DELETIONS_PER_RUN - count( $summary['deleted_token_ids'] );
				$limit               = min( $deletions_remaining, $attempts_remaining );

				list( $deletable, $kept ) = $this->classify( $chunk, $cutoffs['fallback'] );

				$summary['kept_older_apps'] += $kept;

				if ( count( $deletable ) > $limit ) {
					$deletable = array_slice( $deletable, 0, $limit, true );
					$outcome   = CleanupOutcome::CAPPED;
				}

				$attempts_remaining -= count( $deletable );

				foreach ( array( 'check_in', 'fallback' ) as $rule ) {
					$ids    = array_keys( array_filter( $deletable, fn( string $matched ) => $rule === $matched ) );
					$result = $this->data_store->delete_tokens( $ids, $cutoffs[ $rule ] );

					$summary[ 'check_in' === $rule ? 'deleted_daily_check_in' : 'deleted_fallback' ] += count( $result['deleted'] );
					$summary['failed']           += count( $result['failed'] );
					$summary['deleted_token_ids'] = array_merge( $summary['deleted_token_ids'], $result['deleted'] );
					$summary['failed_token_ids']  = array_merge( $summary['failed_token_ids'], $result['failed'] );
				}

				if ( CleanupOutcome::CAPPED === $outcome ) {
					break;
				}
			}
		} catch ( Throwable $e ) {
			$outcome          = CleanupOutcome::FAILED;
			$summary['error'] = $e->getMessage();
		}

		return $this->log_summary( $outcome, $summary );
	}

	/**
	 * Returns the GMT datetime a token must have registered before to be
	 * deleted under a rule, or null while the rule is waiting.
	 *
	 * Silence from before push notifications came back on does not count, so
	 * a rule applies only once its full window has passed since then.
	 *
	 * @param int      $now        Unix timestamp.
	 * @param int      $days       The rule's retention in days.
	 * @param int|null $resumed_at When push notifications came back on, if that still matters.
	 * @return string|null
	 */
	private function get_cutoff( int $now, int $days, ?int $resumed_at ): ?string {
		$cutoff = $now - $days * DAY_IN_SECONDS;

		if ( null !== $resumed_at && $resumed_at >= $cutoff ) {
			return null;
		}

		return gmdate( 'Y-m-d H:i:s', $cutoff );
	}

	/**
	 * Picks the stale tokens to delete, in the order given, and counts the rest.
	 *
	 * @param int[]       $ids             Tokens last registered before the daily check-in cutoff, oldest first.
	 * @param string|null $fallback_cutoff GMT `Y-m-d H:i:s` datetime before which any token is deleted, or null when that rule is paused.
	 * @return array{0: array<int, string>, 1: int} Token ID => the rule it is deleted under (`check_in` or `fallback`), and how many were kept.
	 */
	private function classify( array $ids, ?string $fallback_cutoff ): array {
		$deletable = array();
		$kept      = 0;

		if ( ! empty( $ids ) ) {
			// Prime caches to reduce future queries.
			_prime_post_caches( $ids, false, true );
		}

		foreach ( $ids as $id ) {
			$post = get_post( $id );

			if ( ! $post ) {
				continue;
			}

			if ( $this->checks_in_daily( $id ) ) {
				$deletable[ $id ] = 'check_in';
			} elseif ( null !== $fallback_cutoff && $post->post_modified_gmt < $fallback_cutoff ) {
				$deletable[ $id ] = 'fallback';
			} else {
				++$kept;
			}
		}

		return array( $deletable, $kept );
	}

	/**
	 * Whether a token was last registered by an app that checks in daily.
	 *
	 * Compares only `major.minor`, since `version_compare()` ranks Android's
	 * `25.8-rc-1` below `25.8`. A missing or unrecognised version counts as old.
	 *
	 * @param int $id The token's post ID.
	 * @return bool
	 */
	private function checks_in_daily( int $id ): bool {
		if ( ! in_array( get_post_meta( $id, 'origin', true ), self::CHECK_IN_ORIGINS, true ) ) {
			return false;
		}

		$metadata = get_post_meta( $id, 'metadata', true );

		if ( ! is_array( $metadata ) || ! is_scalar( $metadata['app_version'] ?? null ) ) {
			return false;
		}

		if ( ! preg_match( '/^(\d+)\.(\d+)/', (string) $metadata['app_version'], $matches ) ) {
			return false;
		}

		return version_compare( $matches[1] . '.' . $matches[2], self::MIN_CHECK_IN_APP_VERSION, '>=' );
	}

	/**
	 * Removes the cleanup and records that push notifications are off.
	 *
	 * @return void
	 */
	private function pause(): void {
		as_unschedule_all_actions( self::CLEANUP_HOOK, array(), NotificationProcessor::ACTION_SCHEDULER_GROUP );
		$this->mark_paused( time() );
	}

	/**
	 * Records that push notifications are off, keeping the first time it was seen.
	 *
	 * @param int $now Unix timestamp.
	 * @return void
	 */
	private function mark_paused( int $now ): void {
		add_option( self::PAUSED_AT_OPTION, $now, '', false );
	}

	/**
	 * Turns a recorded pause into a resume time, and returns the resume time
	 * while it still affects a rule.
	 *
	 * @param int $now Unix timestamp.
	 * @return int|null When push notifications came back on, or null if that no longer matters.
	 */
	private function note_resumed( int $now ): ?int {
		if ( false !== get_option( self::PAUSED_AT_OPTION ) ) {
			update_option( self::RESUMED_AT_OPTION, $now, false );
			delete_option( self::PAUSED_AT_OPTION );
		}

		$resumed_at = (int) get_option( self::RESUMED_AT_OPTION, 0 );

		if ( $resumed_at <= 0 ) {
			return null;
		}

		if ( $resumed_at < $now - self::FALLBACK_RETENTION_DAYS * DAY_IN_SECONDS ) {
			delete_option( self::RESUMED_AT_OPTION );
			return null;
		}

		return $resumed_at;
	}

	/**
	 * Whether the cleanup filter allows this run. A callback that throws or
	 * returns something unrecognised turns the cleanup off.
	 *
	 * @return bool
	 */
	private function is_enabled_by_filter(): bool {
		try {
			/**
			 * Filters whether stale push tokens are deleted.
			 *
			 * Runs once per daily cleanup. Return false to keep every token, for
			 * example while investigating missing notifications.
			 *
			 * @since 11.4.0
			 *
			 * @param bool $enabled Whether stale push tokens are deleted. Default true.
			 */
			$enabled = apply_filters( 'woocommerce_push_notification_stale_token_cleanup_enabled', true );

			return ( is_bool( $enabled ) || is_string( $enabled ) || is_int( $enabled ) ) && wc_string_to_bool( $enabled );
		} catch ( Throwable $e ) {
			return false;
		}
	}

	/**
	 * Writes the run's summary line and returns it.
	 *
	 * @param string $outcome One of the CleanupOutcome constants.
	 * @param array  $summary The run's counts.
	 * @return array The summary, with the outcome added.
	 */
	private function log_summary( string $outcome, array $summary ): array {
		$this->step_logger->log_maintenance_step( self::LOG_STEP, $outcome, $summary );

		return array_merge( $summary, array( 'outcome' => $outcome ) );
	}
}
