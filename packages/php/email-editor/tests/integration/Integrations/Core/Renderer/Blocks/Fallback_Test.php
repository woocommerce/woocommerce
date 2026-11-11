<?php
/**
 * This file is part of the WooCommerce Email Editor package
 *
 * @package Automattic\WooCommerce\EmailEditor
 */

declare(strict_types = 1);
namespace Automattic\WooCommerce\EmailEditor\Integrations\Core\Renderer\Blocks;

use Automattic\WooCommerce\EmailEditor\Engine\Renderer\ContentRenderer\Rendering_Context;

/**
 * Integration test for the Fallback renderer, used for blocks without an email renderer.
 */
class Fallback_Test extends \Email_Editor_Integration_Test_Case {
	/**
	 * Test the block is aligned by textAlign first, then align, then the email default.
	 *
	 * @testWith [{"textAlign": "right", "align": "center"}, "right"]
	 *           [{"textAlign": "justify", "align": "center"}, "center"]
	 *           [{"align": "center"}, "center"]
	 *           [{"align": "wide"}, "left"]
	 *           [{}, "left"]
	 *
	 * @param array  $attrs    Block attributes.
	 * @param string $expected Alignment in the email.
	 */
	public function testItAlignsTheBlockByTheFirstValidSetting( array $attrs, string $expected ): void {
		$rendered = ( new Fallback() )->render(
			'<div>Content</div>',
			array(
				'blockName' => 'test/block',
				'attrs'     => $attrs,
			),
			new Rendering_Context( new \WP_Theme_JSON() )
		);

		$this->assertMatchesRegularExpression( '/<td[^>]*align="' . $expected . '"[^>]*>\s*<div>Content<\/div>/', $rendered );
	}
}
