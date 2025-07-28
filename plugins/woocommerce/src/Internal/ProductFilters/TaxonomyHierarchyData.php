<?php

declare(strict_types=1);

namespace Automattic\WooCommerce\Internal\ProductFilters;

use WC_Cache_Helper;

defined( 'ABSPATH' ) || exit;

/**
 * Class for managing taxonomy hierarchy data with performance optimization.
 *
 * Provides a tiered architecture approach:
 * - Small taxonomies: Full hierarchy map for maximum performance
 * - Large taxonomies: Adjacency list with on-demand computation
 *
 * @internal For exclusive usage of WooCommerce core, backwards compatibility not guaranteed.
 */
class TaxonomyHierarchyData {

	/**
	 * Cache group for taxonomy hierarchy data.
	 */
	private const CACHE_GROUP = 'wc_taxonomy_hierarchy';

	/**
	 * Threshold for strategy selection.
	 * Small taxonomies (<1000 terms) use full hierarchy map for maximum performance.
	 * Large taxonomies (1000+ terms) use adjacency list with on-demand computation.
	 */
	private const SMALL_TAXONOMY_THRESHOLD = 1000;

	/**
	 * Get optimized hierarchy map for a taxonomy.
	 *
	 * @param string $taxonomy The taxonomy name.
	 * @return array Hierarchy map structure optimized for the taxonomy size.
	 */
	public function get_hierarchy_map( string $taxonomy ): array {
		if ( ! is_taxonomy_hierarchical( $taxonomy ) ) {
			return array();
		}

		$strategy = $this->get_optimal_strategy( $taxonomy );

		switch ( $strategy ) {
			case 'full_map':
				return $this->get_full_hierarchy_map( $taxonomy );
			case 'adjacency_map':
				return $this->get_adjacency_hierarchy_map( $taxonomy );
			default:
				return $this->get_adjacency_hierarchy_map( $taxonomy );
		}
	}

	/**
	 * Determine the optimal strategy based on taxonomy size.
	 *
	 * @param string $taxonomy The taxonomy name.
	 * @return string The optimal strategy ('full_map' or 'adjacency_map').
	 */
	private function get_optimal_strategy( string $taxonomy ): string {
		$term_count = wp_count_terms( array( 'taxonomy' => $taxonomy ) );

		if ( is_wp_error( $term_count ) ) {
			return 'adjacency_map';
		}

		if ( $term_count < self::SMALL_TAXONOMY_THRESHOLD ) {
			return 'full_map';
		} else {
			return 'adjacency_map';
		}
	}

	/**
	 * Get full hierarchy map for small taxonomies (<1000 terms).
	 *
	 * Provides maximum performance with pre-computed descendants and depth information.
	 *
	 * @param string $taxonomy The taxonomy name.
	 * @return array Full hierarchy map with parents, children, descendants, and depth info.
	 */
	private function get_full_hierarchy_map( string $taxonomy ): array {
		$cache_key = 'wc_hierarchy_full_' . $taxonomy;
		$map       = $this->get_cache( $cache_key );

		if ( ! empty( $map ) ) {
			return $map;
		}

		$map = $this->build_full_hierarchy_map( $taxonomy );

		$this->set_cache( $cache_key, $map );

		return $map;
	}

	/**
	 * Get adjacency hierarchy map for large taxonomies (1000+ terms).
	 *
	 * Provides balanced performance with memory efficiency.
	 *
	 * @param string $taxonomy The taxonomy name.
	 * @return array Adjacency list hierarchy map.
	 */
	private function get_adjacency_hierarchy_map( string $taxonomy ): array {
		$cache_key = 'wc_hierarchy_adj_' . $taxonomy;
		$map       = $this->get_cache( $cache_key );

		if ( ! empty( $map ) ) {
			return $map;
		}

		$map = $this->build_adjacency_hierarchy_map( $taxonomy );

		$this->set_cache( $cache_key, $map );

		return $map;
	}


	/**
	 * Build full hierarchy map with all relationships pre-computed.
	 *
	 * @param string $taxonomy The taxonomy name.
	 * @return array Complete hierarchy map.
	 */
	private function build_full_hierarchy_map( string $taxonomy ): array {
		$terms = get_terms(
			array(
				'taxonomy'   => $taxonomy,
				'hide_empty' => false,
				'fields'     => 'all',
			)
		);

		if ( is_wp_error( $terms ) || empty( $terms ) ) {
			return array();
		}

		$map = array(
			'parents'     => array(),
			'children'    => array(),
			'descendants' => array(),
			'by_depth'    => array(),
			'meta'        => array(),
		);

		// Build basic parent-child relationships
		foreach ( $terms as $term ) {
			$term_id   = $term->term_id;
			$parent_id = $term->parent;

			$map['parents'][ $term_id ] = $parent_id;

			if ( ! isset( $map['children'][ $parent_id ] ) ) {
				$map['children'][ $parent_id ] = array();
			}
			$map['children'][ $parent_id ][] = $term_id;
		}

		// Compute depths and organize by depth
		foreach ( $terms as $term ) {
			$depth                         = $this->compute_term_depth( $term->term_id, $map['parents'] );
			$map['meta'][ $term->term_id ] = array(
				'depth'      => $depth,
				'menu_order' => $term->term_order ?? 0,
			);

			if ( ! isset( $map['by_depth'][ $depth ] ) ) {
				$map['by_depth'][ $depth ] = array();
			}
			$map['by_depth'][ $depth ][] = $term->term_id;
		}

		// Pre-compute all descendants for each term
		foreach ( array_keys( $map['parents'] ) as $term_id ) {
			$map['descendants'][ $term_id ] = $this->compute_descendants( $term_id, $map['children'] );
		}

		return $map;
	}

	/**
	 * Build adjacency list hierarchy map.
	 *
	 * @param string $taxonomy The taxonomy name.
	 * @return array Adjacency list hierarchy map.
	 */
	private function build_adjacency_hierarchy_map( string $taxonomy ): array {
		$terms = get_terms(
			array(
				'taxonomy'   => $taxonomy,
				'hide_empty' => false,
				'fields'     => 'all',
			)
		);

		if ( is_wp_error( $terms ) || empty( $terms ) ) {
			return array();
		}

		$map = array(
			'children' => array(),
			'parents'  => array(),
			'depths'   => array(),
		);

		foreach ( $terms as $term ) {
			$term_id   = $term->term_id;
			$parent_id = $term->parent;

			$map['parents'][ $term_id ] = $parent_id;

			if ( ! isset( $map['children'][ $parent_id ] ) ) {
				$map['children'][ $parent_id ] = array();
			}
			$map['children'][ $parent_id ][] = $term_id;

			$map['depths'][ $term_id ] = $this->compute_term_depth( $term_id, $map['parents'] );
		}

		return $map;
	}


	/**
	 * Compute the depth of a term in the hierarchy.
	 *
	 * @param int   $term_id The term ID.
	 * @param array $parents Parent relationships map.
	 * @return int The depth level (0 for root terms).
	 */
	private function compute_term_depth( int $term_id, array $parents ): int {
		$depth      = 0;
		$current_id = $term_id;

		while ( isset( $parents[ $current_id ] ) && $parents[ $current_id ] > 0 ) {
			$current_id = $parents[ $current_id ];
			++$depth;

			// Prevent infinite loops in case of circular references
			if ( $depth > 50 ) {
				break;
			}
		}

		return $depth;
	}

	/**
	 * Compute all descendants of a term.
	 *
	 * @param int   $term_id  The term ID.
	 * @param array $children Children relationships map.
	 * @return array Array of descendant term IDs.
	 */
	private function compute_descendants( int $term_id, array $children ): array {
		$descendants = array();

		if ( ! isset( $children[ $term_id ] ) ) {
			return $descendants;
		}

		foreach ( $children[ $term_id ] as $child_id ) {
			$descendants[] = $child_id;
			$descendants   = array_merge( $descendants, $this->compute_descendants( $child_id, $children ) );
		}

		return array_unique( $descendants );
	}

	/**
	 * Get parent term ID for a given term (unified API).
	 *
	 * @param int    $term_id  The term ID.
	 * @param string $taxonomy The taxonomy name.
	 * @return int The parent term ID (0 if root level).
	 */
	public function get_parent( int $term_id, string $taxonomy ): int {
		$strategy = $this->get_optimal_strategy( $taxonomy );

		switch ( $strategy ) {
			case 'full_map':
				$map = $this->get_full_hierarchy_map( $taxonomy );
				return $map['parents'][ $term_id ] ?? 0;

			case 'adjacency_map':
				$map = $this->get_adjacency_hierarchy_map( $taxonomy );
				return $map['parents'][ $term_id ] ?? 0;

			default:
				return 0;
		}
	}

	/**
	 * Get direct children for a term (unified API).
	 *
	 * @param int    $term_id  The term ID.
	 * @param string $taxonomy The taxonomy name.
	 * @return array Array of direct children term IDs.
	 */
	public function get_children( int $term_id, string $taxonomy ): array {
		$strategy = $this->get_optimal_strategy( $taxonomy );

		switch ( $strategy ) {
			case 'full_map':
				$map = $this->get_full_hierarchy_map( $taxonomy );
				return $map['children'][ $term_id ] ?? array();

			case 'adjacency_map':
				$map = $this->get_adjacency_hierarchy_map( $taxonomy );
				return $map['children'][ $term_id ] ?? array();

			default:
				return array();
		}
	}

	/**
	 * Get all descendants for a term (unified API).
	 *
	 * @param int    $term_id  The term ID.
	 * @param string $taxonomy The taxonomy name.
	 * @return array Array of all descendant term IDs.
	 */
	public function get_descendants( int $term_id, string $taxonomy ): array {
		$strategy = $this->get_optimal_strategy( $taxonomy );

		switch ( $strategy ) {
			case 'full_map':
				$map = $this->get_full_hierarchy_map( $taxonomy );
				return $map['descendants'][ $term_id ] ?? array();

			case 'adjacency_map':
				$map = $this->get_adjacency_hierarchy_map( $taxonomy );
				return $this->compute_descendants( $term_id, $map['children'] ?? array() );

			default:
				return array();
		}
	}

	/**
	 * Get depth level for a term (unified API).
	 *
	 * @param int    $term_id  The term ID.
	 * @param string $taxonomy The taxonomy name.
	 * @return int The depth level (0 for root terms).
	 */
	public function get_depth( int $term_id, string $taxonomy ): int {
		$strategy = $this->get_optimal_strategy( $taxonomy );

		switch ( $strategy ) {
			case 'full_map':
				$map = $this->get_full_hierarchy_map( $taxonomy );
				return $map['meta'][ $term_id ]['depth'] ?? 0;

			case 'adjacency_map':
				$map = $this->get_adjacency_hierarchy_map( $taxonomy );
				return $map['depths'][ $term_id ] ?? 0;

			default:
				return 0;
		}
	}

	/**
	 * Get terms organized by depth level (unified API).
	 *
	 * @param string $taxonomy The taxonomy name.
	 * @return array Array with depth as key and term IDs as values.
	 */
	public function get_terms_by_depth( string $taxonomy ): array {
		$strategy = $this->get_optimal_strategy( $taxonomy );

		switch ( $strategy ) {
			case 'full_map':
				$map = $this->get_full_hierarchy_map( $taxonomy );
				return $map['by_depth'] ?? array();

			case 'adjacency_map':
				$map = $this->get_adjacency_hierarchy_map( $taxonomy );
				// Group terms by depth from the adjacency map
				$by_depth = array();
				foreach ( $map['depths'] as $term_id => $depth ) {
					if ( ! isset( $by_depth[ $depth ] ) ) {
						$by_depth[ $depth ] = array();
					}
					$by_depth[ $depth ][] = $term_id;
				}
				return $by_depth;

			default:
				return array();
		}
	}

	/**
	 * Get term metadata (unified API).
	 *
	 * @param int    $term_id  The term ID.
	 * @param string $taxonomy The taxonomy name.
	 * @return array Term metadata including depth, menu_order, etc.
	 */
	public function get_term_meta( int $term_id, string $taxonomy ): array {
		$strategy = $this->get_optimal_strategy( $taxonomy );

		switch ( $strategy ) {
			case 'full_map':
				$map = $this->get_full_hierarchy_map( $taxonomy );
				return $map['meta'][ $term_id ] ?? array();

			case 'adjacency_map':
				$map = $this->get_adjacency_hierarchy_map( $taxonomy );
				return array(
					'depth'      => $map['depths'][ $term_id ] ?? 0,
					'menu_order' => 0, // Not stored in adjacency map
				);

			default:
				return array();
		}
	}


	/**
	 * Clear hierarchy cache for a taxonomy.
	 *
	 * @param string $taxonomy The taxonomy name.
	 */
	public function clear_cache( string $taxonomy ): void {
		// Increment cache group version to invalidate all hierarchy caches
		WC_Cache_Helper::invalidate_cache_group( self::CACHE_GROUP );
	}

	/**
	 * Clear all hierarchy caches.
	 */
	public function clear_all_caches(): void {
		// Increment cache group version to invalidate all hierarchy caches
		WC_Cache_Helper::invalidate_cache_group( self::CACHE_GROUP );
	}


	/**
	 * Get cache with debug skip and version checking like FilterData.
	 *
	 * @param string $key Cache key.
	 * @return array|null Cached data or null if not found/invalid.
	 */
	private function get_cache( string $key ): ?array {
		if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
			return null;
		}

		$cache             = get_transient( $key );
		$transient_version = WC_Cache_Helper::get_transient_version( self::CACHE_GROUP );

		if ( empty( $cache['version'] ) ||
			! is_array( $cache['value'] ) ||
			empty( $cache['value'] ) ||
			$transient_version !== $cache['version']
		) {
			return null;
		}

		return $cache['value'];
	}

	/**
	 * Set cache with transient version for cache invalidation like FilterData.
	 *
	 * @param string $key   Cache key.
	 * @param array  $value Value to cache.
	 * @return bool True if cache was set successfully.
	 */
	private function set_cache( string $key, array $value ): bool {
		if ( ! is_array( $value ) ) {
			return false;
		}

		if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
			return false;
		}

		$transient_version = WC_Cache_Helper::get_transient_version( self::CACHE_GROUP );
		$transient_value   = array(
			'version' => $transient_version,
			'value'   => $value,
		);

		return set_transient( $key, $transient_value, DAY_IN_SECONDS );
	}
}
