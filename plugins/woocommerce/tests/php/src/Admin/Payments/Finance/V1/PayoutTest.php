<?php
declare( strict_types=1 );

namespace Automattic\WooCommerce\Tests\Admin\Payments\Finance\V1;

use Automattic\WooCommerce\Admin\Payments\Finance\V1\Payout;
use Automattic\WooCommerce\Enums\PayoutStatus;
use WC_Unit_Test_Case;

/**
 * Tests for the Payout value object.
 */
class PayoutTest extends WC_Unit_Test_Case {

	/**
	 * @testdox Should store the required fields and default the optional ones to null.
	 */
	public function test_stores_required_fields_and_defaults_optionals(): void {
		$initiated = new \DateTimeImmutable( '2026-09-01T12:00:00+00:00' );

		$sut = new Payout( 'po_123', 'gbp', '250.00', PayoutStatus::PENDING, $initiated );

		$this->assertSame( 'po_123', $sut->get_id() );
		$this->assertSame( 'GBP', $sut->get_currency() );
		$this->assertSame( '250.00', $sut->get_amount() );
		$this->assertSame( PayoutStatus::PENDING, $sut->get_status() );
		$this->assertSame( $initiated, $sut->get_date_initiated() );
		$this->assertNull( $sut->get_bank_account() );
		$this->assertNull( $sut->get_date_expected() );
		$this->assertNull( $sut->get_provider_status() );
	}

	/**
	 * @testdox Should reject an empty or whitespace-only id.
	 *
	 * @testWith [""]
	 *           ["   "]
	 *
	 * @param string $id The id.
	 */
	public function test_rejects_empty_id( string $id ): void {
		$this->expectException( \InvalidArgumentException::class );

		new Payout( $id, 'USD', '1.00', PayoutStatus::PENDING, new \DateTimeImmutable() );
	}

	/**
	 * @testdox Should reject statuses outside the PayoutStatus vocabulary.
	 *
	 * @testWith ["paid"]
	 *           ["PENDING"]
	 *           [""]
	 *
	 * @param string $status The status.
	 */
	public function test_rejects_unknown_status( string $status ): void {
		$this->expectException( \InvalidArgumentException::class );

		new Payout( 'po_1', 'USD', '1.00', $status, new \DateTimeImmutable() );
	}

	/**
	 * @testdox Should accept every PayoutStatus value.
	 *
	 * @testWith ["pending"]
	 *           ["complete"]
	 *           ["failed"]
	 *
	 * @param string $status The status.
	 */
	public function test_accepts_each_status( string $status ): void {
		$sut = new Payout( 'po_1', 'USD', '1.00', $status, new \DateTimeImmutable() );

		$this->assertSame( $status, $sut->get_status() );
	}

	/**
	 * @testdox Should trim optional strings and turn blank ones into null.
	 */
	public function test_normalizes_optional_strings(): void {
		$expected = new \DateTime( '2026-09-03T00:00:00+00:00' );
		$sut      = new Payout( 'po_1', 'USD', '1.00', PayoutStatus::COMPLETE, new \DateTimeImmutable() );

		$sut->set_bank_account( '  Chase ****1234  ' )->set_provider_status( 'in_transit' )->set_date_expected( $expected );

		$this->assertSame( 'Chase ****1234', $sut->get_bank_account() );
		$this->assertSame( 'in_transit', $sut->get_provider_status() );
		$this->assertSame( $expected, $sut->get_date_expected() );

		$sut->set_bank_account( '' )->set_provider_status( '   ' )->set_date_expected( null );

		$this->assertNull( $sut->get_bank_account() );
		$this->assertNull( $sut->get_provider_status() );
		$this->assertNull( $sut->get_date_expected() );
	}
}
