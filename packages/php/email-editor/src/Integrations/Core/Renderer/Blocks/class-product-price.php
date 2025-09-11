<?php
/**
 * This file is part of the WooCommerce Email Editor package.
 *
 * @package Automattic\WooCommerce\EmailEditor
 */

declare( strict_types = 1 );
namespace Automattic\WooCommerce\EmailEditor\Integrations\Core\Renderer\Blocks;

use Automattic\WooCommerce\EmailEditor\Engine\Renderer\ContentRenderer\Rendering_Context;
use Automattic\WooCommerce\EmailEditor\Integrations\Utils\Table_Wrapper_Helper;

/**
 * Renders a WooCommerce product price block for email.
 */
class Product_Price extends Abstract_Block_Renderer {
	/**
	 * Render the product price block content for email.
	 *
	 * @param string            $block_content Block content.
	 * @param array             $parsed_block Parsed block.
	 * @param Rendering_Context $rendering_context Rendering context.
	 * @return string
	 */
	protected function render_content( string $block_content, array $parsed_block, Rendering_Context $rendering_context ): string {
		$post_id = $parsed_block['context']['postId'] ?? 0;

		if ( ! $post_id ) {
			global $product;
			if ( $product && is_a( $product, 'WC_Product' ) ) {
				$post_id = $product->get_id();
			}
		}

		if ( ! $post_id ) {
			global $post;
			if ( $post && get_post_type( $post->ID ) === 'product' ) {
				$post_id = $post->ID;
			}
		}

		if ( ! $post_id ) {
			return '';
		}

		$product = wc_get_product( $post_id );
		if ( ! $product ) {
			return '';
		}

		$attributes = $parsed_block['attrs'] ?? array();

		$price_content = $this->generate_price_html( $product, $attributes );

		return $this->apply_email_wrapper( $price_content, $parsed_block );
	}

	/**
	 * Generate clean price HTML from product data.
	 *
	 * @param \WC_Product $product Product object.
	 * @param array       $attributes Block attributes.
	 * @return string
	 */
	private function generate_price_html( \WC_Product $product, array $attributes ): string {
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

		if ( ! empty( $attributes['style'] ) ) {
			$custom_styles = $this->parse_block_styles( $attributes['style'] );
			$price_styles  = array_merge( $price_styles, $custom_styles );
		}

		if ( ! empty( $attributes['textAlign'] ) ) {
			$price_styles['text-align'] = $attributes['textAlign'];
		}

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
		$regular_price = $product->get_regular_price();
		$sale_price    = $product->get_sale_price();

		if ( empty( $regular_price ) ) {
			return '';
		}

		if ( $product->is_on_sale() && ! empty( $sale_price ) ) {
			return sprintf(
				'<del style="text-decoration: line-through; font-size: 0.9em; margin-right: 0.5em;">%s</del><span>%s</span>',
				$this->format_price_simple( $regular_price ),
				$this->format_price_simple( $sale_price )
			);
		} else {
			return sprintf(
				'<span>%s</span>',
				$this->format_price_simple( $regular_price )
			);
		}
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

		return sprintf(
			'<span>%s</span>',
			$this->format_price_simple( $min_price )
		);
	}

	/**
	 * Build price HTML for grouped products.
	 *
	 * @param \WC_Product_Grouped $product Grouped product object.
	 * @return string
	 */
	private function build_grouped_product_price( \WC_Product_Grouped $product ): string {
		$children = $product->get_children();

		if ( empty( $children ) ) {
			return '';
		}

		$prices = array();
		foreach ( $children as $child_id ) {
			$child = wc_get_product( $child_id );
			if ( $child && $child->get_price() !== '' ) {
				$prices[] = $child->get_price();
			}
		}

		if ( empty( $prices ) ) {
			return '';
		}

		$min_price = min( $prices );

		return sprintf(
			'<span style="font-style: italic;">From </span><span>%s</span>',
			$this->format_price_simple( $min_price )
		);
	}

	/**
	 * Format a single price value for display.
	 *
	 * @param string|float $price Price value.
	 * @return string
	 */
	private function format_price_simple( $price ): string {
		$formatted = number_format_i18n( floatval( $price ), wc_get_price_decimals() );
		$currency  = get_woocommerce_currency_symbol();

		$currency_pos = get_option( 'woocommerce_currency_pos' );

		switch ( $currency_pos ) {
			case 'left':
				return $currency . $formatted;
			case 'right':
				return $formatted . '&nbsp;' . $currency;
			case 'left_space':
				return $currency . '&nbsp;' . $formatted;
			case 'right_space':
			default:
				return $formatted . '&nbsp;' . $currency;
		}
	}

	/**
	 * Parse block styles into CSS properties.
	 *
	 * @param array $style_block Style block from attributes.
	 * @return array
	 */
	private function parse_block_styles( array $style_block ): array {
		$styles = array();

		if ( ! empty( $style_block['color'] ) ) {
			$color = $style_block['color'];
			if ( ! empty( $color['text'] ) ) {
				$styles['color'] = $color['text'];
			}
			if ( ! empty( $color['background'] ) ) {
				$styles['background-color'] = $color['background'];
			}
		}

		if ( ! empty( $style_block['typography'] ) ) {
			$typography = $style_block['typography'];
			if ( ! empty( $typography['fontSize'] ) ) {
				$styles['font-size'] = $typography['fontSize'];
			}
			if ( ! empty( $typography['fontWeight'] ) ) {
				$styles['font-weight'] = $typography['fontWeight'];
			}
			if ( ! empty( $typography['lineHeight'] ) ) {
				$styles['line-height'] = $typography['lineHeight'];
			}
		}

		if ( ! empty( $style_block['spacing'] ) ) {
			$spacing = $style_block['spacing'];
			if ( ! empty( $spacing['padding'] ) ) {
				$padding = $spacing['padding'];
				if ( is_array( $padding ) ) {
					foreach ( array( 'top', 'right', 'bottom', 'left' ) as $side ) {
						if ( ! empty( $padding[ $side ] ) ) {
							$styles[ "padding-{$side}" ] = $padding[ $side ];
						}
					}
				} else {
					$styles['padding'] = $padding;
				}
			}
			if ( ! empty( $spacing['margin'] ) ) {
				$margin = $spacing['margin'];
				if ( is_array( $margin ) ) {
					foreach ( array( 'top', 'right', 'bottom', 'left' ) as $side ) {
						if ( ! empty( $margin[ $side ] ) ) {
							$styles[ "margin-{$side}" ] = $margin[ $side ];
						}
					}
				} else {
					$styles['margin'] = $margin;
				}
			}
		}

		return $styles;
	}

	/**
	 * Apply email-compatible table wrapper.
	 *
	 * @param string $price_html Price HTML.
	 * @param array  $parsed_block Parsed block.
	 * @return string
	 */
	private function apply_email_wrapper( string $price_html, array $parsed_block ): string {
		$align = $parsed_block['attrs']['textAlign'] ?? 'left';

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
