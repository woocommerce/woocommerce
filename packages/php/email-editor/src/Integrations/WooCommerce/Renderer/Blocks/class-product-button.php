<?php
/**
 * This file is part of the WooCommerce Email Editor package.
 *
 * @package Automattic\WooCommerce\EmailEditor
 */

declare( strict_types = 1 );
namespace Automattic\WooCommerce\EmailEditor\Integrations\WooCommerce\Renderer\Blocks;

use Automattic\WooCommerce\EmailEditor\Engine\Renderer\ContentRenderer\Rendering_Context;
use Automattic\WooCommerce\EmailEditor\Integrations\Core\Renderer\Blocks\Abstract_Block_Renderer;
use Automattic\WooCommerce\EmailEditor\Integrations\Utils\Table_Wrapper_Helper;

/**
 * Renders a WooCommerce product button block for email.
 */
class Product_Button extends Abstract_Block_Renderer {
	/**
	 * Render the product button block content for email.
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

		$button_text = $product->add_to_cart_text() ? $product->add_to_cart_text() : __( 'Add to cart', 'woocommerce' );

		if ( $product->is_type( 'external' ) && $product instanceof \WC_Product_External ) {
			$external_url = $product->get_product_url();
			$button_url   = $external_url ? $external_url : $product->get_permalink();
		} else {
			$button_url = $product->get_permalink();
		}

		$styles = array(
			'display'          => 'inline-block',
			'padding'          => '12px 24px',
			'background-color' => '#000000',
			'color'            => '#ffffff',
			'text-decoration'  => 'none',
			'font-weight'      => 'bold',
			'font-size'        => '16px',
			'border'           => 'none',
			'border-radius'    => '0',
			'text-align'       => 'center',
			'font-family'      => 'inherit',
		);

		$attributes = $parsed_block['attrs'] ?? array();
		if ( ! empty( $attributes['style']['typography']['fontSize'] ) ) {
			$styles['font-size'] = $attributes['style']['typography']['fontSize'];
		}
		if ( ! empty( $attributes['style']['typography']['fontWeight'] ) ) {
			$styles['font-weight'] = $attributes['style']['typography']['fontWeight'];
		}
		if ( ! empty( $attributes['style']['color']['background'] ) ) {
			$styles['background-color'] = $attributes['style']['color']['background'];
		}
		if ( ! empty( $attributes['style']['color']['text'] ) ) {
			$styles['color'] = $attributes['style']['color']['text'];
		}

		$button_styles = \WP_Style_Engine::compile_css( $styles, '' );

		$button_html = sprintf(
			'<a href="%s" style="%s">%s</a>',
			esc_url( $button_url ),
			esc_attr( $button_styles ),
			esc_html( $button_text )
		);

		$text_align = $parsed_block['attrs']['textAlign'] ?? 'left';

		$table_attrs = array(
			'style' => 'width: 100%; border-collapse: collapse;',
			'width' => '100%',
		);

		$cell_attrs = array(
			'style'  => 'text-align: ' . $text_align . '; vertical-align: top;',
			'align'  => $text_align,
			'valign' => 'top',
		);

		return Table_Wrapper_Helper::render_table_wrapper( $button_html, $table_attrs, $cell_attrs );
	}

	/**
	 * Get product from block context.
	 *
	 * @param array $parsed_block Parsed block.
	 * @return \WC_Product|null
	 */
	private function get_product_from_context( array $parsed_block ): ?\WC_Product {
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

		$product = $post_id ? wc_get_product( $post_id ) : null;
		return $product ? $product : null;
	}
}
