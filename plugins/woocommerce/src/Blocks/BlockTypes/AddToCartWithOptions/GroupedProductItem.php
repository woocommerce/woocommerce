<?php
declare(strict_types=1);

namespace Automattic\WooCommerce\Blocks\BlockTypes\AddToCartWithOptions;

use Automattic\WooCommerce\Blocks\BlockTypes\AbstractBlock;
use Automattic\WooCommerce\Blocks\BlockTypes\EnableBlockJsonAssetsTrait;
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
	 * Product ID of the grouped-product child row currently being rendered.
	 *
	 * Stashed by `get_product_row()` before it renders a row, so that
	 * `set_is_descendant_of_grouped_product_selector_context()` can re-assert
	 * it (see that method's docblock). `null` while no row is being rendered.
	 *
	 * @var int|null
	 */
	private $current_row_product_id = null;

	/**
	 * Modifies the block context for blocks rendered inside a grouped
	 * product's row.
	 *
	 * WordPress applies the `render_block_context` filter to every nested
	 * inner block (see `WP_Block::render()`), running registered filters in
	 * priority order. `get_product_row()` registers this callback at the
	 * default priority (10) only for the duration of a single row's render,
	 * so it always runs after any earlier-priority filter that is also
	 * active for that render — notably `ProductTemplate::render()`'s
	 * priority-1 filter, which unconditionally pins `postId`/`postType` to
	 * the current Product Collection loop item for every block nested
	 * inside it. Without re-asserting the row's own product id here, that
	 * outer filter would win and every block in the row (title, price,
	 * quantity selector, etc.) would resolve the loop's product instead of
	 * this row's child product.
	 *
	 * @param array $context The block context.
	 * @param array $block   The parsed block.
	 * @return array Modified block context.
	 */
	public function set_is_descendant_of_grouped_product_selector_context( $context, $block ) {
		if ( null !== $this->current_row_product_id ) {
			$context['postId']   = $this->current_row_product_id;
			$context['postType'] = 'product';
		}

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

		$this->current_row_product_id = $post->ID;

		add_filter( 'render_block_context', array( $this, 'set_is_descendant_of_grouped_product_selector_context' ), 10, 2 );

		// Create new block with custom context.
		$new_block = new WP_Block(
			$block->parsed_block,
			array(
				'postType' => 'product',
				'postId'   => $post->ID,
			)
		);

		// Render with dynamic set to false to prevent calling render_callback.
		$block_content = $new_block->render( array( 'dynamic' => false ) );

		remove_filter( 'render_block_context', array( $this, 'set_is_descendant_of_grouped_product_selector_context' ) );

		$this->current_row_product_id = null;

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

		// No need to prime post caches here, children are already cached at this point.
		$children = array_filter( array_map( 'wc_get_product', $product->get_children() ), 'wc_products_array_filter_visible_grouped' );

		foreach ( $children as $child ) {
			$content .= $this->get_product_row( $child->get_id(), $attributes, $block );
		}

		return $content;
	}
}
