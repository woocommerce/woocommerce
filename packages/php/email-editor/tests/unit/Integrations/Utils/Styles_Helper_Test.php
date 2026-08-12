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
	 * Test it parses numeric values.
	 */
	public function testItParsesNumericValues(): void {
		$this->assertSame( 12.5, Styles_Helper::parse_value( 12.5 ) );
		$this->assertSame( 100.0, Styles_Helper::parse_value( 100 ) );
	}

	/**
	 * Test it parses negative numeric values.
	 */
	public function testItParsesNegativeNumericValues(): void {
		$this->assertSame( -12.5, Styles_Helper::parse_value( -12.5 ) );
		$this->assertSame( -100.0, Styles_Helper::parse_value( -100 ) );
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
	 * Test it can unset unsupported props using variable depth paths.
	 */
	public function testItUnsetsUnsupportedPropsWithVariableDepthPaths(): void {
		global $wp_filters, $__email_editor_last_wp_style_engine_get_styles_call;
		$wp_filters = array();
		$__email_editor_last_wp_style_engine_get_styles_call = null;

		add_filter(
			'woocommerce_email_editor_styles_unsupported_props',
			function ( $unsupported_props ) {
				$unsupported_props['padding-top'] = array( 'spacing', 'padding', 'top' );
				return $unsupported_props;
			}
		);

		$block_styles = array(
			'spacing' => array(
				'padding' => array(
					'top'    => '12px',
					'bottom' => '8px',
				),
				'margin'  => array(
					'top' => '10px',
				),
			),
		);

		Styles_Helper::get_styles_from_block( $block_styles );

		$this->assertIsArray( $__email_editor_last_wp_style_engine_get_styles_call );
		$this->assertArrayHasKey( 'block_styles', $__email_editor_last_wp_style_engine_get_styles_call );
		$passed_block_styles = $__email_editor_last_wp_style_engine_get_styles_call['block_styles'];

		// Default behavior: margin is removed.
		$this->assertArrayHasKey( 'spacing', $passed_block_styles );
		$this->assertArrayNotHasKey( 'margin', $passed_block_styles['spacing'] );

		// New behavior: deeper paths can be unset too.
		$this->assertArrayHasKey( 'padding', $passed_block_styles['spacing'] );
		$this->assertArrayNotHasKey( 'top', $passed_block_styles['spacing']['padding'] );
		$this->assertSame( '8px', $passed_block_styles['spacing']['padding']['bottom'] );
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

	/**
	 * Test it converts to px.
	 */
	public function testItConvertsToPx(): void {
		$this->assertSame( '16px', Styles_Helper::convert_to_px( '16px' ) );
		$this->assertSame( '16px', Styles_Helper::convert_to_px( '1rem' ) );
		$this->assertSame( '16px', Styles_Helper::convert_to_px( '1em' ) );
		$this->assertSame( '16px', Styles_Helper::convert_to_px( '100%' ) );
		$this->assertSame( '16px', Styles_Helper::convert_to_px( '100vh' ) );
		$this->assertSame( '20px', Styles_Helper::convert_to_px( '1rem', true, 20 ) ); // uses a different base font size.
		$this->assertSame( '25px', Styles_Helper::convert_to_px( '50%', true, 50 ) );
	}

	/**
	 * Test it converts to px with fallback.
	 */
	public function testItConvertsToPxWithFallback(): void {
		$this->assertSame( '16px', Styles_Helper::convert_to_px( '16new' ) );
		$this->assertSame( null, Styles_Helper::convert_to_px( '16new', false ) );
		$this->assertSame( '10px', Styles_Helper::convert_to_px( '1max', true, 10 ) );
		$this->assertSame( null, Styles_Helper::convert_to_px( '1vmin', false, 10 ) );
	}

	/**
	 * Test it converts clamp to static px.
	 */
	public function testItConvertsClampToStaticPx(): void {
		$this->assertSame( '16px', Styles_Helper::clamp_to_static_px( 'clamp(16px, 100%, 32px)' ) );
		$this->assertSame( '34px', Styles_Helper::clamp_to_static_px( 'clamp(2.15rem, 2.15rem + ((1vw - 0.2rem) * 1.333), 3rem)' ) );
		$this->assertSame( '32px', Styles_Helper::clamp_to_static_px( 'clamp(16px, 100%, 32px)', 'max' ) );
		$this->assertSame( '112px', Styles_Helper::clamp_to_static_px( 'clamp(2.15rem, 2.15rem + ((1vw - 0.2rem) * 1.333), max(1rem, 7rem))', 'max' ) );
		$this->assertSame( '24px', Styles_Helper::clamp_to_static_px( 'clamp(16px, 100%, 32px)', 'avg' ) );
		$this->assertSame( '22px', Styles_Helper::clamp_to_static_px( 'clamp(min(12px, 100%), 100%, max(24px, 32px))', 'avg' ) );
	}

	/**
	 * Test it returns original value if invalid clamp.
	 */
	public function testItReturnsOriginalValueIfInvalidClamp(): void {
		$this->assertSame( 'clamp (16px, 100%, 32px)', Styles_Helper::clamp_to_static_px( 'clamp (16px, 100%, 32px)' ) );
		$this->assertSame( 'clamp(2.15rem)', Styles_Helper::clamp_to_static_px( 'clamp(2.15rem)' ) );
	}

	/**
	 * Test it removes css unit.
	 */
	public function testItRemovesCssUnit(): void {
		$this->assertSame( '16', Styles_Helper::remove_css_unit( '16px' ) );
		$this->assertSame( '16', Styles_Helper::remove_css_unit( '16rem' ) );
		$this->assertSame( '16', Styles_Helper::remove_css_unit( '16em' ) );
		$this->assertSame( '100', Styles_Helper::remove_css_unit( '100%' ) );
		$this->assertSame( '100', Styles_Helper::remove_css_unit( '100vh' ) );
	}
}
