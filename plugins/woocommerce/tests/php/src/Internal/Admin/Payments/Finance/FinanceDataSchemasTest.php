<?php
declare( strict_types=1 );

namespace Automattic\WooCommerce\Tests\Internal\Admin\Payments\Finance;

use Automattic\WooCommerce\Admin\Payments\Finance\V1\Balance;
use Automattic\WooCommerce\Admin\Payments\Finance\FinanceDataPage;
use Automattic\WooCommerce\Admin\Payments\Finance\V1\Link;
use Automattic\WooCommerce\Admin\Payments\Finance\V1\Payout;
use Automattic\WooCommerce\Enums\PayoutStatus;
use Automattic\WooCommerce\Internal\Admin\Payments\Finance\FinanceDataSchemas;
use Automattic\WooCommerce\Internal\Admin\Payments\Finance\FinanceDataSerializer;
use WC_Unit_Test_Case;

/**
 * Tests for the FinanceDataSchemas class.
 */
class FinanceDataSchemasTest extends WC_Unit_Test_Case {

	/**
	 * The System Under Test.
	 *
	 * @var FinanceDataSchemas
	 */
	private FinanceDataSchemas $sut;

	/**
	 * Set up test fixtures.
	 */
	public function setUp(): void {
		parent::setUp();
		$this->sut = new FinanceDataSchemas();
	}

	/**
	 * @testdox Should only support version 1 of the balance and payouts data types.
	 *
	 * @testWith ["balance", 1, true]
	 *           ["payouts", 1, true]
	 *           ["balance", 2, false]
	 *           ["balance", 0, false]
	 *           ["payouts", 2, false]
	 *           ["payouts", 0, false]
	 *           ["disputes", 1, false]
	 *
	 * @param string $data_type The data type.
	 * @param int    $version   The version.
	 * @param bool   $expected  The expected.
	 */
	public function test_is_supported( string $data_type, int $version, bool $expected ): void {
		$this->assertSame( $expected, FinanceDataSchemas::is_supported( $data_type, $version ) );
	}

	/**
	 * @testdox Should describe the serialized balances response, including nulls and the payout link.
	 */
	public function test_serialized_balances_match_schema(): void {
		$page = new FinanceDataPage(
			array(
				( new Balance( 'USD', '1234.56' ) )
					->set_available_amount( '1000.00' )
					->set_payout_link( new Link( 'Deposit now', home_url( '/payouts' ) ) ),
				new Balance( 'EUR', '-5.00' ),
			)
		);

		$this->assert_matches_schema( ( new FinanceDataSerializer() )->serialize_page( $page, 1 ), $this->sut->get_balances_schema() );
	}

	/**
	 * @testdox Should describe the serialized payouts response, including nulls and cursors.
	 */
	public function test_serialized_payouts_match_schema(): void {
		$page = new FinanceDataPage(
			array(
				( new Payout( 'po_1', 'GBP', '250.00', PayoutStatus::COMPLETE, new \DateTimeImmutable( '2026-09-01T12:00:00+00:00' ) ) )
					->set_bank_account( 'Barclays ****1234' )
					->set_date_expected( new \DateTimeImmutable( '2026-09-03T00:00:00+00:00' ) )
					->set_provider_status( 'paid' ),
				new Payout( 'po_2', 'GBP', '10.00', PayoutStatus::PENDING, new \DateTimeImmutable( '2026-09-02T12:00:00+00:00' ) ),
			),
			true,
			'next-cursor',
			null
		);

		$this->assert_matches_schema( ( new FinanceDataSerializer() )->serialize_page( $page, 1 ), $this->sut->get_payouts_schema() );
	}

	/**
	 * @testdox Should describe the providers list response.
	 */
	public function test_providers_payload_matches_schema(): void {
		$payload = array(
			'providers' => array(
				array(
					'gateway_id' => 'woocommerce_payments',
					'title'      => 'WooPayments',
					'data_types' => array(
						array(
							'type'           => 'balance',
							'schema_version' => 1,
						),
						array(
							'type'           => 'payouts',
							'schema_version' => 1,
						),
					),
				),
			),
		);

		$this->assert_matches_schema( $payload, $this->sut->get_providers_schema() );
	}

	/**
	 * @testdox Should reject a payout item with an unknown status or an extra property.
	 *
	 * @testWith [{"status": "paid"}]
	 *           [{"surprise": true}]
	 *
	 * @param array $overrides The overrides.
	 */
	public function test_payout_schema_rejects_invalid_items( array $overrides ): void {
		$payout = ( new FinanceDataSerializer() )->serialize_payout(
			new Payout( 'po_1', 'USD', '1.00', PayoutStatus::PENDING, new \DateTimeImmutable( '2026-09-01T12:00:00+00:00' ) )
		);
		$data   = array(
			'schema_version' => 1,
			'items'          => array( array_merge( $payout, $overrides ) ),
			'has_more'       => false,
			'next_cursor'    => null,
			'prev_cursor'    => null,
		);

		$this->assertWPError( rest_validate_value_from_schema( $data, $this->sut->get_payouts_schema(), 'response' ) );
	}

	/**
	 * Assert that data validates against a schema, reporting the validation message on failure.
	 *
	 * @param array $data   The data.
	 * @param array $schema The schema.
	 */
	private function assert_matches_schema( array $data, array $schema ): void {
		$result = rest_validate_value_from_schema( $data, $schema, 'response' );

		$this->assertTrue( $result, is_wp_error( $result ) ? $result->get_error_message() : 'Validation did not return true.' );
	}
}
