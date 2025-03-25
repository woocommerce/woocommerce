<?php
namespace Automattic\WooCommerce\Blocks\BlockTypes;

use Automattic\WooCommerce\Blocks\Utils\StyleAttributesUtils;
use Automattic\WooCommerce\Blocks\Utils\ProductAvailabilityUtils;
use Automattic\WooCommerce\Enums\ProductType;

/**
 * ProductStockIndicator class.
 */
class ProductStockIndicator extends AbstractBlock {

	/**
	 * Block name.
	 *
	 * @var string
	 */
	protected $block_name = 'product-stock-indicator';

	/**
	 * API version name.
	 *
	 * @var string
	 */
	protected $api_version = '3';

	/**
	 * Register script and style assets for the block type before it is registered.
	 *
	 * This registers the scripts; it does not enqueue them.
	 */
	protected function register_block_type_assets() {
		return null;
	}

	/**
	 * Register the context.
	 */
	protected function get_block_type_uses_context() {
		return [ 'query', 'queryId', 'postId' ];
	}

	/**
	 * Get product types that should not display stock indicators.
	 *
	 * @return array
	 */
	protected function get_product_types_without_stock_indicator() {
		return array( ProductType::EXTERNAL, ProductType::GROUPED );
	}

	/**
	 * Extra data passed through from server to client for block.
	 *
	 * @param array $attributes  Any attributes that currently are available from the block.
	 *                           Note, this will be empty in the editor context when the block is
	 *                           not in the post content on editor load.
	 */
	protected function enqueue_data( array $attributes = [] ) {
		parent::enqueue_data( $attributes );

		$this->asset_data_registry->add( 'productTypesWithoutStockIndicator', $this->get_product_types_without_stock_indicator() );
	}

	/**
	 * Renders the stock indicator block.
	 *
	 * This method handles both direct product context and global product context,
	 * ensuring the stock indicator displays correctly in various template scenarios.
	 *
	 * @param array    $attributes Block attributes. Default empty array.
	 * @param string   $content    Block content. Default empty string.
	 * @param WP_Block $block      Block instance.
	 * @return string Rendered block type output.
	 */
	protected function render( $attributes, $content, $block ) {
		global $product;

		if ( ! empty( $content ) ) {
			parent::register_block_type_assets();
			$this->register_chunk_translations( [ $this->block_name ] );
			return $content;
		}
		$post_id           = isset( $block->context['postId'] ) ? $block->context['postId'] : '';
		$product_to_render = wc_get_product( $post_id );

		// Use the global product if the product to render can't be retrieved from the context.
		if ( ! $product_to_render instanceof WC_Product ) {
			$product_to_render = $product;
		}

		if ( ! $product_to_render || in_array( $product_to_render->get_type(), $this->get_product_types_without_stock_indicator(), true ) ) {
			return '';
		}

		$availability = ProductAvailabilityUtils::get_product_availability( $product_to_render );

		if ( empty( $availability['availability'] ) ) {
			return '';
		}

		$total_stock        = $product_to_render->get_stock_quantity();
		$classes_and_styles = StyleAttributesUtils::get_classes_and_styles_by_attributes( $attributes );

		$classnames  = isset( $classes_and_styles['classes'] ) ? ' ' . $classes_and_styles['classes'] . ' ' : '';
		$classnames .= sprintf( ' wc-block-components-product-stock-indicator--%s', $availability['class'] );

		$is_backorder_notification_visible = $product_to_render->is_in_stock() && $product_to_render->backorders_require_notification();

		if ( empty( $content ) && $is_backorder_notification_visible && $total_stock > 0 ) {
			$low_stock_text = sprintf(
				/* translators: %d is number of items in stock for product */
				__( '%d left in stock', 'woocommerce' ),
				$total_stock
			);
		}

		$output_text = $low_stock_text ?? $availability['availability'];

		$output  = '';
		$output .= '<div class="wc-block-components-product-stock-indicator wp-block-woocommerce-product-stock-indicator ' . esc_attr( $classnames ) . '"';
		$output .= isset( $classes_and_styles['styles'] ) ? ' style="' . esc_attr( $classes_and_styles['styles'] ) . '"' : '';
		$output .= '>';
		$output .= wp_kses_post( $output_text );
		$output .= '</div>';

		return $output;
	}
}
