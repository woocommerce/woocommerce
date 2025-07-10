<?php
declare( strict_types = 1 );

namespace Automattic\WooCommerce\Blocks\BlockTypes;

use Automattic\WooCommerce\Blocks\BlockTypes\ProductCollection\Utils as ProductCollectionUtils;
use Automattic\WooCommerce\Internal\ProductFilters\FilterDataProvider;
use Automattic\WooCommerce\Internal\ProductFilters\QueryClauses;

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
				$term_slugs                          = explode( ',', $params[ $param_key ] );
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
		if ( is_admin() || wp_doing_ajax() ) {
			return '';
		}

		// TODO: Implement full frontend rendering logic similar to ProductFilterAttribute.
		// This will include taxonomy term counts, interactive filtering, and proper HTML output.
		return '';
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
		$taxonomies    = get_taxonomies(
			array(
				'public'      => true,
				'object_type' => array( 'product' ),
			),
			'objects'
		);
		$taxonomy_data = array();

		foreach ( $taxonomies as $taxonomy ) {
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
}
