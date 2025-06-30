<?php

declare( strict_types = 1 );

namespace Automattic\WooCommerce\Internal\EmailEditor\Renderer\Blocks;

use Automattic\WooCommerce\Internal\EmailEditor\BlockEmailRenderer;

/**
 * Renders a list item block.
 */
class WooContent {
	/**
	 * Block name.
	 *
	 * @var string
	 */
	private $block_name = 'woo/email-content';

	/**
	 * Registers block with its callback.
	 *
	 * @return void
	 */
	public function register(): void {
		register_block_type(
			$this->block_name,
			array(
				'supports'              => array(
					'inserter' => false,
					'email'    => true,
				),
				'render_email_callback' => array( $this, 'render' ),
			)
		);
	}

	/**
	 * Renders Woo content placeholder to be replaced by content during sending.
	 *
	 * @return string
	 */
	public function render(): string {
		return BlockEmailRenderer::WOO_EMAIL_CONTENT_PLACEHOLDER;
	}
}
