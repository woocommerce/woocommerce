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
 * Renders a WooCommerce product button block for email.
 */
class Product_Button extends Abstract_Product_Block_Renderer {
	/**
	 * Get styles for the wrapper element.
	 *
	 * @param array             $block_attributes Block attributes.
	 * @param Rendering_Context $rendering_context Rendering context.
	 * @return array
	 */
	private function get_wrapper_styles( array $block_attributes, Rendering_Context $rendering_context ): array {
		$block_styles = Styles_Helper::get_block_styles( $block_attributes, $rendering_context, array( 'border', 'background-color', 'color', 'typography', 'spacing' ) );

		return Styles_Helper::extend_block_styles(
			$block_styles,
			array(
				'word-break' => 'break-word',
				'display'    => 'block',
			)
		);
	}

	/**
	 * Get styles for the button link element.
	 *
	 * @param array             $block_attributes Block attributes.
	 * @param Rendering_Context $rendering_context Rendering context.
	 * @return array
	 */
	private function get_button_styles( array $block_attributes, Rendering_Context $rendering_context ): array {
		$block_styles = Styles_Helper::get_block_styles( $block_attributes, $rendering_context, array( 'color', 'typography' ) );

		return Styles_Helper::extend_block_styles(
			$block_styles,
			array(
				'display'         => 'block',
				'text-decoration' => 'none',
				'width'           => '100%',
			)
		);
	}

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

		$block_attributes = array_replace_recursive(
			array(
				'textColor'       => '#ffffff',
				'backgroundColor' => '#000000',
				'textAlign'       => 'left',
				'width'           => '',
				'style'           => array(
					'typography' => array(
						'fontSize'   => '16px',
						'fontWeight' => 'bold',
					),
					'border'     => array(
						'radius' => '0',
					),
					'spacing'    => array(
						'padding' => '12px 24px',
					),
				),
			),
			$parsed_block['attrs'] ?? array()
		);

		$wrapper_styles = $this->get_wrapper_styles( $block_attributes, $rendering_context );
		$button_styles  = $this->get_button_styles( $block_attributes, $rendering_context );

		$table_attrs = array(
			'style' => 'width:' . ( $block_attributes['width'] ? '100%' : 'auto' ) . ';',
			'align' => $block_attributes['textAlign'],
		);

		$cell_attrs = array(
			'class'  => $wrapper_styles['classnames'],
			'style'  => $wrapper_styles['css'],
			'align'  => $block_attributes['textAlign'],
			'valign' => 'middle',
			'role'   => 'presentation',
		);

		$button_content = sprintf(
			'<a class="product-button-link %1$s" style="%2$s" href="%3$s" target="_blank">%4$s</a>',
			esc_attr( $button_styles['classnames'] ),
			esc_attr( $button_styles['css'] ),
			esc_url( $button_url ),
			esc_html( $button_text )
		);

		$button_html = Table_Wrapper_Helper::render_table_wrapper( $button_content, $table_attrs, $cell_attrs );
		return Table_Wrapper_Helper::render_table_wrapper( $button_html, array( 'style' => 'width: 100%' ) );
	}
}
