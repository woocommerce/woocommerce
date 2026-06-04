<?php
/**
 * REST API Analytics Imports Controller
 *
 * Handles requests to get batch import status and trigger manual imports.
 */

declare( strict_types = 1 );

namespace Automattic\WooCommerce\Admin\API;

use WP_Error;
use Automattic\WooCommerce\Internal\Admin\Schedulers\OrdersScheduler;

defined( 'ABSPATH' ) || exit;

/**
 * REST API Analytics Imports Controller.
 *
 * @internal
 */
class AnalyticsImports extends \WC_REST_Data_Controller {
	/**
	 * Endpoint namespace.
	 *
	 * @var string
	 */
	protected $namespace = 'wc-analytics';

	/**
	 * Route base.
	 *
	 * @var string
	 */
	protected $rest_base = 'imports';

	/**
	 * Register routes.
	 *
	 * @return void
	 */
	public function register_routes(): void {
		register_rest_route(
			$this->namespace,
			'/' . $this->rest_base . '/status',
			array(
				array(
					'methods'             => \WP_REST_Server::READABLE,
					'callback'            => array( $this, 'get_status' ),
					'permission_callback' => array( $this, 'permissions_check' ),
				),
				'schema' => array( $this, 'get_status_schema' ),
			)
		);

		register_rest_route(
			$this->namespace,
			'/' . $this->rest_base . '/trigger',
			array(
				array(
					'methods'             => \WP_REST_Server::CREATABLE,
					'callback'            => array( $this, 'trigger_import' ),
					'permission_callback' => array( $this, 'permissions_check' ),
				),
				'schema' => array( $this, 'get_trigger_schema' ),
			)
		);

		register_rest_route(
			$this->namespace,
			'/' . $this->rest_base . '/retry-failed',
			array(
				array(
					'methods'             => \WP_REST_Server::CREATABLE,
					'callback'            => array( $this, 'retry_failed_imports' ),
					'permission_callback' => array( $this, 'permissions_check' ),
				),
				'schema' => array( $this, 'get_retry_failed_schema' ),
			)
		);
	}

	/**
	 * Check if a given request has access to analytics imports.
	 *
	 * @param  \WP_REST_Request<array<string, mixed>> $request Full details about the request.
	 * @return WP_Error|boolean
	 */
	public function permissions_check( $request ) {
		if ( ! current_user_can( 'manage_woocommerce' ) ) {
			return new WP_Error(
				'woocommerce_rest_cannot_access',
				__( 'Sorry, you cannot access analytics imports.', 'woocommerce' ),
				array( 'status' => rest_authorization_required_code() )
			);
		}

		return true;
	}

	/**
	 * Get the current import status.
	 *
	 * @param  \WP_REST_Request<array<string, mixed>> $request Full details about the request.
	 * @return \WP_REST_Response|WP_Error Response object on success, or WP_Error object on failure.
	 */
	public function get_status( $request ) {
		$is_scheduled_mode = $this->is_scheduled_import_enabled();
		$mode              = $is_scheduled_mode ? 'scheduled' : 'immediate';

		$failed_imports = OrdersScheduler::get_failed_order_imports();

		$response = array(
			'mode'                      => $mode,
			'last_processed_date'       => null,
			'next_scheduled'            => null,
			'import_in_progress_or_due' => null,
			'failed_count'              => count( $failed_imports['ids'] ),
			'failed_overflow_count'     => $failed_imports['overflow'],
		);

		// For scheduled mode, populate additional fields.
		if ( $is_scheduled_mode ) {
			$last_processed_gmt                    = get_option( OrdersScheduler::LAST_PROCESSED_ORDER_DATE_OPTION, null );
			$response['last_processed_date']       = ( is_string( $last_processed_gmt ) && $last_processed_gmt ) ? get_date_from_gmt( $last_processed_gmt, 'Y-m-d H:i:s' ) : null;
			$response['next_scheduled']            = $this->get_next_scheduled_time();
			$response['import_in_progress_or_due'] = $this->is_import_in_progress_or_due();
		}

		return rest_ensure_response( $response );
	}

	/**
	 * Trigger a manual import.
	 *
	 * @param  \WP_REST_Request<array<string, mixed>> $request Full details about the request.
	 * @return \WP_REST_Response|WP_Error Response object on success, or WP_Error object on failure.
	 */
	public function trigger_import( $request ) {
		$is_scheduled_mode = $this->is_scheduled_import_enabled();

		// Return error if in immediate mode.
		if ( ! $is_scheduled_mode ) {
			return new WP_Error(
				'woocommerce_rest_analytics_import_immediate_mode',
				__( 'Manual import is not available in immediate mode. Imports happen automatically.', 'woocommerce' ),
				array( 'status' => 400 )
			);
		}

		// Check if an import is already in progress or due to run soon.
		if ( $this->is_import_in_progress_or_due() ) {
			return new WP_Error(
				'woocommerce_rest_analytics_import_in_progress',
				__( 'A batch import is already in progress or scheduled to run soon. Please wait for it to complete before triggering a new import.', 'woocommerce' ),
				array( 'status' => 400 )
			);
		}

		// Trigger the batch import immediately by rescheduling the recurring processor.
		// This unschedules the current recurring action and reschedules it to run now.
		$action_hook = OrdersScheduler::get_action( OrdersScheduler::PROCESS_PENDING_ORDERS_BATCH_ACTION );
		if ( ! is_string( $action_hook ) ) {
			return new WP_Error(
				'woocommerce_rest_analytics_import_invalid_action',
				__( 'Invalid action hook for batch import.', 'woocommerce' ),
				array( 'status' => 500 )
			);
		}
		WC()->queue()->cancel_all( $action_hook, array(), (string) OrdersScheduler::$group );
		OrdersScheduler::schedule_recurring_batch_processor();

		return rest_ensure_response(
			array(
				'success' => true,
				'message' => __( 'Batch import triggered successfully.', 'woocommerce' ),
			)
		);
	}

	/**
	 * Re-schedule imports for orders that previously failed.
	 *
	 * Order IDs whose orders no longer exist are pruned (they can never import
	 * successfully). Orders with an import already pending are skipped and
	 * reported separately, so repeated requests don't claim to schedule new
	 * work. The remaining IDs stay recorded until their import succeeds, so a
	 * retry that fails again remains visible.
	 *
	 * @param  \WP_REST_Request<array<string, mixed>> $request Full details about the request.
	 * @return \WP_REST_Response|WP_Error Response object on success, or WP_Error object on failure.
	 */
	public function retry_failed_imports( $request ) {
		$failed = OrdersScheduler::get_failed_order_imports();

		if ( empty( $failed['ids'] ) ) {
			return new WP_Error(
				'woocommerce_rest_analytics_no_failed_imports',
				__( 'There are no failed order imports to retry.', 'woocommerce' ),
				array( 'status' => 400 )
			);
		}

		$retried_count           = 0;
		$pruned_count            = 0;
		$already_scheduled_count = 0;
		$error_count             = 0;
		foreach ( $failed['ids'] as $order_id ) {
			if ( ! wc_get_order( $order_id ) ) {
				OrdersScheduler::clear_failed_order_import( $order_id );
				++$pruned_count;
				continue;
			}

			// schedule_action() silently no-ops when the same import is
			// already pending, so check first to report an accurate count.
			if ( OrdersScheduler::has_existing_jobs( 'import', array( $order_id ) ) ) {
				++$already_scheduled_count;
				continue;
			}

			try {
				OrdersScheduler::schedule_action( 'import', array( $order_id ) );
				++$retried_count;
			} catch ( \Throwable $e ) {
				// schedule_action() may run the import synchronously (e.g. when
				// Action Scheduler is unavailable); a failing order must not
				// abort the whole retry request.
				++$error_count;
				wc_get_logger()->error(
					sprintf( 'Failed to schedule analytics re-import for order %d: %s', $order_id, $e->getMessage() ),
					array( 'source' => 'wc-analytics-order-import' )
				);
			}
		}

		// Nothing was scheduled and nothing is pending: surface the failure
		// instead of reporting success for work that didn't happen.
		if ( 0 === $retried_count && 0 === $already_scheduled_count && $error_count > 0 ) {
			return new WP_Error(
				'woocommerce_rest_analytics_retry_failed',
				__( 'The failed orders could not be scheduled for re-import. Check the order import log for details.', 'woocommerce' ),
				array( 'status' => 500 )
			);
		}

		if ( $retried_count > 0 ) {
			$message = sprintf(
				/* translators: %d: number of orders scheduled for re-import */
				_n( 'Re-import scheduled for %d order.', 'Re-import scheduled for %d orders.', $retried_count, 'woocommerce' ),
				$retried_count
			);
		} elseif ( $already_scheduled_count > 0 ) {
			$message = __( 'Re-import is already scheduled for the previously failed orders.', 'woocommerce' );
		} else {
			$message = __( 'No orders were scheduled for re-import. The previously failed orders no longer exist.', 'woocommerce' );
		}

		if ( $error_count > 0 ) {
			$message .= ' ' . sprintf(
				/* translators: %d: number of orders that could not be scheduled for re-import */
				_n( '%d order could not be scheduled. Check the order import log for details.', '%d orders could not be scheduled. Check the order import log for details.', $error_count, 'woocommerce' ),
				$error_count
			);
		}

		return rest_ensure_response(
			array(
				'success'                 => true,
				'message'                 => $message,
				'retried_count'           => $retried_count,
				'pruned_count'            => $pruned_count,
				'already_scheduled_count' => $already_scheduled_count,
				'error_count'             => $error_count,
			)
		);
	}

	/**
	 * Get the schema for the retry-failed endpoint, conforming to JSON Schema.
	 *
	 * @return array
	 */
	public function get_retry_failed_schema() {
		$schema = array(
			'$schema'    => 'https://json-schema.org/draft-04/schema#',
			'title'      => 'analytics_import_retry_failed',
			'type'       => 'object',
			'properties' => array(
				'success'                 => array(
					'type'        => 'boolean',
					'description' => __( 'Whether the retry was scheduled successfully.', 'woocommerce' ),
					'context'     => array( 'view' ),
					'readonly'    => true,
				),
				'message'                 => array(
					'type'        => 'string',
					'description' => __( 'Result message.', 'woocommerce' ),
					'context'     => array( 'view' ),
					'readonly'    => true,
				),
				'retried_count'           => array(
					'type'        => 'integer',
					'description' => __( 'Number of orders scheduled for re-import.', 'woocommerce' ),
					'context'     => array( 'view' ),
					'readonly'    => true,
				),
				'pruned_count'            => array(
					'type'        => 'integer',
					'description' => __( 'Number of failed records removed because their orders no longer exist.', 'woocommerce' ),
					'context'     => array( 'view' ),
					'readonly'    => true,
				),
				'already_scheduled_count' => array(
					'type'        => 'integer',
					'description' => __( 'Number of orders skipped because their re-import is already pending.', 'woocommerce' ),
					'context'     => array( 'view' ),
					'readonly'    => true,
				),
				'error_count'             => array(
					'type'        => 'integer',
					'description' => __( 'Number of orders that could not be scheduled for re-import.', 'woocommerce' ),
					'context'     => array( 'view' ),
					'readonly'    => true,
				),
			),
		);

		return $this->add_additional_fields_schema( $schema );
	}

	/**
	 * Check if scheduled import is enabled.
	 *
	 * Delegates to OrdersScheduler so the API reflects the same mode the
	 * scheduler actually runs in (feature flag check + legacy option fallback).
	 *
	 * @return bool
	 */
	private function is_scheduled_import_enabled() {
		return OrdersScheduler::is_scheduled_import_enabled();
	}

	/**
	 * Get the next scheduled time for the batch processor.
	 *
	 * @return string|null Datetime string in site timezone or null if not scheduled.
	 */
	private function get_next_scheduled_time() {
		$action_hook = OrdersScheduler::get_action( OrdersScheduler::PROCESS_PENDING_ORDERS_BATCH_ACTION );
		if ( ! is_string( $action_hook ) ) {
			return null;
		}
		$next_time = WC()->queue()->get_next( $action_hook, array(), (string) OrdersScheduler::$group );

		if ( ! $next_time ) {
			return null;
		}

		// Convert UTC timestamp to site timezone.
		return get_date_from_gmt( $next_time->format( 'Y-m-d H:i:s' ), 'Y-m-d H:i:s' );
	}

	/**
	 * Get the schema for the status endpoint, conforming to JSON Schema.
	 *
	 * @return array
	 */
	public function get_status_schema() {
		$schema = array(
			'$schema'    => 'https://json-schema.org/draft-04/schema#',
			'title'      => 'analytics_import_status',
			'type'       => 'object',
			'properties' => array(
				'mode'                      => array(
					'type'        => 'string',
					'enum'        => array( 'scheduled', 'immediate' ),
					'description' => __( 'Current import mode.', 'woocommerce' ),
					'context'     => array( 'view' ),
					'readonly'    => true,
				),
				'last_processed_date'       => array(
					'type'        => array( 'string', 'null' ),
					'description' => __( 'Last processed order date (null in immediate mode).', 'woocommerce' ),
					'context'     => array( 'view' ),
					'readonly'    => true,
				),
				'next_scheduled'            => array(
					'type'        => array( 'string', 'null' ),
					'description' => __( 'Next scheduled import time (null in immediate mode).', 'woocommerce' ),
					'context'     => array( 'view' ),
					'readonly'    => true,
				),
				'import_in_progress_or_due' => array(
					'type'        => array( 'boolean', 'null' ),
					'description' => __( 'Whether a batch import is currently running or scheduled to run within the next minute (null in immediate mode).', 'woocommerce' ),
					'context'     => array( 'view' ),
					'readonly'    => true,
				),
				'failed_count'              => array(
					'type'        => 'integer',
					'description' => __( 'Number of orders that failed analytics import and are pending retry.', 'woocommerce' ),
					'context'     => array( 'view' ),
					'readonly'    => true,
				),
				'failed_overflow_count'     => array(
					'type'        => 'integer',
					'description' => __( 'Number of failed order IDs dropped because the stored list reached its limit.', 'woocommerce' ),
					'context'     => array( 'view' ),
					'readonly'    => true,
				),
			),
		);

		return $this->add_additional_fields_schema( $schema );
	}

	/**
	 * Get the schema for the trigger endpoint, conforming to JSON Schema.
	 *
	 * @return array
	 */
	public function get_trigger_schema() {
		$schema = array(
			'$schema'    => 'https://json-schema.org/draft-04/schema#',
			'title'      => 'analytics_import_trigger',
			'type'       => 'object',
			'properties' => array(
				'success' => array(
					'type'        => 'boolean',
					'description' => __( 'Whether the trigger was successful.', 'woocommerce' ),
					'context'     => array( 'view' ),
					'readonly'    => true,
				),
				'message' => array(
					'type'        => 'string',
					'description' => __( 'Result message.', 'woocommerce' ),
					'context'     => array( 'view' ),
					'readonly'    => true,
				),
			),
		);

		return $this->add_additional_fields_schema( $schema );
	}

	/**
	 * Check if a batch import is currently in progress or due to run soon.
	 *
	 * @return bool True if a batch import is in progress or scheduled to run within the next minute, false otherwise.
	 */
	private function is_import_in_progress_or_due() {
		$hook = OrdersScheduler::get_action( OrdersScheduler::PROCESS_PENDING_ORDERS_BATCH_ACTION );
		if ( ! is_string( $hook ) ) {
			return false;
		}

		// Check for actions with 'in-progress' status.
		$in_progress_actions = WC()->queue()->search(
			array(
				'hook'     => $hook,
				'status'   => 'in-progress',
				'per_page' => 1,
			),
			'ids'
		);

		if ( ! empty( $in_progress_actions ) ) {
			return true;
		}

		// Check if the next scheduled import is due within 1 minute.
		$next_scheduled = WC()->queue()->get_next( $hook, array(), (string) OrdersScheduler::$group );
		if ( $next_scheduled ) {
			$time_until_next = $next_scheduled->getTimestamp() - time();
			// Consider it "due" if it's scheduled to run within the next 60 seconds.
			if ( $time_until_next <= MINUTE_IN_SECONDS ) {
				return true;
			}
		}

		return false;
	}
}
