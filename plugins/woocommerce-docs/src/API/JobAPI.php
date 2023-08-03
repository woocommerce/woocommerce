<?php
/**
 * JobAPI class file
 *
 * @package  WooCommerceDocs
 */

namespace WooCommerceDocs\API;

/**
 * A class to register the job API endpoints.
 */
class JobAPI {

	/**
	 * Register the routes for the objects of the controller.
	 */
	public static function register_routes() {
		register_rest_route(
			'woocommerce-docs/v1',
			'/job_log',
			array(
				'methods'             => 'GET',
				'callback'            => array( '\WooCommerceDocs\API\JobAPI', 'get_job_log' ),
				'permission_callback' => array( '\WooCommerceDocs\API\JobAPI', 'permission_check' ),
			)
		);
	}

	/**
	 * Get a list of ActionScheduler completed jobs for the manifest job.
	 */
	public static function get_job_log() {
		$action_id   = \ActionScheduler::store()->query_action(
			array(
				'hook' => 'woocommerce_docs_manifest_job',
			)
		);
		$log_entries = \ActionScheduler::logger()->get_logs( $action_id );

		$entries = array();

		foreach ( $log_entries as $log_entry ) {
			$entries[] = array(
				'action_id' => $log_entry->get_action_id(),
				'message'   => $log_entry->get_message(),
				'date'      => $log_entry->get_date()->format( 'Y-m-d H:i:s' ),
			);
		}

		$response = new \WP_REST_Response( $entries );
		$response->set_status( 200 );

		return $response;
	}

	/**
	 * Check if user is allowed to use this endpoint.
	 *
	 * @param WP_REST_Request $request Full data about the request.
	 * @return WP_Error|bool
	 */
	public static function permission_check( $request ) {
		return current_user_can( 'edit_posts' );
	}
}
