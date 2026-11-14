<?php

declare( strict_types=1 );

namespace Automattic\WooCommerce\Internal\Admin\ImportExport\Product;

/**
 * Removes placeholder products the product CSV importer creates for unresolved references
 * (for example a variation whose parent has not been imported yet) once the import that
 * created them has finished.
 *
 * Shared by the admin product importer and the `wp wc product import` CLI command, both of
 * which run this cleanup over every placeholder on the site rather than just the ones their
 * own run created.
 *
 * @since 11.4.0
 */
final class ImportPlaceholders {

	/**
	 * Number of temporary products deleted per query when cleaning up after an import.
	 */
	private const CLEANUP_BATCH_SIZE = 100;

	/**
	 * Status the importer gives the temporary products it creates for unresolved references.
	 */
	private const CLEANUP_PLACEHOLDER_STATUS = 'importing';

	/**
	 * Status a temporary product is moved to once cleanup has claimed it for deletion.
	 */
	private const CLEANUP_CLAIMED_STATUS = 'importing-cleanup';

	/**
	 * Remove temporary products and mapping data left by the importer.
	 *
	 * @param int $cursor Highest placeholder ID earlier cleanup requests have examined.
	 * @return int|null ID to resume from, or null once no placeholders are left.
	 */
	public function cleanup_after_import( int $cursor = 0 ): ?int {
		global $wpdb;

		if ( 0 === $cursor ) {
			// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.SlowDBQuery.slow_db_query_meta_key -- The importer requires one uncached cleanup of its temporary mapping markers.
			$wpdb->delete( $wpdb->postmeta, array( 'meta_key' => '_original_id' ) );
		}

		/** This filter is documented in includes/import/abstract-wc-product-importer.php */
		$time_limit = (int) apply_filters( 'woocommerce_product_importer_default_time_limit', 20 ); // phpcs:ignore WooCommerce.Commenting.CommentHooks.MissingSinceComment
		$deadline   = time() + $time_limit;

		// Page by ID so a placeholder that cannot be deleted is passed over instead of being read again.
		while ( true ) {
			// The claimed status is also selected so a request that died mid-batch does not strand its placeholders.
			// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
			$post_ids = $wpdb->get_col(
				$wpdb->prepare(
					"SELECT ID FROM {$wpdb->posts} WHERE post_type IN ( 'product', 'product_variation' ) AND post_status IN ( %s, %s ) AND ID > %d ORDER BY ID ASC LIMIT %d",
					self::CLEANUP_PLACEHOLDER_STATUS,
					self::CLEANUP_CLAIMED_STATUS,
					$cursor,
					self::CLEANUP_BATCH_SIZE
				)
			);

			if ( ! $post_ids ) {
				return null;
			}

			$post_ids = array_map( 'absint', $post_ids );
			$cursor   = (int) end( $post_ids );

			// Ascending IDs delete a placeholder parent before its variations, so WooCommerce removes them through the normal lifecycle.
			foreach ( $post_ids as $post_id ) {
				$this->delete_claimed_placeholder( $post_id );

				// One delete lifecycle can take seconds, so check the budget per placeholder rather than per batch.
				if ( time() >= $deadline ) {
					return $post_id;
				}
			}
		}
	}

	/**
	 * Release temporary products a cleanup request claimed but did not live to delete.
	 *
	 * A fatal inside the delete lifecycle leaves the claimed status behind, and the importer only
	 * recognises a placeholder by the status it was created with, so the CSV row owning that SKU
	 * would be skipped as an existing product. Healing at the start of a run keeps that contained.
	 */
	public function release_stranded_cleanup_claims(): void {
		global $wpdb;

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		$post_ids = $wpdb->get_col(
			$wpdb->prepare(
				"SELECT ID FROM {$wpdb->posts} WHERE post_type IN ( 'product', 'product_variation' ) AND post_status = %s",
				self::CLEANUP_CLAIMED_STATUS
			)
		);

		if ( ! $post_ids ) {
			return;
		}

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		$wpdb->query(
			$wpdb->prepare(
				"UPDATE {$wpdb->posts} SET post_status = %s WHERE post_type IN ( 'product', 'product_variation' ) AND post_status = %s",
				self::CLEANUP_PLACEHOLDER_STATUS,
				self::CLEANUP_CLAIMED_STATUS
			)
		);

		wp_cache_delete_multiple( array_map( 'absint', $post_ids ), 'posts' );
	}

	/**
	 * Count placeholder products an import left behind, whether still pending deletion
	 * or claimed by a cleanup pass that did not finish.
	 *
	 * @return int
	 */
	public function count_pending_placeholders(): int {
		global $wpdb;

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		return (int) $wpdb->get_var(
			$wpdb->prepare(
				"SELECT COUNT(*) FROM {$wpdb->posts} WHERE post_type IN ( 'product', 'product_variation' ) AND post_status IN ( %s, %s )",
				self::CLEANUP_PLACEHOLDER_STATUS,
				self::CLEANUP_CLAIMED_STATUS
			)
		);
	}

	/**
	 * Claim one importer placeholder and delete it only if the claim succeeded.
	 *
	 * Reading the status from the object cache cannot decide this: a process that completes a
	 * placeholder does not invalidate this process's cache, so a published product would still
	 * look like a placeholder and be force deleted. The guarded UPDATE makes the status check
	 * atomic instead, the way ActionScheduler_DBStore::claim_actions() claims actions, and runs
	 * per post so a placeholder completed while the batch is being deleted is still protected.
	 *
	 * @param int $post_id Candidate post ID.
	 */
	private function delete_claimed_placeholder( int $post_id ): void {
		global $wpdb;

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		$wpdb->query(
			$wpdb->prepare(
				"UPDATE {$wpdb->posts} SET post_status = %s WHERE ID = %d AND post_type IN ( 'product', 'product_variation' ) AND post_status IN ( %s, %s )",
				self::CLEANUP_CLAIMED_STATUS,
				$post_id,
				self::CLEANUP_PLACEHOLDER_STATUS,
				self::CLEANUP_CLAIMED_STATUS
			)
		);

		// The claim wrote the row behind the object cache's back, so read it back from the database.
		wp_cache_delete( $post_id, 'posts' );

		$post = get_post( $post_id );

		if ( ! $post || self::CLEANUP_CLAIMED_STATUS !== $post->post_status ) {
			return;
		}

		try {
			wp_delete_post( $post_id, true );
		} finally {
			// Release the claim if the post outlived the delete lifecycle, so it stays a placeholder for the next run.
			// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
			$released = $wpdb->query(
				$wpdb->prepare(
					"UPDATE {$wpdb->posts} SET post_status = %s WHERE ID = %d AND post_status = %s",
					self::CLEANUP_PLACEHOLDER_STATUS,
					$post_id,
					self::CLEANUP_CLAIMED_STATUS
				)
			);

			if ( $released ) {
				wp_cache_delete( $post_id, 'posts' );
			}
		}
	}
}
