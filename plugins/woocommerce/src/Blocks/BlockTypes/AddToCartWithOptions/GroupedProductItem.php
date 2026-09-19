<?php
declare(strict_types=1);

namespace Automattic\WooCommerce\Blocks\BlockTypes\AddToCartWithOptions;

use Automattic\WooCommerce\Blocks\BlockTypes\AbstractBlock;
use Automattic\WooCommerce\Blocks\BlockTypes\EnableBlockJsonAssetsTrait;
use Automattic\WooCommerce\Blocks\SharedStores\ProductScopes;
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
	 * Modifies the block context for product price blocks when inside the Grouped Product Selector block, and
	 * forwards the enclosing form's name to every descendant so a child row rebuilt with a fresh block context
	 * (see get_product_row()) can still derive its own scope name from it.
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

		$context['formName'] = ProductScopes::get_current_form_name();

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

		$post    = $previous_post; // phpcs:ignore WordPress.WP.GlobalVariablesOverride.Prohibited
		$product = $previous_product;

		$scope_name = ProductScopes::get_grouped_child_scope_name( ProductScopes::get_current_form_name(), (int) $product_id );

		return $this->declare_row_scope( $block_content, (int) $product_id, $scope_name );
	}

	/**
	 * Declare the row's `woocommerce` context on its wrapper element, so
	 * Product Price, Stock Indicator and the child's own add to cart controls
	 * resolve `state.productScope.*` against this specific child.
	 *
	 * @param string $block_content The rendered row HTML.
	 * @param int    $product_id    The child row's product id.
	 * @param string $scope_name    The child row's scope name.
	 * @return string The row HTML with its `woocommerce` context declared on the wrapper element.
	 */
	private function declare_row_scope( string $block_content, int $product_id, string $scope_name ): string {
		$processor = new \WP_HTML_Tag_Processor( $block_content );

		if ( $processor->next_tag() ) {
			$processor->set_attribute(
				'data-wp-context',
				'woocommerce::' . wp_json_encode(
					ProductScopes::get_scope_context( $product_id, array(), $scope_name ),
					JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP
				)
			);
		}

		return $processor->get_updated_html();
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
