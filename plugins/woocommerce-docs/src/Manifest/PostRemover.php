<?php

namespace WooCommerceDocs\Manifest;

use WooCommerceDocs\Data\DocsStore;

/**
 * Class Remover
 *
 * Remove posts that are no longer in the manifest.
 *
 * @package WooCommerceDocs\Manifest
 */
class PostRemover {
	/**
	 * Remove posts that are no longer in the manifest.
	 *
	 * @param Object $manifest The manifest to process.
	 * @param Object $previous_manifest The previous manifest.
	 * @param int    $logger_action_id The logger action ID.
	 */
	public static function remove_deleted_posts( $manifest, $previous_manifest, $logger_action_id ) {
		$doc_ids_to_delete = self::get_doc_ids_to_delete( $manifest, $previous_manifest );

		foreach ( $doc_ids_to_delete as $doc_id ) {
			\ActionScheduler_Logger::instance()->log( $logger_action_id, 'Removing post deleted post with document id: ' . $doc_id );
			DocsStore::delete_docs_post( $doc_id );
		}
	}

	/**
	 * Get a list of doc IDs to delete.
	 *
	 * @param Object $manifest The manifest to process.
	 * @param Object $previous_manifest The previous manifest.
	 */
	public static function get_doc_ids_to_delete( $manifest, $previous_manifest ) {
		$manifest_doc_ids          = ManifestProcessor::collect_doc_ids_from_manifest( $manifest );
		$previous_manifest_doc_ids = ManifestProcessor::collect_doc_ids_from_manifest( $previous_manifest );

		return array_diff( $previous_manifest_doc_ids, $manifest_doc_ids );
	}
}
