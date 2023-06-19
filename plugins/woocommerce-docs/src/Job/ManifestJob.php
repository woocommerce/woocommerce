<?php
/**
 * ManifestJob class file
 *
 * @package  WooCommerceDocs
 */

namespace WooCommerceDocs\Job;

/**
 * A class to handle the manifest job.
 */
class ManifestJob {
	/**
	 * Constructor
	 */
	public function __construct() {
		add_action( 'init', array( $this, 'schedule_job' ) );
		add_action( 'woocommerce_docs_manifest_job', array( $this, 'run_job' ) );
	}

	/**
	 * Schedule the job
	 */
	public function schedule_job() {
		if ( ! as_has_scheduled_action( 'woocommerce_docs_manifest_job' ) ) {
			as_schedule_recurring_action( time(), 15, 'woocommerce_docs_manifest_job', array(), '', false );
		}
	}

	/**
	 * Run the job
	 */
	public function run_job() {
		$action_id = \ActionScheduler::store()->query_action(
			array(
				'hook' => current_action(),
				'args' => func_get_args(),
			)
		);

		// Manifests are a list of tuples like ["url", {...}].
		$manifests = \WooCommerceDocs\Data\ManifestStore::get_manifest_list();

		foreach ( $manifests as $manifest ) {
			$manifest_url = $manifest[0];
			$response     = wp_remote_get( $manifest_url );

			if ( is_wp_error( $response ) ) {
				\ActionScheduler_Logger::instance()->log( $action_id, 'Error retrieving manifest: ' . $response->get_error_message() );
			}

			$json = json_decode( wp_remote_retrieve_body( $response ), true );

			if ( json_last_error() !== JSON_ERROR_NONE ) {
				\ActionScheduler_Logger::instance()->log( $action_id, 'Error decoding manifest: ' . json_last_error_msg() );
			}

			// first check if the manifest has changed.
			$existing_manifest = \WooCommerceDocs\Data\ManifestStore::get_manifest_by_url( $manifest_url );

			if ( $existing_manifest && $existing_manifest['hash'] !== $json['hash'] ) {
				// update the manifest if it changed.
				\WooCommerceDocs\Data\ManifestStore::update_manifest( $manifest_url, $json );

				// process the manifest.
				\WooCommerceDocs\Data\ManifestProcessor::process_manifest( $json );
			}
		}

		\ActionScheduler_Logger::instance()->log( $action_id, 'Manifest job completed.' );
	}

}

