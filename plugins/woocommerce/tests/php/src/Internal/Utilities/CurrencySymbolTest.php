<?php
declare( strict_types = 1 );

namespace Automattic\WooCommerce\Tests\Internal\Utilities;

use Automattic\WooCommerce\Enums\CurrencyPosition;
use Automattic\WooCommerce\Internal\Utilities\CurrencySymbol;
use WC_Unit_Test_Case;

/**
 * Tests for the CurrencySymbol class.
 */
class CurrencySymbolTest extends WC_Unit_Test_Case {

	/**
	 * @testdox Should decode HTML entities in the currency symbol.
	 * @testWith ["EUR", "€"]
	 *           ["USD", "$"]
	 *           ["AED", "د.إ"]
	 *
	 * @param string $currency The store currency.
	 * @param string $expected The expected decoded symbol.
	 */
	public function test_symbol_is_decoded( string $currency, string $expected ): void {
		update_option( 'woocommerce_currency', $currency );

		$this->assertSame( $expected, CurrencySymbol::get_symbol(), "The {$currency} symbol should be decoded to plain text" );
	}

	/**
	 * @testdox Should decode a filtered currency symbol the way browsers parse it.
	 * @testWith ["z&#322;", "zł"]
	 *           ["&dollar;", "$"]
	 *           ["&apos;", "'"]
	 *
	 * @param string $filtered The filtered symbol.
	 * @param string $expected The expected decoded symbol.
	 */
	public function test_filtered_symbol_is_decoded( string $filtered, string $expected ): void {
		add_filter(
			'woocommerce_currency_symbol',
			function () use ( $filtered ) {
				return $filtered;
			}
		);

		$this->assertSame( $expected, CurrencySymbol::get_symbol(), "A symbol filtered to '{$filtered}' should decode to '{$expected}'" );
	}

	/**
	 * @testdox Should match the currency position option when the price format is not filtered.
	 * @testWith ["left"]
	 *           ["right"]
	 *           ["left_space"]
	 *           ["right_space"]
	 *
	 * @param string $position The stored currency position.
	 */
	public function test_matches_option_when_price_format_is_not_filtered( string $position ): void {
		update_option( 'woocommerce_currency_pos', $position );

		$this->assertSame( $position, CurrencySymbol::get_position(), "The '{$position}' option should be returned unchanged" );
	}

	/**
	 * @testdox Should derive the position from a filtered price format.
	 * @dataProvider filtered_price_format_data
	 *
	 * @param string $format   The filtered price format.
	 * @param string $expected The expected symbol position.
	 */
	public function test_derives_position_from_filtered_price_format( string $format, string $expected ): void {
		update_option( 'woocommerce_currency_pos', CurrencyPosition::LEFT );
		add_filter(
			'woocommerce_price_format',
			function () use ( $format ) {
				return $format;
			}
		);

		$this->assertSame( $expected, CurrencySymbol::get_position(), "The '{$format}' price format should map to '{$expected}'" );
	}

	/**
	 * Data provider of filtered price formats and their expected symbol positions.
	 *
	 * @return array
	 */
	public function filtered_price_format_data(): array {
		return array(
			'right'                    => array( '%2$s%1$s', CurrencyPosition::RIGHT ),
			'right with nbsp'          => array( '%2$s&nbsp;%1$s', CurrencyPosition::RIGHT_SPACE ),
			'right with space'         => array( '%2$s %1$s', CurrencyPosition::RIGHT_SPACE ),
			'left with nbsp'           => array( '%1$s&nbsp;%2$s', CurrencyPosition::LEFT_SPACE ),
			'right with code appended' => array( '%2$s&nbsp;%1$sPLN', CurrencyPosition::RIGHT_SPACE ),
			'left with code prepended' => array( 'USD%1$s%2$s', CurrencyPosition::LEFT ),
			'left with empty markup'   => array( '%1$s<span></span>%2$s', CurrencyPosition::LEFT ),
			'right with spaced markup' => array( '%2$s<span>&nbsp;</span>%1$s', CurrencyPosition::RIGHT_SPACE ),
			'left with dash'           => array( '%1$s-%2$s', CurrencyPosition::LEFT ),
		);
	}

	/**
	 * @testdox Should fall back to the currency position option when the price format has no symbol placeholder.
	 */
	public function test_falls_back_to_option_without_symbol_placeholder(): void {
		update_option( 'woocommerce_currency_pos', CurrencyPosition::RIGHT );
		add_filter(
			'woocommerce_price_format',
			function () {
				return '%2$s';
			}
		);

		$this->assertSame( CurrencyPosition::RIGHT, CurrencySymbol::get_position(), 'A format without the symbol should not change the position' );
	}
}
