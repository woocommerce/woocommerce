<?php
declare( strict_types = 1 );

namespace Automattic\WooCommerce\Tests\Blocks\Utils;

use Automattic\WooCommerce\Blocks\Utils\MiniCartUtils;

/**
 * Tests for the MiniCartUtils class
 *
 * @since $VID:$
 */
class MiniCartUtilsTest extends \WP_UnitTestCase {
	private const CART_SVG = '<svg xmlns="http://www.w3.org/2000/svg" fill="currentColor" class="wc-block-mini-cart__icon" viewBox="0 0 32 32"><circle cx="12.667" cy="24.667" r="2"/><circle cx="23.333" cy="24.667" r="2"/><path fill-rule="evenodd" d="M9.285 10.036a1 1 0 0 1 .776-.37h15.272a1 1 0 0 1 .99 1.142l-1.333 9.333A1 1 0 0 1 24 21H12a1 1 0 0 1-.98-.797L9.083 10.87a1 1 0 0 1 .203-.834m2.005 1.63L12.814 19h10.319l1.047-7.333z" clip-rule="evenodd"/><path fill-rule="evenodd" d="M5.667 6.667a1 1 0 0 1 1-1h2.666a1 1 0 0 1 .984.82l.727 4a1 1 0 1 1-1.967.359l-.578-3.18H6.667a1 1 0 0 1-1-1" clip-rule="evenodd"/></svg>';

	private const BAG_SVG = '<svg xmlns="http://www.w3.org/2000/svg" fill="none" class="wc-block-mini-cart__icon" viewBox="0 0 32 32"><path fill="currentColor" fill-rule="evenodd" d="M12.444 14.222a.89.89 0 0 1 .89.89 2.667 2.667 0 0 0 5.333 0 .889.889 0 1 1 1.777 0 4.444 4.444 0 1 1-8.888 0c0-.492.398-.89.888-.89M11.24 6.683a1 1 0 0 1 .76-.35h8a1 1 0 0 1 .76.35l4 4.666A1 1 0 0 1 24 13H8a1 1 0 0 1-.76-1.65zm1.22 1.65L10.174 11h11.652L19.54 8.333z" clip-rule="evenodd"/><path fill="currentColor" fill-rule="evenodd" d="M7 12a1 1 0 0 1 1-1h16a1 1 0 0 1 1 1v13.333a1 1 0 0 1-1 1H8a1 1 0 0 1-1-1zm2 1v11.333h14V13z" clip-rule="evenodd"/></svg>';

	private const BAG_ALT_SVG = '<svg xmlns="http://www.w3.org/2000/svg" fill="none" class="wc-block-mini-cart__icon" viewBox="0 0 32 32"><path fill="currentColor" fill-rule="evenodd" d="M19.556 12.333a.89.89 0 0 1-.89-.889c0-.707-.28-3.385-.78-3.885a2.667 2.667 0 0 0-3.772 0c-.5.5-.78 3.178-.78 3.885a.889.889 0 1 1-1.778 0c0-1.178.468-4.309 1.301-5.142a4.445 4.445 0 0 1 6.286 0c.833.833 1.302 3.964 1.302 5.142a.89.89 0 0 1-.89.89" clip-rule="evenodd"/><path fill="currentColor" fill-rule="evenodd" d="M7.5 12a1 1 0 0 1 1-1h15a1 1 0 0 1 1 1v13.333a1 1 0 0 1-1 1h-15a1 1 0 0 1-1-1zm2 1v11.333h13V13z" clip-rule="evenodd"/></svg>';

	private const COLORED_CART_SVG = '<svg xmlns="http://www.w3.org/2000/svg" fill="#123456" class="wc-block-mini-cart__icon" viewBox="0 0 32 32"><circle cx="12.667" cy="24.667" r="2"/><circle cx="23.333" cy="24.667" r="2"/><path fill-rule="evenodd" d="M9.285 10.036a1 1 0 0 1 .776-.37h15.272a1 1 0 0 1 .99 1.142l-1.333 9.333A1 1 0 0 1 24 21H12a1 1 0 0 1-.98-.797L9.083 10.87a1 1 0 0 1 .203-.834m2.005 1.63L12.814 19h10.319l1.047-7.333z" clip-rule="evenodd"/><path fill-rule="evenodd" d="M5.667 6.667a1 1 0 0 1 1-1h2.666a1 1 0 0 1 .984.82l.727 4a1 1 0 1 1-1.967.359l-.578-3.18H6.667a1 1 0 0 1-1-1" clip-rule="evenodd"/></svg>';

	private const COLORED_BAG_SVG = '<svg xmlns="http://www.w3.org/2000/svg" fill="none" class="wc-block-mini-cart__icon" viewBox="0 0 32 32"><path fill="#123456" fill-rule="evenodd" d="M12.444 14.222a.89.89 0 0 1 .89.89 2.667 2.667 0 0 0 5.333 0 .889.889 0 1 1 1.777 0 4.444 4.444 0 1 1-8.888 0c0-.492.398-.89.888-.89M11.24 6.683a1 1 0 0 1 .76-.35h8a1 1 0 0 1 .76.35l4 4.666A1 1 0 0 1 24 13H8a1 1 0 0 1-.76-1.65zm1.22 1.65L10.174 11h11.652L19.54 8.333z" clip-rule="evenodd"/><path fill="#123456" fill-rule="evenodd" d="M7 12a1 1 0 0 1 1-1h16a1 1 0 0 1 1 1v13.333a1 1 0 0 1-1 1H8a1 1 0 0 1-1-1zm2 1v11.333h14V13z" clip-rule="evenodd"/></svg>';

	private const COLORED_BAG_ALT_SVG = '<svg xmlns="http://www.w3.org/2000/svg" fill="none" class="wc-block-mini-cart__icon" viewBox="0 0 32 32"><path fill="#123456" fill-rule="evenodd" d="M19.556 12.333a.89.89 0 0 1-.89-.889c0-.707-.28-3.385-.78-3.885a2.667 2.667 0 0 0-3.772 0c-.5.5-.78 3.178-.78 3.885a.889.889 0 1 1-1.778 0c0-1.178.468-4.309 1.301-5.142a4.445 4.445 0 0 1 6.286 0c.833.833 1.302 3.964 1.302 5.142a.89.89 0 0 1-.89.89" clip-rule="evenodd"/><path fill="#123456" fill-rule="evenodd" d="M7.5 12a1 1 0 0 1 1-1h15a1 1 0 0 1 1 1v13.333a1 1 0 0 1-1 1h-15a1 1 0 0 1-1-1zm2 1v11.333h13V13z" clip-rule="evenodd"/></svg>';

	/**
	 * @testdox get_svg_icon() keeps its legacy public static signature.
	 */
	public function test_get_svg_icon_keeps_legacy_signature(): void {
		$method     = new \ReflectionMethod( MiniCartUtils::class, 'get_svg_icon' );
		$parameters = $method->getParameters();

		$this->assertTrue( $method->isPublic() );
		$this->assertTrue( $method->isStatic() );
		$this->assertSame( 2, $method->getNumberOfParameters() );
		$this->assertSame( 1, $method->getNumberOfRequiredParameters() );
		$this->assertSame( array( 'icon_name', 'icon_color' ), array_map( static fn( $parameter ) => $parameter->getName(), $parameters ) );
		$this->assertTrue( $parameters[1]->isDefaultValueAvailable() );
		$this->assertSame( 'currentColor', $parameters[1]->getDefaultValue() );
	}

	/**
	 * @testdox get_svg_icon() keeps the exact legacy bundled SVG bytes.
	 * @dataProvider provide_legacy_cart_icons
	 *
	 * @param mixed  $requested_icon Requested icon.
	 * @param string $expected_svg   Exact legacy SVG.
	 */
	public function test_get_svg_icon_keeps_legacy_output( $requested_icon, string $expected_svg ): void {
		$this->assertSame( $expected_svg, MiniCartUtils::get_svg_icon( $requested_icon ) );
	}

	/**
	 * Provides the exact legacy bundled SVGs.
	 *
	 * @return array<string, array{mixed, string}>
	 */
	public function provide_legacy_cart_icons(): array {
		return array(
			'cart'    => array( 'cart', self::CART_SVG ),
			'bag'     => array( 'bag', self::BAG_SVG ),
			'bag alt' => array( 'bag-alt', self::BAG_ALT_SVG ),
		);
	}

	/**
	 * @testdox get_svg_icon() keeps the exact legacy SVG bytes for an explicit color.
	 * @dataProvider provide_legacy_colored_cart_icons
	 *
	 * @param string $requested_icon Requested icon.
	 * @param string $expected_svg   Exact legacy SVG.
	 */
	public function test_get_svg_icon_keeps_legacy_explicit_color_output( string $requested_icon, string $expected_svg ): void {
		$this->assertSame( $expected_svg, MiniCartUtils::get_svg_icon( $requested_icon, '#123456' ) );
	}

	/**
	 * Provides exact legacy SVGs with an explicit color.
	 *
	 * @return array<string, array{string, string}>
	 */
	public function provide_legacy_colored_cart_icons(): array {
		return array(
			'cart'    => array( 'cart', self::COLORED_CART_SVG ),
			'bag'     => array( 'bag', self::COLORED_BAG_SVG ),
			'bag alt' => array( 'bag-alt', self::COLORED_BAG_ALT_SVG ),
		);
	}

	/**
	 * @testdox get_svg_icon() keeps the exact legacy cart fallback bytes.
	 * @dataProvider provide_legacy_cart_icon_fallbacks
	 *
	 * @param mixed $requested_icon Requested icon.
	 */
	public function test_get_svg_icon_keeps_legacy_cart_fallback( $requested_icon ): void {
		$this->assertSame( self::CART_SVG, MiniCartUtils::get_svg_icon( $requested_icon ) );
	}

	/**
	 * Provides requests that use the legacy cart fallback.
	 *
	 * @return array<string, array{mixed}>
	 */
	public function provide_legacy_cart_icon_fallbacks(): array {
		return array(
			'empty string' => array( '' ),
			'unknown'      => array( 'unknown' ),
			'null'         => array( null ),
			'array'        => array( array() ),
			'object'       => array( new \stdClass() ),
		);
	}

	/**
	 * We ensure old attributes are migrated.
	 */
	public function test_migrate_attributes_to_color_panel() {
		$mock_attributes     = array(
			'priceColorValue'        => '#9b51e0',
			'iconColorValue'         => '#fcb900',
			'productCountColorValue' => '#000000',
		);
		$expected_attributes = array(
			'priceColor'        => array(
				'color' => '#9b51e0',
			),
			'iconColor'         => array(
				'color' => '#fcb900',
			),
			'productCountColor' => array(
				'color' => '#000000',
			),
		);

		$this->assertEquals( $expected_attributes, MiniCartUtils::migrate_attributes_to_color_panel( $mock_attributes ) );
	}
}
