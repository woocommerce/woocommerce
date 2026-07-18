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

	private const STATIC_SVG_FIXTURE = '<svg class="fixture-icon" xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" preserveAspectRatio="xMidYMid meet" fill="none" stroke="currentColor" fill-opacity="0.9" stroke-opacity="0.8" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="bevel" stroke-miterlimit="4" opacity="0.95" role="presentation"><g class="fixture-group" fill="red" stroke="#abc" opacity="0.75" transform="translate(1 1)"><rect class="fixture-rect" x="1" y="1" width="4" height="3" rx="1" ry="1"/><circle class="fixture-circle" cx="9" cy="3" r="2"/><ellipse class="fixture-ellipse" cx="15" cy="3" rx="3" ry="2"/><line class="fixture-line" x1="1" y1="8" x2="7" y2="8"/><polyline class="fixture-polyline" points="9,9 11,7 13,9"/><polygon class="fixture-polygon" points="15,9 17,7 19,9"/><path class="fixture-path" d="M2 12h18v8H2z" fill="#aabbccdd" fill-rule="evenodd" clip-rule="evenodd" stroke="rgba(1 2 3 / 50%)" stroke-width="2" stroke-linecap="square" stroke-linejoin="round" stroke-miterlimit="3" opacity="0.6" transform="rotate(1)"/></g></svg>';

	/**
	 * Tear down test fixtures.
	 */
	public function tearDown(): void {
		remove_all_filters( 'woocommerce_blocks_icon_svg' );
		parent::tearDown();
	}

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
	 * @testdox Empty and whitespace-only filter returns remove the icon.
	 * @dataProvider provide_empty_replacements
	 *
	 * @param string $replacement Filter replacement.
	 */
	public function test_filter_block_icon_allows_empty_replacement( string $replacement ): void {
		$this->assertSame( '', $this->filter_with_replacement( $replacement ) );
	}

	/**
	 * Provides empty icon replacements.
	 *
	 * @return array<string, array{string}>
	 */
	public function provide_empty_replacements(): array {
		return array(
			'empty'            => array( '' ),
			'spaces'           => array( '   ' ),
			'mixed whitespace' => array( " \n\t " ),
		);
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
	 * @testdox A resource filter return falls back to the exact default and is closed after use.
	 */
	public function test_filter_block_icon_rejects_resource_replacement(): void {
		// phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_fopen -- A real resource is required to exercise the filter boundary.
		$resource = fopen( 'php://memory', 'r+' );
		if ( false === $resource ) {
			$this->fail( 'Expected the in-memory stream to open.' );
		}

		try {
			$this->assertSame( self::DEFAULT_SVG, $this->filter_with_replacement( $resource ) );
		} finally {
			// phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_fclose -- The test-owned resource must be closed after the assertion.
			fclose( $resource );
		}
	}

	/**
	 * @testdox A representative static SVG vocabulary survives with caller invariants.
	 */
	public function test_filter_block_icon_accepts_representative_static_svg(): void {
		$result = $this->filter_with_replacement( self::STATIC_SVG_FIXTURE );

		$this->assertNotSame( self::DEFAULT_SVG, $result );
		$this->assert_root_invariants( $result, array( 'fixture-icon', 'required-icon', 'another-required' ) );

		$processor = new \WP_HTML_Tag_Processor( $result );
		$this->assertTrue( $processor->next_tag( array( 'tag_name' => 'svg' ) ) );
		$this->assertSame( '24', $processor->get_attribute( 'width' ) );
		$this->assertSame( '24', $processor->get_attribute( 'height' ) );
		$this->assertSame( '0 0 24 24', $processor->get_attribute( 'viewBox' ) );
		$this->assertSame( 'xMidYMid meet', $processor->get_attribute( 'preserveAspectRatio' ) );
		$this->assertSame( 'none', $processor->get_attribute( 'fill' ) );
		$this->assertSame( 'currentColor', $processor->get_attribute( 'stroke' ) );
		$this->assertSame( '0.9', $processor->get_attribute( 'fill-opacity' ) );
		$this->assertSame( '0.8', $processor->get_attribute( 'stroke-opacity' ) );
		$this->assertSame( '1.5', $processor->get_attribute( 'stroke-width' ) );
		$this->assertSame( 'round', $processor->get_attribute( 'stroke-linecap' ) );
		$this->assertSame( 'bevel', $processor->get_attribute( 'stroke-linejoin' ) );
		$this->assertSame( '4', $processor->get_attribute( 'stroke-miterlimit' ) );
		$this->assertSame( '0.95', $processor->get_attribute( 'opacity' ) );
		$this->assertSame( 'presentation', $processor->get_attribute( 'role' ) );

		$this->assertTrue( $processor->next_tag( array( 'tag_name' => 'g' ) ) );
		$this->assertSame( 'red', $processor->get_attribute( 'fill' ) );
		$this->assertSame( '#abc', $processor->get_attribute( 'stroke' ) );
		$this->assertSame( '0.75', $processor->get_attribute( 'opacity' ) );
		$this->assertSame( 'translate(1 1)', $processor->get_attribute( 'transform' ) );

		$this->assertTrue( $processor->next_tag( array( 'tag_name' => 'rect' ) ) );
		$this->assertSame( '1', $processor->get_attribute( 'x' ) );
		$this->assertSame( '1', $processor->get_attribute( 'y' ) );
		$this->assertSame( '4', $processor->get_attribute( 'width' ) );
		$this->assertSame( '3', $processor->get_attribute( 'height' ) );
		$this->assertSame( '1', $processor->get_attribute( 'rx' ) );
		$this->assertSame( '1', $processor->get_attribute( 'ry' ) );

		$this->assertTrue( $processor->next_tag( array( 'tag_name' => 'circle' ) ) );
		$this->assertSame( '9', $processor->get_attribute( 'cx' ) );
		$this->assertSame( '3', $processor->get_attribute( 'cy' ) );
		$this->assertSame( '2', $processor->get_attribute( 'r' ) );

		$this->assertTrue( $processor->next_tag( array( 'tag_name' => 'ellipse' ) ) );
		$this->assertSame( '15', $processor->get_attribute( 'cx' ) );
		$this->assertSame( '3', $processor->get_attribute( 'cy' ) );
		$this->assertSame( '3', $processor->get_attribute( 'rx' ) );
		$this->assertSame( '2', $processor->get_attribute( 'ry' ) );

		$this->assertTrue( $processor->next_tag( array( 'tag_name' => 'line' ) ) );
		$this->assertSame( '1', $processor->get_attribute( 'x1' ) );
		$this->assertSame( '8', $processor->get_attribute( 'y1' ) );
		$this->assertSame( '7', $processor->get_attribute( 'x2' ) );
		$this->assertSame( '8', $processor->get_attribute( 'y2' ) );

		$this->assertTrue( $processor->next_tag( array( 'tag_name' => 'polyline' ) ) );
		$this->assertSame( '9,9 11,7 13,9', $processor->get_attribute( 'points' ) );

		$this->assertTrue( $processor->next_tag( array( 'tag_name' => 'polygon' ) ) );
		$this->assertSame( '15,9 17,7 19,9', $processor->get_attribute( 'points' ) );

		$this->assertTrue( $processor->next_tag( array( 'tag_name' => 'path' ) ) );
		$this->assertSame( 'M2 12h18v8H2z', $processor->get_attribute( 'd' ) );
		$this->assertSame( '#aabbccdd', $processor->get_attribute( 'fill' ) );
		$this->assertSame( 'evenodd', $processor->get_attribute( 'fill-rule' ) );
		$this->assertSame( 'evenodd', $processor->get_attribute( 'clip-rule' ) );
		$this->assertSame( 'rgba(1 2 3 / 50%)', $processor->get_attribute( 'stroke' ) );
		$this->assertSame( '2', $processor->get_attribute( 'stroke-width' ) );
		$this->assertSame( 'square', $processor->get_attribute( 'stroke-linecap' ) );
		$this->assertSame( 'round', $processor->get_attribute( 'stroke-linejoin' ) );
		$this->assertSame( '3', $processor->get_attribute( 'stroke-miterlimit' ) );
		$this->assertSame( '0.6', $processor->get_attribute( 'opacity' ) );
		$this->assertSame( 'rotate(1)', $processor->get_attribute( 'transform' ) );
	}

	/**
	 * @testdox A valid changed SVG with surrounding whitespace is accepted and trimmed.
	 */
	public function test_filter_block_icon_trims_valid_replacement(): void {
		$replacement = " \n\t<svg class=\"fixture-icon\" viewBox=\"0 0 24 24\"><path d=\"M1 1h22v22H1z\" fill=\"currentColor\"/></svg>\r\n ";

		$result = $this->filter_with_replacement( $replacement );

		$this->assertNotSame( self::DEFAULT_SVG, $result );
		$this->assertSame( trim( $result ), $result );
		$this->assert_root_invariants( $result, array( 'fixture-icon', 'required-icon', 'another-required' ) );
	}

	/**
	 * @testdox Invalid or text-bearing changed markup falls back to the exact default.
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
			'plain text'           => array( 'hello' ),
			'foreign content text' => array( '<svg><foreignObject><div>visible</div></foreignObject></svg>' ),
			'script text'          => array( '<svg><script>alert(1)</script></svg>' ),
			'title text'           => array( '<svg><title>visible</title><path d="M0 0"/></svg>' ),
			'zero roots'           => array( '<path d="M0 0"/>' ),
			'multiple roots'       => array( '<svg></svg><svg></svg>' ),
			'malformed nesting'    => array( '<svg><g><path d="M0 0"/></svg></g>' ),
			'comment token'        => array( '<svg><!-- comment --><path d="M0 0"/></svg>' ),
			'incomplete input'     => array( '<svg><path d="M0 0"' ),
		);
	}

	/**
	 * @testdox Unsafe event attributes are stripped from otherwise valid SVG.
	 */
	public function test_filter_block_icon_strips_event_attributes(): void {
		$result = $this->filter_with_replacement( '<svg class="fixture-icon"><path d="M0 0h2v2H0z" onload="alert(1)"/></svg>' );

		$this->assertNotSame( self::DEFAULT_SVG, $result );
		$this->assertStringContainsString( '<path', $result );
		$this->assertStringNotContainsString( 'onload', $result );
		$this->assert_root_invariants( $result, array( 'fixture-icon', 'required-icon', 'another-required' ) );
	}

	/**
	 * @testdox External href attributes are stripped from otherwise valid geometry.
	 */
	public function test_filter_block_icon_strips_external_href(): void {
		$result = $this->filter_with_replacement( '<svg class="fixture-icon"><path d="M0 0h2v2H0z" href="https://example.com/icon.svg#shape"/></svg>' );

		$this->assertNotSame( self::DEFAULT_SVG, $result );
		$this->assertStringContainsString( '<path', $result );
		$this->assertStringNotContainsString( 'href', $result );
		$this->assertStringNotContainsString( 'example.com', $result );
	}

	/**
	 * @testdox External use elements and references do not survive sanitization.
	 */
	public function test_filter_block_icon_strips_external_use(): void {
		$result = $this->filter_with_replacement( '<svg class="fixture-icon"><use href="https://example.com/icon.svg#shape"/></svg>' );

		$this->assertNotSame( self::DEFAULT_SVG, $result );
		$this->assertStringNotContainsString( '<use', $result );
		$this->assertStringNotContainsString( 'href', $result );
		$this->assertStringNotContainsString( 'example.com', $result );
		$this->assert_root_invariants( $result, array( 'fixture-icon', 'required-icon', 'another-required' ) );
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
		$paints = array(
			'none'            => 'none',
			'current color'   => 'currentColor',
			'named color'     => 'red',
			'three digit hex' => '#abc',
			'four digit hex'  => '#abcd',
			'six digit hex'   => '#aabbcc',
			'eight digit hex' => '#aabbccdd',
			'rgb'             => 'rgb(1, 2, 3)',
			'rgba'            => 'rgba(1 2 3 / 50%)',
			'hsl'             => 'hsl(120deg 50% 25%)',
			'hsla'            => 'hsla(.5turn, 50%, 25%, .8)',
		);
		$cases  = array();

		foreach ( array( 'fill', 'stroke' ) as $attribute ) {
			foreach ( $paints as $name => $paint ) {
				$cases[ $attribute . ' ' . $name ] = array( $attribute, $paint );
			}
		}

		return $cases;
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
		$paints = array(
			'external URL'          => 'url(https://example.com/icon.svg#shape)',
			'local URL'             => 'url(#shape)',
			'JavaScript URL'        => 'url(javascript:alert(1))',
			'mixed case URL'        => 'UrL(https://example.com/icon.svg#shape)',
			'CSS escaped URL'       => 'u\\72l(https://example.com/icon.svg#shape)',
			'CSS variable'          => 'var(--icon-color)',
			'arbitrary function'    => 'calc(1 + 1)',
			'declaration injection' => 'red;stroke:url(https://example.com/icon.svg#shape)',
		);
		$cases  = array();

		foreach ( array( 'fill', 'stroke' ) as $attribute ) {
			foreach ( $paints as $name => $paint ) {
				$cases[ $attribute . ' ' . $name ] = array( $attribute, $paint );
			}
		}

		return $cases;
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
