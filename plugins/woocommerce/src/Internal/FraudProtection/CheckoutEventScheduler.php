<?php
/**
 * CheckoutEventScheduler class file.
 */

declare( strict_types=1 );

namespace Automattic\WooCommerce\Internal\FraudProtection;

defined( 'ABSPATH' ) || exit;

/**
 * Handles scheduling and processing of checkout events for fraud protection.
 *
 * This class provides shared scheduling functionality for both traditional
 * and Blocks checkout event tracking. It implements batching/debouncing to
 * reduce API calls for rapid successive updates.
 *
 * @since 10.5.0
 * @internal This class is part of the internal API and is subject to change without notice.
 */
class CheckoutEventScheduler {

	/**
	 * Fraud protection tracker instance.
	 *
	 * @var FraudProtectionTracker
	 */
	private FraudProtectionTracker $tracker;

	/**
	 * Session data collector instance.
	 *
	 * @var SessionDataCollector
	 */
	private SessionDataCollector $data_collector;

	/**
	 * Batch interval in seconds for checkout events.
	 *
	 * This defines how long to wait after the last event before tracking.
	 * Each new event resets this timer (debouncing).
	 *
	 * @var int
	 */
	private const BATCH_INTERVAL_SECONDS = 15;

	/**
	 * Action hook name for scheduled event tracking.
	 *
	 * @var string
	 */
	private const SCHEDULED_ACTION_HOOK = 'woocommerce_fraud_protection_track_checkout_event';

	/**
	 * Initialize with dependencies.
	 *
	 * @internal
	 *
	 * @param FraudProtectionTracker $tracker        The fraud protection tracker instance.
	 * @param SessionDataCollector   $data_collector The session data collector instance.
	 */
	final public function init(
		FraudProtectionTracker $tracker,
		SessionDataCollector $data_collector
	): void {
		$this->tracker        = $tracker;
		$this->data_collector = $data_collector;
	}

	/**
	 * Register the scheduled action hook.
	 *
	 * @return void
	 */
	public function register_hooks(): void {
		// Scheduled action to track pending events after debounce interval.
		add_action( self::SCHEDULED_ACTION_HOOK, array( $this, 'process_scheduled_tracking' ), 10, 1 );
	}

	/**
	 * Schedule a tracking action to run after the debounce interval.
	 *
	 * Collects comprehensive session data and schedules it for tracking.
	 * Cancels any existing scheduled action for this session before scheduling a new one.
	 *
	 * @param string $event_type          Event type identifier.
	 * @param array  $event_specific_data Event-specific data to merge with session context.
	 * @return void
	 */
	public function schedule_tracking( string $event_type, array $event_specific_data ): void {
		$timestamp = time();
		// Get session ID to use as a unique identifier for this customer's actions.
		$session_id = WC()->session instanceof \WC_Session ? WC()->session->get_customer_id() : null;

		if ( ! $session_id ) {
			// Can't schedule without a session ID.
			return;
		}

		// Collect comprehensive session data NOW (while session is available).
		try {
			$collected_data = $this->data_collector->collect( $event_type, $event_specific_data );
		} catch ( \Exception $e ) {
			// If collection fails, log and abort scheduling.
			FraudProtectionController::log(
				'error',
				sprintf(
					'Failed to collect session data for checkout event: %s | Error: %s',
					$event_type,
					$e->getMessage()
				),
				array(
					'event_type' => $event_type,
					'exception'  => $e,
				)
			);
			return;
		}

		// Cancel any existing scheduled action for this session first.
		$this->cancel_scheduled_tracking( $session_id, $event_type );

		// Schedule action to run after the debounce interval.
		// Pass the COLLECTED data with the action so it's available when it runs.
		$run_time = $timestamp + self::BATCH_INTERVAL_SECONDS;

		if ( function_exists( 'as_schedule_single_action' ) ) {
			as_schedule_single_action(
				$run_time,
				self::SCHEDULED_ACTION_HOOK,
				array(
					'session_id'     => $session_id,
					'event_type'     => $event_type,
					'collected_data' => $collected_data,
					'timestamp'      => $timestamp,
				),
				'woocommerce-fraud-protection'
			);
		}
	}

	/**
	 * Get scheduled action IDs for a specific session and event type.
	 *
	 * Queries Action Scheduler for pending actions matching the session_id and event_type
	 * in the extended_args column using JSON_EXTRACT.
	 *
	 * @param string $session_id Session ID to search for.
	 * @param string $event_type Event type to search for.
	 * @return array Array of action IDs.
	 */
	public function get_scheduled_action_ids( string $session_id, string $event_type ): array {
		global $wpdb;

		$sql = $wpdb->prepare(
			"SELECT a.action_id
			FROM {$wpdb->actionscheduler_actions} a
			LEFT JOIN {$wpdb->actionscheduler_groups} g ON g.group_id = a.group_id
			WHERE a.hook = %s
			AND g.slug = %s
			AND a.status = %s
			AND a.extended_args IS NOT NULL
			AND JSON_EXTRACT(a.extended_args, '$.session_id') = %s
			AND JSON_EXTRACT(a.extended_args, '$.event_type') = %s
			ORDER BY a.scheduled_date_gmt ASC",
			self::SCHEDULED_ACTION_HOOK,
			'woocommerce-fraud-protection',
			\ActionScheduler_Store::STATUS_PENDING, // phpcs:ignore Squiz.Commenting.InlineComment.InvalidEndChar -- @phpstan-ignore-line class.notFound
			$session_id,
			$event_type
		);

		return $wpdb->get_col( $sql ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
	}

	/**
	 * Cancel scheduled tracking actions for a specific session and event type.
	 *
	 * Uses custom SQL query with JSON_EXTRACT on extended_args to find actions
	 * matching session_id and event_type. This is necessary because our collected_data
	 * is too large and gets stored in extended_args, and Action Scheduler's query
	 * builder doesn't support partial matching on extended_args.
	 *
	 * @param string|null $session_id  Optional session ID. If not provided, uses current session.
	 * @param string      $event_type Event type to cancel.
	 * @return void
	 */
	public function cancel_scheduled_tracking( ?string $session_id = null, string $event_type = '' ): void {
		if ( null === $session_id ) {
			$session_id = WC()->session instanceof \WC_Session ? WC()->session->get_customer_id() : null;
		}

		if ( ! $session_id ) {
			return;
		}

		// Use custom SQL with JSON_EXTRACT on extended_args column.
		if ( class_exists( 'ActionScheduler' ) && \ActionScheduler::is_initialized( __FUNCTION__ ) ) {
			// Get all pending actions matching session_id and event_type.
			$action_ids = $this->get_scheduled_action_ids( $session_id, $event_type );

			// Cancel all found actions.
			foreach ( $action_ids as $action_id ) {
				try {
					\ActionScheduler::store()->cancel_action( (int) $action_id );
				} catch ( \Exception $e ) {
					// Log but continue - action might have been cancelled by another process.
					FraudProtectionController::log(
						'warning',
						sprintf( 'Failed to cancel scheduled action %d: %s', $action_id, $e->getMessage() )
					);
				}
			}
		}
	}

	/**
	 * Process scheduled tracking action.
	 *
	 * Called by Action Scheduler after the debounce interval has passed.
	 * Receives fully-collected event data as arguments, so it doesn't depend on session availability.
	 *
	 * @internal
	 *
	 * @param array $args Action arguments containing session_id, event_type, collected_data, and timestamp.
	 * @return void
	 */
	public function process_scheduled_tracking( array $args ): void {
		$event_type     = $args['event_type'] ?? null;
		$collected_data = $args['collected_data'] ?? array();
		$timestamp      = $args['timestamp'] ?? null;

		// Validate required parameters.
		if ( ! $event_type || ! $timestamp || empty( $collected_data ) ) {
			return;
		}

		$this->tracker->track_event( $event_type, $collected_data );
	}
}
