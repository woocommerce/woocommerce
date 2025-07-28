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
	 * In-memory cache for hierarchy maps.
	 *
	 * @var array
	 */
	private $hierarchy_cache = array();

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

		// Check in-memory cache first
		if ( isset( $this->hierarchy_cache[ $taxonomy ] ) ) {
			return $this->hierarchy_cache[ $taxonomy ];
		}

		// Check transient cache
		$cache_key  = 'wc_hierarchy_' . $taxonomy;
		$cached_map = null;

		if ( ! ( defined( 'WP_DEBUG' ) && WP_DEBUG ) ) {
			$cache             = get_transient( $cache_key );
			$transient_version = WC_Cache_Helper::get_transient_version( self::CACHE_GROUP );

			if ( ! empty( $cache['version'] ) &&
				is_array( $cache['value'] ) &&
				! empty( $cache['value'] ) &&
				$transient_version === $cache['version']
			) {
				$cached_map = $cache['value'];
			}
		}

		if ( ! empty( $cached_map ) ) {
			// Cache in memory and return
			$this->hierarchy_cache[ $taxonomy ] = $cached_map;
			return $cached_map;
		}

		// Build the map based on current strategy
		$strategy = $this->get_optimal_strategy( $taxonomy );
		switch ( $strategy ) {
			case 'full_map':
				$map = $this->build_full_hierarchy_map( $taxonomy );
				break;
			default:
				$map = $this->build_adjacency_hierarchy_map( $taxonomy );
				break;
		}

		// Cache the map in transient and memory
		if ( ! ( defined( 'WP_DEBUG' ) && WP_DEBUG ) ) {
			$transient_version = WC_Cache_Helper::get_transient_version( self::CACHE_GROUP );
			$transient_value   = array(
				'version' => $transient_version,
				'value'   => $map,
			);
			set_transient( $cache_key, $transient_value, DAY_IN_SECONDS );
		}

		$this->hierarchy_cache[ $taxonomy ] = $map;

		return $map;
	}

	/**
	 * Determine the optimal strategy based on taxonomy size.
	 *
	 * @param string $taxonomy The taxonomy name.
	 * @return string The optimal strategy ('full_map' or 'adjacency_map').
	 */
	private function get_optimal_strategy( string $taxonomy ): string {
		$term_count = wp_count_terms( array( 'taxonomy' => $taxonomy ) );

		if ( $term_count >= self::SMALL_TAXONOMY_THRESHOLD ) {
			return 'adjacency_map';
		} else {
			return 'full_map';
		}
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
		$map = $this->get_hierarchy_map( $taxonomy );
		return $map['parents'][ $term_id ] ?? 0;
	}

	/**
	 * Get direct children for a term (unified API).
	 *
	 * @param int    $term_id  The term ID.
	 * @param string $taxonomy The taxonomy name.
	 * @return array Array of direct children term IDs.
	 */
	public function get_children( int $term_id, string $taxonomy ): array {
		$map = $this->get_hierarchy_map( $taxonomy );
		return $map['children'][ $term_id ] ?? array();
	}

	/**
	 * Get all descendants for a term (unified API).
	 *
	 * @param int    $term_id  The term ID.
	 * @param string $taxonomy The taxonomy name.
	 * @return array Array of all descendant term IDs.
	 */
	public function get_descendants( int $term_id, string $taxonomy ): array {
		$map = $this->get_hierarchy_map( $taxonomy );

		// Full map has pre-computed descendants
		if ( isset( $map['descendants'] ) ) {
			return $map['descendants'][ $term_id ] ?? array();
		}

		// Adjacency map requires computation
		return $this->compute_descendants( $term_id, $map['children'] ?? array() );
	}

	/**
	 * Get depth level for a term (unified API).
	 *
	 * @param int    $term_id  The term ID.
	 * @param string $taxonomy The taxonomy name.
	 * @return int The depth level (0 for root terms).
	 */
	public function get_depth( int $term_id, string $taxonomy ): int {
		$map = $this->get_hierarchy_map( $taxonomy );

		// Full map stores depth in meta array
		if ( isset( $map['meta'][ $term_id ]['depth'] ) ) {
			return $map['meta'][ $term_id ]['depth'];
		}

		// Adjacency map stores depth directly in depths array
		return $map['depths'][ $term_id ] ?? 0;
	}

	/**
	 * Get terms organized by depth level (unified API).
	 *
	 * @param string $taxonomy The taxonomy name.
	 * @return array Array with depth as key and term IDs as values.
	 */
	public function get_terms_by_depth( string $taxonomy ): array {
		$map = $this->get_hierarchy_map( $taxonomy );

		// Full map has pre-computed by_depth array
		if ( isset( $map['by_depth'] ) ) {
			return $map['by_depth'];
		}

		// Adjacency map requires grouping by depth
		$by_depth = array();
		foreach ( $map['depths'] as $term_id => $depth ) {
			if ( ! isset( $by_depth[ $depth ] ) ) {
				$by_depth[ $depth ] = array();
			}
			$by_depth[ $depth ][] = $term_id;
		}

		return $by_depth;
	}

	/**
	 * Get term metadata (unified API).
	 *
	 * @param int    $term_id  The term ID.
	 * @param string $taxonomy The taxonomy name.
	 * @return array Term metadata including depth, menu_order, etc.
	 */
	public function get_term_meta( int $term_id, string $taxonomy ): array {
		$map = $this->get_hierarchy_map( $taxonomy );

		// Full map has complete metadata
		if ( isset( $map['meta'][ $term_id ] ) ) {
			return $map['meta'][ $term_id ];
		}

		// Adjacency map has limited metadata
		return array(
			'depth'      => $map['depths'][ $term_id ] ?? 0,
			'menu_order' => 0, // Not stored in adjacency map
		);
	}


	/**
	 * Clear hierarchy cache for a taxonomy.
	 *
	 * @param string $taxonomy The taxonomy name.
	 */
	public function clear_cache( string $taxonomy ): void {
		// Clear in-memory cache for this taxonomy
		unset( $this->hierarchy_cache[ $taxonomy ] );

		// Increment cache group version to invalidate all hierarchy caches
		WC_Cache_Helper::invalidate_cache_group( self::CACHE_GROUP );
	}

	/**
	 * Clear all hierarchy caches.
	 */
	public function clear_all_caches(): void {
		// Clear all in-memory caches
		$this->hierarchy_cache = array();

		// Increment cache group version to invalidate all hierarchy caches
		WC_Cache_Helper::invalidate_cache_group( self::CACHE_GROUP );
	}
}
