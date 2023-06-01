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

		\WooCommerceDocs\Data\ManifestStore::add_manifest_url(
			substr( str_shuffle( md5( time() ) ), 0, 20 )
		);

	}
}
