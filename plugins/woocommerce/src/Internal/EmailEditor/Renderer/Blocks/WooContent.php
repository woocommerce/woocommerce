<?php

declare( strict_types = 1 );

namespace Automattic\WooCommerce\Internal\EmailEditor\Renderer\Blocks;

use Automattic\WooCommerce\EmailEditor\Engine\Settings_Controller;
use Automattic\WooCommerce\EmailEditor\Integrations\Core\Renderer\Blocks\Abstract_Block_Renderer;
use Automattic\WooCommerce\Internal\EmailEditor\BlockEmailRenderer;

/**
 * Renders a list item block.
 */
class WooContent extends Abstract_Block_Renderer {
	/**
	 * Renders Woo content placeholder to be replaced by content during sending.
	 *
	 * @param string              $block_content Block content.
	 * @param array               $parsed_block Parsed block.
	 * @param Settings_Controller $settings_controller Settings controller.
	 * @return string
	 */
	protected function render_content( $block_content, array $parsed_block, Settings_Controller $settings_controller ): string {
		return BlockEmailRenderer::WOO_EMAIL_CONTENT_PLACEHOLDER;
	}
}
