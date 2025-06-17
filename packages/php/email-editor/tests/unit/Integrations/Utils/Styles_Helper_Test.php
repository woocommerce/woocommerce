<?php
/**
 * This file is part of the WooCommerce Email Editor package
 *
 * @package Automattic\WooCommerce\EmailEditor
 */

declare(strict_types = 1);

namespace Automattic\WooCommerce\EmailEditor\Integrations\Utils;

/**
 * Unit test for Styles_Helper class.
 */
class Styles_Helper_Test extends \Email_Editor_Unit_Test {
	/**
	 * Test it parses float from string with default unit.
	 */
	public function testItParsesValueWithDefaultUnit(): void {
		$this->assertSame( 12.5, Styles_Helper::parse_value( '12.5px' ) );
		$this->assertSame( 100.0, Styles_Helper::parse_value( '100px' ) );
		$this->assertSame( 0.0, Styles_Helper::parse_value( '0px' ) );
	}

	/**
	 * Test it parses float from string with custom unit.
	 */
	public function testItParsesValueWithCustomUnit(): void {
		$this->assertSame( 1.25, Styles_Helper::parse_value( '1.25em' ) );
		$this->assertSame( 80.0, Styles_Helper::parse_value( '80%' ) );
	}

	/**
	 * Test it parses style string to associative array.
	 */
	public function testItParsesStylesToArray(): void {
		$input    = 'margin: 10px; padding: 5px; color: red;';
		$expected = array(
			'margin'  => '10px',
			'padding' => '5px',
			'color'   => 'red',
		);
		$this->assertSame( $expected, Styles_Helper::parse_styles_to_array( $input ) );
	}

	/**
	 * Test it ignores malformed styles.
	 */
	public function testItIgnoresMalformedStyles(): void {
		$input    = 'margin: 10px; broken-style color red; font-size: 12px;';
		$expected = array(
			'margin'    => '10px',
			'font-size' => '12px',
		);
		$this->assertSame( $expected, Styles_Helper::parse_styles_to_array( $input ) );
	}

	/**
	 * Test it trims whitespace in styles.
	 */
	public function testItTrimsWhitespace(): void {
		$input    = '  margin : 10px ; color :  blue ; ';
		$expected = array(
			'margin' => '10px',
			'color'  => 'blue',
		);
		$this->assertSame( $expected, Styles_Helper::parse_styles_to_array( $input ) );
	}
}
