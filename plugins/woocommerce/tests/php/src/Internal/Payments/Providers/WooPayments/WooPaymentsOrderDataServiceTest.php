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
	 * Original store currency.
	 *
	 * @var string
	 */
	private string $original_currency;

	/**
	 * Set up test fixtures.
	 */
	public function setUp(): void {
		parent::setUp();
		$this->original_currency = (string) get_option( 'woocommerce_currency', 'USD' );
		$this->sut               = wc_get_container()->get( WooPaymentsOrderDataService::class );
	}

	/**
	 * Tear down test fixtures.
	 */
	public function tearDown(): void {
		update_option( 'woocommerce_currency', $this->original_currency );
		parent::tearDown();
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
	 * @testdox PaymentIntent fee-breakdown refresh is required for a non-FX envelope missing its fee rate.
	 */
	public function test_intent_needs_fee_breakdown_refresh_for_non_fx_envelope_without_rate(): void {
		$intent = $this->get_intent_with_non_fx_fee_breakdown();
		unset( $intent['charges']['data'][0]['fee_breakdown_v1']['totals']['fee']['rate'] );

		$this->assertTrue( $this->sut->intent_needs_fee_breakdown_refresh( $intent ) );
	}

	/**
	 * @testdox PaymentIntent fee-breakdown refresh is not required for a non-FX envelope with its fee rate.
	 */
	public function test_intent_does_not_need_fee_breakdown_refresh_for_non_fx_envelope_with_rate(): void {
		$this->assertFalse( $this->sut->intent_needs_fee_breakdown_refresh( $this->get_intent_with_non_fx_fee_breakdown() ) );
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
	 * @testdox Settlement exchange-rate meta should preserve Stripe conversion rates for converted-currency orders.
	 * @dataProvider settlement_exchange_rate_provider
	 *
	 * @param string $store_currency Store default currency.
	 * @param string $order_currency Order presentment currency.
	 * @param string $account_currency WooPayments account default currency.
	 * @param float  $provider_exchange_rate Stripe exchange rate.
	 * @param string $expected_exchange_rate Expected order meta exchange rate.
	 */
	public function test_get_settlement_exchange_rate_order_meta_preserves_provider_rate_for_converted_order( string $store_currency, string $order_currency, string $account_currency, float $provider_exchange_rate, string $expected_exchange_rate ): void {
		update_option( 'woocommerce_currency', $store_currency );
		$order = $this->create_order_with_currency( $order_currency );

		$meta = $this->sut->get_settlement_exchange_rate_order_meta(
			$order,
			array(
				'balance_transaction' => array(
					'exchange_rate' => $provider_exchange_rate,
				),
			),
			$account_currency
		);

		$this->assertSame(
			array(
				'_wcpay_multi_currency_stripe_exchange_rate' => $expected_exchange_rate,
			),
			$meta
		);
	}

	/**
	 * Data provider for settlement exchange-rate meta tests.
	 *
	 * @return array<string,array{string,string,string,float,string}>
	 */
	public function settlement_exchange_rate_provider(): array {
		return array(
			'two-decimal presentment and account currencies' => array( 'USD', 'GBP', 'usd', 1.33127, '1.33127' ),
			'zero-decimal presentment currency' => array( 'USD', 'JPY', 'usd', 0.63, '0.0063' ),
			'integer interpreted rate'          => array( 'USD', 'JPY', 'usd', 1000.0, '10' ),
			'zero-decimal account currency'     => array( 'JPY', 'USD', 'jpy', 0.0063, '0.63' ),
		);
	}

	/**
	 * @testdox Settlement exchange-rate meta should be omitted when store and account default currencies differ.
	 */
	public function test_get_settlement_exchange_rate_order_meta_skips_provider_rate_when_store_and_account_defaults_differ(): void {
		update_option( 'woocommerce_currency', 'EUR' );
		$order = $this->create_order_with_currency( 'GBP' );

		$meta = $this->sut->get_settlement_exchange_rate_order_meta(
			$order,
			array(
				'balance_transaction' => array(
					'exchange_rate' => 1.33127,
				),
			),
			'usd'
		);

		$this->assertSame( array(), $meta );
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
	 * Get a PaymentIntent envelope with a non-FX fee breakdown.
	 *
	 * @return array<string,mixed>
	 */
	private function get_intent_with_non_fx_fee_breakdown(): array {
		return array(
			'id'      => 'pi_123',
			'charges' => array(
				'data' => array(
					array_diff_key( $this->get_non_fx_captured_timeline_event(), array( 'type' => true ) ),
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

	/**
	 * Create an order in the given currency.
	 *
	 * @param string $currency Currency code.
	 * @return WC_Order
	 */
	private function create_order_with_currency( string $currency ): WC_Order {
		$order = wc_create_order();
		$this->assertInstanceOf( WC_Order::class, $order );
		$order->set_currency( $currency );
		$order->save();

		return $order;
	}
}
