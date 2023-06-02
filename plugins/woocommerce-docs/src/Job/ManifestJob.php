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

		// Query the manifests
		$manifests = \WooCommerceDocs\Data\ManifestStore::get_manifest_list();

		// Loop through the manifests and update the data.
		foreach ( $manifests as $manifest ) {
			// $manifest_data = \WooCommerceDocs\Data\ManifestStore::get_manifest( $manifest );

			// // Get the manifest data.
			// $manifest_data = \WooCommerceDocs\API\ManifestAPI::get_manifest_data( $manifest_data['url'] );

			// // Update the manifest data.
			// \WooCommerceDocs\Data\ManifestStore::update_manifest( $manifest, $manifest_data );
		}

		// Log the execution.
		\ActionScheduler_Logger::instance()->log( $action_id, 'Manifest job completed.' );
	}

}

