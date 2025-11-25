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
use Automattic\WooCommerce\EmailEditor\Integrations\Utils\Styles_Helper;
use Automattic\WooCommerce\EmailEditor\Integrations\Utils\Table_Wrapper_Helper;

/**
 * Renders a WooCommerce coupon code block for email.
 */
class Coupon_Code extends Abstract_Block_Renderer {
	/**
	 * Render the coupon code block content for email.
	 *
	 * @param string            $block_content Block content.
	 * @param array             $parsed_block Parsed block.
	 * @param Rendering_Context $rendering_context Rendering context.
	 * @return string
	 */
	protected function render_content( string $block_content, array $parsed_block, Rendering_Context $rendering_context ): string {
		// $block_content is unused in this renderer but required by the parent interface.
		unset( $block_content );

		$attributes_raw  = $parsed_block['attrs'] ?? array();
		$attributes      = is_array( $attributes_raw ) ? $attributes_raw : array();
		$coupon_code_raw = $attributes['couponCode'] ?? '';
		$coupon_code     = is_string( $coupon_code_raw ) ? $coupon_code_raw : '';

		// Do not render anything if no coupon code is set.
		if ( empty( $coupon_code ) ) {
			return '';
		}

		$coupon_html = $this->build_coupon_html( $coupon_code, $attributes, $rendering_context );
		$result      = $this->apply_email_wrapper( $coupon_html, $parsed_block );

		return $result;
	}

	/**
	 * Build email-compatible coupon HTML.
	 *
	 * @param string            $coupon_code Coupon code text.
	 * @param array             $attributes Block attributes.
	 * @param Rendering_Context $rendering_context Rendering context.
	 * @return string
	 */
	private function build_coupon_html( string $coupon_code, array $attributes, Rendering_Context $rendering_context ): string {
		$default_styles = array(
			'font-size'      => '1.2em',
			'padding'        => '12px 20px',
			'display'        => 'inline-block',
			'border'         => '2px dashed #cccccc',
			'border-radius'  => '4px',
			'box-sizing'     => 'border-box',
			'color'          => '#000000',
			'background'     => '#f5f5f5',
			'text-align'     => 'center',
			'font-weight'    => 'bold',
			'letter-spacing' => '1px',
		);

		$custom_styles = Styles_Helper::get_block_styles(
			$attributes,
			$rendering_context,
			array( 'border', 'background-color', 'color', 'typography', 'spacing' )
		);

		$merged_styles = array_merge( $default_styles, $custom_styles['declarations'] ?? array() );
		$style_attr    = \WP_Style_Engine::compile_css( $merged_styles, '' );

		return sprintf(
			'<span class="woocommerce-coupon-code" style="%s">%s</span>',
			esc_attr( $style_attr ),
			esc_html( $coupon_code )
		);
	}

	/**
	 * Apply email-compatible table wrapper.
	 *
	 * @param string $coupon_html Coupon HTML.
	 * @param array  $parsed_block Parsed block.
	 * @return string
	 */
	private function apply_email_wrapper( string $coupon_html, array $parsed_block ): string {
		$align_raw = 'center';
		if (
			is_array( $parsed_block['attrs'] ?? null ) &&
			isset( $parsed_block['attrs']['align'] ) &&
			is_string( $parsed_block['attrs']['align'] )
		) {
			$align_raw = $parsed_block['attrs']['align'];
		}

		$align              = $align_raw;
		$allowed_alignments = array( 'left', 'center', 'right' );
		if ( ! in_array( $align, $allowed_alignments, true ) ) {
			$align = 'center';
		}

		$wrapper_styles = array(
			'border-collapse' => 'collapse',
			'width'           => '100%',
		);

		$cell_styles = array(
			'padding'    => '10px 0',
			'text-align' => $align,
		);

		$table_attrs = array(
			'style' => \WP_Style_Engine::compile_css( $wrapper_styles, '' ),
			'width' => '100%',
		);

		$cell_attrs = array(
			'class' => 'email-coupon-code-cell',
			'style' => \WP_Style_Engine::compile_css( $cell_styles, '' ),
			'align' => $align,
		);

		return Table_Wrapper_Helper::render_table_wrapper( $coupon_html, $table_attrs, $cell_attrs );
	}
}
