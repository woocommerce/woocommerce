<?php
declare( strict_types=1 );

namespace Automattic\WooCommerce\Tests\Internal\Admin\Payments\Finance;

use Automattic\WooCommerce\Admin\Payments\Finance\V1\Balance;
use Automattic\WooCommerce\Admin\Payments\Finance\FinanceDataPage;
use Automattic\WooCommerce\Admin\Payments\Finance\V1\Link;
use Automattic\WooCommerce\Admin\Payments\Finance\V1\Payout;
use Automattic\WooCommerce\Enums\PayoutStatus;
use Automattic\WooCommerce\Internal\Admin\Payments\Finance\FinanceDataSerializer;
use WC_Unit_Test_Case;

/**
 * Tests for the FinanceDataSerializer class.
 */
class FinanceDataSerializerTest extends WC_Unit_Test_Case {

	/**
	 * The System Under Test.
	 *
	 * @var FinanceDataSerializer
	 */
	private FinanceDataSerializer $sut;

	/**
	 * Set up test fixtures.
	 */
	public function setUp(): void {
		parent::setUp();
		$this->sut = new FinanceDataSerializer();
	}

	/**
	 * @testdox Should serialize a page into the envelope with the version, items and pagination state.
	 */
	public function test_serializes_page_envelope(): void {
		$page = new FinanceDataPage( array( new Balance( 'mock', 'USD', '1.00' ) ), true, 'next-cursor', 'prev-cursor' );

		$result = $this->sut->serialize_page( $page, 1 );

		$this->assertSame( array( 'schema_version', 'items', 'has_more', 'next_cursor', 'prev_cursor' ), array_keys( $result ) );
		$this->assertSame( 1, $result['schema_version'] );
		$this->assertTrue( $result['has_more'] );
		$this->assertSame( 'next-cursor', $result['next_cursor'] );
		$this->assertSame( 'prev-cursor', $result['prev_cursor'] );
		$this->assertCount( 1, $result['items'] );
		$this->assertSame( 'USD', $result['items'][0]['currency'] );
	}

	/**
	 * @testdox Should serialize a balance with every optional field set.
	 */
	public function test_serializes_balance_with_optionals(): void {
		$balance = ( new Balance( 'mock_gateway', 'usd', '1234.56' ) )
			->set_available_amount( '1000.00' )
			->set_payout_link( new Link( 'Deposit now', 'https://example.com/payouts' ) );

		$this->assertSame(
			array(
				'provider_id'      => 'mock_gateway',
				'currency'         => 'USD',
				'amount'           => '1234.56',
				'available_amount' => '1000.00',
				'payout_link'      => array(
					'title' => 'Deposit now',
					'url'   => 'https://example.com/payouts',
				),
			),
			$this->sut->serialize_balance( $balance )
		);
	}

	/**
	 * @testdox Should serialize a balance without optional fields as nulls.
	 */
	public function test_serializes_balance_without_optionals(): void {
		$this->assertSame(
			array(
				'provider_id'      => 'mock_gateway',
				'currency'         => 'EUR',
				'amount'           => '-5.00',
				'available_amount' => null,
				'payout_link'      => null,
			),
			$this->sut->serialize_balance( new Balance( 'mock_gateway', 'EUR', '-5.00' ) )
		);
	}

	/**
	 * @testdox Should serialize a payout with every field set.
	 */
	public function test_serializes_payout_with_optionals(): void {
		$payout = ( new Payout( 'mock_gateway', 'po_1', 'gbp', '250.00', PayoutStatus::COMPLETE, new \DateTimeImmutable( '2026-09-01T12:00:00+00:00' ), new \DateTimeImmutable( '2026-09-03T00:00:00+00:00' ), 'paid' ) )
			->set_bank_account( 'Barclays ****1234' );

		$this->assertSame(
			array(
				'provider_id'     => 'mock_gateway',
				'id'              => 'po_1',
				'currency'        => 'GBP',
				'amount'          => '250.00',
				'bank_account'    => 'Barclays ****1234',
				'date_initiated'  => '2026-09-01T12:00:00+00:00',
				'date_expected'   => '2026-09-03T00:00:00+00:00',
				'status'          => 'complete',
				'provider_status' => 'paid',
			),
			$this->sut->serialize_payout( $payout )
		);
	}

	/**
	 * @testdox Should serialize a payout without optional fields as nulls.
	 */
	public function test_serializes_payout_without_optionals(): void {
		$payout = new Payout( 'mock', 'po_2', 'USD', '10.00', PayoutStatus::PENDING, new \DateTimeImmutable( '2026-09-01T12:00:00+00:00' ) );

		$result = $this->sut->serialize_payout( $payout );

		$this->assertNull( $result['bank_account'] );
		$this->assertNull( $result['date_expected'] );
		$this->assertNull( $result['provider_status'] );
	}

	/**
	 * @testdox Should serialize dates from any timezone and date class as RFC 3339 in UTC.
	 *
	 * @dataProvider dates_provider
	 *
	 * @param \DateTimeInterface $date     The date.
	 * @param string             $expected The expected serialized value.
	 */
	public function test_serializes_dates_in_utc( \DateTimeInterface $date, string $expected ): void {
		$payout = new Payout( 'mock', 'po_1', 'USD', '1.00', PayoutStatus::PENDING, $date );

		$this->assertSame( $expected, $this->sut->serialize_payout( $payout )['date_initiated'] );
	}

	/**
	 * Dates in several timezones and classes with their UTC serialization.
	 *
	 * @return array
	 */
	public function dates_provider(): array {
		return array(
			'DateTime in New York'       => array( new \DateTime( '2026-09-01 08:00:00', new \DateTimeZone( 'America/New_York' ) ), '2026-09-01T12:00:00+00:00' ),
			'DateTimeImmutable in Tokyo' => array( new \DateTimeImmutable( '2026-09-02 09:00:00', new \DateTimeZone( 'Asia/Tokyo' ) ), '2026-09-02T00:00:00+00:00' ),
			'WC_DateTime in UTC'         => array( new \WC_DateTime( '2026-09-01 12:00:00', new \DateTimeZone( 'UTC' ) ), '2026-09-01T12:00:00+00:00' ),
		);
	}

	/**
	 * @testdox Should reject page items that are neither balances nor payouts.
	 */
	public function test_rejects_unknown_item_types(): void {
		$this->expectException( \InvalidArgumentException::class );

		$this->sut->serialize_page( new FinanceDataPage( array( new \stdClass() ) ), 1 );
	}
}
