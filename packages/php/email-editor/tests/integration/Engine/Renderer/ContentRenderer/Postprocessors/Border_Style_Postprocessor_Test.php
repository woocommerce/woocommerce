<?php
/**
 * This file is part of the WooCommerce Email Editor package
 *
 * @package Automattic\WooCommerce\EmailEditor
 */

declare(strict_types=1);

namespace Automattic\WooCommerce\EmailEditor\Tests\Integration\Engine\Renderer\ContentRenderer\Postprocessors;

use PHPUnit\Framework\TestCase;
use Automattic\WooCommerce\EmailEditor\Engine\Renderer\ContentRenderer\Postprocessors\Border_Style_Postprocessor;

/**
 * Tests for Border_Style_Postprocessor.
 *
 * @package Automattic\WooCommerce\EmailEditor
 */
class Border_Style_Postprocessor_Test extends TestCase {
	/**
	 * The postprocessor instance.
	 *
	 * @var Border_Style_Postprocessor
	 */
	protected Border_Style_Postprocessor $postprocessor;

	/**
	 * Set up the test environment.
	 *
	 * @return void
	 */
	public function setUp(): void {
		parent::setUp();
		$this->postprocessor = new Border_Style_Postprocessor();
	}

	/**
	 * Test border style processing for various cases.
	 *
	 * @dataProvider borderStyleCases
	 *
	 * @param string $input    The input HTML string.
	 * @param string $expected The expected style string after processing.
	 *
	 * @return void
	 */
	public function test_border_style_processing( string $input, string $expected ) {
		$result = $this->postprocessor->postprocess( $input );

		$this->assertSame( $expected, $this->extract_style( $result ) );
	}

	/**
	 * Data provider for border style processing cases.
	 *
	 * @return array[]
	 */
	public function borderStyleCases() {
		return array(
			// No border, no style.
			array( '<div style="color: red;"></div>', 'color: red;' ),

			// Border-width > 0, no style: should add border-style: solid.
			array( '<div style="border-width: 2px;"></div>', 'border-width: 2px; border-style: solid;' ),

			// Border-width = 0, border-style present: should remove border-style.
			array( '<div style="border-width: 0; border-style: dashed;"></div>', 'border-width: 0;' ),

			// Shorthand border with width > 0, no style: should add border-style: solid.
			array( '<div style="border: 2px red;"></div>', 'border: 2px red; border-style: solid;' ),

			// Shorthand border with width = 0, style present: no need to add border-style.
			array( '<div style="border: 0 dashed red;"></div>', 'border: 0 dashed red;' ),

			// Longhand border-top-width > 0, no style: should add border-top-style: solid.
			array( '<div style="border-top-width: 3px;"></div>', 'border-top-width: 3px; border-top-style: solid;' ),

			// Longhand border-top-width = 0, style present: should remove border-top-style.
			array( '<div style="border-top-width: 0; border-top-style: dotted;"></div>', 'border-top-width: 0;' ),

			// Existing border-style is kept if width > 0.
			array( '<div style="border-top-width: 2px; border-top-style: dashed;"></div>', 'border-top-width: 2px; border-top-style: dashed;' ),

			// All sides, all widths > 0, all styles same: should use shorthand.
			array( '<div style="border-width: 1px 1px 1px 1px; border-style: dotted;"></div>', 'border-width: 1px 1px 1px 1px; border-style: dotted;' ),

			// All sides, mixed widths, mixed styles: should use longhand.
			array( '<div style="border-top-width: 2px; border-top-style: dashed; border-right-width: 0; border-bottom-width: 2px; border-bottom-style: solid; border-left-width: 2px;"></div>', 'border-top-width: 2px; border-right-width: 0; border-bottom-width: 2px; border-left-width: 2px; border-top-style: dashed; border-bottom-style: solid; border-left-style: solid;' ),

			// Border shorthand with style and width > 0.
			array( '<div style="border: 2px solid red;"></div>', 'border: 2px solid red;' ),

			// Border shorthand with style and width = 0.
			array( '<div style="border: 0 solid red;"></div>', 'border: 0 solid red;' ),

			// Border shorthand with only color.
			array( '<div style="border: red;"></div>', 'border: red;' ),

			// Border shorthand with only style.
			array( '<div style="border: dashed;"></div>', 'border: dashed;' ),

			// Border shorthand with only width.
			array( '<div style="border: 2px;"></div>', 'border: 2px; border-style: solid;' ),

			// Border shorthand with width = 0 and no style.
			array( '<div style="border: 0;"></div>', 'border: 0;' ),
		);
	}

	/**
	 * Extracts the style attribute value from the given HTML string.
	 *
	 * @param string $html The HTML string.
	 * @return string The extracted style attribute value, or an empty string if not found.
	 */
	private function extract_style( string $html ): string {
		preg_match( '/style="([^"]*)"/', $html, $matches );
		return isset( $matches[1] ) ? trim( $matches[1] ) : '';
	}
}
