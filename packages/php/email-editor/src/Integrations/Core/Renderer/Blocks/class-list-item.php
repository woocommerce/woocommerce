<?php
/**
 * This file is part of the WooCommerce Email Editor package
 *
 * @package Automattic\WooCommerce\EmailEditor
 */

declare( strict_types = 1 );
namespace Automattic\WooCommerce\EmailEditor\Integrations\Core\Renderer\Blocks;

use Automattic\WooCommerce\EmailEditor\Engine\Settings_Controller;

/**
 * Renders a list item block.
 */
class List_Item extends Abstract_Block_Renderer {
	/**
	 * Override this method to disable spacing (block gap) for list items.
	 *
	 * @param string $content Content.
	 * @param array  $email_attrs Email attributes.
	 */
	protected function add_spacer( $content, $email_attrs ): string {
		return $content;
	}

	/**
	 * Renders the block content.
	 *
	 * @param string              $block_content Block content.
	 * @param array               $parsed_block Parsed block.
	 * @param Settings_Controller $settings_controller Settings controller.
	 * @return string
	 */
	protected function render_content( $block_content, array $parsed_block, Settings_Controller $settings_controller ): string {
		return $block_content;
	}
}
