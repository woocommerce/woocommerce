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

		// The content cell is the only table cell with an align attribute.
		$processor = new \WP_HTML_Tag_Processor( $rendered );
		$align     = null;
		while ( null === $align && $processor->next_tag( array( 'tag_name' => 'td' ) ) ) {
			$align = $processor->get_attribute( 'align' );
		}
		$this->assertSame( $expected, $align );
		$this->assertStringContainsString( '<div>Content</div>', $rendered );
	}
}
