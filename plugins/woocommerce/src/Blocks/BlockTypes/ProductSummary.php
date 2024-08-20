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
	 * Overwrite parent method to prevent script registration.
	 *
	 * It is necessary to register and enqueues assets during the render
	 * phase because we want to load assets only if the block has the content.
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
	 * Get product description depends on config.
	 *
	 * @param WC_Product $product                   Product object.
	 * @param bool       $show_description_if_empty Defines if fallback to full description.
	 * @return string
	 */
	private function get_source( $product, $show_description_if_empty ) {
		$short_description = $product->get_short_description();

		if ( $short_description ) {
			return $short_description;
		}

		$description = $product->get_description();

		if ( $show_description_if_empty && $description ) {
			return $description;
		}

		return '';
	}

	/**
	 * Create anchor element based on input.
	 *
	 * @param WC_Product $product   Product object.
	 * @param string     $link_text Link text.
	 * @return string
	 */
	private function create_anchor( $product, $link_text ) {
		$href = esc_url( $product->get_permalink() );
		$text = wp_kses_post( $link_text );

		return '</br></br><a class="wp-block-woocommerce-product-summary__read_more" href="' . $href . '#tab-description">' . $text . '</a>';
	}

	/**
	 * Trim the product description.
	 *
	 * @param string $input  Product description.
	 * @param number $length Expected length of final description.
	 * @return string
	 */
	private function trim_keeping_html_formatting( $input, $length ) {

		return html_entity_decode( wp_trim_words( htmlentities( wpautop( $input, false ) ), $length ) );
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

		$post_id = isset( $block->context['postId'] ) ? $block->context['postId'] : '';
		$product = wc_get_product( $post_id );

		if ( ! $product ) {
			return '';
		}

		$show_description_if_empty = isset( $attributes['showDescriptionIfEmpty'] ) && $attributes['showDescriptionIfEmpty'];
		$source                    = $this->get_source( $product, $show_description_if_empty );

		if ( ! $source ) {
			return '';
		}

		$summary_length = isset( $attributes['summaryLength'] ) ? $attributes['summaryLength'] : false;
		$link_text      = isset( $attributes['linkText'] ) ? $attributes['linkText'] : '';
		$show_link      = isset( $attributes['showLink'] ) && $attributes['showLink'];
		$summary        = $summary_length ? $this->trim_keeping_html_formatting( $source, $summary_length ) : $source;
		$final_summary  = $show_link && $link_text ? $summary . $this->create_anchor( $product, $link_text ) : $summary;

		$styles_and_classes = StyleAttributesUtils::get_classes_and_styles_by_attributes( $attributes );

		return sprintf(
			'<div class="wp-block-woocommerce-product-summary"><div class="wc-block-components-product-summary %1$s" style="%2$s">
				%3$s
			</div></div>',
			esc_attr( $styles_and_classes['classes'] ),
			esc_attr( $styles_and_classes['styles'] ?? '' ),
			$final_summary
		);
	}
}
