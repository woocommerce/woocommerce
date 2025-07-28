<?php
declare( strict_types = 1 );

namespace Automattic\WooCommerce\Blocks\BlockTypes;

use Automattic\WooCommerce\Blocks\BlockTypes\ProductCollection\Utils as ProductCollectionUtils;
use Automattic\WooCommerce\Internal\ProductFilters\FilterDataProvider;
use Automattic\WooCommerce\Internal\ProductFilters\QueryClauses;
use Automattic\WooCommerce\Internal\ProductFilters\TaxonomyHierarchyData;

/**
 * Product Filter: Taxonomy Block.
 */
final class ProductFilterTaxonomy extends AbstractBlock {

	/**
	 * Block name.
	 *
	 * @var string
	 */
	protected $block_name = 'product-filter-taxonomy';

	/**
	 * Initialize this block type.
	 *
	 * - Hook into WP lifecycle.
	 * - Register the block with WordPress.
	 */
	protected function initialize() {
		parent::initialize();

		add_filter( 'woocommerce_blocks_product_filters_selected_items', array( $this, 'prepare_selected_filters' ), 10, 2 );
	}

	/**
	 * Extra data passed through from server to client for block.
	 *
	 * @param array $attributes  Any attributes that currently are available from the block.
	 *                           Note, this will be empty in the editor context when the block is
	 *                           not in the post content on editor load.
	 */
	protected function enqueue_data( array $attributes = array() ) {
		parent::enqueue_data( $attributes );

		if ( is_admin() ) {
			$this->asset_data_registry->add( 'filterableProductTaxonomies', $this->get_taxonomies() );
		}
	}

	/**
	 * Prepare the active filter items.
	 *
	 * @internal For exclusive usage of WooCommerce core, backwards compatibility not guaranteed.
	 *
	 * @param array $items  The active filter items.
	 * @param array $params The query param parsed from the URL.
	 * @return array Active filters items.
	 */
	public function prepare_selected_filters( $items, $params ) {
		$container      = wc_get_container();
		$params_handler = $container->get( \Automattic\WooCommerce\Internal\ProductFilters\Params::class );

		// Use centralized parameter mapping to avoid hardcoding URL parameter formats.
		$taxonomy_params = $params_handler->get_param( 'taxonomy' );

		$active_taxonomies = array();
		$all_term_slugs    = array();

		foreach ( $taxonomy_params as $taxonomy_slug => $param_key ) {
			if ( ! empty( $params[ $param_key ] ) && is_string( $params[ $param_key ] ) ) {
				$term_slugs                          = array_map( 'sanitize_title', explode( ',', $params[ $param_key ] ) );
				$active_taxonomies[ $taxonomy_slug ] = $term_slugs;
				$all_term_slugs                      = array_merge( $all_term_slugs, $term_slugs );
			}
		}

		if ( empty( $active_taxonomies ) ) {
			return $items;
		}

		// Single query for all taxonomies and terms to avoid N+1 query problem.
		$terms = get_terms(
			array(
				'taxonomy'   => array_keys( $active_taxonomies ),
				'slug'       => array_unique( $all_term_slugs ),
				'hide_empty' => false,
			)
		);

		if ( is_wp_error( $terms ) || empty( $terms ) ) {
			return $items;
		}

		foreach ( $terms as $term ) {
			$taxonomy_object = get_taxonomy( $term->taxonomy );
			if ( $taxonomy_object ) {
				$items[] = array(
					'type'        => 'taxonomy/' . $term->taxonomy,
					'value'       => $term->slug,
					'activeLabel' => $taxonomy_object->labels->singular_name . ': ' . $term->name,
				);
			}
		}

		return $items;
	}

	/**
	 * Render the block.
	 *
	 * @param array    $block_attributes Block attributes.
	 * @param string   $content          Block content.
	 * @param WP_Block $block            Block instance.
	 * @return string Rendered block type output.
	 */
	protected function render( $block_attributes, $content, $block ) {
		// Skip rendering in admin or during AJAX requests.
		if ( is_admin() || wp_doing_ajax() || empty( $block_attributes['taxonomy'] ) ) {
			return '';
		}

		$taxonomy        = $block_attributes['taxonomy'];
		$taxonomy_object = get_taxonomy( $taxonomy );

		if ( ! $taxonomy_object || ! taxonomy_exists( $taxonomy ) ) {
			return '';
		}

		// Validate that this taxonomy is configured in the parameter map.
		$container       = wc_get_container();
		$params_handler  = $container->get( \Automattic\WooCommerce\Internal\ProductFilters\Params::class );
		$taxonomy_params = $params_handler->get_param( 'taxonomy' );

		if ( ! isset( $taxonomy_params[ $taxonomy ] ) ) {
			return '';
		}

		// Pass taxonomy parameter mapping to frontend via interactivity config.
		wp_interactivity_config(
			'woocommerce/product-filters',
			array(
				'taxonomyParamsMap' => $taxonomy_params,
			)
		);

		$taxonomy_counts = $this->get_taxonomy_term_counts( $block, $taxonomy );
		$hide_empty      = $block_attributes['hideEmpty'] ?? true;
		$orderby         = $block_attributes['sortOrder'] ? explode( '-', $block_attributes['sortOrder'] )[0] : 'name';
		$order           = $block_attributes['sortOrder'] ? strtoupper( explode( '-', $block_attributes['sortOrder'] )[1] ) : 'DESC';

		$taxonomy_terms = $this->get_hierarchical_terms( $taxonomy, $taxonomy_counts, $hide_empty, $orderby, $order );

		if ( is_wp_error( $taxonomy_terms ) ) {
			return '';
		}

		// Get selected terms from filter params.
		$filter_params  = $block->context['filterParams'] ?? array();
		$selected_terms = array();
		$param_key      = $taxonomy_params[ $taxonomy ];

		if ( $filter_params && ! empty( $filter_params[ $param_key ] ) && is_string( $filter_params[ $param_key ] ) ) {
			$selected_terms = array_filter( array_map( 'sanitize_title', explode( ',', $filter_params[ $param_key ] ) ) );
		}

		$filter_context = array(
			'showCounts' => $block_attributes['showCounts'] ?? false,
			'items'      => array(),
			'groupLabel' => $taxonomy_object->labels->singular_name,
		);

		if ( ! empty( $taxonomy_counts ) ) {
			$taxonomy_options = array_map(
				function ( $term ) use ( $taxonomy_counts, $selected_terms, $taxonomy ) {
					$term          = (array) $term;
					$term['count'] = $taxonomy_counts[ $term['term_id'] ] ?? 0;

					return array(
						'label'    => $term['name'],
						'value'    => $term['slug'],
						'selected' => in_array( $term['slug'], $selected_terms, true ),
						'count'    => $term['count'],
						'type'     => 'taxonomy/' . $taxonomy,
					);
				},
				$taxonomy_terms
			);

			$filter_context['items'] = $taxonomy_options;
		}

		$wrapper_attributes = array(
			'data-wp-interactive' => 'woocommerce/product-filters',
			'data-wp-key'         => wp_unique_prefixed_id( $this->get_block_type() ),
			'data-wp-context'     => wp_json_encode(
				array(
					'activeLabelTemplate' => $taxonomy_object->labels->singular_name . ': {{label}}',
					'filterType'          => 'taxonomy/' . $taxonomy,
				),
				JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP
			),
		);

		if ( empty( $filter_context['items'] ) ) {
			$wrapper_attributes['hidden'] = true;
			$wrapper_attributes['class']  = 'wc-block-product-filter--hidden';
		}

		return sprintf(
			'<div %1$s>%2$s</div>',
			get_block_wrapper_attributes( $wrapper_attributes ),
			array_reduce(
				$block->parsed_block['innerBlocks'],
				function ( $carry, $parsed_block ) use ( $filter_context ) {
					$carry .= ( new \WP_Block( $parsed_block, array( 'filterData' => $filter_context ) ) )->render();
					return $carry;
				},
				''
			)
		);
	}

	/**
	 * Retrieve the taxonomy term counts for current block.
	 *
	 * @param WP_Block $block    Block instance.
	 * @param string   $taxonomy Taxonomy slug.
	 * @return array Term counts with term_id as key and count as value.
	 */
	private function get_taxonomy_term_counts( $block, $taxonomy ) {
		if ( ! isset( $block->context['filterParams'] ) ) {
			return array();
		}

		$query_vars = ProductCollectionUtils::get_query_vars( $block, 1 );

		// Remove current taxonomy from query vars to avoid circular counting.
		$container       = wc_get_container();
		$params_handler  = $container->get( \Automattic\WooCommerce\Internal\ProductFilters\Params::class );
		$taxonomy_params = $params_handler->get_param( 'taxonomy' );

		if ( isset( $taxonomy_params[ $taxonomy ] ) ) {
			$param_key = $taxonomy_params[ $taxonomy ];
			unset( $query_vars[ $param_key ] );
		}

		/**
		 * Prevent circular counting when calculating filter counts with active attribute filters.
		 * Removes product attribute taxonomy filters to ensure accurate cross-filter counting.
		 *
		 * @see https://github.com/woocommerce/woocommerce/pull/52759
		 */
		if ( isset( $query_vars['taxonomy'] ) && false !== strpos( $query_vars['taxonomy'], 'pa_' ) ) {
			unset(
				$query_vars['taxonomy'],
				$query_vars['term']
			);
		}

		// Remove from tax_query if present.
		if ( ! empty( $query_vars['tax_query'] ) ) {
			// phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_tax_query
			$query_vars['tax_query'] = ProductCollectionUtils::remove_query_array( $query_vars['tax_query'], 'taxonomy', $taxonomy );
		}

		$counts = $container->get( FilterDataProvider::class )->with( $container->get( QueryClauses::class ) )->get_taxonomy_counts( $query_vars, $taxonomy );

		return $counts;
	}

	/**
	 * Get product taxonomies for the block.
	 *
	 * @return array
	 */
	private function get_taxonomies() {
		$container       = wc_get_container();
		$params_handler  = $container->get( \Automattic\WooCommerce\Internal\ProductFilters\Params::class );
		$taxonomy_params = $params_handler->get_param( 'taxonomy' );
		$taxonomy_data   = array();

		foreach ( array_keys( $taxonomy_params ) as $taxonomy_slug ) {
			$taxonomy = get_taxonomy( $taxonomy_slug );

			if ( ! $taxonomy ) {
				continue;
			}

			$taxonomy_data[] = array(
				'label'  => $taxonomy->label,
				'name'   => $taxonomy->name,
				'labels' => array(
					'singular_name' => $taxonomy->labels->singular_name,
				),
			);
		}

		return $taxonomy_data;
	}

	/**
	 * Get taxonomy terms ordered hierarchically.
	 *
	 * @param string $taxonomy        Taxonomy slug.
	 * @param array  $taxonomy_counts Term counts with term_id as key.
	 * @param bool   $hide_empty      Whether to hide empty terms.
	 * @param string $orderby         Sort field for siblings (name, count, menu_order).
	 * @param string $order           Sort direction (ASC, DESC).
	 * @return array|\WP_Error Hierarchically ordered terms or error.
	 */
	private function get_hierarchical_terms( string $taxonomy, array $taxonomy_counts, bool $hide_empty, string $orderby, string $order ) {
		// For non-hierarchical taxonomies, use standard ordering
		if ( ! is_taxonomy_hierarchical( $taxonomy ) ) {
			$args = array(
				'taxonomy' => $taxonomy,
				'orderby'  => $orderby,
				'order'    => $order,
			);

			if ( $hide_empty ) {
				$args['include'] = array_keys( $taxonomy_counts );
			} else {
				$args['hide_empty'] = false;
			}

			return get_terms( $args );
		}

		// Get terms with WordPress native sorting for hierarchical taxonomy
		$args = array(
			'taxonomy'   => $taxonomy,
			'orderby'    => $orderby,
			'order'      => $order,
			'hide_empty' => false,
			'fields'     => 'all',
		);

		if ( $hide_empty ) {
			$args['include'] = array_keys( $taxonomy_counts );
		}

		$sorted_terms = get_terms( $args );

		if ( is_wp_error( $sorted_terms ) || empty( $sorted_terms ) ) {
			return $sorted_terms;
		}

		// Use TaxonomyHierarchyData for hierarchy operations
		$container      = wc_get_container();
		$hierarchy_data = $container->get( TaxonomyHierarchyData::class );

		// Build sibling order maps from WordPress sorted terms
		$terms_by_id    = array();
		$sibling_orders = array();

		foreach ( $sorted_terms as $term ) {
			$terms_by_id[ $term->term_id ] = $term;
			$parent_id                     = $hierarchy_data->get_parent( $term->term_id, $taxonomy );

			if ( ! isset( $sibling_orders[ $parent_id ] ) ) {
				$sibling_orders[ $parent_id ] = array();
			}
			$sibling_orders[ $parent_id ][] = $term->term_id;
		}

		// Get root terms in WordPress sort order
		$root_term_ids = $sibling_orders[0] ?? array();

		// Build depth-first hierarchical list using WordPress sibling order
		$hierarchical_terms = array();
		foreach ( $root_term_ids as $root_id ) {
			if ( isset( $terms_by_id[ $root_id ] ) ) {
				$this->add_term_and_descendants_wp_order(
					$root_id,
					$hierarchical_terms,
					$terms_by_id,
					$sibling_orders
				);
			}
		}

		return $hierarchical_terms;
	}

	/**
	 * Recursively add a term and its descendants in depth-first order using WordPress sibling ordering.
	 *
	 * @param int   $term_id           Current term ID to add.
	 * @param array &$hierarchical_terms Reference to the result array.
	 * @param array $terms_by_id       Terms indexed by ID for lookup.
	 * @param array $sibling_orders    WordPress-sorted sibling order maps by parent ID.
	 */
	private function add_term_and_descendants_wp_order(
		int $term_id,
		array &$hierarchical_terms,
		array $terms_by_id,
		array $sibling_orders
	): void {
		// Add current term first
		if ( isset( $terms_by_id[ $term_id ] ) ) {
			$hierarchical_terms[] = $terms_by_id[ $term_id ];
		}

		// Get children in WordPress sort order
		$child_ids = $sibling_orders[ $term_id ] ?? array();

		// Recursively add each child and its descendants
		foreach ( $child_ids as $child_id ) {
			$this->add_term_and_descendants_wp_order(
				$child_id,
				$hierarchical_terms,
				$terms_by_id,
				$sibling_orders
			);
		}
	}
}
