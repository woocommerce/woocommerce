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
 * @extends WC_REST_Data_Controller
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
	 */
	public function register_routes() {
		register_rest_route(
			$this->namespace,
			'/' . $this->rest_base . '/status',
			array(
				array(
					'methods'             => \WP_REST_Server::READABLE,
					'callback'            => array( $this, 'get_status' ),
					'permission_callback' => array( $this, 'get_item_permissions_check' ),
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
					'permission_callback' => array( $this, 'create_item_permissions_check' ),
				),
				'schema' => array( $this, 'get_trigger_schema' ),
			)
		);
	}

	/**
	 * Check if a given request has access to read import status.
	 *
	 * @param  \WP_REST_Request $request Full details about the request.
	 * @return WP_Error|boolean
	 */
	public function get_item_permissions_check( $request ) {
		if ( ! current_user_can( 'manage_woocommerce' ) ) {
			return new WP_Error(
				'woocommerce_rest_cannot_view',
				__( 'Sorry, you cannot view analytics import status.', 'woocommerce' ),
				array( 'status' => rest_authorization_required_code() )
			);
		}

		return true;
	}

	/**
	 * Check if a given request has access to trigger import.
	 *
	 * @param  \WP_REST_Request $request Full details about the request.
	 * @return WP_Error|boolean
	 */
	public function create_item_permissions_check( $request ) {
		if ( ! current_user_can( 'manage_woocommerce' ) ) {
			return new WP_Error(
				'woocommerce_rest_cannot_create',
				__( 'Sorry, you cannot trigger analytics imports.', 'woocommerce' ),
				array( 'status' => rest_authorization_required_code() )
			);
		}

		return true;
	}

	/**
	 * Get the current import status.
	 *
	 * @param  \WP_REST_Request $request Full details about the request.
	 * @return \WP_REST_Response|WP_Error Response object on success, or WP_Error object on failure.
	 */
	public function get_status( $request ) {
		$is_immediate_mode = $this->is_immediate_import_enabled();
		$mode              = $is_immediate_mode ? 'immediate' : 'scheduled';

		$response = array(
			'mode'                              => $mode,
			'last_processed_date'               => null,
			'next_scheduled'                    => null,
			'manual_triggered_import_scheduled' => null,
		);

		// For scheduled mode, populate additional fields.
		if ( ! $is_immediate_mode ) {
			$last_processed_gmt              = get_option( OrdersScheduler::LAST_PROCESSED_ORDER_DATE_OPTION, null );
			$response['last_processed_date'] = $last_processed_gmt ? get_date_from_gmt( $last_processed_gmt, 'Y-m-d H:i:s' ) : null;
			$response['next_scheduled']      = $this->get_next_scheduled_time();

			$response['manual_triggered_import_scheduled'] = $this->has_manual_triggered_import_scheduled();
		}

		return rest_ensure_response( $response );
	}

	/**
	 * Trigger a manual import.
	 *
	 * @param  \WP_REST_Request $request Full details about the request.
	 * @return \WP_REST_Response|WP_Error Response object on success, or WP_Error object on failure.
	 */
	public function trigger_import( $request ) {
		$is_immediate_mode = $this->is_immediate_import_enabled();

		// Return error if in immediate mode.
		if ( $is_immediate_mode ) {
			return new WP_Error(
				'woocommerce_rest_analytics_import_immediate_mode',
				__( 'Manual import is not available in immediate mode. Imports happen automatically.', 'woocommerce' ),
				array( 'status' => 400 )
			);
		}

		// Determine if a batch import has already been scheduled or is in progress, excluding the recurring import action.
		if ( $this->has_manual_triggered_import_scheduled() ) {
			return new WP_Error(
				'woocommerce_rest_analytics_import_already_scheduled_or_in_progress',
				__( 'A batch import is already scheduled or in progress. Please wait for it to complete before triggering a new import.', 'woocommerce' ),
				array( 'status' => 400 )
			);
		}

		// Schedule the batch import action.
		OrdersScheduler::schedule_action( 'process_pending_batch', array( null, null ) );

		return rest_ensure_response(
			array(
				'success' => true,
				'message' => __( 'Batch import scheduled successfully.', 'woocommerce' ),
			)
		);
	}

	/**
	 * Check if immediate import is enabled.
	 *
	 * @return bool
	 */
	private function is_immediate_import_enabled() {
		return 'no' !== get_option( OrdersScheduler::IMMEDIATE_IMPORT_OPTION, OrdersScheduler::IMMEDIATE_IMPORT_OPTION_DEFAULT_VALUE );
	}

	/**
	 * Get the next scheduled time for the batch processor.
	 *
	 * @return string|null Datetime string in site timezone or null if not scheduled.
	 */
	private function get_next_scheduled_time() {
		$action_hook = OrdersScheduler::get_action( 'process_pending_batch' );
		$next_time   = as_next_scheduled_action( $action_hook );

		if ( false === $next_time ) {
			return null;
		}

		// Convert UTC timestamp to site timezone.
		return get_date_from_gmt( gmdate( 'Y-m-d H:i:s', $next_time ), 'Y-m-d H:i:s' );
	}

	/**
	 * Get the schema for the status endpoint, conforming to JSON Schema.
	 *
	 * @return array
	 */
	public function get_status_schema() {
		$schema = array(
			'$schema'    => 'http://json-schema.org/draft-04/schema#',
			'title'      => 'analytics_import_status',
			'type'       => 'object',
			'properties' => array(
				'mode'                              => array(
					'type'        => 'string',
					'enum'        => array( 'scheduled', 'immediate' ),
					'description' => __( 'Current import mode.', 'woocommerce' ),
					'context'     => array( 'view' ),
					'readonly'    => true,
				),
				'last_processed_date'               => array(
					'type'        => array( 'string', 'null' ),
					'description' => __( 'Last processed order date (null in immediate mode).', 'woocommerce' ),
					'context'     => array( 'view' ),
					'readonly'    => true,
				),
				'next_scheduled'                    => array(
					'type'        => array( 'string', 'null' ),
					'description' => __( 'Next scheduled import time (null in immediate mode).', 'woocommerce' ),
					'context'     => array( 'view' ),
					'readonly'    => true,
				),
				'manual_triggered_import_scheduled' => array(
					'type'        => array( 'boolean', 'null' ),
					'description' => __( 'Whether a manual triggered import has already been scheduled.', 'woocommerce' ),
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
			'$schema'    => 'http://json-schema.org/draft-04/schema#',
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
	 * Check if a manual triggered import has already been scheduled.
	 *
	 * @return bool True if a manual triggered import has already been scheduled, false otherwise.
	 */
	private function has_manual_triggered_import_scheduled() {
		$hook = OrdersScheduler::get_action( 'process_pending_batch' );

		return as_has_scheduled_action( $hook, array( null, null ) );
	}
}
