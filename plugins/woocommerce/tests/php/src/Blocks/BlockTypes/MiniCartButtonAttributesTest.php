<?php

declare( strict_types = 1 );

namespace Automattic\WooCommerce\Tests\Blocks\BlockTypes;

use WC_Unit_Test_Case;
use WP_HTML_Tag_Processor;

/**
 * Tests for Mini-Cart button URL attributes.
 */
class MiniCartButtonAttributesTest extends WC_Unit_Test_Case {

	/**
	 * @testdox Mini-Cart links preserve query strings and reject unsafe URL protocols.
	 * @dataProvider button_url_data
	 *
	 * @param string $button Button block name.
	 * @param string $url Filtered page URL.
	 * @param string $expected Expected parsed URL.
	 */
	public function test_button_url_is_sanitized_and_escaped( string $button, string $url, string $expected ): void {
		$page_id = self::factory()->post->create( array( 'post_type' => 'page' ) );
		update_option( 'woocommerce_cart_page_id', $page_id );
		update_option( 'woocommerce_checkout_page_id', $page_id );
		add_filter(
			'page_link',
			static function () use ( $url ) {
				return $url;
			}
		);

		$markup = do_blocks( '<!-- wp:woocommerce/mini-cart-' . $button . '-button-block /-->' );
		$tags   = new WP_HTML_Tag_Processor( $markup );

		$this->assertTrue( $tags->next_tag( array( 'tag_name' => 'a' ) ), 'The Mini-Cart button should render a link.' );
		$this->assertSame( $expected, $tags->get_attribute( 'href' ), 'The link must preserve safe URL values and reject unsafe protocols.' );
		$this->assertNull( $tags->get_attribute( 'onmouseover' ), 'Quotes in a URL must not create another attribute.' );
	}

	/**
	 * URL cases for both Mini-Cart buttons.
	 *
	 * @return array
	 */
	public static function button_url_data(): array {
		$cases = array();
		foreach ( array( 'cart', 'checkout' ) as $button ) {
			$cases[ $button . ' query string' ] = array( $button, 'https://example.org/?a=1&b=2', 'https://example.org/?a=1&b=2' );
			$cases[ $button . ' quotes' ]       = array( $button, 'https://example.org/?q="onmouseover="alert(1)', 'https://example.org/?q=onmouseover=alert(1)' );
			$cases[ $button . ' unsafe URL' ]   = array( $button, 'javascript:alert(1)', '' );
		}
		return $cases;
	}
}
