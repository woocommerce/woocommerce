<?php
namespace Automattic\WooCommerce\Blocks\BlockTypes;

/**
 * RelatedProducts class.
 */
class RelatedProducts extends AbstractBlock {
	/**
	 * Block name.
	 *
	 * @var string
	 */
	protected $block_name = 'related-products';

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
	 * It isn't necessary register block assets because it is a server side block.
	 */
	protected function register_block_type_assets() {
		return null;
	}

	/**
	 * Update the query for the product query block.
	 *
	 * @param string|null $pre_render   The pre-rendered content. Default null.
	 * @param array       $parsed_block The block being rendered.
	 */
	public function update_query( $pre_render, $parsed_block ) {
		if ( 'core/query' !== $parsed_block['blockName'] ) {
			return;
		}

		$this->parsed_block = $parsed_block;

		if ( ProductQuery::is_woocommerce_variation( $parsed_block ) && 'woocommerce/related-products' === $parsed_block['attrs']['namespace'] ) {
			// Set this so that our product filters can detect if it's a PHP template.
			add_filter(
				'query_loop_block_query_vars',
				array( $this, 'build_query' ),
				10,
				1
			);
		}
	}

	/**
	 * Return a custom query based on attributes, filters and global WP_Query.
	 *
	 * @param WP_Query $query The WordPress Query.
	 * @return array
	 */
	public function build_query( $query ) {
		$parsed_block = $this->parsed_block;
		if ( ! ProductQuery::is_woocommerce_variation( $parsed_block ) && 'woocommerce/related-products' !== $parsed_block['attrs']['namespace'] ) {
			return $query;
		}
		$product          = wc_get_product();
		$related_products = wc_get_related_products( $product->get_id() );

		return array(
			'post_type'   => 'product',
			'post__in'    => $related_products,
			'post_status' => 'publish',
		);
	}
}
