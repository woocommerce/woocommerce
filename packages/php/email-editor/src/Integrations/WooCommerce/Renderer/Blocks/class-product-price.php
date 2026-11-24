<?php
/**
 * This file is part of the WooCommerce Email Editor package.
 *
 * @package Automattic\WooCommerce\EmailEditor
 */

declare( strict_types = 1 );
namespace Automattic\WooCommerce\EmailEditor\Integrations\WooCommerce\Renderer\Blocks;

use Automattic\WooCommerce\EmailEditor\Engine\Renderer\ContentRenderer\Rendering_Context;
use Automattic\WooCommerce\EmailEditor\Integrations\Utils\Styles_Helper;
use Automattic\WooCommerce\EmailEditor\Integrations\Utils\Table_Wrapper_Helper;

/**
 * Renders a WooCommerce product price block for email.
 */
class Product_Price extends Abstract_Product_Block_Renderer {
	/**
	 * Render the product price block content for email.
	 *
	 * @param string            $block_content Block content.
	 * @param array             $parsed_block Parsed block.
	 * @param Rendering_Context $rendering_context Rendering context.
	 * @return string
	 */
	protected function render_content( string $block_content, array $parsed_block, Rendering_Context $rendering_context ): string {
		$product = $this->get_product_from_context( $parsed_block );
		if ( ! $product ) {
			return '';
		}

		$attributes = $parsed_block['attrs'] ?? array();

		$price_content = $this->generate_price_html( $product, $attributes, $rendering_context );

		return $this->apply_email_wrapper( $price_content, $parsed_block, $rendering_context );
	}

	/**
	 * Generate clean price HTML from product data.
	 *
	 * @param \WC_Product       $product Product object.
	 * @param array             $attributes Block attributes.
	 * @param Rendering_Context $rendering_context Rendering context.
	 * @return string
	 */
	private function generate_price_html( \WC_Product $product, array $attributes, Rendering_Context $rendering_context ): string {
		$price_html = $this->build_price_from_scratch( $product );

		if ( empty( $price_html ) ) {
			return '';
		}

		$price_styles = array(
			'display'         => 'block',
			'margin'          => '0',
			'padding'         => '0',
			'font-family'     => 'inherit',
			'color'           => 'inherit',
			'text-decoration' => 'none',
		);

		$custom_styles = Styles_Helper::get_block_styles( $attributes, $rendering_context, array( 'border', 'background-color', 'color', 'typography', 'spacing' ) );
		$price_styles  = array_merge( $price_styles, $custom_styles['declarations'] );

		$style_attr = \WP_Style_Engine::compile_css( $price_styles, '' );

		return sprintf(
			'<div class="wc-block-components-product-price" style="%s">%s</div>',
			esc_attr( $style_attr ),
			$price_html
		);
	}

	/**
	 * Build price HTML completely from scratch based on product type.
	 *
	 * @param \WC_Product $product Product object.
	 * @return string
	 */
	private function build_price_from_scratch( \WC_Product $product ): string {
		$product_type = $product->get_type();

		switch ( $product_type ) {
			case 'simple':
			case 'external':
				return $this->build_simple_product_price( $product );

			case 'variable':
				// When the product does not have a correct type, the default will be used.
				if ( $product instanceof \WC_Product_Variable ) {
					return $this->build_variable_product_price( $product );
				}
				return $this->build_simple_product_price( $product );

			case 'grouped':
				// When the product does not have a correct type, the default will be used.
				if ( $product instanceof \WC_Product_Grouped ) {
					return $this->build_grouped_product_price( $product );
				}
				return $this->build_simple_product_price( $product );

			default:
				return $this->build_simple_product_price( $product );
		}
	}

	/**
	 * Build price HTML for simple products.
	 *
	 * @param \WC_Product $product Product object.
	 * @return string
	 */
	private function build_simple_product_price( \WC_Product $product ): string {
		$regular_price = wc_get_price_to_display( $product, array( 'price' => (float) $product->get_regular_price() ) );
		$sale_price    = $product->get_sale_price() !== '' ? wc_get_price_to_display( $product, array( 'price' => (float) $product->get_sale_price() ) ) : '';

		if ( empty( $regular_price ) ) {
			return '';
		}

		if ( $product->is_on_sale() && '' !== $sale_price ) {
			return $this->format_sale_price( (float) $regular_price, (float) $sale_price );
		}
		return $this->format_price( (float) $regular_price );
	}

	/**
	 * Build price HTML for variable products.
	 * Uses the same logic as the editor: get_variation_price() methods.
	 *
	 * @param \WC_Product_Variable $product Variable product object.
	 * @return string
	 */
	private function build_variable_product_price( \WC_Product_Variable $product ): string {
		$min_price = $product->get_variation_price( 'min', true );
		$max_price = $product->get_variation_price( 'max', true );

		if ( '' === $min_price || false === $min_price ) {
			return '';
		}

		$min_regular_price = $product->get_variation_regular_price( 'min', true );
		$max_regular_price = $product->get_variation_regular_price( 'max', true );
		$one_price         = is_numeric( $max_price ) && (float) $min_price === (float) $max_price;
		$one_regular_price = is_numeric( $min_regular_price ) && is_numeric( $max_regular_price ) && (float) $min_regular_price === (float) $max_regular_price;
		if ( $product->is_on_sale() && $one_price && $one_regular_price ) {
			return $this->format_sale_price( (float) $min_regular_price, (float) $min_price );
		}

		return $this->format_price_range( (float) $min_price, (float) $max_price );
	}

	/**
	 * Build price HTML for grouped products.
	 *
	 * @param \WC_Product_Grouped $product Grouped product object.
	 * @return string
	 */
	private function build_grouped_product_price( \WC_Product_Grouped $product ): string {
		$prices = array();
		foreach ( $product->get_children() as $child_id ) {
			$child = wc_get_product( $child_id );
			if ( $child && 'publish' === $child->get_status() && $child->get_price() !== '' ) {
				$prices[] = wc_get_price_to_display( $child, array( 'price' => (float) $child->get_price() ) );
			}
		}

		if ( empty( $prices ) ) {
			return '';
		}

		return $this->format_price_range( (float) min( $prices ), (float) max( $prices ) );
	}

	/**
	 * Format a regular price struck through, followed by the sale price.
	 *
	 * @param float $regular_price Regular price.
	 * @param float $sale_price    Sale price.
	 * @return string
	 */
	private function format_sale_price( float $regular_price, float $sale_price ): string {
		return sprintf(
			'<del style="text-decoration: line-through; font-size: 0.9em; margin-right: 0.5em;">%s</del><span>%s</span>',
			wc_price( $regular_price, array( 'in_span' => false ) ),
			wc_price( $sale_price, array( 'in_span' => false ) )
		);
	}

	/**
	 * Format a single price.
	 *
	 * @param float $price Price.
	 * @return string
	 */
	private function format_price( float $price ): string {
		return sprintf( '<span>%s</span>', wc_price( $price, array( 'in_span' => false ) ) );
	}

	/**
	 * Format a price range, or a single price when both ends are equal, as the shop does.
	 *
	 * @param float $min_price Lowest price.
	 * @param float $max_price Highest price.
	 * @return string
	 */
	private function format_price_range( float $min_price, float $max_price ): string {
		if ( $min_price === $max_price ) {
			return $this->format_price( $min_price );
		}

		return sprintf(
			'<span>%s — %s</span>',
			wc_price( $min_price, array( 'in_span' => false ) ),
			wc_price( $max_price, array( 'in_span' => false ) )
		);
	}

	/**
	 * Apply email-compatible table wrapper.
	 *
	 * @param string            $price_html Price HTML.
	 * @param array             $parsed_block Parsed block.
	 * @param Rendering_Context $rendering_context Rendering context.
	 * @return string
	 */
	private function apply_email_wrapper( string $price_html, array $parsed_block, Rendering_Context $rendering_context ): string {
		$align = $rendering_context->resolve_text_align( $parsed_block['attrs']['textAlign'] ?? null );

		$wrapper_styles = array(
			'border-collapse' => 'collapse',
			'width'           => '100%',
		);

		$cell_styles = array(
			'padding'     => '5px 0',
			'text-align'  => $align,
			'font-family' => 'inherit',
		);

		$table_attrs = array(
			'style' => \WP_Style_Engine::compile_css( $wrapper_styles, '' ),
			'width' => '100%',
		);

		$cell_attrs = array(
			'class' => 'email-product-price-cell',
			'style' => \WP_Style_Engine::compile_css( $cell_styles, '' ),
			'align' => $align,
		);

		return Table_Wrapper_Helper::render_table_wrapper( $price_html, $table_attrs, $cell_attrs );
	}
}
