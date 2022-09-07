<?php
namespace Automattic\WooCommerce\Blocks\BlockTypes;

/**
 * ProductQuery class.
 */
class ProductQuery extends AbstractBlock {
	/**
	 * Block name.
	 *
	 * @var string
	 */
	protected $block_name = 'product-query';

	/**
	 * The Block with its attributes before it gets rendered
	 *
	 * @var array
	 */
	protected $parsed_block;

	/**
	 * Initialize this block type.
	 *
	 * - Hook into WP lifecycle.
	 * - Register the block with WordPress.
	 * - Hook into pre_render_block to update the query.
	 */
	protected function initialize() {
		parent::initialize();
		add_filter(
			'pre_render_block',
			array( $this, 'update_query' ),
			10,
			2
		);

	}

	/**
	 * Remove the query block filter and parse the custom query
	 *
	 * This function is supposed to be called by the `query_loop_block_query_vars`
	 * filter. It de-registers the filter to make sure it runs only once and doesn't end
	 * up hi-jacking future Query Loop blocks.
	 *
	 * It needs unfortunately to be `public` or otherwise the filter can't call it.
	 *
	 * @param WP_Query $query The WordPress Query.
	 * @return array
	 */
	public function get_query_by_attributes_once( $query ) {
		remove_filter(
			'query_loop_block_query_vars',
			array( $this, 'get_query_by_attributes_once' ),
			10
		);

		return $this->get_query_by_attributes( $query, $this->parsed_block );
	}

	/**
	 * Update the query for the product query block.
	 *
	 * @param string|null $pre_render   The pre-rendered content. Default null.
	 * @param array       $parsed_block The block being rendered.
	 */
	public function update_query( $pre_render, $parsed_block ) {
		if ( 'core/query' !== $parsed_block['blockName'] || ! isset( $parsed_block['attrs']['__woocommerceVariationProps'] ) ) {
			return;
		}

		$this->parsed_block = $parsed_block;

		add_filter(
			'query_loop_block_query_vars',
			array( $this, 'get_query_by_attributes_once' ),
			10,
			1
		);
	}

	/**
	 * Return a custom query based on the attributes.
	 *
	 * @param WP_Query $query         The WordPress Query.
	 * @param WP_Block $parsed_block  The block being rendered.
	 * @return array
	 */
	public function get_query_by_attributes( $query, $parsed_block ) {
		if ( ! isset( $parsed_block['attrs']['__woocommerceVariationProps'] ) ) {
			return $query;
		}

		$variation_props     = $parsed_block['attrs']['__woocommerceVariationProps'];
		$common_query_values = array(
			'post_type'      => 'product',
			'post_status'    => 'publish',
			'posts_per_page' => $query['posts_per_page'],
			'orderby'        => $query['orderby'],
			'order'          => $query['order'],
		);
		$on_sale_query       = $this->get_on_sale_products_query( $variation_props );

		return array_merge( $query, $common_query_values, $on_sale_query );
	}

	/**
	 * Return a query for on sale products.
	 *
	 * @param array $variation_props Dedicated attributes for the variation.
	 * @return array
	 */
	private function get_on_sale_products_query( $variation_props ) {
		if ( ! isset( $variation_props['attributes']['query']['onSale'] ) || true !== $variation_props['attributes']['query']['onSale'] ) {
			return array();
		}

		return array(
			// Ignoring the warning of not using meta queries.
			// phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_query
			'meta_query' => array(
				'relation' => 'OR',
				array(
					'key'     => '_sale_price',
					'value'   => 0,
					'compare' => '>',
					'type'    => 'numeric',
				),
				array(
					'key'     => '_min_variation_sale_price',
					'value'   => 0,
					'compare' => '>',
					'type'    => 'numeric',
				),
			),
		);
	}
}
