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
			'/completed_jobs',
			array(
				'methods'             => 'GET',
				'callback'            => array( '\WooCommerceDocs\API\JobAPI', 'get_completed_jobs' ),
				'permission_callback' => array( '\WooCommerceDocs\API\ManifestAPI', 'permission_check' ),
			)
		);
	}

	/**
	 * Get a list of ActionScheduler completed jobs
	 */
	public static function get_completed_jobs() {
		$completed_actions = \ActionScheduler_Store::instance()->query_actions(
			array(
				'status' => 'complete',
				'hook'   => 'woocommerce_docs_manifest_job',
			)
		);

		$jobs_array = array();

		foreach ( $completed_actions as $completed_action ) {
			$jobs_array[] = array(
				'action_id'   => $completed_action->get_id(),
				'action_data' => $completed_action->get_data(),
				'started'     => $completed_action->get_started_date()->format( 'Y-m-d H:i:s' ),
				'completed'   => $completed_action->get_completed_date()->format( 'Y-m-d H:i:s' ),
			);
		}

		$response = new \WP_REST_Response( $jobs_array );
		$response->set_status( 200 );

		return $response;
	}
}
