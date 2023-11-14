<?php
/**
 * ManifestJob class file
 *
 * @package  WooCommerceDocs
 */

namespace WooCommerceDocs\Job;

use WooCommerceDocs\Data;
use WooCommerceDocs\Data\DocsStore;
use WooCommerceDocs\Manifest\ManifestProcessor;
use WooCommerceDocs\Manifest\RelativeLinkParser;

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
					continue;
				}

				$json = json_decode( wp_remote_retrieve_body( $response ), true );

				if ( json_last_error() !== JSON_ERROR_NONE ) {
					\ActionScheduler_Logger::instance()->log( $action_id, 'Error decoding manifest: ' . json_last_error_msg() );
					continue;
				}

				// first check if the manifest has changed.
				$existing_manifest = Data\ManifestStore::get_manifest_by_url( $manifest_url );
				$hash              = array_key_exists( 'hash', $json ) ? $json['hash'] : null;
				$hash_changed      = array_key_exists( 'hash', $existing_manifest ) && $existing_manifest['hash'] !== $hash;

				if ( ! array_key_exists( 'hash', $existing_manifest ) || $hash_changed ) {
					if ( $hash_changed ) {
						\ActionScheduler_Logger::instance()->log( $action_id, "Manifest hash changed: `$hash`, processing manifest." );
					} else {
						\ActionScheduler_Logger::instance()->log( $action_id, 'No previous manifest found, processing manifest.' );
						$existing_manifest = null;
					}

					ManifestProcessor::process_manifest( $json, $action_id, $existing_manifest );

					$doc_ids        = ManifestProcessor::collect_doc_ids_from_manifest( $json );
					$relative_links = RelativeLinkParser::extract_links_from_manifest( $json );

					foreach ( $doc_ids as $doc_id ) {
						$post = DocsStore::get_post( $doc_id );

						if ( null !== $post ) {
							$content            = $post->post_content;
							$updated_content    = RelativeLinkParser::replace_relative_links( $content, $relative_links, $action_id );
							$post->post_content = $updated_content;
							DocsStore::update_docs_post( $post, $doc_id );
						} else {
							\ActionScheduler_Logger::instance()->log( $action_id, "During link replacement, post was not found for doc: `$doc_id`" );
						}
					}

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

