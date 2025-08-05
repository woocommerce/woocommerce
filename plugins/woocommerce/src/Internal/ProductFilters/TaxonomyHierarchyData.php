<?php

declare(strict_types=1);

namespace Automattic\WooCommerce\Internal\ProductFilters;

defined( 'ABSPATH' ) || exit;

/**
 * Class for managing taxonomy hierarchy data with performance optimization.
 *
 * @internal For exclusive usage of WooCommerce core, backwards compatibility not guaranteed.
 */
class TaxonomyHierarchyData {

	/**
	 * Cache group for taxonomy hierarchy data.
	 */
	private const CACHE_GROUP = 'wc_taxonomy_hierarchy';

	/**
	 * In-memory cache for hierarchy maps.
	 *
	 * @var array
	 */
	private $hierarchy_data = array();

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

		// Check in-memory cache first.
		if ( isset( $this->hierarchy_data[ $taxonomy ] ) ) {
			return $this->hierarchy_data[ $taxonomy ];
		}

		// Check option cache.
		$cache_key  = self::CACHE_GROUP . '_' . $taxonomy;
		$cached_map = null;

		if ( ! ( defined( 'WP_DEBUG' ) && WP_DEBUG ) ) {
			$cached_map = get_option( $cache_key );
		}

		if ( ! empty( $cached_map ) && $this->validate_cache( $cached_map ) ) {
			// Cache in memory and return.
			$this->hierarchy_data[ $taxonomy ] = $cached_map;
			return $cached_map;
		}

		// Build the complete hierarchy map with all descendants pre-computed.
		$map = $this->build_full_hierarchy_map( $taxonomy );

		// Cache the map in options and memory.
		if ( ! ( defined( 'WP_DEBUG' ) && WP_DEBUG ) ) {
			update_option( $cache_key, $map, false );
		}

		$this->hierarchy_data[ $taxonomy ] = $map;

		return $map;
	}

	/**
	 * Get all descendants for a term.
	 *
	 * @param int    $term_id  The term ID.
	 * @param string $taxonomy The taxonomy name.
	 * @return array Array of all descendant term IDs.
	 */
	public function get_descendants( int $term_id, string $taxonomy ): array {
		$map = $this->get_hierarchy_map( $taxonomy );
		return $map['descendants'][ $term_id ] ?? array();
	}

	/**
	 * Get ancestor chain for batch processing.
	 *
	 * @param int    $term_id  The term ID.
	 * @param string $taxonomy The taxonomy name.
	 * @return array Array of ancestor term IDs (bottom-up).
	 */
	public function get_ancestors( int $term_id, string $taxonomy ): array {
		$map = $this->get_hierarchy_map( $taxonomy );
		return $map['ancestors'][ $term_id ] ?? array();
	}

	/**
	 * Clear hierarchy cache for a taxonomy.
	 *
	 * @param string $taxonomy The taxonomy name.
	 */
	public function clear_cache( string $taxonomy ): void {
		// Clear in-memory cache for this taxonomy.
		unset( $this->hierarchy_data[ $taxonomy ] );

		// Clear only the specific taxonomy's option cache.
		$cache_key = self::CACHE_GROUP . '_' . $taxonomy;
		delete_option( $cache_key );
	}

	/**
	 * Check if the cache is valid.
	 *
	 * @param array $data Cache data.
	 *
	 * @return boolean
	 */
	private function validate_cache( $data ) {
		return is_array( $data ) &&
			array_key_exists( 'descendants', $data ) &&
			array_key_exists( 'ancestors', $data ) &&
			array_key_exists( 'tree', $data );
	}

	/**
	 * Build hierarchy map for FilterData and ProductFilterTaxonomy.
	 *
	 * Pre-computes descendants and ancestor chains for maximum query speed.
	 *
	 * @param string $taxonomy The taxonomy name.
	 * @return array Complete hierarchy map with descendants and ancestor chains.
	 */
	private function build_full_hierarchy_map( string $taxonomy ): array {
		$terms = get_terms(
			array(
				'taxonomy'   => $taxonomy,
				'hide_empty' => false,
				'orderby'    => 'name',
				'order'      => 'ASC',
			)
		);

		if ( is_wp_error( $terms ) || empty( $terms ) ) {
			return array();
		}

		$map = array(
			'descendants' => array(), // term_id => [descendant_ids].
			'ancestors'   => array(), // term_id => [ancestor_ids].
			'tree'        => array(),
		);

		// Build core lookups and temporary structures.
		$temp_children = array();
		$temp_parents  = array();
		$temp_terms    = array();

		foreach ( $terms as $term ) {
			$term_id   = $term->term_id;
			$parent_id = $term->parent;

			$temp_parents[ $term_id ] = $parent_id;

			if ( ! isset( $temp_children[ $parent_id ] ) ) {
				$temp_children[ $parent_id ] = array();
			}

			$temp_children[ $parent_id ][] = $term_id;

			$temp_terms[ $term_id ] = array(
				'slug'    => $term->slug,
				'name'    => $term->name,
				'parent'  => $parent_id,
				'term_id' => $term->term_id,
			);
		}

		// Pre-compute descendants and ancestors.
		foreach ( array_keys( $temp_parents ) as $term_id ) {
			$map['descendants'][ $term_id ] = $this->compute_descendants( $term_id, $temp_children );
			$map['ancestors'][ $term_id ]   = $this->compute_ancestors( $term_id, $temp_parents );
		}

		foreach ( $temp_children[0] as $term_id ) {
			$this->build_term_tree( $map['tree'], $term_id, $temp_children, $temp_terms );
		}

		return $map;
	}

	/**
	 * Recursively build hierarchical term tree with depth and parent.
	 *
	 * @param array $tree       Reference to tree array being built.
	 * @param int   $term_id    Current term ID.
	 * @param array $children   Children relationships map (parent_id => [child_ids]).
	 * @param array $temp_terms Term data indexed by term_id.
	 * @param int   $depth      Current depth level in hierarchy.
	 */
	private function build_term_tree( &$tree, $term_id, $children, $temp_terms, $depth = 0 ) {
		$tree[ $term_id ]          = $temp_terms[ $term_id ];
		$tree[ $term_id ]['depth'] = $depth;

		if ( ! empty( $children[ $term_id ] ) ) {
			foreach ( $children[ $term_id ] as $child_id ) {
				$this->build_term_tree( $tree[ $term_id ]['children'], $child_id, $children, $temp_terms, $depth + 1 );
			}
		}
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
	 * Compute ancestor chain for a term.
	 *
	 * @param int   $term_id The term ID.
	 * @param array $parent_lookup Parent relationships.
	 * @return array Array of ancestor term IDs (bottom-up).
	 */
	private function compute_ancestors( int $term_id, array $parent_lookup ): array {
		$ancestors  = array();
		$current_id = $term_id;

		while ( isset( $parent_lookup[ $current_id ] ) && $parent_lookup[ $current_id ] > 0 ) {
			$parent_id   = $parent_lookup[ $current_id ];
			$ancestors[] = $parent_id;
			$current_id  = $parent_id;
		}

		return $ancestors;
	}
}
