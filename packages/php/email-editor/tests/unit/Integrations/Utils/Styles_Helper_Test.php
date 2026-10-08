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
	 * Test it tells a literal color from a palette slug.
	 */
	public function testItIdentifiesColorLiterals(): void {
		$this->assertTrue( Styles_Helper::is_color_literal( '#abcdef' ) );
		$this->assertTrue( Styles_Helper::is_color_literal( '#abc' ) );
		$this->assertTrue( Styles_Helper::is_color_literal( 'rgb(1, 2, 3)' ) );
		$this->assertTrue( Styles_Helper::is_color_literal( 'rgba(1, 2, 3, 0.5)' ) );
		$this->assertTrue( Styles_Helper::is_color_literal( 'hsl(1, 2%, 3%)' ) );
		$this->assertTrue( Styles_Helper::is_color_literal( 'var(--wp--preset--color--base)' ) );
		$this->assertTrue( Styles_Helper::is_color_literal( '  #abcdef  ' ) );

		// Any functional notation is a literal, including CSS Color 4/5 forms.
		$this->assertTrue( Styles_Helper::is_color_literal( 'oklch(0.7 0.1 200)' ) );
		$this->assertTrue( Styles_Helper::is_color_literal( 'lab(50% 40 59.5)' ) );
		$this->assertTrue( Styles_Helper::is_color_literal( 'lch(50% 40 30)' ) );
		$this->assertTrue( Styles_Helper::is_color_literal( 'hwb(90 10% 10%)' ) );
		$this->assertTrue( Styles_Helper::is_color_literal( 'color(display-p3 1 0 0)' ) );
		$this->assertTrue( Styles_Helper::is_color_literal( 'color-mix(in srgb, red, blue)' ) );

		// Keywords that are colors in their own right.
		$this->assertTrue( Styles_Helper::is_color_literal( 'transparent' ) );
		$this->assertTrue( Styles_Helper::is_color_literal( 'currentColor' ) );
		$this->assertTrue( Styles_Helper::is_color_literal( 'inherit' ) );

		// CSS named colors. Real emails use these as color slugs with no palette entry behind them,
		// and they rendered as valid CSS before the palette lookup started dropping unmatched slugs.
		$this->assertTrue( Styles_Helper::is_color_literal( 'blue' ) );
		$this->assertTrue( Styles_Helper::is_color_literal( 'gray' ) );
		$this->assertTrue( Styles_Helper::is_color_literal( 'green' ) );
		$this->assertTrue( Styles_Helper::is_color_literal( 'orange' ) );
		$this->assertTrue( Styles_Helper::is_color_literal( 'lightgray' ) );
		$this->assertTrue( Styles_Helper::is_color_literal( 'rebeccapurple' ) );
		$this->assertTrue( Styles_Helper::is_color_literal( 'RED' ) );

		// Compound CSS system colors.
		$this->assertTrue( Styles_Helper::is_color_literal( 'buttontext' ) );
		$this->assertTrue( Styles_Helper::is_color_literal( 'canvastext' ) );
		$this->assertTrue( Styles_Helper::is_color_literal( 'linktext' ) );
		$this->assertTrue( Styles_Helper::is_color_literal( 'accentcolor' ) );

		// The four single-word system colors stay out, because they are plausible palette slugs and
		// nothing in the editor can produce them as colors. See self::COLOR_KEYWORDS.
		$this->assertFalse( Styles_Helper::is_color_literal( 'canvas' ) );
		$this->assertFalse( Styles_Helper::is_color_literal( 'field' ) );
		$this->assertFalse( Styles_Helper::is_color_literal( 'mark' ) );
		$this->assertFalse( Styles_Helper::is_color_literal( 'highlight' ) );

		// An identifier that is not a color is a slug nothing defines.
		$this->assertFalse( Styles_Helper::is_color_literal( 'theme-4' ) );
		$this->assertFalse( Styles_Helper::is_color_literal( 'primary' ) );
		$this->assertFalse( Styles_Helper::is_color_literal( 'foreground' ) );
		$this->assertFalse( Styles_Helper::is_color_literal( 'dark-gray' ) );
		$this->assertFalse( Styles_Helper::is_color_literal( 'light-gray' ) );
		$this->assertFalse( Styles_Helper::is_color_literal( '' ) );
	}

	/**
	 * Test it resolves a slug against every palette origin, highest priority first.
	 */
	public function testItResolvesColorsFromEveryPaletteOrigin(): void {
		$settings = array(
			'color' => array(
				'palette' => array(
					'default' => array(
						array(
							'slug'  => 'core-slug',
							'color' => '#111111',
						),
						array(
							'slug'  => 'shared',
							'color' => '#aaaaaa',
						),
					),
					'theme'   => array(
						array(
							'slug'  => 'theme-slug',
							'color' => '#222222',
						),
						array(
							'slug'  => 'shared',
							'color' => '#bbbbbb',
						),
					),
					'blocks'  => array(
						array(
							'slug'  => 'block-slug',
							'color' => '#333333',
						),
					),
					'custom'  => array(
						array(
							'slug'  => 'user-slug',
							'color' => '#DDEEFF',
						),
						array(
							'slug'  => 'shared',
							'color' => '#cccccc',
						),
					),
				),
			),
		);

		// A color the user defined in the email's own global styles resolves, not just the theme's.
		$this->assertSame( '#ddeeff', Styles_Helper::resolve_color_from_palette( $settings, 'user-slug' ) );
		$this->assertSame( '#222222', Styles_Helper::resolve_color_from_palette( $settings, 'theme-slug' ) );
		$this->assertSame( '#333333', Styles_Helper::resolve_color_from_palette( $settings, 'block-slug' ) );
		$this->assertSame( '#111111', Styles_Helper::resolve_color_from_palette( $settings, 'core-slug' ) );

		// On a slug several origins define, the highest priority origin wins.
		$this->assertSame( '#cccccc', Styles_Helper::resolve_color_from_palette( $settings, 'shared' ) );

		// A slug no origin defines yields nothing, so the caller skips the declaration.
		$this->assertSame( '', Styles_Helper::resolve_color_from_palette( $settings, 'theme-4' ) );

		// A literal still passes through, and an absent palette is not a fatal.
		$this->assertSame( '#012345', Styles_Helper::resolve_color_from_palette( $settings, '#012345' ) );
		$this->assertSame( '', Styles_Helper::resolve_color_from_palette( array(), 'theme-4' ) );
		$this->assertSame( '#012345', Styles_Helper::resolve_color_from_palette( array(), '#012345' ) );

		// A CSS named color no palette defines renders as that color rather than being dropped.
		$this->assertSame( 'blue', Styles_Helper::resolve_color_from_palette( $settings, 'blue' ) );
	}

	/**
	 * Color slug values observed on real emails, and what each should resolve to.
	 *
	 * Seeded from a read-only sample of sent newsletters whose blocks name a color slug, which is
	 * what caught the CSS-named-color regression that syntax review did not. The point of the table
	 * is to keep answering "does this still match real data?" rather than "did we think of every
	 * CSS color syntax?" -- so append to it when a new value turns up in the wild, rather than
	 * reasoning about whether the matcher covers its shape.
	 *
	 * `null` means the value must resolve to nothing, so the caller omits the declaration and the
	 * block keeps the surrounding theme color.
	 *
	 * @return array<string, array{string, string|null}>
	 */
	public function observedColorSlugProvider(): array {
		return array(
			// Theme palette slugs left behind by a theme switch, or from a palette the email does
			// not carry. These are the values the fix exists for.
			'theme palette slug'          => array( 'theme-4', null ),
			'semantic slug'               => array( 'primary', null ),
			'semantic slug, foreground'   => array( 'foreground', null ),
			'hyphenated gray slug'        => array( 'dark-gray', null ),
			'hyphenated gray slug, light' => array( 'light-gray', null ),

			// Slugs that happen to be CSS color names, with no palette entry behind them. These
			// rendered as valid CSS before the fix, so dropping them is a visible regression.
			'named color, gray'           => array( 'gray', 'gray' ),
			'named color, blue'           => array( 'blue', 'blue' ),
			'named color, green'          => array( 'green', 'green' ),
			'named color, orange'         => array( 'orange', 'orange' ),

			// Literal colors, which reach these attributes too and must pass through untouched.
			'hex'                         => array( '#abcdef', '#abcdef' ),
			'rgb'                         => array( 'rgb(1, 2, 3)', 'rgb(1, 2, 3)' ),
			'preset variable'             => array( 'var(--wp--preset--color--base)', 'var(--wp--preset--color--base)' ),
		);
	}

	/**
	 * Test every observed slug value resolves the way it should against a palette that defines none of them.
	 *
	 * @dataProvider observedColorSlugProvider
	 *
	 * @param string      $value    Value of a color block attribute.
	 * @param string|null $expected Expected color, or null when nothing should be emitted.
	 */
	public function testItResolvesObservedColorSlugs( string $value, ?string $expected ): void {
		// A palette that defines none of the values above, standing in for an email whose theme has
		// changed since the post was written.
		$settings = array(
			'color' => array(
				'palette' => array(
					'theme' => array(
						array(
							'slug'  => 'base',
							'color' => '#ffffff',
						),
						array(
							'slug'  => 'contrast',
							'color' => '#000000',
						),
					),
				),
			),
		);

		$this->assertSame(
			null === $expected ? '' : $expected,
			Styles_Helper::resolve_color_from_palette( $settings, $value )
		);
	}

	/**
	 * Test a slug several origins define yields exactly one definition, the highest priority one.
	 *
	 * A duplicate is read two ways: a lookup takes the first entry, a stylesheet emits a rule per
	 * entry and the cascade takes the last. Returning one entry per slug is what stops a block's
	 * color depending on which route it arrives through.
	 */
	public function testItReturnsOneDefinitionPerSlug(): void {
		$settings = array(
			'color' => array(
				'palette' => array(
					'default' => array(
						array(
							'slug'  => 'shared',
							'color' => '#aaaaaa',
						),
						array(
							'slug'  => 'core-only',
							'color' => '#111111',
						),
					),
					'theme'   => array(
						array(
							'slug'  => 'shared',
							'color' => '#bbbbbb',
						),
					),
					'custom'  => array(
						array(
							'slug'  => 'shared',
							'color' => '#cccccc',
						),
					),
				),
			),
		);

		$definitions = Styles_Helper::palette_definitions( $settings );
		$slugs       = array_column( $definitions, 'slug' );

		$this->assertSame( array( 'shared', 'core-only' ), $slugs );
		$this->assertCount( 1, array_keys( $slugs, 'shared', true ) );

		// The one definition kept is the one the lookup resolves to, so both agree.
		$this->assertSame( '#cccccc', $definitions[0]['color'] );
		$this->assertSame( '#cccccc', Styles_Helper::resolve_color_from_palette( $settings, 'shared' ) );
	}

	/**
	 * Test a palette entry without a usable slug and color is dropped rather than reaching a caller.
	 *
	 * The palette arrives through a filter, and callers read `slug` and `color` directly.
	 */
	public function testItDropsUnusablePaletteEntries(): void {
		$settings = array(
			'color' => array(
				'palette' => array(
					'theme' => array(
						array( 'color' => '#aaaaaa' ),
						array(
							'slug'  => 123,
							'color' => '#bbbbbb',
						),
						array( 'slug' => 'no-color' ),
						array(
							'slug'  => 'empty-color',
							'color' => '   ',
						),
						array(
							'slug'  => 'array-color',
							'color' => array( '#dddddd' ),
						),
						'not-an-array',
						array(
							'slug'  => 'usable',
							'color' => '#cccccc',
						),
					),
				),
			),
		);

		$definitions = Styles_Helper::palette_definitions( $settings );

		$this->assertSame( array( 'usable' ), array_column( $definitions, 'slug' ) );
		$this->assertSame( '#cccccc', Styles_Helper::resolve_color_from_palette( $settings, 'usable' ) );
		$this->assertSame( '', Styles_Helper::resolve_color_from_palette( $settings, 'no-color' ) );
	}

	/**
	 * Test an incomplete higher-priority entry does not shut out an origin that defines the slug.
	 *
	 * Claiming the slug on the first entry that merely names it would discard the `default` color
	 * here, losing a color the palette does define.
	 */
	public function testItPrefersALowerPriorityOriginOverAColorlessDuplicate(): void {
		$settings = array(
			'color' => array(
				'palette' => array(
					'default' => array(
						array(
							'slug'  => 'shared',
							'color' => '#aaaaaa',
						),
					),
					'custom'  => array(
						array( 'slug' => 'shared' ),
					),
				),
			),
		);

		$definitions = Styles_Helper::palette_definitions( $settings );

		$this->assertCount( 1, $definitions );
		$this->assertSame( '#aaaaaa', $definitions[0]['color'] );
		$this->assertSame( '#aaaaaa', Styles_Helper::resolve_color_from_palette( $settings, 'shared' ) );
	}

	/**
	 * Test a palette entry wins over a CSS color of the same name.
	 *
	 * This is why treating a named color as a literal is safe: the lookup searches every origin
	 * first, so a theme that names a swatch `blue` still gets its own color.
	 */
	public function testItPrefersAPaletteEntryOverASameNamedCssColor(): void {
		$settings = array(
			'color' => array(
				'palette' => array(
					'theme' => array(
						array(
							'slug'  => 'blue',
							'color' => '#0000AA',
						),
					),
				),
			),
		);

		$this->assertSame( '#0000aa', Styles_Helper::resolve_color_from_palette( $settings, 'blue' ) );
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
	 * Test that a color slug the palette cannot resolve produces no declaration.
	 *
	 * A block written under another theme can name a slug this email's palette lacks. Emitting the
	 * slug as the value (background-color: theme-4) is invalid CSS that mail clients drop, so a
	 * button can lose its background and keep its text color. Omitting the declaration instead lets
	 * the surrounding theme color show through, which is what the editor already previews.
	 */
	public function testItOmitsUnresolvedColorSlugsFromNormalizedBlockStyles(): void {
		$block_attributes = array(
			'backgroundColor' => 'theme-4',
			'textColor'       => 'primary',
			'borderColor'     => 'theme-9',
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
					array( 'primary', '#00ff00' ),
					array( 'theme-4', '' ),
					array( 'theme-9', '' ),
				)
			);

		$result = Styles_Helper::get_normalized_block_styles( $block_attributes, $rendering_context );

		$expected = array(
			'color'   => array(
				'text' => '#00ff00',
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
