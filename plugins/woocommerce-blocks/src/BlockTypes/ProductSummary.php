<?php
namespace Automattic\WooCommerce\Blocks\BlockTypes;

use Automattic\WooCommerce\Blocks\Utils\StyleAttributesUtils;

/**
 * ProductSummary class.
 */
class ProductSummary extends AbstractBlock {

	/**
	 * Block name.
	 *
	 * @var string
	 */
	protected $block_name = 'product-summary';

	/**
	 * API version name.
	 *
	 * @var string
	 */
	protected $api_version = '2';


	/**
	 * Get block supports. Shared with the frontend.
	 * IMPORTANT: If you change anything here, make sure to update the JS file too.
	 *
	 * @return array
	 */
	protected function get_block_type_supports() {
		return array(
			'color'                  =>
			array(
				'link'       => true,
				'background' => false,
				'text'       => true,
			),
			'typography'             =>
			array(
				'fontSize' => true,
			),
			'__experimentalSelector' => '.wc-block-components-product-summary',
		);
	}

	/**
	 * Register script and style assets for the block type before it is registered.
	 *
	 * This registers the scripts; it does not enqueue them.
	 */
	protected function register_block_type_assets() {
		parent::register_block_type_assets();
		$this->register_chunk_translations( [ $this->block_name ] );
		return null;
	}

	/**
	 * Register the context.
	 */
	protected function get_block_type_uses_context() {
		return [ 'query', 'queryId', 'postId' ];
	}

	/**
	 * Include and render the block.
	 *
	 * @param array    $attributes Block attributes. Default empty array.
	 * @param string   $content    Block content. Default empty string.
	 * @param WP_Block $block      Block instance.
	 * @return string Rendered block type output.
	 */
	protected function render( $attributes, $content, $block ) {
		if ( ! empty( $content ) ) {
			parent::register_block_type_assets();
			$this->register_chunk_translations( [ $this->block_name ] );
			return $content;
		}

		$post_id         = $block->context['postId'];
		$product         = wc_get_product( $post_id );
		$summary_content = self::get_summary_content( $product );

		if ( ! $summary_content ) {
			return '';
		}

		$classes_and_styles = StyleAttributesUtils::get_classes_and_styles_by_attributes( $attributes );
		$classname          = isset( $attributes['className'] ) ? $attributes['className'] : '';

		$output = '<div class="wc-block-components-product-summary '
			. esc_attr( $classes_and_styles['classes'] ) . ' ' . esc_attr( $classname ) . '"';

		if ( isset( $classes_and_styles['styles'] ) ) {
			$output .= ' style="' . esc_attr( $classes_and_styles['styles'] ) . '"';
		}

		$output .= '>';
		$output .= '<p>' . wp_kses_post( $summary_content ) . '</p>';
		$output .= '</div>';

		return $output;
	}

	/**
	 * Returns summary text for a product.
	 *
	 * @param [WC_Product] $product Product to get summary text for.
	 * @return (string|null) Summary text for the product.
	 */
	protected function get_summary_content( $product ) {
		$short_description = $product->get_short_description();
		$summary_content   = $short_description ? $short_description : $product->get_description();

		if ( ! $summary_content ) {
			return null;
		}

		$summary = wc_trim_string( $summary_content, 150 );
		return $summary;
	}
}
