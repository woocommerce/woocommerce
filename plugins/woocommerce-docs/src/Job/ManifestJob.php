<?php
/**
 * ManifestJob class file
 *
 * @package  WooCommerceDocs
 */

namespace WooCommerceDocs\Job;

use WooCommerceDocs\Data;
use WooCommerceDocs\Manifest;

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
			as_enqueue_async_action( 'woocommerce_docs_manifest_job', array(), '', false );
			as_schedule_recurring_action( time() + 60, 60, 'woocommerce_docs_manifest_job', array(), '', false );
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
		$manifests = Data\ManifestStore::get_manifest_list();

		try {
			foreach ( $manifests as $manifest ) {
				$manifest_url = $manifest[0];
				$response     = wp_remote_get( $manifest_url );

				if ( is_wp_error( $response ) || wp_remote_retrieve_response_code( $response ) !== 200 ) {
					\ActionScheduler_Logger::instance()->log( $action_id, 'Error retrieving manifest: ' . $response->get_error_message() );
				}

				$json = json_decode( wp_remote_retrieve_body( $response ), true );

				if ( json_last_error() !== JSON_ERROR_NONE ) {
					\ActionScheduler_Logger::instance()->log( $action_id, 'Error decoding manifest: ' . json_last_error_msg() );
				}

				// first check if the manifest has changed.
				$existing_manifest = Data\ManifestStore::get_manifest_by_url( $manifest_url );
				$hash              = $json['hash'];

				if ( $existing_manifest['hash'] !== $hash ) {
					\ActionScheduler_Logger::instance()->log( $action_id, "Manifest hash changed: `$hash`, processing manifest." );
					Manifest\ManifestProcessor::process_manifest( $json, $action_id );
					Data\ManifestStore::update_manifest( $manifest_url, $json );
				} else {
					\ActionScheduler_Logger::instance()->log( $action_id, "Manifest hash unchanged: `$hash`, skipping manifest." );
				}
			}
		} catch ( \Exception $e ) {
			\ActionScheduler_Logger::instance()->log( $action_id, 'Error processing manifests: ' . $e->getMessage() );
		}

		\ActionScheduler_Logger::instance()->log( $action_id, 'Manifest job completed.' );
	}

}

