<?php
declare( strict_types = 1 );

namespace Automattic\WooCommerce\Tests\Internal\Payments\Providers\WooPayments;

use Automattic\WooCommerce\Internal\Payments\Providers\WooPayments\WooPaymentsOrderDataService;
use WC_Order;
use WC_Unit_Test_Case;

/**
 * Tests for the WooPaymentsOrderDataService class.
 */
class WooPaymentsOrderDataServiceTest extends WC_Unit_Test_Case {

	/**
	 * The System Under Test.
	 *
	 * @var WooPaymentsOrderDataService
	 */
	private WooPaymentsOrderDataService $sut;

	/**
	 * Set up test fixtures.
	 */
	public function setUp(): void {
		parent::setUp();
		$this->sut = wc_get_container()->get( WooPaymentsOrderDataService::class );
	}

	/**
	 * @testdox Billing details preserve the WooPayments order-to-Stripe address shape.
	 */
	public function test_get_billing_data_from_order_preserves_woopayments_shape(): void {
		$order = wc_create_order();
		$this->assertInstanceOf( WC_Order::class, $order );

		$order->set_billing_first_name( 'Ada' );
		$order->set_billing_last_name( 'Lovelace' );
		$order->set_billing_email( 'ada@example.com' );
		$order->set_billing_phone( '+15555550123' );
		$order->set_billing_address_1( '1 Main St' );
		$order->set_billing_address_2( 'Suite 2' );
		$order->set_billing_city( 'Austin' );
		$order->set_billing_state( 'TX' );
		$order->set_billing_postcode( '78701' );
		$order->set_billing_country( 'US' );

		$this->assertSame(
			array(
				'address' => array(
					'country'     => 'US',
					'line1'       => '1 Main St',
					'line2'       => 'Suite 2',
					'city'        => 'Austin',
					'state'       => 'TX',
					'postal_code' => '78701',
				),
				'email'   => 'ada@example.com',
				'phone'   => '+15555550123',
				'name'    => 'Ada Lovelace',
			),
			$this->sut->get_billing_data_from_order( $order )
		);
	}

	/**
	 * @testdox Fee-breakdown notes render from a PaymentIntent envelope.
	 */
	public function test_get_fee_breakdown_note_from_intent_renders_native_charge_envelope(): void {
		$this->assertSame( $this->get_expected_fee_note(), $this->sut->get_fee_breakdown_note_from_intent( $this->get_intent_with_fee_breakdown() ) );
	}

	/**
	 * @testdox PaymentIntent fee-breakdown notes preserve the newest-first charge order used by webhooks.
	 */
	public function test_get_fee_breakdown_note_from_intent_uses_first_charge(): void {
		$intent = array(
			'id'      => 'pi_123',
			'charges' => array(
				'data' => array(
					$this->get_charge_with_fee_breakdown(),
					$this->get_charge_with_fee_breakdown( 999, 1234, 5432 ),
				),
			),
		);

		$this->assertSame( $this->get_expected_fee_note(), $this->sut->get_fee_breakdown_note_from_intent( $intent ) );
	}

	/**
	 * @testdox PaymentIntent fee-breakdown notes can preserve the API-client latest-charge selector.
	 */
	public function test_get_fee_breakdown_note_from_intent_can_use_last_charge(): void {
		$intent = array(
			'id'      => 'pi_123',
			'charges' => array(
				'data' => array(
					$this->get_charge_with_fee_breakdown( 999, 1234, 5432 ),
					$this->get_charge_with_fee_breakdown(),
				),
			),
		);

		$this->assertSame( $this->get_expected_fee_note(), $this->sut->get_fee_breakdown_note_from_intent( $intent, false ) );
	}

	/**
	 * @testdox Fee-breakdown notes render from a captured timeline event.
	 */
	public function test_get_fee_breakdown_note_from_timeline_event_renders_captured_event(): void {
		$this->assertSame( $this->get_expected_fee_note(), $this->sut->get_fee_breakdown_note_from_timeline_event( $this->get_captured_timeline_event() ) );
	}

	/**
	 * @testdox Fee-breakdown notes render a non-FX captured-event fee rate when provided by the platform.
	 */
	public function test_get_fee_breakdown_note_from_timeline_event_renders_non_fx_rate(): void {
		$this->assertSame(
			'<strong>Fee details:</strong><div class="captured-event-details">' . PHP_EOL
			. '<p>Fee (2.9% + $0.30): $1.75 USD</p>' . PHP_EOL
			. '<p>Net payout: $48.25 USD</p>' . PHP_EOL
			. '</div>',
			$this->sut->get_fee_breakdown_note_from_timeline_event( $this->get_non_fx_captured_timeline_event() )
		);
	}

	/**
	 * @testdox Fee-breakdown notes are not duplicated on the same order.
	 */
	public function test_add_fee_breakdown_note_from_timeline_event_does_not_duplicate_notes(): void {
		$order = wc_create_order();
		$this->assertInstanceOf( WC_Order::class, $order );
		$order->save();

		$this->assertTrue( $this->sut->add_fee_breakdown_note_from_timeline_event( $order, $this->get_captured_timeline_event() ) );
		$this->assertFalse( $this->sut->add_fee_breakdown_note_from_timeline_event( $order, $this->get_captured_timeline_event() ) );

		$matching_notes = array_filter(
			wc_get_order_notes( array( 'order_id' => $order->get_id() ) ),
			fn( $note ): bool => $note->content === $this->get_expected_fee_note()
		);

		$this->assertCount( 1, $matching_notes );
	}

	/**
	 * Get a PaymentIntent envelope with a fee breakdown.
	 *
	 * @return array<string,mixed>
	 */
	private function get_intent_with_fee_breakdown(): array {
		return array(
			'id'      => 'pi_123',
			'charges' => array(
				'data' => array(
					$this->get_charge_with_fee_breakdown(),
				),
			),
		);
	}

	/**
	 * Get a captured timeline event with a fee breakdown.
	 *
	 * @return array<string,mixed>
	 */
	private function get_captured_timeline_event(): array {
		return array_merge(
			array( 'type' => 'captured' ),
			$this->get_charge_with_fee_breakdown()
		);
	}

	/**
	 * Get a non-FX captured timeline event with a fee rate.
	 *
	 * @return array<string,mixed>
	 */
	private function get_non_fx_captured_timeline_event(): array {
		return array(
			'type'             => 'captured',
			'fee_breakdown_v1' => array(
				'rows'   => array(
					array(
						'key'      => 'base',
						'kind'     => 'fee',
						'amount'   => 175,
						'currency' => 'usd',
						'rate'     => array(
							'percentage'     => 0.029,
							'fixed'          => 30,
							'fixed_currency' => 'usd',
						),
					),
				),
				'totals' => array(
					'fee'         => array(
						'amount'   => 175,
						'currency' => 'usd',
						'rate'     => array(
							'percentage'     => 0.029,
							'fixed'          => 30,
							'fixed_currency' => 'usd',
						),
					),
					'net'         => array(
						'amount'   => 4825,
						'currency' => 'usd',
					),
					'capture_net' => array(
						'amount'   => 4825,
						'currency' => 'usd',
					),
				),
			),
		);
	}

	/**
	 * Get a charge-like payload with a fee breakdown.
	 *
	 * @param int $fee_amount Fee amount in minor units.
	 * @param int $net_amount Net amount in minor units.
	 * @param int $to_amount  Total amount in minor units.
	 * @return array<string,mixed>
	 */
	private function get_charge_with_fee_breakdown( int $fee_amount = 293, int $net_amount = 6422, int $to_amount = 6715 ): array {
		return array(
			'id'               => 'ch_123',
			'fee_breakdown_v1' => array(
				'totals'  => array(
					'fee'         => array(
						'amount'   => $fee_amount,
						'currency' => 'usd',
					),
					'net'         => array(
						'amount'   => $net_amount,
						'currency' => 'usd',
					),
					'capture_net' => array(
						'amount'   => $net_amount,
						'currency' => 'usd',
					),
				),
				'fx'      => array(
					'from_currency' => 'gbp',
					'to_currency'   => 'usd',
					'from_amount'   => 5000,
					'to_amount'     => $to_amount,
				),
				'sources' => array(
					'balance_transaction_exchange_rate' => 1.3428,
				),
			),
		);
	}

	/**
	 * Get the expected fee note HTML.
	 *
	 * @return string
	 */
	private function get_expected_fee_note(): string {
		return '<strong>Fee details:</strong><div class="captured-event-details">' . PHP_EOL
			. '<p>1.00 GBP → 1.3428 USD: $67.15 USD</p>' . PHP_EOL
			. '<p>Fee (3.9% + $0.30): $2.93 USD</p>' . PHP_EOL
			. '<p>&nbsp;&nbsp;&nbsp;&nbsp;Base fee: 2.9% + $0.30</p>' . PHP_EOL
			. '<p>&nbsp;&nbsp;&nbsp;&nbsp;Currency conversion fee: 1%</p>' . PHP_EOL
			. '<p>Net payout: $64.22 USD</p>' . PHP_EOL
			. '</div>';
	}
}
