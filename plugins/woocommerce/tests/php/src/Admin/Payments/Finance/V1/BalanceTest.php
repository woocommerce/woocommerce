<?php
declare( strict_types=1 );

namespace Automattic\WooCommerce\Tests\Admin\Payments\Finance\V1;

use Automattic\WooCommerce\Admin\Payments\Finance\V1\Balance;
use Automattic\WooCommerce\Admin\Payments\Finance\V1\Link;
use WC_Unit_Test_Case;

/**
 * Tests for the Balance value object.
 */
class BalanceTest extends WC_Unit_Test_Case {

	/**
	 * @testdox Should keep the gateway id, uppercase the currency, keep the amount and default the optional fields to null.
	 */
	public function test_stores_gateway_id_normalizes_currency_and_defaults_optionals(): void {
		$sut = new Balance( 'mock_gateway', 'usd', '1234.56' );

		$this->assertSame( 'mock_gateway', $sut->get_gateway_id() );
		$this->assertSame( 'USD', $sut->get_currency() );
		$this->assertSame( '1234.56', $sut->get_amount() );
		$this->assertNull( $sut->get_available_amount() );
		$this->assertNull( $sut->get_payout_link() );
	}

	/**
	 * @testdox Should reject currencies that are not three letters.
	 *
	 * @testWith ["US"]
	 *           ["USDD"]
	 *           ["U$D"]
	 *           [""]
	 *
	 * @param string $currency The currency.
	 */
	public function test_rejects_invalid_currency( string $currency ): void {
		$this->expectException( \InvalidArgumentException::class );

		new Balance( 'mock', $currency, '1.00' );
	}

	/**
	 * @testdox Should reject amounts that are not plain decimal strings.
	 *
	 * @testWith ["1,234.56"]
	 *           ["12."]
	 *           [".5"]
	 *           ["abc"]
	 *           ["1e3"]
	 *           [" 12"]
	 *           [""]
	 *
	 * @param string $amount The amount.
	 */
	public function test_rejects_invalid_amount( string $amount ): void {
		$this->expectException( \InvalidArgumentException::class );

		new Balance( 'mock', 'USD', $amount );
	}

	/**
	 * @testdox Should accept integer, negative and fractional decimal amounts unchanged.
	 *
	 * @testWith ["0"]
	 *           ["-10"]
	 *           ["1234.56"]
	 *           ["0.005"]
	 *           ["-5.321"]
	 *
	 * @param string $amount The amount.
	 */
	public function test_accepts_valid_amounts( string $amount ): void {
		$sut = new Balance( 'mock', 'USD', $amount );

		$this->assertSame( $amount, $sut->get_amount() );
	}

	/**
	 * @testdox Should store the optional available amount and payout link, and clear them with null.
	 */
	public function test_sets_and_clears_optional_fields(): void {
		$link = new Link( 'Deposit now', 'https://example.com/payouts' );
		$sut  = new Balance( 'mock', 'EUR', '50.00' );

		$sut->set_available_amount( '10.00' )->set_payout_link( $link );

		$this->assertSame( '10.00', $sut->get_available_amount() );
		$this->assertSame( $link, $sut->get_payout_link() );

		$sut->set_available_amount( null )->set_payout_link( null );

		$this->assertNull( $sut->get_available_amount() );
		$this->assertNull( $sut->get_payout_link() );
	}

	/**
	 * @testdox Should reject an invalid available amount.
	 */
	public function test_rejects_invalid_available_amount(): void {
		$this->expectException( \InvalidArgumentException::class );

		( new Balance( 'mock', 'USD', '1.00' ) )->set_available_amount( 'ten' );
	}
}
