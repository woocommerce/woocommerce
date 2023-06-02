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
		$actions = as_get_scheduled_actions(
			array(
				'hook' => 'woocommerce_docs_manifest_job',
			)
		);

		$log_entries = array();

		foreach ( $actions as $action ) {
			$log_entries = array_merge( $log_entries, \ActionScheduler::logger()->get_logs( $action ) );
		}

		$entries = array();

		foreach ( $log_entries as $log_entry ) {
			$entries[] = array(
				'id'        => $log_entry->get_id(),
				'action_id' => $log_entry->get_action_id(),
				'message'   => $log_entry->get_message(),
				'date'      => $log_entry->get_date()->format( 'Y-m-d H:i:s' ),
			);
		}

		$response = new \WP_REST_Response( $entries );
		$response->set_status( 200 );

		return $response;
	}
}
