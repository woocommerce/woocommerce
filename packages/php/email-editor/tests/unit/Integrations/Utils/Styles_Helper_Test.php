<?php
/**
 * This file is part of the WooCommerce Email Editor package
 *
 * @package Automattic\WooCommerce\EmailEditor
 */

declare(strict_types = 1);

namespace Automattic\WooCommerce\EmailEditor\Integrations\Utils;

use Automattic\WooCommerce\EmailEditor\Engine\Renderer\ContentRenderer\Rendering_Context;

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
	 * Test it parses negative values.
	 */
	public function testItParsesNegativeValues(): void {
		$this->assertSame( -12.5, Styles_Helper::parse_value( '-12.5px' ) );
		$this->assertSame( -100.0, Styles_Helper::parse_value( '-100em' ) );
	}

	/**
	 * Test it handles invalid values.
	 */
	public function testItHandlesInvalidValues(): void {
		$this->assertSame( 0.0, Styles_Helper::parse_value( 'invalid' ) );
		$this->assertSame( 0.0, Styles_Helper::parse_value( '' ) );
		$this->assertSame( 0.0, Styles_Helper::parse_value( 'px' ) );
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

	/**
	 * Test it handles empty styles string.
	 */
	public function testItHandlesEmptyStylesString(): void {
		$this->assertSame( array(), Styles_Helper::parse_styles_to_array( '' ) );
		$this->assertSame( array(), Styles_Helper::parse_styles_to_array( '   ' ) );
	}

	/**
	 * Test it handles styles with colons in values.
	 */
	public function testItHandlesStylesWithColonsInValues(): void {
		$input    = 'background: url(http://example.com); color: red;';
		$expected = array(
			'background' => 'url(http://example.com)',
			'color'      => 'red',
		);
		$this->assertSame( $expected, Styles_Helper::parse_styles_to_array( $input ) );
	}

	/**
	 * Test it gets normalized block styles with color translations.
	 */
	public function testItGetsNormalizedBlockStylesWithColorTranslations(): void {
		$block_attributes = array(
			'backgroundColor' => 'primary',
			'textColor'       => 'secondary',
			'borderColor'     => 'accent',
			'style'           => array(
				'spacing' => array(
					'padding' => '10px',
				),
			),
		);

		/**
		 * Rendering_Context mock for using in test.
		 *
		 * @var Rendering_Context&\PHPUnit\Framework\MockObject\MockObject $rendering_context
		 */
		$rendering_context = $this->createMock( Rendering_Context::class );
		$rendering_context->method( 'translate_slug_to_color' )
			->willReturnMap(
				array(
					array( 'primary', '#ff0000' ),
					array( 'secondary', '#00ff00' ),
					array( 'accent', '#0000ff' ),
				)
			);

		$result = Styles_Helper::get_normalized_block_styles( $block_attributes, $rendering_context );

		$expected = array(
			'color'   => array(
				'background' => '#ff0000',
				'text'       => '#00ff00',
			),
			'border'  => array(
				'color' => '#0000ff',
			),
			'spacing' => array(
				'padding' => '10px',
			),
		);

		$this->assertSame( $expected, $result );
	}

	/**
	 * Test it gets normalized block styles without color attributes.
	 */
	public function testItGetsNormalizedBlockStylesWithoutColorAttributes(): void {
		$block_attributes = array(
			'style' => array(
				'spacing' => array(
					'padding' => '10px',
				),
			),
		);

		/**
		 * Rendering_Context mock for using in test.
		 *
		 * @var Rendering_Context&\PHPUnit\Framework\MockObject\MockObject $rendering_context
		 */
		$rendering_context = $this->createMock( Rendering_Context::class );

		$result = Styles_Helper::get_normalized_block_styles( $block_attributes, $rendering_context );

		$expected = array(
			'spacing' => array(
				'padding' => '10px',
			),
		);

		$this->assertSame( $expected, $result );
	}

	/**
	 * Test it gets normalized block styles with empty color values.
	 */
	public function testItGetsNormalizedBlockStylesWithEmptyColorValues(): void {
		$block_attributes = array(
			'backgroundColor' => '',
			'textColor'       => null,
			'borderColor'     => false,
			'style'           => array(
				'spacing' => array(
					'padding' => '10px',
				),
			),
		);

		/**
		 * Rendering_Context mock for using in test.
		 *
		 * @var Rendering_Context&\PHPUnit\Framework\MockObject\MockObject $rendering_context
		 */
		$rendering_context = $this->createMock( Rendering_Context::class );

		$result = Styles_Helper::get_normalized_block_styles( $block_attributes, $rendering_context );

		$expected = array(
			'spacing' => array(
				'padding' => '10px',
			),
		);

		$this->assertSame( $expected, $result );
	}

	/**
	 * Test it gets styles from block with default parameters.
	 */
	public function testItGetsStylesFromBlockWithDefaultParameters(): void {
		$block_styles = array(
			'spacing' => array(
				'padding' => '10px',
			),
		);

		$result = Styles_Helper::get_styles_from_block( $block_styles );

		$this->assertIsArray( $result['declarations'] );
		$this->assertIsString( $result['css'] );
		$this->assertIsString( $result['classnames'] );
	}

	/**
	 * Test it gets styles from empty block.
	 */
	public function testItGetsStylesFromEmptyBlock(): void {
		$result = Styles_Helper::get_styles_from_block( array() );

		$expected = Styles_Helper::$empty_block_styles;
		$this->assertSame( $expected, $result );
	}

	/**
	 * Test it extends block styles with CSS declarations.
	 */
	public function testItExtendsBlockStylesWithCssDeclarations(): void {
		$block_styles = array(
			'declarations' => array(
				'padding' => '10px',
			),
			'css'          => 'padding: 10px;',
			'classnames'   => 'test-class',
		);

		$css_declarations = array(
			'margin' => '20px',
			'color'  => 'red',
		);

		$result = Styles_Helper::extend_block_styles( $block_styles, $css_declarations );

		$expected_declarations = array(
			'padding' => '10px',
			'margin'  => '20px',
			'color'   => 'red',
		);

		$this->assertSame( $expected_declarations, $result['declarations'] );
		$this->assertStringContainsString( 'padding: 10px', $result['css'] );
		$this->assertStringContainsString( 'margin: 20px', $result['css'] );
		$this->assertStringContainsString( 'color: red', $result['css'] );
		$this->assertSame( 'test-class', $result['classnames'] );
	}

	/**
	 * Test it extends block styles with empty declarations.
	 */
	public function testItExtendsBlockStylesWithEmptyDeclarations(): void {
		$block_styles = array(
			'declarations' => array(
				'padding' => '10px',
			),
			'css'          => 'padding: 10px;',
			'classnames'   => 'test-class',
		);

		$result = Styles_Helper::extend_block_styles( $block_styles, array() );

		$this->assertSame( array( 'padding' => '10px' ), $result['declarations'] );
		$this->assertSame( 'padding: 10px;', $result['css'] );
		$this->assertSame( 'test-class', $result['classnames'] );
	}

	/**
	 * Test it extends block styles with invalid WP_Style_Engine structure.
	 */
	public function testItExtendsBlockStylesWithInvalidStructure(): void {
		$css_declarations = array(
			'margin' => '20px',
			'color'  => 'red',
		);

		$result = Styles_Helper::extend_block_styles( array( 'something' => 'else' ), $css_declarations );

		$this->assertSame( $css_declarations, $result['declarations'] );

		$result = Styles_Helper::extend_block_styles( array( 'declarations' => 'invalid-declarations' ), $css_declarations );

		$this->assertSame( $css_declarations, $result['declarations'] );
	}

	/**
	 * Test it gets block styles.
	 */
	public function testItGetsBlockStyles(): void {
		$block_attributes = array(
			'backgroundColor' => 'primary',
			'textAlign'       => 'center',
			'style'           => array(
				'spacing' => array(
					'padding' => '10px',
				),
			),
		);

		/**
		 * Rendering_Context mock for using in test.
		 *
		 * @var Rendering_Context&\PHPUnit\Framework\MockObject\MockObject $rendering_context
		 */
		$rendering_context = $this->createMock( Rendering_Context::class );
		$rendering_context->method( 'translate_slug_to_color' )
			->willReturn( '#ff0000' );

		$result = Styles_Helper::get_block_styles( $block_attributes, $rendering_context, array( 'spacing', 'background-color', 'text-align' ) );

		$this->assertArrayHasKey( 'css', $result );
		$this->assertArrayHasKey( 'declarations', $result );
		$this->assertArrayHasKey( 'classnames', $result );
		$this->assertIsString( $result['css'] );
		$this->assertIsArray( $result['declarations'] );
		$this->assertIsString( $result['classnames'] );
	}

	/**
	 * Test it gets block styles with empty properties.
	 */
	public function testItGetsBlockStylesWithEmptyProperties(): void {
		$block_attributes = array(
			'style' => array(
				'spacing' => array(
					'padding' => '10px',
				),
			),
		);

		/**
		 * Rendering_Context mock for using in test.
		 *
		 * @var Rendering_Context&\PHPUnit\Framework\MockObject\MockObject $rendering_context
		 */
		$rendering_context = $this->createMock( Rendering_Context::class );

		$result = Styles_Helper::get_block_styles( $block_attributes, $rendering_context, array() );

		$this->assertSame( Styles_Helper::$empty_block_styles, $result );
	}

	/**
	 * Test it gets block styles with unknown properties.
	 */
	public function testItGetsBlockStylesWithUnknownProperties(): void {
		$block_attributes = array(
			'style' => array(
				'spacing' => array(
					'padding' => '10px',
				),
			),
		);

		/**
		 * Rendering_Context mock for using in test.
		 *
		 * @var Rendering_Context&\PHPUnit\Framework\MockObject\MockObject $rendering_context
		 */
		$rendering_context = $this->createMock( Rendering_Context::class );

		$properties = array( 'unknown-property' );

		$result = Styles_Helper::get_block_styles( $block_attributes, $rendering_context, $properties );

		$this->assertSame( Styles_Helper::$empty_block_styles, $result );
	}
}
