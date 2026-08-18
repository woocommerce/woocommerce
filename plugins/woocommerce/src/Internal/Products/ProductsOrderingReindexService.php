<?php declare( strict_types=1 );

namespace Automattic\WooCommerce\Internal\Products;

/**
 * Assigns sequential menu_order values to all products, enabling deterministic drag-and-drop ordering.
 */
final class ProductsOrderingReindexService {
	/**
	 * Designed for an HVM with 500K+ products catalog operating in a clustered environment. To satisfy this setup:
	 * - The batch size is set to 250. Increasing this value may negatively impact catalog browsing performance.
	 * - Cache invalidation targets the posts cache only, since menu_order lives in wp_posts and is not stored in meta or term caches.
	 * - Resources allocation for 500K products catalog: product-position map - 20 MB RAM, 2000 SQLs for full reindexing ± 2 seconds.
	 *
	 * @since 11.2.0
	 *
	 * @param int $batch_size Number of products included in each batch for re-indexing.
	 * @return array<int,int>
	 */
	public function reindex_products( int $batch_size = 250 ): array {
		global $wpdb;

		// Performance note: prefetch product ids; enables deterministic behaviour and faster queries below.
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		$product_ids = array_map( 'intval', $wpdb->get_col( "SELECT ID FROM {$wpdb->posts} WHERE post_type = 'product' ORDER BY menu_order ASC, post_title ASC, ID ASC" ) );
		/**
		 * Whether to fire the clean_post_cache action per product after reordering or apply targeted cache invalidation.
		 * Default strategy is clean_post_cache is suboptimal, but applied for backward compatibility reasons.
		 *
		 * @since 11.2.0
		 *
		 * @param bool $clean_post_cache Whether to fire clean_post_cache per product.
		 * @returns bool
		 */
		$clean_post_cache = (bool) apply_filters( 'woocommerce_single_product_ordering_clean_post_cache', true );

		$result           = array();
		$current_position = 1;
		for ( $offset = 0, $total = count( $product_ids ), $batch_size = max( 1, $batch_size ); $offset < $total; $offset += $batch_size ) {
			$batch_ids       = array_slice( $product_ids, $offset, $batch_size );
			$batch_positions = array();
			$batch_branches  = array();
			foreach ( $batch_ids as $id ) {
				$batch_positions[ $id ] = $current_position;
				$batch_branches[]       = sprintf( 'WHEN %d THEN %d', $id, $current_position++ );
			}

			$batch_branches = implode( ' ', $batch_branches );
			$in_values      = implode( ', ', $batch_ids );
			// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.NotPrepared, WordPress.DB.PreparedSQL.InterpolatedNotPrepared
			$updated = (int) $wpdb->query( "UPDATE {$wpdb->posts} SET menu_order = CASE ID {$batch_branches} END WHERE ID IN ( {$in_values} )" );
			if ( $updated > 0 ) {
				if ( $clean_post_cache ) {
					// Performance note: fires clean_post_cache action per product for cache plugins compatibility (WooCommerce v11.2).
					array_walk( $batch_ids, 'clean_post_cache' );
				} else {
					// Performance note: clear only the posts cache — menu_order lives in wp_posts, not in meta or term caches.
					wp_cache_delete_multiple( $batch_ids, 'posts' );
					wp_cache_set_posts_last_changed();
				}

				// Update the result entries only if update is confirmed.
				foreach ( $batch_positions as $id => $position ) {
					$result[ $id ] = $position;
				}
			}
		}

		return $result;
	}
}
