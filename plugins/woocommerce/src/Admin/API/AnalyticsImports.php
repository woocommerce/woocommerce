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

		$response = array(
			'mode'                      => $mode,
			'last_processed_date'       => null,
			'next_scheduled'            => null,
			'import_in_progress_or_due' => null,
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
	 * Check if scheduled import is enabled.
	 *
	 * @return bool
	 */
	private function is_scheduled_import_enabled() {
		return 'yes' === get_option( OrdersScheduler::SCHEDULED_IMPORT_OPTION, OrdersScheduler::SCHEDULED_IMPORT_OPTION_DEFAULT_VALUE );
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
