<?php declare( strict_types=1 );

namespace Automattic\WooCommerce\Internal\Products;

/**
 * Repositions a single product within the catalog's menu_order sequence.
 */
final class ProductsOrderingMoveService {

	/**
	 * Reindex service.
	 *
	 * @var ProductsOrderingReindexService
	 */
	private ProductsOrderingReindexService $reindex_service;

	/**
	 * Initialize the service with dependencies.
	 *
	 * @internal
	 * @param ProductsOrderingReindexService $reindex_service Reindex service.
	 */
	final public function init( ProductsOrderingReindexService $reindex_service ): void { // phpcs:ignore Generic.CodeAnalysis.UnnecessaryFinalModifier.Found
		$this->reindex_service = $reindex_service;
	}

	/**
	 * Moves a product to the position between $previous_id and $next_id, triggering a full reindex first if positions are
	 * uninitialized or colliding. Indexed positions start at 1; menu_order = 0 is the sentinel for an unindexed product.
	 *
	 * Designed for an HVM with 500K+ products catalog operating in a clustered environment. To satisfy this setup:
	 * - if on-the-spot re-indexing is triggered: see design notes in \Automattic\WooCommerce\Internal\Products\ProductsOrderingReindexService::reindex_products
	 * - otherwise, the move takes 5 SQLs for any catalog size (4 of them PK-driven, hence nearly instant; operating on the targeted range of positions)
	 *
	 * @since 11.2.0
	 *
	 * @param int $previous_id ID of the product immediately before the target position, or 0 when moving to the start.
	 * @param int $product_id  ID of the product being repositioned.
	 * @param int $next_id     ID of the product immediately after the target position, or 0 when moving to the end.
	 * @return object{ moved:array<int,int>, reindexed:array<int,int> }
	 */
	public function move( int $previous_id, int $product_id, int $next_id ): object {
		$result = array(
			'moved'     => array(),
			'reindexed' => array(),
		);

		$anchor_positions = $this->compose_anchor_positions( $previous_id, $product_id, $next_id );
		if ( ! $this->has_moved( $anchor_positions, $previous_id, $product_id, $next_id ) ) {
			return (object) $result;
		}

		// Re-indexing is required when: a position collision is detected or moving between groups one of which is unindexed.
		$map              = $this->compose_move_map( $previous_id, $product_id, $next_id, $anchor_positions );
		$needs_reindexing = $map->old_position === $map->new_position || 0 === $map->new_position || 0 === $map->old_position;
		$needs_reindexing = $needs_reindexing || count( array_unique( $anchor_positions ) ) !== count( $anchor_positions );
		if ( $needs_reindexing ) {
			$result['reindexed'] = $this->reindex_service->reindex_products();

			$anchor_positions = $this->compose_anchor_positions( $previous_id, $product_id, $next_id );
			$map              = $this->compose_move_map( $previous_id, $product_id, $next_id, $anchor_positions );
			if ( ! $this->has_moved( $anchor_positions, $previous_id, $product_id, $next_id ) ) {
				return (object) $result;
			}
		}

		$result['moved'] = $this->apply( $map, $result['reindexed'] );
		// Deduct the move related modification from re-indexing to de-duplicate change events in outside workflow.
		foreach ( $result['moved'] as $id => $position ) {
			unset( $result['reindexed'][ $id ] );
		}

		return (object) $result;
	}

	/**
	 * Applies the move to the database and returns updated positions of all affected products.
	 *
	 * @phpstan-param object{ product_id:int, old_position:int, new_position:int, range_from:int, range_to:int, range_delta:int } $map Map with the move route.
	 * @param object         $map       Map with the move route.
	 * @param array<int,int> $reindexed Reindexed positions map.
	 * @return array<int,int>
	 */
	private function apply( object $map, array $reindexed ): array {
		global $wpdb;

		$range_ids          = array_merge( array( $map->product_id ), $this->compose_range_ids( $map, $reindexed ) );
		$range_placeholders = implode( ', ', array_fill( 0, count( $range_ids ), '%d' ) );

		// Shift the affected range (including the moved product) then pin it to the exact target position; update by PK is nearly instant.
		$updated_count  = 0;
		$updated_count += (int) $wpdb->query(
			// phpcs:ignore WordPress.DB.PreparedSQLPlaceholders.ReplacementsWrongNumber
			$wpdb->prepare(
				"UPDATE {$wpdb->posts} SET menu_order = menu_order + %d WHERE ID IN ( {$range_placeholders} )", // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
				$map->range_delta,
				...$range_ids
			)
		);
		$updated_count += (int) $wpdb->update( $wpdb->posts, array( 'menu_order' => $map->new_position ), array( 'ID' => $map->product_id ) );
		if ( $updated_count > 0 ) {
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
			if ( $clean_post_cache ) {
				// Performance note: fires clean_post_cache action per product for cache plugins compatibility (WooCommerce v11.2).
				array_walk( $range_ids, 'clean_post_cache' );
			} else {
				// Performance note: clear only the posts cache — menu_order lives in wp_posts, not in meta or term caches.
				wp_cache_delete_multiple( $range_ids, 'posts' );
				wp_cache_set_posts_last_changed();
			}
		}

		// Fetch updated positions for cache invalidation, hooks, and response; fetch by PK is nearly instant.
		$updated_positions = array_column(
			$wpdb->get_results(
				$wpdb->prepare(
					"SELECT ID, menu_order FROM {$wpdb->posts} WHERE ID IN ( {$range_placeholders} ) ORDER BY menu_order ASC", // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.PreparedSQLPlaceholders.UnfinishedPrepare
					$range_ids
				)
			),
			'menu_order',
			'ID'
		);

		return array_map( 'intval', $updated_positions );
	}

	/**
	 * Determines whether the product's position needs updating.
	 *
	 * @param array<int,int> $anchor_positions Map of product ID → current menu_order, ordered by menu_order ASC.
	 * @param int            $previous_id      ID of the product before the target position, or 0 for start.
	 * @param int            $product_id       ID of the product being repositioned.
	 * @param int            $next_id          ID of the product after the target position, or 0 for end.
	 *
	 * @return bool
	 */
	private function has_moved( array $anchor_positions, int $previous_id, int $product_id, int $next_id ): bool {
		// Compare DB ordering (array keys sorted by menu_order) against the requested ordering (filtering out 0 pseudo-IDs).
		$has_moved = array_keys( $anchor_positions ) !== array_values( array_filter( array( $previous_id, $product_id, $next_id ) ) );
		if ( $has_moved ) {
			return true;
		}

		// Unindexed (position 0) or colliding (duplicate positions) anchors always need work.
		$needs_reindexing = in_array( 0, $anchor_positions, true ) || count( array_unique( $anchor_positions ) ) !== count( $anchor_positions );
		if ( $needs_reindexing ) {
			return true;
		}

		return false;
	}

	/**
	 * Computes the target position and the range of products that must shift to accommodate the move.
	 *
	 * @param int            $previous_id      ID of the product immediately before the target position, or 0 when moving to the start.
	 * @param int            $product_id       ID of the product being repositioned.
	 * @param int            $next_id          ID of the product immediately after the target position, or 0 when moving to the end.
	 * @param array<int,int> $anchor_positions Map of product ID → current menu_order for the three anchor products.
	 *
	 * @return object{ product_id:int, old_position:int, new_position:int, range_from:int, range_to:int, range_delta:int }
	 */
	private function compose_move_map( int $previous_id, int $product_id, int $next_id, array $anchor_positions ): object {
		$previous_position = (int) ( $anchor_positions[ $previous_id ] ?? 0 );
		$product_position  = (int) ( $anchor_positions[ $product_id ] ?? 0 );
		$next_position     = (int) ( $anchor_positions[ $next_id ] ?? 0 );

		if ( $previous_position > $product_position ) {
			// Moving forward: products between current and new position shift down.
			$range_from   = $product_position + 1;
			$range_to     = $previous_position;
			$range_delta  = -1;
			$new_position = $previous_position;
		} else {
			// Moving backward: products between new and current position shift up.
			$range_from   = $next_position;
			$range_to     = $product_position - 1;
			$range_delta  = +1;
			$new_position = $next_position;
		}

		return (object) array(
			'product_id'   => $product_id,
			'old_position' => $product_position,
			'new_position' => $new_position,
			'range_from'   => $range_from,
			'range_to'     => $range_to,
			'range_delta'  => $range_delta,
		);
	}

	/**
	 * Fetches the current menu_order for the anchor products.
	 *
	 * @param int $previous_id ID of the product immediately before the target position, or 0 when moving to the start.
	 * @param int $product_id  ID of the product being repositioned.
	 * @param int $next_id     ID of the product immediately after the target position, or 0 when moving to the end.
	 * @return array<int,int>
	 */
	private function compose_anchor_positions( int $previous_id, int $product_id, int $next_id ): array {
		global $wpdb;

		return array_column(
			$wpdb->get_results(
				$wpdb->prepare(
					"SELECT ID, menu_order FROM {$wpdb->posts} WHERE ID IN (%d, %d, %d) ORDER BY menu_order ASC",
					$previous_id,
					$product_id,
					$next_id
				)
			),
			'menu_order',
			'ID'
		);
	}

	/**
	 * Returns IDs of products whose position falls within the move range.
	 *
	 * @phpstan-param object{ range_from:int, range_to:int } $map Map with the move route.
	 * @param object         $map       Map with the move route.
	 * @param array<int,int> $reindexed Reindexed positions map, keyed by product ID.
	 * @return int[]
	 */
	private function compose_range_ids( object $map, array $reindexed ): array {
		global $wpdb;

		// Performance note: when a prior reindex is available, derive range IDs from it — avoids a DB round-trip.
		$expected_count = $map->range_to - $map->range_from + 1;
		$range_ids      = array_keys( array_filter( $reindexed, static fn( $position ) => $position >= $map->range_from && $position <= $map->range_to ) );
		if ( count( $range_ids ) !== $expected_count ) {
			// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
			$range_ids = $wpdb->get_col(
				$wpdb->prepare(
					"SELECT ID FROM {$wpdb->posts} WHERE post_type = 'product' AND menu_order BETWEEN %d AND %d",
					$map->range_from,
					$map->range_to
				)
			);
		}

		return array_map( 'intval', $range_ids );
	}
}
