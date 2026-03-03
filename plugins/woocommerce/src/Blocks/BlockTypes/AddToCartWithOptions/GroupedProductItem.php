<?php
declare(strict_types=1);

namespace Automattic\WooCommerce\Blocks\BlockTypes\AddToCartWithOptions;

use Automattic\WooCommerce\Blocks\BlockTypes\AbstractBlock;
use Automattic\WooCommerce\Blocks\BlockTypes\EnableBlockJsonAssetsTrait;
use Automattic\WooCommerce\Blocks\BlockTypes\AddToCartWithOptions\Utils as AddToCartWithOptionsUtils;
use WP_Block;

/**
 * Block type for grouped product selector item in add to cart with options.
 * It's responsible to render each child product in a form of a list item.
 */
class GroupedProductItem extends AbstractBlock {

	use EnableBlockJsonAssetsTrait;

	/**
	 * Block name.
	 *
	 * @var string
	 */
	protected $block_name = 'add-to-cart-with-options-grouped-product-item';

	/**
	 * Modifies the block context for product price blocks when inside the Grouped Product Selector block.
	 *
	 * @param array $context The block context.
	 * @param array $block   The parsed block.
	 * @return array Modified block context.
	 */
	public function set_is_descendant_of_grouped_product_selector_context( $context, $block ) {
		if (
			'woocommerce/product-price' === $block['blockName'] ||
			'woocommerce/product-stock-indicator' === $block['blockName']
		) {
			$context['isDescendantOfGroupedProductSelector'] = true;
		}
		return $context;
	}

	/**
	 * Get product row HTML.
	 *
	 * @param string   $product_id Product ID.
	 * @param array    $attributes Block attributes.
	 * @param WP_Block $block The Block.
	 * @return string Row HTML
	 */
	private function get_product_row( $product_id, $attributes, $block ): string {
		global $post, $product;
		$previous_post    = $post;
		$previous_product = $product;

		// Since this template uses the core/post-title block to show the product name
		// a temporally replacement of the global post is needed. This is reverted back
		// to its initial post value that is stored in the $previous_post variable.
		$post    = get_post( $product_id ); // phpcs:ignore WordPress.WP.GlobalVariablesOverride.Prohibited
		$product = wc_get_product( $product_id );

		add_filter( 'render_block_context', array( $this, 'set_is_descendant_of_grouped_product_selector_context' ), 10, 2 );

		// Render the inner blocks of the Post Template block with `dynamic` set to `false` to prevent calling
		// `render_callback` and ensure that no wrapper markup is included.
		$block_content = AddToCartWithOptionsUtils::render_block_with_context(
			$block,
			array(
				'postType' => 'product',
				'postId'   => $post->ID,
			),
		);

		remove_filter( 'render_block_context', array( $this, 'set_is_descendant_of_grouped_product_selector_context' ) );

		$post    = $previous_post; // phpcs:ignore WordPress.WP.GlobalVariablesOverride.Prohibited
		$product = $previous_product;
		return $block_content;
	}

	/**
	 * Render the block.
	 *
	 * @param array    $attributes Block attributes.
	 * @param string   $content Block content.
	 * @param WP_Block $block Block instance.
	 * @return string Rendered block output.
	 */
	protected function render( $attributes, $content, $block ): string {
		global $product;

		if ( ! $product instanceof \WC_Product_Grouped ) {
			return '';
		}

		$content = '';

		$children = array_filter( array_map( 'wc_get_product', $product->get_children() ), 'wc_products_array_filter_visible_grouped' );

		foreach ( $children as $child ) {
			$content .= $this->get_product_row( $child->get_id(), $attributes, $block );
		}

		return $content;
	}
}
