<?php
declare( strict_types = 1 );

namespace Automattic\WooCommerce\Tests\Internal\Utilities;

use Automattic\WooCommerce\Internal\Utilities\PriceSeparators;
use WC_Unit_Test_Case;

/**
 * Tests for the PriceSeparators class.
 */
class PriceSeparatorsTest extends WC_Unit_Test_Case {

	/**
	 * @testdox Should decode HTML entities stored as separators, including HTML5-only entities.
	 * @dataProvider separator_entity_data
	 *
	 * @param string $stored   The raw option value.
	 * @param string $expected The expected decoded separator.
	 */
	public function test_separators_are_decoded( string $stored, string $expected ): void {
		update_option( 'woocommerce_price_thousand_sep', $stored );
		update_option( 'woocommerce_price_decimal_sep', $stored );

		$this->assertSame( $expected, PriceSeparators::get_thousand(), "Thousand separator stored as '{$stored}' should decode to '{$expected}'" );
		$this->assertSame( $expected, PriceSeparators::get_decimal(), "Decimal separator stored as '{$stored}' should decode to '{$expected}'" );
	}

	/**
	 * Data provider of stored separator values and their expected decoded forms.
	 *
	 * @return array
	 */
	public function separator_entity_data(): array {
		return array(
			'plain comma'             => array( ',', ',' ),
			'plain space'             => array( ' ', ' ' ),
			'non-breaking space char' => array( "\u{00A0}", "\u{00A0}" ),
			'named entity nbsp'       => array( '&nbsp;', "\u{00A0}" ),
			'numeric entity comma'    => array( '&#44;', ',' ),
			'HTML5-only entity apos'  => array( '&apos;', "'" ),
		);
	}

	/**
	 * @testdox Should preserve the core getters' fallbacks when the options are empty.
	 */
	public function test_empty_options_preserve_getter_fallbacks(): void {
		update_option( 'woocommerce_price_thousand_sep', '' );
		update_option( 'woocommerce_price_decimal_sep', '' );

		$this->assertSame( '', PriceSeparators::get_thousand(), 'An empty thousand separator should stay empty' );
		$this->assertSame( '.', PriceSeparators::get_decimal(), 'An empty decimal separator should fall back to a period, matching wc_get_price_decimal_separator()' );
	}
}
