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
 * Renders a WooCommerce product sale badge block for email.
 */
class Product_Sale_Badge extends Abstract_Product_Block_Renderer {
	/**
	 * Render the product sale badge block content for email.
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

		if ( ! $product->is_on_sale() ) {
			return '';
		}

		$attributes = $parsed_block['attrs'] ?? array();

		/**
		 * Filters the product sale badge text.
		 *
		 * @hook woocommerce_sale_badge_text
		 * @since 10.0.0
		 *
		 * @param string $sale_text The sale badge text.
		 * @param \WC_Product $product The product object.
		 * @return string The filtered sale badge text.
		 */
		$sale_text = apply_filters( 'woocommerce_sale_badge_text', __( 'Sale', 'woocommerce' ), $product );

		$badge_html = $this->build_badge_html( $sale_text, $attributes, $rendering_context );
		return $this->apply_email_wrapper( $badge_html, $parsed_block );
	}

	/**
	 * Build email-compatible badge HTML.
	 *
	 * @param string            $sale_text Sale badge text.
	 * @param array             $attributes Block attributes.
	 * @param Rendering_Context $rendering_context Rendering context.
	 * @return string
	 */
	private function build_badge_html( string $sale_text, array $attributes, Rendering_Context $rendering_context ): string {
		$align = $attributes['align'] ?? 'left';

		$position_style = $this->get_position_style( $align );

		$badge_styles = array_merge(
			array(
				'font-size'      => '0.875em',
				'padding'        => '0.25em 0.75em',
				'display'        => 'inline-block',
				'width'          => 'fit-content',
				'border'         => '1px solid #43454b',
				'border-radius'  => '4px',
				'box-sizing'     => 'border-box',
				'color'          => '#43454b',
				'background'     => '#fff',
				'text-align'     => 'center',
				'text-transform' => 'uppercase',
				'font-weight'    => '600',
				'z-index'        => '9',
				'position'       => 'static',
			),
			$position_style
		);

		$custom_styles = Styles_Helper::get_block_styles(
			$attributes,
			$rendering_context,
			array( 'border', 'background-color', 'color', 'typography', 'spacing' )
		);

		$style_attr = \WP_Style_Engine::compile_css(
			array_merge( $badge_styles, $custom_styles['declarations'] ?? array() ),
			''
		);

		return sprintf(
			'<span class="wc-block-components-product-sale-badge__text" style="%s">%s</span>',
			esc_attr( $style_attr ),
			esc_html( $sale_text )
		);
	}

	/**
	 * Get positioning styles based on alignment.
	 *
	 * @param string $align Alignment value.
	 * @return array
	 */
	private function get_position_style( string $align ): array {
		switch ( $align ) {
			case 'left':
				return array(
					'text-align'   => 'left',
					'margin-right' => 'auto',
				);
			case 'center':
				return array(
					'text-align'   => 'center',
					'margin-left'  => 'auto',
					'margin-right' => 'auto',
				);
			case 'right':
				return array(
					'text-align'  => 'right',
					'margin-left' => 'auto',
				);
			default:
				return array(
					'text-align' => 'left',
				);
		}
	}

	/**
	 * Apply email-compatible table wrapper.
	 *
	 * @param string $badge_html Badge HTML.
	 * @param array  $parsed_block Parsed block.
	 * @return string
	 */
	private function apply_email_wrapper( string $badge_html, array $parsed_block ): string {
		$align = $parsed_block['attrs']['align'] ?? 'left';

		$wrapper_styles = array(
			'border-collapse' => 'collapse',
			'width'           => '100%',
		);

		$cell_styles = array(
			'padding'    => '5px 0',
			'text-align' => $align,
		);

		$table_attrs = array(
			'style' => \WP_Style_Engine::compile_css( $wrapper_styles, '' ),
			'width' => '100%',
		);

		$cell_attrs = array(
			'class' => 'email-product-sale-badge-cell',
			'style' => \WP_Style_Engine::compile_css( $cell_styles, '' ),
			'align' => $align,
		);

		return Table_Wrapper_Helper::render_table_wrapper( $badge_html, $table_attrs, $cell_attrs );
	}
}
