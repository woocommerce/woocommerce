<?php
declare( strict_types = 1 );

namespace Automattic\WooCommerce\Tests\Blocks\Utils;

use Automattic\WooCommerce\Blocks\Utils\BlockIconUtils;
use Automattic\WooCommerce\Blocks\Utils\MiniCartUtils;
use WC_Unit_Test_Case;

/**
 * Tests for the BlockIconUtils class.
 */
class BlockIconUtilsTest extends WC_Unit_Test_Case {
	private const DEFAULT_SVG = '<svg class="required-icon another-required" viewBox="0 0 24 24"><path d="M1 1h22v22H1z" fill="currentColor"/></svg>';

	private const STATIC_SVG_FIXTURE = '<svg class="fixture-icon" xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" preserveAspectRatio="xMidYMid meet" fill="none" stroke="currentColor" fill-opacity="0.9" stroke-opacity="0.8" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="bevel" stroke-miterlimit="4" opacity="0.95" role="presentation"><g class="fixture-group" fill="red" stroke="#abc" opacity="0.75" transform="translate(1 1)"><rect class="fixture-rect" fill="none" x="1" y="1" width="4" height="3" rx="1" ry="1"/><circle class="fixture-circle" fill="none" cx="9" cy="3" r="2"/><ellipse class="fixture-ellipse" fill="none" cx="15" cy="3" rx="3" ry="2"/><line class="fixture-line" fill="none" x1="1" y1="8" x2="7" y2="8"/><polyline class="fixture-polyline" fill="none" points="9,9 11,7 13,9"/><polygon class="fixture-polygon" fill="none" points="15,9 17,7 19,9"/><path class="fixture-path" d="M2 12h18v8H2z" fill="#aabbccdd" fill-rule="evenodd" clip-rule="evenodd" stroke="rgba(1 2 3 / 50%)" stroke-width="2" stroke-linecap="square" stroke-linejoin="round" stroke-miterlimit="3" opacity="0.6" transform="rotate(1)"/></g></svg>';

	/**
	 * @testdox The exact default SVG is returned when no filter is registered.
	 */
	public function test_filter_block_icon_keeps_exact_default_without_filter(): void {
		$result = BlockIconUtils::filter_block_icon(
			self::DEFAULT_SVG,
			'line',
			'woocommerce/customer-account',
			array( 'iconStyle' => 'line' )
		);

		$this->assertSame( self::DEFAULT_SVG, $result );
	}

	/**
	 * @testdox The filter receives exactly four context arguments and an exact no-op preserves bytes.
	 */
	public function test_filter_block_icon_passes_exact_hook_arguments_and_keeps_no_op_bytes(): void {
		$attributes    = array(
			'iconStyle' => 'line',
			'customKey' => 'value',
		);
		$call_count    = 0;
		$received_args = null;
		add_filter(
			'woocommerce_blocks_icon_svg',
			static function ( ...$args ) use ( &$call_count, &$received_args ) {
				++$call_count;
				$received_args = $args;

				return $args[0];
			},
			10,
			99
		);

		$result = BlockIconUtils::filter_block_icon(
			self::DEFAULT_SVG,
			'line',
			'woocommerce/customer-account',
			$attributes
		);

		$this->assertSame( 1, $call_count );
		$this->assertCount( 4, $received_args );
		$this->assertSame(
			array( self::DEFAULT_SVG, 'line', 'woocommerce/customer-account', $attributes ),
			$received_args
		);
		$this->assertSame( self::DEFAULT_SVG, $result );
	}

	/**
	 * @testdox Non-string filter returns fall back to the exact default.
	 * @dataProvider provide_non_string_replacements
	 *
	 * @param mixed $replacement Filter replacement.
	 */
	public function test_filter_block_icon_rejects_non_string_replacement( $replacement ): void {
		$this->assertSame( self::DEFAULT_SVG, $this->filter_with_replacement( $replacement ) );
	}

	/**
	 * Provides invalid non-string icon replacements.
	 *
	 * @return array<string, array{mixed}>
	 */
	public function provide_non_string_replacements(): array {
		return array(
			'false'   => array( false ),
			'null'    => array( null ),
			'integer' => array( 42 ),
			'float'   => array( 4.2 ),
			'array'   => array( array() ),
			'object'  => array( new \stdClass() ),
		);
	}

	/**
	 * @testdox A representative static SVG vocabulary survives with caller invariants.
	 */
	public function test_filter_block_icon_accepts_representative_static_svg(): void {
		$result = $this->filter_with_replacement( self::STATIC_SVG_FIXTURE );

		$this->assert_root_invariants( $result, array( 'fixture-icon', 'required-icon', 'another-required' ) );

		$expected = new \WP_HTML_Tag_Processor( self::STATIC_SVG_FIXTURE );
		$actual   = new \WP_HTML_Tag_Processor( $result );
		while ( $expected->next_tag() ) {
			$this->assertTrue( $actual->next_tag(), 'Every fixture element should survive.' );
			$tag = $expected->get_tag();
			$this->assertSame( $tag, $actual->get_tag() );

			foreach ( $expected->get_attribute_names_with_prefix( '' ) as $name ) {
				// The root class list gains the caller's classes; assert_root_invariants() checks it.
				if ( 'SVG' === $tag && 'class' === $name ) {
					continue;
				}
				$this->assertSame( $expected->get_attribute( $name ), $actual->get_attribute( $name ), "Expected {$tag} to keep its {$name} attribute." );
			}
		}
		$this->assertFalse( $actual->next_tag(), 'No element should be added.' );
	}

	/**
	 * @testdox A valid changed SVG with surrounding whitespace is accepted and trimmed.
	 */
	public function test_filter_block_icon_trims_valid_replacement(): void {
		$replacement = " \n\t<?xml version=\"1.0\"?>\n<svg class=\"fixture-icon\" viewBox=\"0 0 24 24\">\n\t<path d=\"M1 1h22v22H1z\" fill=\"currentColor\"/></svg>\r\n ";

		$result = $this->filter_with_replacement( $replacement );

		$this->assertNotSame( self::DEFAULT_SVG, $result );
		$this->assertSame( trim( $result ), $result );
		$this->assert_root_invariants( $result, array( 'fixture-icon', 'required-icon', 'another-required' ) );
	}

	/**
	 * @testdox Empty, invalid, or text-bearing changed markup falls back to the exact default.
	 * @dataProvider provide_invalid_svg_replacements
	 *
	 * @param string $replacement Filter replacement.
	 */
	public function test_filter_block_icon_rejects_invalid_svg( string $replacement ): void {
		$this->assertSame( self::DEFAULT_SVG, $this->filter_with_replacement( $replacement ) );
	}

	/**
	 * Provides invalid changed SVG strings.
	 *
	 * @return array<string, array{string}>
	 */
	public function provide_invalid_svg_replacements(): array {
		return array(
			'empty'                 => array( '' ),
			'spaces'                => array( '   ' ),
			'mixed whitespace'      => array( " \n\t " ),
			'form feed'             => array( "\f" ),
			'plain text'            => array( 'hello' ),
			'foreign content text'  => array( '<svg><foreignObject><div>visible</div></foreignObject></svg>' ),
			'script text'           => array( '<svg><script>alert(1)</script></svg>' ),
			'title text'            => array( '<svg><title>visible</title><path d="M0 0"/></svg>' ),
			'zero roots'            => array( '<path d="M0 0"/>' ),
			'multiple roots'        => array( '<svg></svg><svg></svg>' ),
			'malformed nesting'     => array( '<svg><g><path d="M0 0"/></svg></g>' ),
			'comment token'         => array( '<svg><!-- comment --><path d="M0 0"/></svg>' ),
			'incomplete input'      => array( '<svg><path d="M0 0"' ),
			'nul is not whitespace' => array( "\0" ),
			'encoded URL paint'     => array( '<svg><path fill="&#117;rl(&#35;shape)"/></svg>' ),
			'encoded src paint'     => array( '<svg><path stroke="&#115;rc(&#35;shape)"/></svg>' ),
			'encoded text'          => array( '<svg>&lt;path/&gt;</svg>' ),
			'CDATA'                 => array( '<svg><![CDATA[<path/>]]></svg>' ),
			'unclosed group'        => array( '<svg><g></svg>' ),
		);
	}

	/**
	 * @testdox Unsafe attributes and elements are stripped from otherwise valid SVG.
	 * @dataProvider provide_stripped_markup
	 *
	 * @param string $replacement Filter replacement.
	 * @param string $removed     Markup that must not survive.
	 */
	public function test_filter_block_icon_strips_unsafe_markup( string $replacement, string $removed ): void {
		$result = $this->filter_with_replacement( $replacement );

		$this->assertStringNotContainsString( $removed, $result );
		$this->assertStringNotContainsString( 'example.com', $result );
		$this->assert_root_invariants( $result, array( 'fixture-icon', 'required-icon', 'another-required' ) );
	}

	/**
	 * Provides otherwise valid SVGs carrying markup the sanitizer removes.
	 *
	 * @return array<string, array{string, string}>
	 */
	public function provide_stripped_markup(): array {
		return array(
			'event attribute' => array( '<svg class="fixture-icon"><path d="M0 0h2v2H0z" onload="alert(1)"/></svg>', 'onload' ),
			'external href'   => array( '<svg class="fixture-icon"><path d="M0 0h2v2H0z" href="https://example.com/icon.svg#shape"/></svg>', 'href' ),
			'use element'     => array( '<svg class="fixture-icon"><use href="https://example.com/icon.svg#shape"/></svg>', '<use' ),
		);
	}

	/**
	 * @testdox Safe flat fill and stroke paints survive validation.
	 * @dataProvider provide_safe_paints
	 *
	 * @param string $attribute Paint attribute.
	 * @param string $paint     Paint value.
	 */
	public function test_filter_block_icon_accepts_safe_paint( string $attribute, string $paint ): void {
		$replacement = '<svg class="fixture-icon"><path d="M0 0h2v2H0z" ' . $attribute . '="' . $paint . '"/></svg>';

		$result = $this->filter_with_replacement( $replacement );

		$this->assertNotSame( self::DEFAULT_SVG, $result );
		$this->assertStringContainsString( $attribute . '="' . $paint . '"', $result );
	}

	/**
	 * Provides safe flat SVG paints for both paint attributes.
	 *
	 * @return array<string, array{string, string}>
	 */
	public function provide_safe_paints(): array {
		return $this->paint_cases(
			array(
				'none'                => 'none',
				'current color'       => 'currentColor',
				'named color'         => 'red',
				'name containing url' => 'burlywood',
				'hex'                 => '#aabbccdd',
				'rgba'                => 'rgba(1 2 3 / 50%)',
				'hsla'                => 'hsla(.5turn, 50%, 25%, .8)',
				'paint whitespace'    => " \tred \n",
				'oklch'               => 'oklch(70% 0.1 200)',
				'color-mix'           => 'color-mix(in srgb, red 40%, blue)',
				'light-dark'          => 'light-dark(#000, #fff)',
				'relative color'      => 'rgb(from red r g b / 50%)',
				'CSS variable'        => 'var(--wp--preset--color--primary)',
				'calc function'       => 'calc(1 + 1)',
			)
		);
	}

	/**
	 * @testdox Unsafe fill and stroke paints fall back to the exact default.
	 * @dataProvider provide_unsafe_paints
	 *
	 * @param string $attribute Paint attribute.
	 * @param string $paint     Paint value.
	 */
	public function test_filter_block_icon_rejects_unsafe_paint( string $attribute, string $paint ): void {
		$replacement = '<svg><path d="M0 0h2v2H0z" ' . $attribute . '="' . $paint . '"/></svg>';

		$this->assertSame( self::DEFAULT_SVG, $this->filter_with_replacement( $replacement ) );
	}

	/**
	 * Provides unsafe SVG paints for both paint attributes.
	 *
	 * @return array<string, array{string, string}>
	 */
	public function provide_unsafe_paints(): array {
		return $this->paint_cases(
			array(
				'external URL'          => 'url(https://example.com/icon.svg#shape)',
				'local URL'             => 'url(#shape)',
				'JavaScript URL'        => 'url(javascript:alert(1))',
				'mixed case URL'        => 'UrL(https://example.com/icon.svg#shape)',
				'CSS escaped URL'       => 'u\\72l(#shape)',
				'URL with fallback'     => 'url(#shape) red',
				'src function'          => 'src(https://example.com/icon.svg#shape)',
				'mixed case src'        => 'SrC(https://example.com/icon.svg#shape)',
				'CSS escaped src'       => 's\\72 c(#shape)',
				'URL inside a function' => 'color-mix(in srgb, url(#shape), red)',
				'quoted value'          => "'red'",
				'declaration injection' => 'red;stroke:url(https://example.com/icon.svg#shape)',
			)
		);
	}

	/**
	 * @testdox Cart icon requests expose only canonical names while preserving legacy visible output.
	 * @dataProvider provide_cart_icon_requests
	 *
	 * @param mixed  $requested_icon Requested cart icon.
	 * @param string $expected_name  Canonical icon name.
	 */
	public function test_get_cart_icon_canonicalizes_name_and_keeps_legacy_output( $requested_icon, string $expected_name ): void {
		$attributes       = array( 'cartIcon' => $requested_icon );
		$expected_default = MiniCartUtils::get_svg_icon( $requested_icon, '#123456' );
		$received_name    = null;
		add_filter(
			'woocommerce_blocks_icon_svg',
			function ( $svg, $icon_name, $block_name, $received_attributes ) use ( &$received_name, $expected_default, $attributes ) {
				$this->assertSame( $expected_default, $svg );
				$this->assertSame( 'woocommerce/cart-link', $block_name );
				$this->assertSame( $attributes, $received_attributes );
				$received_name = $icon_name;

				return $svg;
			},
			10,
			4
		);

		$result = BlockIconUtils::get_cart_icon( $requested_icon, '#123456', 'woocommerce/cart-link', $attributes );

		$this->assertSame( $expected_name, $received_name );
		$this->assertSame( $expected_default, $result );
	}

	/**
	 * Provides canonical and fallback cart icon requests.
	 *
	 * @return array<string, array{mixed, string}>
	 */
	public function provide_cart_icon_requests(): array {
		return array(
			'exact bag'      => array( 'bag', 'bag' ),
			'exact bag alt'  => array( 'bag-alt', 'bag-alt' ),
			'explicit cart'  => array( 'cart', 'cart' ),
			'missing value'  => array( null, 'cart' ),
			'empty string'   => array( '', 'cart' ),
			'unknown string' => array( 'unknown', 'cart' ),
			'wrong case'     => array( 'Bag', 'cart' ),
			'false'          => array( false, 'cart' ),
			'integer'        => array( 42, 'cart' ),
			'array'          => array( array(), 'cart' ),
			'object'         => array( new \stdClass(), 'cart' ),
		);
	}

	/**
	 * Crosses paint values with both paint attributes.
	 *
	 * @param array<string, string> $paints Paint values by case name.
	 * @return array<string, array{string, string}>
	 */
	private function paint_cases( array $paints ): array {
		$cases = array();
		foreach ( array( 'fill', 'stroke' ) as $attribute ) {
			foreach ( $paints as $name => $paint ) {
				$cases[ $attribute . ' ' . $name ] = array( $attribute, $paint );
			}
		}

		return $cases;
	}

	/**
	 * Applies a test replacement to the generic icon boundary.
	 *
	 * @param mixed $replacement Filter replacement.
	 * @return string Filtered icon.
	 */
	private function filter_with_replacement( $replacement ): string {
		add_filter(
			'woocommerce_blocks_icon_svg',
			static function () use ( $replacement ) {
				return $replacement;
			}
		);

		return BlockIconUtils::filter_block_icon(
			self::DEFAULT_SVG,
			'line',
			'woocommerce/customer-account',
			array( 'iconStyle' => 'line' )
		);
	}

	/**
	 * Asserts caller-owned root classes and decorative attributes.
	 *
	 * @param string        $svg              Filtered SVG.
	 * @param array<string> $expected_classes Expected root classes.
	 */
	private function assert_root_invariants( string $svg, array $expected_classes ): void {
		$processor = new \WP_HTML_Tag_Processor( $svg );

		$this->assertTrue( $processor->next_tag( array( 'tag_name' => 'svg' ) ), 'Expected one SVG root.' );
		foreach ( $expected_classes as $expected_class ) {
			$this->assertTrue( $processor->has_class( $expected_class ), "Expected the {$expected_class} root class." );
		}
		$this->assertSame( 'true', $processor->get_attribute( 'aria-hidden' ) );
		$this->assertSame( 'false', $processor->get_attribute( 'focusable' ) );
	}
}
