<?php

declare( strict_types=1 );

namespace Automattic\WooCommerce\Internal\Logging;

/**
 * Class to handle deletions of logs by source using scheduled actions.
 *
 * 'register_source_pending_deletion' will queue a new source whose logs are to be deleted
 * at some point in the future, consecutive multiple calls to the method will queue up
 * all the sources to be deleted in one single run of the action.
 */
class LogsDeletionScheduler {

	/**
	 * The name of the option where the names of the log sources pending deletion are stored.
	 *
	 * @var string
	 */
	public const SOURCES_LIST_OPTION_NAME = 'wc_log_sources_pending_deletion';

	/**
	 * The name of the hook for the deletion scheduled action.
	 */
	public const ACTION_HOOK_NAME = 'woocommerce_delete_logs_pending_deletion';

	/**
	 * Seconds to wait between the first source registration until the deletion action runs.
	 *
	 * @var int
	 */
	private int $wait_seconds;

	/**
	 * Max size of the sources queue.
	 *
	 * @var int
	 */
	private int $max_queue_length;

	/**
	 * Max amount of sources to be deleted in one single run of the action.
	 *
	 * @var int
	 */
	private int $max_items_per_step;

	/**
	 * True if the scheduling of deletions is actually enabled.
	 *
	 * @var bool
	 */
	private bool $is_enabled = true;

	/**
	 * Creates a new instance of the class.
	 */
	public function __construct() {
		/**
		 * Filter to customize the parameters to control the scheduled deletion of logs by source.
		 * Returns an array with three keys, each containing a number:
		 *
		 * - wait_seconds: seconds to wait since the first source registration until the deletion action runs.
		 * - max_queue_length: maximum count of items that will be added to the sources queue.
		 * - max_items_per_step: how many log sources will be processed at most in one single action run.
		 *
		 * If any of these keys is removed or set to zero, deletion scheduling will be disabled entirely,
		 * and register_source_pending_deletion will always either delete the logs immediately or return an error.
		 *
		 * @param array $parameters Parameters for the action scheduling.
		 *
		 * @since 10.3.0
		 */
		$parameters = apply_filters(
			'woocommerce_logs_deletion_scheduler_parameters',
			array(
				'wait_seconds'       => 5 * MINUTE_IN_SECONDS,
				'max_queue_length'   => 100000,
				'max_items_per_step' => 10000,
			)
		);

		$this->wait_seconds       = absint( $parameters['wait_seconds'] ?? 0 );
		$this->max_queue_length   = absint( $parameters['max_queue_length'] ?? 0 );
		$this->max_items_per_step = absint( $parameters['max_items_per_step'] ?? 0 );
		if ( ! $this->wait_seconds || ! $this->max_queue_length || ! $this->max_items_per_step ) {
			$this->is_enabled = false;
			return;
		}

		add_action( self::ACTION_HOOK_NAME, array( $this, 'handle_delete_logs_pending_deletion' ), 10, 0 );
	}

	/**
	 * Register a new source of logs to be scheduled for deletion.
	 *
	 * "The queue is full" means that there are $max_queue_length sources registered already.
	 * See the 'woocommerce_logs_deletion_scheduler_parameters' filter for the meaning of "scheduling is disabled".
	 *
	 * @param string $source The name of the source.
	 * @param bool   $delete_if_cant_register If the source can't be registered because the queue is full or scheduling is disabled, true will cause the logs to be deleted immediately, false will cause an error to be returned.
	 * @return bool True if ok (the source was either queued for deletion or processed immediately), false on error (queue is full or scheduling is disabled, and $delete_if_cant_register is false).
	 */
	public function register_source_pending_deletion( string $source, bool $delete_if_cant_register = false ): bool {
		if ( ! $this->is_enabled ) {
			if ( $delete_if_cant_register ) {
				$logger = WC()->call_function( 'wc_get_logger' );
				$logger->clear( $source, true );
				return true;
			} else {
				return false;
			}
		}

		if ( $this->add_source_to_pending_deletions_list( $source ) ) {
			$this->schedule_action();
			return true;
		} elseif ( $delete_if_cant_register ) {
			$logger = WC()->call_function( 'wc_get_logger' );
			$logger->clear( $source, true );
			return true;
		} else {
			return false;
		}
	}

	/**
	 * Add one logs source name to the queue of pending sources to be processed.
	 *
	 * @param string $source Name of the source.
	 * @return bool True if queueing succeeded, false if it failed (queue is full or failed to set the option).
	 */
	private function add_source_to_pending_deletions_list( string $source ): bool {
		$new_option               = false;
		$sources_pending_deletion = get_option( self::SOURCES_LIST_OPTION_NAME );
		if ( false === $sources_pending_deletion ) {
			$sources_pending_deletion = array();
			$new_option               = true;
		}

		if ( count( $sources_pending_deletion ) >= $this->max_queue_length ) {
			return false;
		}

		$sources_pending_deletion[] = $source;

		// Use add_option if the option doesn't exist already to create it as not-autoloading.
		return $new_option ?
			add_option( self::SOURCES_LIST_OPTION_NAME, $sources_pending_deletion, '', false ) :
			update_option( self::SOURCES_LIST_OPTION_NAME, $sources_pending_deletion );
	}

	/**
	 * Handler for the scheduled action hook.
	 * It will delete the logs for up to $max_items_per_step sources, and if more are pending after that,
	 * it will update the queue and scheduled the action again.
	 *
	 * @internal
	 */
	public function handle_delete_logs_pending_deletion() {
		$sources_pending_deletion = get_option( self::SOURCES_LIST_OPTION_NAME, array() );
		if ( ! $sources_pending_deletion ) {
			return;
		}

		$step_count = min( count( $sources_pending_deletion ), $this->max_items_per_step );
		$logger     = WC()->call_function( 'wc_get_logger' );
		for ( $i = 0; $i < $step_count; $i++ ) {
			$logger->clear( $sources_pending_deletion[ $i ], true );
		}

		$still_pending_count = count( $sources_pending_deletion ) - $step_count;
		if ( $still_pending_count ) {
			$sources_pending_deletion = array_slice( $sources_pending_deletion, $step_count );
			update_option( self::SOURCES_LIST_OPTION_NAME, $sources_pending_deletion );
			$this->schedule_action();
		} else {
			delete_option( self::SOURCES_LIST_OPTION_NAME );
		}
	}

	/**
	 * Schedule the action to handle the deletion of logs.
	 * Note that we register it as unique, so consecutive source registrations
	 * will be handled in the same action run.
	 */
	private function schedule_action() {
		as_schedule_single_action(
			time() + $this->wait_seconds,
			self::ACTION_HOOK_NAME,
			array(),
			'woocommerce',
			true
		);
	}
}
