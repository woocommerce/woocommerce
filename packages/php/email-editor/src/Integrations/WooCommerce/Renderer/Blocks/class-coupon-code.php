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
 * Renders the woocommerce/coupon-code block for email.
 *
 * For "existing" source, the block content passes through unchanged.
 * For "createNew" source, the placeholder (XXXX-XXXXXX-XXXX) is replaced
 * with a generated coupon code via the woocommerce_coupon_code_block_auto_generate filter.
 */
class Coupon_Code extends Abstract_Block_Renderer {

	const COUPON_CODE_PLACEHOLDER = 'XXXX-XXXXXX-XXXX';

	/**
	 * Render the coupon code block content for email.
	 *
	 * @param string            $block_content Block content from the standard WP render.
	 * @param array             $parsed_block Parsed block data.
	 * @param Rendering_Context $rendering_context Rendering context with email-specific data.
	 * @return string
	 */
	protected function render_content( string $block_content, array $parsed_block, Rendering_Context $rendering_context ): string {
		$attrs  = $parsed_block['attrs'] ?? array();
		$source = $attrs['source'] ?? 'createNew';

		if ( 'createNew' === $source ) {
			/**
			 * Filters the auto-generated coupon code for the coupon-code block.
			 *
			 * Integrators (MailPoet, WooCommerce, third-party plugins) hook into this filter
			 * to generate a WooCommerce coupon at send time and return its code.
			 *
			 * @hook woocommerce_coupon_code_block_auto_generate
			 * @since 10.6.0
			 *
			 * @param string            $coupon_code       The coupon code. Empty by default.
			 * @param array             $attrs             Block attributes (discountType, amount, expiryDay, etc.).
			 * @param Rendering_Context $rendering_context The rendering context with email-specific data
			 *                                             (recipient email, user ID, etc.).
			 * @return string The generated coupon code. Return empty string to suppress the block output.
			 */
			$coupon_code = apply_filters(
				'woocommerce_coupon_code_block_auto_generate',
				'',
				$attrs,
				$rendering_context
			);

			if ( empty( $coupon_code ) ) {
				return '';
			}

			$block_content = str_replace(
				self::COUPON_CODE_PLACEHOLDER,
				esc_html( $coupon_code ),
				$block_content
			);
		}

		$align = $attrs['align'] ?? 'center';
		if ( ! in_array( $align, array( 'left', 'center', 'right' ), true ) ) {
			$align = 'center';
		}

		$table_attrs = array(
			'style' => 'border-collapse: separate;',
			'width' => '100%',
		);

		$cell_attrs = array(
			'align' => $align,
			'style' => \WP_Style_Engine::compile_css(
				array(
					'text-align' => $align,
				),
				''
			),
		);

		return Table_Wrapper_Helper::render_table_wrapper( $block_content, $table_attrs, $cell_attrs );
	}
}
