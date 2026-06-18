<?php
declare( strict_types = 1 );

namespace Automattic\WooCommerce\Tests\Internal\Payments\Providers\WooPayments;

use Automattic\WooCommerce\Internal\Admin\Settings\Utils;
use Automattic\WooCommerce\Internal\Payments\OrderPaymentStore;
use Automattic\WooCommerce\Internal\Payments\OrderPaymentLifecycleService;
use Automattic\WooCommerce\Internal\Payments\Providers\WooPayments\Api\WooPaymentsApiClient;
use Automattic\WooCommerce\Internal\Payments\Providers\WooPayments\WooPaymentsAccountService;
use Automattic\WooCommerce\Internal\Payments\Providers\WooPayments\WooPaymentsEventIngestor;
use Automattic\WooCommerce\Internal\Payments\Providers\WooPayments\WooPaymentsLegacyRuntime;
use Automattic\WooCommerce\Internal\Payments\Providers\WooPayments\WooPaymentsRemoteNoteService;
use Automattic\WooCommerce\Internal\Payments\Providers\WooPayments\WooPaymentsTokenService;
use Automattic\WooCommerce\Admin\Notes\Note;
use Automattic\WooCommerce\Admin\Notes\Notes;
use Automattic\WooCommerce\Proxies\LegacyProxy;
use Exception;
use InvalidArgumentException;
use RuntimeException;
use WC_Order;
use WC_Order_Refund;
use WC_Unit_Test_Case;

/**
 * Tests for the WooPaymentsEventIngestor class.
 */
class WooPaymentsEventIngestorTest extends WC_Unit_Test_Case {

	/**
	 * The System Under Test.
	 *
	 * @var WooPaymentsEventIngestor
	 */
	private $sut;

	/**
	 * Last refund charge ID created by the refundable order fixture.
	 *
	 * @var string
	 */
	private string $last_refund_charge_id = 'ch_123';

	/**
	 * Set up test fixtures.
	 */
	public function setUp(): void {
		parent::setUp();
		$this->sut                   = wc_get_container()->get( WooPaymentsEventIngestor::class );
		$this->last_refund_charge_id = 'ch_123';
	}

	/**
	 * Tear down test fixtures.
	 */
	public function tearDown(): void {
		remove_all_filters( WooPaymentsEventIngestor::FILTER_LIVE_MODE );
		remove_all_actions( 'woocommerce_payments_before_webhook_delivery' );
		remove_all_actions( 'woocommerce_payments_after_webhook_delivery' );
		$this->delete_dispute_cache_options();
		delete_option( 'wcpay_account_data' );
		delete_option( 'wcpay_multi_currency_enabled_currencies' );
		delete_option( 'woocommerce_woocommerce_payments_settings' );
		delete_option( 'wcpay_onboarding_test_mode' );
		delete_option( '_wcpay_onboarding_stripe_connected' );
		delete_option( 'woocommerce_woopayments_nox_profile' );
		delete_option( 'woocommerce_woopayments_nox_onboarding_locked' );
		delete_option( 'wcpay_account_deletion_pending_id' );
		parent::tearDown();
	}

	/**
	 * @testdox payment_intent.succeeded completes the order.
	 */
	public function test_payment_intent_succeeded_completes_order(): void {
		$order = $this->create_woopayments_order();

		$this->sut->process( $this->create_payment_intent_event( 'payment_intent.succeeded', $order ) );

		$order = wc_get_order( $order->get_id() );

		$this->assertInstanceOf( WC_Order::class, $order );
		$this->assertSame( 'completed', $order->get_status() );
		$this->assertSame( 'pi_123', $order->get_transaction_id() );
		$this->assertSame( 'pi_123', $order->get_meta( '_intent_id', true ) );
		$this->assertSame( 'ch_123', $order->get_meta( '_charge_id', true ) );
		$this->assertSame( 'pm_123', $order->get_meta( '_payment_method_id', true ) );
		$this->assertSame( 'succeeded', $order->get_meta( '_intention_status', true ) );
		$this->assertSame( 'usd', $order->get_meta( '_wcpay_intent_currency', true ) );
		$this->assertSame( 'mandate_123', $order->get_meta( '_stripe_mandate_id', true ) );
		$this->assertSame( '1.23', $order->get_meta( '_wcpay_transaction_fee', true ) );
		$this->assertSame( '11.11', $order->get_meta( '_wcpay_net', true ) );
		$this->assertSame( 'mobile_pos', $order->get_meta( '_wcpay_ipp_channel', true ) );
	}

	/**
	 * @testdox payment_intent.succeeded does not add a generic completion note to an already paid order.
	 */
	public function test_payment_intent_succeeded_does_not_add_generic_completion_note_to_paid_order(): void {
		$order = $this->create_woopayments_order();
		$order->set_payment_method_title( 'Visa credit card' );
		$order->set_transaction_id( 'pi_123' );
		$order->set_status( 'processing' );
		$order->update_meta_data( '_intent_id', 'pi_123' );
		$order->update_meta_data( '_intention_status', 'succeeded' );
		$order->save();
		$order->add_order_note( 'A test payment of $10.00 USD was processed using WooPayments in <strong>test mode</strong> (<a>pi_123</a>). No real funds were collected.' );

		$this->sut->process( $this->create_payment_intent_event( 'payment_intent.succeeded', $order ) );

		$order_id = $order->get_id();
		$order    = wc_get_order( $order->get_id() );
		$notes    = array_map(
			static fn( $note ): string => (string) $note->content,
			wc_get_order_notes( array( 'order_id' => $order_id ) )
		);

		$this->assertInstanceOf( WC_Order::class, $order );
		$this->assertSame( 'pi_123', $order->get_meta( '_intent_id', true ) );
		$this->assertSame( '1.23', $order->get_meta( '_wcpay_transaction_fee', true ) );
		$this->assertNotContains( 'Payment complete.', $notes );
	}

	/**
	 * @testdox payment_intent.succeeded adds fee-breakdown details from the native charge envelope.
	 */
	public function test_payment_intent_succeeded_adds_fee_breakdown_details_note(): void {
		$order = $this->create_woopayments_order();
		$order->set_transaction_id( 'pi_123' );
		$order->set_status( 'processing' );
		$order->update_meta_data( '_intent_id', 'pi_123' );
		$order->save();

		$this->sut->process(
			$this->create_payment_intent_event(
				'payment_intent.succeeded',
				$order,
				array(
					'amount'  => 5000,
					'charges' => array(
						'data' => array(
							array(
								'id'                     => 'ch_123',
								'payment_method'         => 'pm_123',
								'application_fee_amount' => 218,
								'payment_method_details' => array(
									'card' => array(
										'mandate' => 'mandate_123',
									),
								),
								'fee_breakdown_v1'       => array(
									'totals'  => array(
										'fee'         => array(
											'amount'   => 293,
											'currency' => 'usd',
										),
										'tax'         => array(
											'amount'   => 0,
											'currency' => 'usd',
										),
										'net'         => array(
											'amount'   => 6422,
											'currency' => 'usd',
										),
										'capture_net' => array(
											'amount'   => 6422,
											'currency' => 'usd',
										),
										'gross'       => array(
											'amount'   => 6715,
											'currency' => 'usd',
										),
									),
									'fx'      => array(
										'from_currency' => 'gbp',
										'to_currency'   => 'usd',
										'from_amount'   => 5000,
										'to_amount'     => 6715,
									),
									'sources' => array(
										'balance_transaction_exchange_rate' => 1.3428,
									),
								),
							),
						),
					),
				)
			)
		);

		$order = wc_get_order( $order->get_id() );

		$this->assertInstanceOf( WC_Order::class, $order );
		$this->assertOrderHasNote(
			$order,
			'<strong>Fee details:</strong><div class="captured-event-details">' . PHP_EOL
			. '<p>1.00 GBP → 1.3428 USD: $67.15 USD</p>' . PHP_EOL
			. '<p>Fee (3.9% + $0.30): $2.93 USD</p>' . PHP_EOL
			. '<p>&nbsp;&nbsp;&nbsp;&nbsp;Base fee: 2.9% + $0.30</p>' . PHP_EOL
			. '<p>&nbsp;&nbsp;&nbsp;&nbsp;Currency conversion fee: 1%</p>' . PHP_EOL
			. '<p>Net payout: $64.22 USD</p>' . PHP_EOL
			. '</div>'
		);
	}

	/**
	 * @testdox payment_intent.succeeded fetches fee-breakdown details when the webhook envelope is stale.
	 */
	public function test_payment_intent_succeeded_fetches_fee_breakdown_details_when_webhook_envelope_is_stale(): void {
		$order = $this->create_woopayments_order();
		$order->set_payment_method_title( 'Visa credit card' );
		$order->set_transaction_id( 'pi_123' );
		$order->set_status( 'processing' );
		$order->update_meta_data( '_intent_id', 'pi_123' );
		$order->update_meta_data( '_intention_status', 'succeeded' );
		$order->save();

		$api_client = new class() extends WooPaymentsApiClient {
			/**
			 * Requested intent IDs.
			 *
			 * @var string[]
			 */
			public array $requested_intents = array();

			/**
			 * Requested timeline IDs.
			 *
			 * @var string[]
			 */
			public array $requested_timelines = array();

			/**
			 * Retrieve a WooPayments PaymentIntent.
			 *
			 * @param string $intent_id Intent ID.
			 * @return array<string,mixed>
			 */
			public function get_payment_intention( string $intent_id ): array {
				$this->requested_intents[] = $intent_id;

				return array(
					'id'      => $intent_id,
					'charges' => array(
						'data' => array(
							array(
								'id'               => 'ch_123',
								'fee_breakdown_v1' => array(
									'totals'  => array(
										'fee'         => array(
											'amount'   => 293,
											'currency' => 'usd',
										),
										'net'         => array(
											'amount'   => 6421,
											'currency' => 'usd',
										),
										'capture_net' => array(
											'amount'   => 6421,
											'currency' => 'usd',
										),
									),
									'fx'      => array(
										'from_currency' => 'gbp',
										'to_currency'   => 'usd',
										'from_amount'   => 5000,
										'to_amount'     => 6714,
									),
									'sources' => array(
										'balance_transaction_exchange_rate' => 1.34274,
									),
								),
							),
						),
					),
				);
			}
		};

		$sut = new WooPaymentsEventIngestor();
		$sut->init(
			wc_get_container()->get( OrderPaymentLifecycleService::class ),
			new LegacyProxy(),
			new WooPaymentsLegacyRuntime(),
			$api_client
		);

		$sut->process( $this->create_payment_intent_event( 'payment_intent.succeeded', $order ) );

		$order = wc_get_order( $order->get_id() );

		$this->assertInstanceOf( WC_Order::class, $order );
		$this->assertSame( array( 'pi_123' ), $api_client->requested_intents );
		$this->assertOrderHasNote(
			$order,
			'<strong>Fee details:</strong><div class="captured-event-details">' . PHP_EOL
			. '<p>1.00 GBP → 1.34274 USD: $67.14 USD</p>' . PHP_EOL
			. '<p>Fee (3.9% + $0.30): $2.93 USD</p>' . PHP_EOL
			. '<p>&nbsp;&nbsp;&nbsp;&nbsp;Base fee: 2.9% + $0.30</p>' . PHP_EOL
			. '<p>&nbsp;&nbsp;&nbsp;&nbsp;Currency conversion fee: 1%</p>' . PHP_EOL
			. '<p>Net payout: $64.21 USD</p>' . PHP_EOL
			. '</div>'
		);
	}

	/**
	 * @testdox payment_intent.succeeded refreshes non-FX fee-breakdown details when the webhook envelope is missing the fee rate.
	 */
	public function test_payment_intent_succeeded_fetches_non_fx_fee_rate_when_webhook_envelope_is_stale(): void {
		$order = $this->create_woopayments_order();
		$order->set_payment_method_title( 'Visa credit card' );
		$order->set_transaction_id( 'pi_123' );
		$order->set_status( 'processing' );
		$order->update_meta_data( '_intent_id', 'pi_123' );
		$order->update_meta_data( '_intention_status', 'succeeded' );
		$order->save();

		$api_client = new class() extends WooPaymentsApiClient {
			/**
			 * Requested intent IDs.
			 *
			 * @var string[]
			 */
			public array $requested_intents = array();

			/**
			 * Retrieve a WooPayments PaymentIntent.
			 *
			 * @param string $intent_id Intent ID.
			 * @return array<string,mixed>
			 */
			public function get_payment_intention( string $intent_id ): array {
				$this->requested_intents[] = $intent_id;

				return array(
					'id'      => $intent_id,
					'charges' => array(
						'data' => array(
							array(
								'id'               => 'ch_123',
								'fee_breakdown_v1' => array(
									'totals' => array(
										'fee'         => array(
											'amount'   => 175,
											'currency' => 'usd',
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
							),
						),
					),
				);
			}

			/**
			 * Retrieve a WooPayments timeline.
			 *
			 * @param string $id Payment intent ID.
			 * @return array<string,mixed>
			 */
			public function get_timeline( string $id ): array {
				$this->requested_timelines[] = $id;

				return array(
					'data' => array(
						array(
							'type'             => 'captured',
							'fee_breakdown_v1' => array(
								'totals' => array(
									'fee'         => array(
										'amount'   => 175,
										'currency' => 'usd',
										'rate'     => array(
											'percentage' => 0.029,
											'fixed'      => 30,
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
						),
					),
				);
			}
		};

		$sut = new WooPaymentsEventIngestor();
		$sut->init(
			wc_get_container()->get( OrderPaymentLifecycleService::class ),
			new LegacyProxy(),
			new WooPaymentsLegacyRuntime(),
			$api_client
		);

		$sut->process(
			$this->create_payment_intent_event(
				'payment_intent.succeeded',
				$order,
				array(
					'charges' => array(
						'data' => array(
							array(
								'id'               => 'ch_123',
								'fee_breakdown_v1' => array(
									'totals' => array(
										'fee'         => array(
											'amount'   => 175,
											'currency' => 'usd',
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
							),
						),
					),
				)
			)
		);

		$order = wc_get_order( $order->get_id() );

		$this->assertInstanceOf( WC_Order::class, $order );
		$this->assertSame( array( 'pi_123' ), $api_client->requested_intents );
		$this->assertSame( array( 'pi_123' ), $api_client->requested_timelines );
		$this->assertOrderHasNote(
			$order,
			'<strong>Fee details:</strong><div class="captured-event-details">' . PHP_EOL
			. '<p>Fee (2.9% + $0.30): $1.75 USD</p>' . PHP_EOL
			. '<p>Net payout: $48.25 USD</p>' . PHP_EOL
			. '</div>'
		);
	}

	/**
	 * @testdox payment_intent.succeeded can resolve an order from existing intent meta.
	 */
	public function test_payment_intent_succeeded_resolves_order_from_existing_intent_meta(): void {
		$order = $this->create_woopayments_order();
		$order->update_meta_data( '_intent_id', 'pi_123' );
		$order->save();

		$this->sut->process(
			$this->create_payment_intent_event(
				'payment_intent.succeeded',
				$order,
				array(
					'metadata' => array(
						'order_id'  => null,
						'order_key' => null,
					),
				)
			)
		);

		$order = wc_get_order( $order->get_id() );

		$this->assertInstanceOf( WC_Order::class, $order );
		$this->assertSame( 'completed', $order->get_status() );
	}

	/**
	 * @testdox payment_intent.payment_failed marks the order failed.
	 */
	public function test_payment_intent_failed_marks_order_failed(): void {
		$order = $this->create_woopayments_order();

		$this->sut->process(
			$this->create_payment_intent_event(
				'payment_intent.payment_failed',
				$order,
				array(
					'status'             => 'requires_payment_method',
					'last_payment_error' => array(
						'payment_method' => array(
							'id'   => 'pm_123',
							'type' => 'card',
						),
					),
				)
			)
		);

		$order = wc_get_order( $order->get_id() );

		$this->assertInstanceOf( WC_Order::class, $order );
		$this->assertSame( 'failed', $order->get_status() );
		$this->assertSame( 'pi_123', $order->get_meta( '_intent_id', true ) );
		$this->assertSame( 'requires_payment_method', $order->get_meta( '_intention_status', true ) );
	}

	/**
	 * @testdox payment_intent.payment_failed ignores non-actionable payment methods.
	 */
	public function test_payment_intent_failed_ignores_non_actionable_payment_methods(): void {
		$order = $this->create_woopayments_order();

		$this->sut->process(
			$this->create_payment_intent_event(
				'payment_intent.payment_failed',
				$order,
				array(
					'status'             => 'requires_payment_method',
					'last_payment_error' => array(
						'payment_method' => array(
							'id'   => 'pm_123',
							'type' => 'klarna',
						),
					),
				)
			)
		);

		$order = wc_get_order( $order->get_id() );

		$this->assertInstanceOf( WC_Order::class, $order );
		$this->assertSame( 'pending', $order->get_status() );
	}

	/**
	 * @testdox payment_intent.canceled is a successful order no-op.
	 */
	public function test_payment_intent_canceled_is_successful_order_noop(): void {
		$order = $this->create_woopayments_order();
		$order->update_meta_data( '_wcpay_transaction_fee', '100' );
		$order->update_meta_data( '_wcpay_net', '900' );
		$order->save();

		$this->sut->process(
			$this->create_payment_intent_event(
				'payment_intent.canceled',
				$order,
				array(
					'status' => 'canceled',
				)
			)
		);

		$order = wc_get_order( $order->get_id() );

		$this->assertInstanceOf( WC_Order::class, $order );
		$this->assertSame( 'pending', $order->get_status() );
		$this->assertSame( '', $order->get_meta( '_intention_status', true ) );
		$this->assertSame( '100', $order->get_meta( '_wcpay_transaction_fee', true ) );
		$this->assertSame( '900', $order->get_meta( '_wcpay_net', true ) );
	}

	/**
	 * @testdox charge.expired marks the order failed.
	 */
	public function test_charge_expired_marks_order_failed(): void {
		$order = $this->create_woopayments_order();
		$order->update_meta_data( '_charge_id', 'ch_expired' );
		$order->save();

		$this->sut->process(
			array(
				'id'   => 'evt_expired',
				'type' => 'charge.expired',
				'data' => array(
					'object' => array(
						'id' => 'ch_expired',
					),
				),
			)
		);

		$order = wc_get_order( $order->get_id() );

		$this->assertInstanceOf( WC_Order::class, $order );
		$this->assertSame( 'failed', $order->get_status() );
		$this->assertSame( 'ch_expired', $order->get_meta( '_charge_id', true ) );
	}

	/**
	 * @testdox charge.dispute.created resolves the order by charge ID and places it on hold.
	 */
	public function test_dispute_created_marks_order_on_hold(): void {
		$order = $this->create_woopayments_order();
		$order->set_status( 'processing' );
		$order->update_meta_data( '_charge_id', 'ch_123' );
		$order->update_meta_data( '_wcpay_payment_transaction_id', 'txn_123' );
		$order->save();

		$this->sut->process( $this->create_dispute_event( 'charge.dispute.created', 'needs_response' ) );

		$order = wc_get_order( $order->get_id() );

		$this->assertInstanceOf( WC_Order::class, $order );
		$this->assertSame( 'on-hold', $order->get_status() );
		$this->assertOrderHasNoteContaining(
			$order,
			array(
				'Payment has been disputed for',
				'&#36;</span>50.00',
				'with reason "Transaction unauthorized"',
				'Response due by July 1, 2026',
				'/woopayments/transactions/details',
				'id=ch_123',
				'transaction_id=txn_123',
			)
		);
		$this->assertSame( '', $order->get_meta( '_dispute_id', true ) );
	}

	/**
	 * @testdox charge.dispute.created deletes stale dispute caches.
	 */
	public function test_dispute_created_deletes_dispute_caches(): void {
		$order = $this->create_woopayments_order();
		$order->set_status( 'processing' );
		$order->update_meta_data( '_charge_id', 'ch_123' );
		$order->save();
		$this->seed_dispute_cache_options();

		$this->sut->process( $this->create_dispute_event( 'charge.dispute.created', 'needs_response' ) );

		$this->assert_dispute_cache_options_deleted();
	}

	/**
	 * @testdox charge.dispute.created uses inquiry wording for warning dispute statuses.
	 */
	public function test_dispute_created_uses_inquiry_note_for_warning_status(): void {
		$order = $this->create_woopayments_order();
		$order->set_status( 'processing' );
		$order->update_meta_data( '_charge_id', 'ch_123' );
		$order->save();

		$this->sut->process( $this->create_dispute_event( 'charge.dispute.created', 'warning_needs_response' ) );

		$order = wc_get_order( $order->get_id() );

		$this->assertInstanceOf( WC_Order::class, $order );
		$this->assertSame( 'on-hold', $order->get_status() );
		$this->assertOrderHasNoteContaining(
			$order,
			array(
				'A payment inquiry has been raised for',
				'&#36;</span>50.00',
				'with reason "Transaction unauthorized"',
				'Response due by July 1, 2026',
				'/woopayments/transactions/details',
				'id=ch_123',
			)
		);
	}

	/**
	 * @testdox charge.dispute.updated adds the reference dispute update note without changing order status.
	 *
	 * @dataProvider dispute_update_event_provider
	 *
	 * @param string $event_type Event type.
	 * @param string $message    Expected message.
	 */
	public function test_dispute_updates_add_notes_without_changing_status( string $event_type, string $message ): void {
		$order = $this->create_woopayments_order();
		$order->set_status( 'processing' );
		$order->update_meta_data( '_charge_id', 'ch_123' );
		$order->save();

		$this->sut->process( $this->create_dispute_event( $event_type, 'needs_response' ) );

		$order = wc_get_order( $order->get_id() );

		$this->assertInstanceOf( WC_Order::class, $order );
		$this->assertSame( 'processing', $order->get_status() );
		$this->assertOrderHasNote( $order, $message . '. See <a href="' . $this->get_expected_dispute_url( 'ch_123' ) . '">dispute overview</a> for more details.' );
	}

	/**
	 * @testdox charge.dispute.updated deletes stale dispute caches when an update note is applied.
	 */
	public function test_dispute_updated_deletes_dispute_caches(): void {
		$order = $this->create_woopayments_order();
		$order->set_status( 'processing' );
		$order->update_meta_data( '_charge_id', 'ch_123' );
		$order->save();
		$this->seed_dispute_cache_options();

		$this->sut->process( $this->create_dispute_event( 'charge.dispute.updated', 'needs_response' ) );

		$this->assert_dispute_cache_options_deleted();
	}

	/**
	 * Provide dispute update events.
	 *
	 * @return array<string,array{0:string,1:string}>
	 */
	public function dispute_update_event_provider(): array {
		return array(
			'updated'          => array( 'charge.dispute.updated', 'Payment dispute has been updated' ),
			'funds withdrawn'  => array( 'charge.dispute.funds_withdrawn', 'Payment dispute and fees have been deducted from your next payout' ),
			'funds reinstated' => array( 'charge.dispute.funds_reinstated', 'Payment dispute funds have been reinstated' ),
		);
	}

	/**
	 * @testdox charge.dispute.closed completes the order for won disputes.
	 */
	public function test_dispute_closed_won_completes_order(): void {
		$order = $this->create_woopayments_order();
		$order->set_status( 'on-hold' );
		$order->update_meta_data( '_charge_id', 'ch_123' );
		$order->save();

		$sut = new WooPaymentsEventIngestor();
		$sut->init(
			wc_get_container()->get( OrderPaymentLifecycleService::class ),
			new LegacyProxy(),
			new WooPaymentsLegacyRuntime(),
			$this->create_dispute_summary_api_client()
		);

		$sut->process( $this->create_dispute_event( 'charge.dispute.closed', 'won' ) );

		$order = wc_get_order( $order->get_id() );

		$this->assertInstanceOf( WC_Order::class, $order );
		$this->assertSame( 'completed', $order->get_status() );
		$this->assertOrderHasNote( $order, 'Dispute has been closed with status won. See <a href="' . $this->get_expected_dispute_url( 'ch_123' ) . '" target="_blank" rel="noopener noreferrer">dispute overview</a> for more details.' );
	}

	/**
	 * @testdox charge.dispute.closed deletes stale dispute caches.
	 */
	public function test_dispute_closed_deletes_dispute_caches(): void {
		$order = $this->create_woopayments_order();
		$order->set_status( 'on-hold' );
		$order->update_meta_data( '_charge_id', 'ch_123' );
		$order->save();
		$this->seed_dispute_cache_options();

		$sut = new WooPaymentsEventIngestor();
		$sut->init(
			wc_get_container()->get( OrderPaymentLifecycleService::class ),
			new LegacyProxy(),
			new WooPaymentsLegacyRuntime(),
			$this->create_dispute_summary_api_client()
		);

		$sut->process( $this->create_dispute_event( 'charge.dispute.closed', 'won' ) );

		$this->assert_dispute_cache_options_deleted();
	}

	/**
	 * @testdox charge.dispute.closed creates a capped local refund for lost disputes.
	 */
	public function test_dispute_closed_lost_creates_local_refund(): void {
		$order = $this->create_woopayments_order();
		$order->set_status( 'on-hold' );
		$order->update_meta_data( '_charge_id', 'ch_123' );
		$order->save();

		$sut = new WooPaymentsEventIngestor();
		$sut->init(
			wc_get_container()->get( OrderPaymentLifecycleService::class ),
			new LegacyProxy(),
			new WooPaymentsLegacyRuntime(),
			$this->create_dispute_summary_api_client(
				array(
					'disputed_amount' => 500,
					'currency'        => 'usd',
				)
			)
		);

		$sut->process( $this->create_dispute_event( 'charge.dispute.closed', 'lost' ) );
		$sut->process( $this->create_dispute_event( 'charge.dispute.closed', 'lost' ) );

		$order   = wc_get_order( $order->get_id() );
		$refunds = $order instanceof WC_Order ? $order->get_refunds() : array();

		$this->assertInstanceOf( WC_Order::class, $order );
		$this->assertCount( 1, $refunds );
		$this->assertSame( '-5.00', $refunds[0]->get_total() );
		$this->assertSame( 'Dispute lost.', $refunds[0]->get_reason() );
		$this->assertSame( '', $refunds[0]->get_meta( '_wcpay_refund_id', true ) );
		$this->assertOrderHasNote( $order, 'Dispute has been closed with status lost. See <a href="' . $this->get_expected_dispute_url( 'ch_123' ) . '" target="_blank" rel="noopener noreferrer">dispute overview</a> for more details.' );
	}

	/**
	 * @testdox charge.dispute.closed lost fails closed when the local refund cannot be created.
	 */
	public function test_dispute_closed_lost_fails_closed_when_local_refund_creation_fails(): void {
		$order = $this->create_woopayments_order();
		$order->set_status( 'on-hold' );
		$order->update_meta_data( '_charge_id', 'ch_123' );
		$order->save();
		$refund_blocker = static function (): void {
			throw new Exception( 'Refund creation blocked.' );
		};

		$sut = new WooPaymentsEventIngestor();
		$sut->init(
			wc_get_container()->get( OrderPaymentLifecycleService::class ),
			new LegacyProxy(),
			new WooPaymentsLegacyRuntime(),
			$this->create_dispute_summary_api_client(
				array(
					'disputed_amount' => 500,
					'currency'        => 'usd',
				)
			)
		);

		add_action( 'woocommerce_create_refund', $refund_blocker );
		$exception = null;
		try {
			$sut->process( $this->create_dispute_event( 'charge.dispute.closed', 'lost' ) );
		} catch ( RuntimeException $caught ) {
			$exception = $caught;
		} finally {
			remove_action( 'woocommerce_create_refund', $refund_blocker );
		}

		$this->assertInstanceOf( RuntimeException::class, $exception, 'Expected lost dispute processing to fail when local refund creation fails.' );
		$this->assertStringContainsString( 'Could not create local dispute refund', $exception->getMessage() );

		$order   = wc_get_order( $order->get_id() );
		$refunds = $order instanceof WC_Order ? $order->get_refunds() : array();

		$this->assertInstanceOf( WC_Order::class, $order );
		$this->assertSame( 'on-hold', $order->get_status() );
		$this->assertCount( 0, $refunds );
		$this->assertOrderLacksNoteContaining( $order, array( 'Dispute has been closed with status lost' ) );
	}

	/**
	 * @testdox charge.dispute.closed fails closed when required dispute fields are missing.
	 *
	 * @dataProvider malformed_closed_dispute_event_provider
	 *
	 * @param string $missing_field Missing field.
	 */
	public function test_dispute_closed_fails_closed_when_required_fields_are_missing( string $missing_field ): void {
		$order = $this->create_woopayments_order();
		$order->set_status( 'on-hold' );
		$order->update_meta_data( '_charge_id', 'ch_123' );
		$order->save();
		$event = $this->create_dispute_event( 'charge.dispute.closed', 'won' );
		unset( $event['data']['object'][ $missing_field ] );

		$exception = null;
		try {
			$this->sut->process( $event );
		} catch ( RuntimeException $caught ) {
			$exception = $caught;
		}

		$this->assertInstanceOf( RuntimeException::class, $exception, 'Expected malformed dispute closed event to fail closed.' );
		$this->assertStringContainsString( $missing_field, $exception->getMessage() );

		$order = wc_get_order( $order->get_id() );

		$this->assertInstanceOf( WC_Order::class, $order );
		$this->assertSame( 'on-hold', $order->get_status() );
		$this->assertOrderLacksNoteContaining( $order, array( 'Dispute has been closed' ) );
	}

	/**
	 * Provide malformed closed dispute event fields.
	 *
	 * @return array<string,array{0:string}>
	 */
	public function malformed_closed_dispute_event_provider(): array {
		return array(
			'missing status' => array( 'status' ),
			'missing id'     => array( 'id' ),
		);
	}

	/**
	 * @testdox charge.dispute.created fails closed when required dispute fields are missing.
	 *
	 * @dataProvider malformed_created_dispute_event_provider
	 *
	 * @param string[] $missing_path Missing field path under the dispute object.
	 * @param string   $missing_key  Missing field name.
	 */
	public function test_dispute_created_fails_closed_when_required_fields_are_missing( array $missing_path, string $missing_key ): void {
		$order = $this->create_woopayments_order();
		$order->set_status( 'processing' );
		$order->update_meta_data( '_charge_id', 'ch_123' );
		$order->save();
		$event = $this->create_dispute_event( 'charge.dispute.created', 'needs_response' );
		$this->unset_dispute_object_path( $event, $missing_path );

		$exception = null;
		try {
			$this->sut->process( $event );
		} catch ( RuntimeException $caught ) {
			$exception = $caught;
		}

		$this->assertInstanceOf( RuntimeException::class, $exception, 'Expected malformed dispute created event to fail closed.' );
		$this->assertStringContainsString( $missing_key, $exception->getMessage() );

		$order = wc_get_order( $order->get_id() );

		$this->assertInstanceOf( WC_Order::class, $order );
		$this->assertSame( 'processing', $order->get_status() );
		$this->assertOrderLacksNoteContaining( $order, array( 'Payment has been disputed' ) );
		$this->assertOrderLacksNoteContaining( $order, array( 'A payment inquiry has been raised' ) );
	}

	/**
	 * Provide malformed created dispute event fields.
	 *
	 * @return array<string,array{0:string[],1:string}>
	 */
	public function malformed_created_dispute_event_provider(): array {
		return array(
			'missing status'           => array( array( 'status' ), 'status' ),
			'missing reason'           => array( array( 'reason' ), 'reason' ),
			'missing amount'           => array( array( 'amount' ), 'amount' ),
			'missing evidence details' => array( array( 'evidence_details' ), 'evidence_details' ),
			'missing due by'           => array( array( 'evidence_details', 'due_by' ), 'due_by' ),
		);
	}

	/**
	 * @testdox charge.dispute.closed with no matching order fails closed.
	 */
	public function test_dispute_closed_without_matching_charge_fails_closed(): void {
		$this->expectException( RuntimeException::class );

		$this->sut->process( $this->create_dispute_event( 'charge.dispute.closed', 'won' ) );
	}

	/**
	 * @testdox Unknown event types are successful no-ops.
	 */
	public function test_unknown_event_type_is_a_successful_noop(): void {
		$order = $this->create_woopayments_order();

		$this->sut->process( $this->create_payment_intent_event( 'customer.created', $order ) );

		$order = wc_get_order( $order->get_id() );

		$this->assertInstanceOf( WC_Order::class, $order );
		$this->assertSame( 'pending', $order->get_status() );
		$this->assertSame( '', $order->get_meta( '_intent_id', true ) );
	}

	/**
	 * @testdox Migrated account and refund webhook event types are no longer cutover blockers.
	 */
	public function test_refund_webhook_events_are_not_known_unhandled(): void {
		$this->assertNotContains( 'charge.refunded', WooPaymentsEventIngestor::KNOWN_UNHANDLED_EVENT_TYPES );
		$this->assertNotContains( 'charge.refund.updated', WooPaymentsEventIngestor::KNOWN_UNHANDLED_EVENT_TYPES );
		$this->assertNotContains( 'account.updated', WooPaymentsEventIngestor::KNOWN_UNHANDLED_EVENT_TYPES );
		$this->assertNotContains( 'account.deleted', WooPaymentsEventIngestor::KNOWN_UNHANDLED_EVENT_TYPES );
		$this->assertNotContains( 'wcpay.notification', WooPaymentsEventIngestor::KNOWN_UNHANDLED_EVENT_TYPES );
		$this->assertNotContains( 'invoice.paid', WooPaymentsEventIngestor::KNOWN_UNHANDLED_EVENT_TYPES );
		$this->assertNotContains( 'invoice.payment_failed', WooPaymentsEventIngestor::KNOWN_UNHANDLED_EVENT_TYPES );
		$this->assertNotContains( 'invoice.upcoming', WooPaymentsEventIngestor::KNOWN_UNHANDLED_EVENT_TYPES );
	}

	/**
	 * @testdox Retired Stripe Billing invoice events are alarmed instead of falling through silently.
	 */
	public function test_retired_stripe_billing_invoice_event_logs_alarm(): void {
		$logger = new class() {
			/**
			 * Logged entries.
			 *
			 * @var array<int,array{0:string,1:array<string,mixed>}>
			 */
			public array $entries = array();

			/**
			 * Record an error message.
			 *
			 * @param string              $message Error message.
			 * @param array<string,mixed> $context Error context.
			 */
			public function error( string $message, array $context = array() ): void {
				$this->entries[] = array( $message, $context );
			}
		};

		add_filter( WooPaymentsEventIngestor::FILTER_LIVE_MODE, '__return_false' );

		$runtime = new WooPaymentsLegacyRuntime();
		$runtime->init( new LegacyRuntimeProxy( true, null, null, null, $logger ) );

		$sut = new WooPaymentsEventIngestor();
		$sut->init(
			wc_get_container()->get( OrderPaymentLifecycleService::class ),
			wc_get_container()->get( LegacyProxy::class ),
			$runtime,
			new class() extends WooPaymentsApiClient {
				/**
				 * Retrieve a WooPayments PaymentIntent.
				 *
				 * @param string $intent_id Intent ID.
				 * @return array<string,mixed>
				 */
				public function get_payment_intention( string $intent_id ): array {
					return array();
				}
			}
		);

		$hook_calls = array();
		add_action(
			'woocommerce_payments_before_webhook_delivery',
			function ( string $event_type, array $event_body ) use ( &$hook_calls ): void {
				$hook_calls[] = array( 'before', $event_type, $event_body['id'] );
			},
			10,
			2
		);
		add_action(
			'woocommerce_payments_after_webhook_delivery',
			function ( string $event_type, array $event_body ) use ( &$hook_calls ): void {
				$hook_calls[] = array( 'after', $event_type, $event_body['id'] );
			},
			10,
			2
		);

		$sut->process(
			array(
				'id'       => 'evt_invoice_123',
				'type'     => 'invoice.paid',
				'livemode' => true,
				'data'     => array(
					'object' => array(
						'id' => 'in_123',
					),
				),
			)
		);

		$this->assertCount( 1, $logger->entries );
		$this->assertStringContainsString( 'Retired WooPayments Stripe Billing invoice event reached native webhook processing: invoice.paid', $logger->entries[0][0] );
		$this->assertSame( 'evt_invoice_123', $logger->entries[0][1]['event_id'] );
		$this->assertSame( 'native-payments-webhook', $logger->entries[0][1]['source'] );
		$this->assertSame(
			array(
				array( 'before', 'invoice.paid', 'evt_invoice_123' ),
				array( 'after', 'invoice.paid', 'evt_invoice_123' ),
			),
			$hook_calls
		);
	}

	/**
	 * @testdox account.updated refreshes account data and clears preserved payment method caches.
	 */
	public function test_account_updated_refreshes_account_data_and_clears_payment_method_caches(): void {
		$account_service = $this->getMockBuilder( WooPaymentsAccountService::class )
			->disableOriginalConstructor()
			->onlyMethods( array( 'refresh_account_data_strict' ) )
			->getMock();
		$account_service->expects( $this->once() )
			->method( 'refresh_account_data_strict' )
			->willReturn( array( 'account_id' => 'acct_123' ) );

		$token_service = $this->getMockBuilder( WooPaymentsTokenService::class )
			->disableOriginalConstructor()
			->onlyMethods( array( 'clear_all_cached_payment_methods' ) )
			->getMock();
		$token_service->expects( $this->once() )
			->method( 'clear_all_cached_payment_methods' );

		$sut = $this->create_ingestor_with_account_services( $account_service, $token_service );

		$sut->process( $this->create_account_event( 'account.updated' ) );
	}

	/**
	 * @testdox account.deleted resets account state, refreshes account data, and clears preserved payment method caches.
	 */
	public function test_account_deleted_cleans_state_refreshes_account_data_and_clears_payment_method_caches(): void {
		$account_service = $this->getMockBuilder( WooPaymentsAccountService::class )
			->disableOriginalConstructor()
			->onlyMethods( array( 'cleanup_after_account_reset', 'refresh_account_data_strict', 'get_preserved_account_id', 'get_pending_account_deletion_id', 'mark_account_deletion_pending', 'clear_pending_account_deletion' ) )
			->getMock();
		$account_service->expects( $this->once() )
			->method( 'get_pending_account_deletion_id' )
			->willReturn( '' );
		$account_service->expects( $this->once() )
			->method( 'get_preserved_account_id' )
			->willReturn( 'acct_123' );
		$account_service->expects( $this->once() )
			->method( 'mark_account_deletion_pending' )
			->with( 'acct_123' );
		$account_service->expects( $this->once() )
			->method( 'cleanup_after_account_reset' );
		$account_service->expects( $this->once() )
			->method( 'refresh_account_data_strict' )
			->willReturn( array() );
		$account_service->expects( $this->once() )
			->method( 'clear_pending_account_deletion' );

		$token_service = $this->getMockBuilder( WooPaymentsTokenService::class )
			->disableOriginalConstructor()
			->onlyMethods( array( 'clear_all_cached_payment_methods' ) )
			->getMock();
		$token_service->expects( $this->once() )
			->method( 'clear_all_cached_payment_methods' );

		$sut = $this->create_ingestor_with_account_services( $account_service, $token_service );

		$sut->process( $this->create_account_event( 'account.deleted' ) );
	}

	/**
	 * @testdox account.deleted ignores stale account deletion events for a different connected account.
	 */
	public function test_account_deleted_ignores_stale_delete_for_different_account(): void {
		$account_service = $this->getMockBuilder( WooPaymentsAccountService::class )
			->disableOriginalConstructor()
			->onlyMethods( array( 'cleanup_after_account_reset', 'refresh_account_data_strict', 'get_preserved_account_id', 'get_pending_account_deletion_id', 'mark_account_deletion_pending', 'clear_pending_account_deletion' ) )
			->getMock();
		$account_service->expects( $this->once() )
			->method( 'get_pending_account_deletion_id' )
			->willReturn( '' );
		$account_service->expects( $this->once() )
			->method( 'get_preserved_account_id' )
			->willReturn( 'acct_current' );
		$account_service->expects( $this->never() )
			->method( 'mark_account_deletion_pending' );
		$account_service->expects( $this->never() )
			->method( 'cleanup_after_account_reset' );
		$account_service->expects( $this->never() )
			->method( 'refresh_account_data_strict' );
		$account_service->expects( $this->never() )
			->method( 'clear_pending_account_deletion' );

		$token_service = $this->getMockBuilder( WooPaymentsTokenService::class )
			->disableOriginalConstructor()
			->onlyMethods( array( 'clear_all_cached_payment_methods' ) )
			->getMock();
		$token_service->expects( $this->never() )
			->method( 'clear_all_cached_payment_methods' );

		$sut = $this->create_ingestor_with_account_services( $account_service, $token_service );

		$sut->process( $this->create_account_event( 'account.deleted', 'acct_deleted' ) );
	}

	/**
	 * @testdox account.deleted continues pending cleanup retries after the local account cache was cleared.
	 */
	public function test_account_deleted_continues_pending_cleanup_retry(): void {
		$account_service = $this->getMockBuilder( WooPaymentsAccountService::class )
			->disableOriginalConstructor()
			->onlyMethods( array( 'cleanup_after_account_reset', 'refresh_account_data_strict', 'get_preserved_account_id', 'get_pending_account_deletion_id', 'mark_account_deletion_pending', 'clear_pending_account_deletion' ) )
			->getMock();
			$account_service->expects( $this->once() )
				->method( 'get_pending_account_deletion_id' )
				->willReturn( 'acct_123' );
			$account_service->expects( $this->once() )
				->method( 'get_preserved_account_id' )
				->willReturn( '' );
			$account_service->expects( $this->once() )
				->method( 'mark_account_deletion_pending' )
				->with( 'acct_123' );
		$account_service->expects( $this->once() )
			->method( 'cleanup_after_account_reset' );
		$account_service->expects( $this->once() )
			->method( 'refresh_account_data_strict' )
			->willReturn( array() );
		$account_service->expects( $this->once() )
			->method( 'clear_pending_account_deletion' );

		$token_service = $this->getMockBuilder( WooPaymentsTokenService::class )
			->disableOriginalConstructor()
			->onlyMethods( array( 'clear_all_cached_payment_methods' ) )
			->getMock();
		$token_service->expects( $this->once() )
			->method( 'clear_all_cached_payment_methods' );

		$sut = $this->create_ingestor_with_account_services( $account_service, $token_service );

		$sut->process( $this->create_account_event( 'account.deleted' ) );
	}

	/**
	 * @testdox account.deleted ignores stale pending markers when a different account is connected.
	 */
	public function test_account_deleted_ignores_stale_pending_marker_for_different_account(): void {
		$account_service = $this->getMockBuilder( WooPaymentsAccountService::class )
			->disableOriginalConstructor()
			->onlyMethods( array( 'cleanup_after_account_reset', 'refresh_account_data_strict', 'get_preserved_account_id', 'get_pending_account_deletion_id', 'mark_account_deletion_pending', 'clear_pending_account_deletion' ) )
			->getMock();
		$account_service->expects( $this->once() )
			->method( 'get_pending_account_deletion_id' )
			->willReturn( 'acct_deleted' );
		$account_service->expects( $this->once() )
			->method( 'get_preserved_account_id' )
			->willReturn( 'acct_current' );
		$account_service->expects( $this->never() )
			->method( 'mark_account_deletion_pending' );
		$account_service->expects( $this->never() )
			->method( 'cleanup_after_account_reset' );
		$account_service->expects( $this->never() )
			->method( 'refresh_account_data_strict' );
		$account_service->expects( $this->never() )
			->method( 'clear_pending_account_deletion' );

		$token_service = $this->getMockBuilder( WooPaymentsTokenService::class )
			->disableOriginalConstructor()
			->onlyMethods( array( 'clear_all_cached_payment_methods' ) )
			->getMock();
		$token_service->expects( $this->never() )
			->method( 'clear_all_cached_payment_methods' );

		$sut = $this->create_ingestor_with_account_services( $account_service, $token_service );

		$sut->process( $this->create_account_event( 'account.deleted', 'acct_deleted' ) );
	}

	/**
	 * @testdox account.deleted keeps the pending marker when strict refresh fails after cleanup.
	 */
	public function test_account_deleted_keeps_pending_marker_when_refresh_fails(): void {
		$account_service = $this->getMockBuilder( WooPaymentsAccountService::class )
			->disableOriginalConstructor()
			->onlyMethods( array( 'cleanup_after_account_reset', 'refresh_account_data_strict', 'get_preserved_account_id', 'get_pending_account_deletion_id', 'mark_account_deletion_pending', 'clear_pending_account_deletion' ) )
			->getMock();
		$account_service->expects( $this->once() )
			->method( 'get_pending_account_deletion_id' )
			->willReturn( '' );
		$account_service->expects( $this->once() )
			->method( 'get_preserved_account_id' )
			->willReturn( 'acct_123' );
		$account_service->expects( $this->once() )
			->method( 'mark_account_deletion_pending' )
			->with( 'acct_123' );
		$account_service->expects( $this->once() )
			->method( 'cleanup_after_account_reset' );
		$account_service->expects( $this->once() )
			->method( 'refresh_account_data_strict' )
			->willThrowException( new RuntimeException( 'Temporary refresh failure.' ) );
		$account_service->expects( $this->never() )
			->method( 'clear_pending_account_deletion' );

		$token_service = $this->getMockBuilder( WooPaymentsTokenService::class )
			->disableOriginalConstructor()
			->onlyMethods( array( 'clear_all_cached_payment_methods' ) )
			->getMock();
		$token_service->expects( $this->never() )
			->method( 'clear_all_cached_payment_methods' );

		$sut = $this->create_ingestor_with_account_services( $account_service, $token_service );

		$this->expectException( RuntimeException::class );

		$sut->process( $this->create_account_event( 'account.deleted' ) );
	}

	/**
	 * @testdox account.deleted fails closed when the event account ID is missing.
	 */
	public function test_account_deleted_fails_closed_for_missing_account_id(): void {
		$account_service = $this->getMockBuilder( WooPaymentsAccountService::class )
			->disableOriginalConstructor()
			->getMock();
		$token_service   = $this->getMockBuilder( WooPaymentsTokenService::class )
			->disableOriginalConstructor()
			->getMock();
		$sut             = $this->create_ingestor_with_account_services( $account_service, $token_service );
		$event           = $this->create_account_event( 'account.deleted' );
		unset( $event['data']['object']['id'] );

		$this->expectException( InvalidArgumentException::class );

		$sut->process( $event );
	}

	/**
	 * @testdox wcpay.notification creates a remote note without requiring a data.object payload.
	 */
	public function test_wcpay_notification_creates_remote_note_without_event_object(): void {
		$note_slug = 'h30-ingestor-' . wp_generate_uuid4();
		update_option( 'woocommerce_woocommerce_payments_settings', array( 'test_mode' => 'yes' ) );

		$this->sut->process(
			array(
				'id'       => 'evt_note',
				'type'     => 'wcpay.notification',
				'livemode' => false,
				'data'     => array(
					'name'    => $note_slug,
					'title'   => 'Remote note',
					'content' => 'Remote note content.',
					'actions' => array(
						'settings' => array(
							'label' => 'Open settings',
							'url'   => 'wcpay_settings',
						),
					),
				),
			)
		);

		$note = Notes::get_note_by_name( WooPaymentsRemoteNoteService::NOTE_NAME_PREFIX . $note_slug );

		$this->assertInstanceOf( Note::class, $note );
		$this->assertSame( 'Remote note', $note->get_title() );
		$this->assertSame( 'Remote note content.', $note->get_content() );
	}

	/**
	 * @testdox wcpay.notification fails closed for invalid remote note payloads.
	 */
	public function test_wcpay_notification_fails_closed_for_invalid_note_payload(): void {
		update_option( 'woocommerce_woocommerce_payments_settings', array( 'test_mode' => 'yes' ) );

		$this->expectException( InvalidArgumentException::class );

		$this->sut->process(
			array(
				'id'       => 'evt_note_invalid',
				'type'     => 'wcpay.notification',
				'livemode' => false,
				'data'     => array(
					'title' => 'Missing content',
				),
			)
		);
	}

	/**
	 * @testdox charge.refunded creates a full local refund with WooPayments metadata.
	 */
	public function test_charge_refunded_creates_full_local_refund_with_wcpay_metadata(): void {
		$order = $this->create_refundable_woopayments_order( '10.00' );

		$this->sut->process( $this->create_charge_refunded_event( $order, 1000, 1000, 'succeeded' ) );

		$order = wc_get_order( $order->get_id() );

		$this->assertInstanceOf( WC_Order::class, $order );
		$refunds = $order->get_refunds();
		$this->assertCount( 1, $refunds );
		$this->assertInstanceOf( WC_Order_Refund::class, $refunds[0] );
		$this->assertSame( '-10.00', $refunds[0]->get_total() );
		$this->assertCount( 1, $refunds[0]->get_items(), 'Full external refunds should preserve refunded line items.' );
		$this->assertSame( 'requested_by_customer', $refunds[0]->get_reason() );
		$this->assertSame( 'successful', $order->get_meta( '_wcpay_refund_status', true ) );
		$this->assertSame( 're_123', $refunds[0]->get_meta( '_wcpay_refund_id', true ) );
		$this->assertSame( 'txn_123', $refunds[0]->get_meta( '_wcpay_refund_transaction_id', true ) );
		$this->assertOrderHasNoteContaining( $order, array( 'A refund of', 'was successfully processed using WooPayments', 'requested_by_customer', 're_123' ) );
	}

	/**
	 * @testdox charge.refunded creates a pending partial local refund.
	 */
	public function test_charge_refunded_creates_pending_partial_refund(): void {
		$order = $this->create_refundable_woopayments_order( '10.00' );

		$this->sut->process( $this->create_charge_refunded_event( $order, 1000, 400, 'pending' ) );

		$order = wc_get_order( $order->get_id() );

		$this->assertInstanceOf( WC_Order::class, $order );
		$refunds = $order->get_refunds();
		$this->assertCount( 1, $refunds );
		$this->assertInstanceOf( WC_Order_Refund::class, $refunds[0] );
		$this->assertSame( '-4.00', $refunds[0]->get_total() );
		$this->assertCount( 0, $refunds[0]->get_items(), 'Partial external refunds should not synthesize line-item refunds.' );
		$this->assertSame( 'pending', $order->get_meta( '_wcpay_refund_status', true ) );
		$this->assertSame( 're_123', $refunds[0]->get_meta( '_wcpay_refund_id', true ) );
		$this->assertOrderHasNoteContaining( $order, array( 'A refund of', 'is pending', 'WooPayments', 're_123' ) );
	}

	/**
	 * @testdox charge.refunded ignores already persisted WooPayments refund IDs.
	 */
	public function test_charge_refunded_ignores_duplicate_provider_refund_id(): void {
		$order  = $this->create_refundable_woopayments_order( '10.00' );
		$refund = $this->create_local_refund( $order, 4.00, 'Existing refund' );
		$refund->update_meta_data( '_wcpay_refund_id', 're_123' );
		$refund->save_meta_data();

		$this->sut->process( $this->create_charge_refunded_event( $order, 1000, 400, 'succeeded' ) );

		$order  = wc_get_order( $order->get_id() );
		$refund = wc_get_order( $refund->get_id() );

		$this->assertInstanceOf( WC_Order::class, $order );
		$this->assertInstanceOf( WC_Order_Refund::class, $refund );
		$this->assertCount( 1, $order->get_refunds() );
		$this->assertSame( 'successful', $order->get_meta( '_wcpay_refund_status', true ) );
		$this->assertSame( 'txn_123', $refund->get_meta( '_wcpay_refund_transaction_id', true ) );
		$this->assertOrderHasNoteContaining( $order, array( 'A refund of', 'was successfully processed using WooPayments', 're_123' ) );
	}

	/**
	 * @testdox charge.refunded does not downgrade successful duplicate refunds to pending.
	 */
	public function test_charge_refunded_duplicate_pending_retry_does_not_downgrade_successful_refund(): void {
		$order  = $this->create_refundable_woopayments_order( '10.00' );
		$refund = $this->create_local_refund( $order, 4.00, 'Existing refund' );
		$refund->update_meta_data( '_wcpay_refund_id', 're_123' );
		$refund->update_meta_data( '_wcpay_refund_transaction_id', 'txn_existing' );
		$refund->save_meta_data();
		$order->update_meta_data( '_wcpay_refund_status', 'successful' );
		$order->save();

		$this->sut->process( $this->create_charge_refunded_event( $order, 1000, 400, 'pending' ) );

		$order  = wc_get_order( $order->get_id() );
		$refund = wc_get_order( $refund->get_id() );

		$this->assertInstanceOf( WC_Order::class, $order );
		$this->assertInstanceOf( WC_Order_Refund::class, $refund );
		$this->assertSame( 'successful', $order->get_meta( '_wcpay_refund_status', true ) );
		$this->assertSame( 'txn_existing', $refund->get_meta( '_wcpay_refund_transaction_id', true ) );
		$this->assertOrderLacksNoteContaining( $order, array( 'is pending', 're_123' ) );
	}

	/**
	 * @testdox charge.refunded accepts split-UPE WooPayments gateway IDs.
	 */
	public function test_charge_refunded_accepts_split_upe_gateway_ids(): void {
		$order = $this->create_refundable_woopayments_order( '10.00' );
		$order->set_payment_method( OrderPaymentStore::GATEWAY_ID_PREFIX . 'sepa_debit' );
		$order->save();

		$this->sut->process( $this->create_charge_refunded_event( $order, 1000, 400, 'succeeded' ) );

		$order = wc_get_order( $order->get_id() );

		$this->assertInstanceOf( WC_Order::class, $order );
		$this->assertCount( 1, $order->get_refunds() );
		$this->assertSame( 'successful', $order->get_meta( '_wcpay_refund_status', true ) );
	}

	/**
	 * @testdox charge.refunded notes include explicit currency when native multi-currency has additional currencies.
	 */
	public function test_charge_refunded_uses_explicit_currency_in_created_refund_notes(): void {
		update_option( 'wcpay_multi_currency_enabled_currencies', array( 'EUR' ) );
		$order = $this->create_refundable_woopayments_order( '10.00' );

		$this->sut->process( $this->create_charge_refunded_event( $order, 1000, 400, 'succeeded' ) );

		$order = wc_get_order( $order->get_id() );

		$this->assertInstanceOf( WC_Order::class, $order );
		$this->assertOrderHasNoteContaining( $order, array( 'A refund of', 'USD', 'WooPayments', 're_123' ) );
	}

	/**
	 * @testdox charge.refunded ignores canceled authorizations.
	 */
	public function test_charge_refunded_ignores_uncaptured_charges(): void {
		$order = $this->create_refundable_woopayments_order( '10.00' );

		$this->sut->process( $this->create_charge_refunded_event( $order, 1000, 400, 'succeeded', array( 'captured' => false ) ) );

		$order = wc_get_order( $order->get_id() );

		$this->assertInstanceOf( WC_Order::class, $order );
		$this->assertCount( 0, $order->get_refunds(), 'Uncaptured charges should not create local refunds.' );
		$this->assertSame( '', $order->get_meta( '_wcpay_refund_status', true ) );
	}

	/**
	 * @testdox charge.refunded fails closed when the captured field is missing.
	 */
	public function test_charge_refunded_fails_closed_for_missing_captured_field(): void {
		$order = $this->create_refundable_woopayments_order( '10.00' );
		$event = $this->create_charge_refunded_event( $order, 1000, 400, 'succeeded' );
		unset( $event['data']['object']['captured'] );

		$this->expectException( RuntimeException::class );
		$this->expectExceptionMessage( 'missing required field: captured' );

		$this->sut->process( $event );
	}

	/**
	 * @testdox charge.refunded fails closed when the captured field is malformed.
	 */
	public function test_charge_refunded_fails_closed_for_malformed_captured_field(): void {
		$order = $this->create_refundable_woopayments_order( '10.00' );

		$this->expectException( RuntimeException::class );
		$this->expectExceptionMessage( 'missing required field: captured' );

		$this->sut->process( $this->create_charge_refunded_event( $order, 1000, 400, 'succeeded', array( 'captured' => 'yes' ) ) );
	}

	/**
	 * @testdox charge.refunded fails closed for invalid refund amounts.
	 */
	public function test_charge_refunded_fails_closed_for_invalid_amount(): void {
		$order = $this->create_refundable_woopayments_order( '10.00' );

		try {
			$this->sut->process( $this->create_charge_refunded_event( $order, 1000, 1500, 'succeeded' ) );
			$this->fail( 'Expected invalid external refund amount to fail closed.' );
		} catch ( RuntimeException $exception ) {
			$this->assertStringContainsString( 'refund amount is not valid', $exception->getMessage() );
		}

		$order = wc_get_order( $order->get_id() );

		$this->assertInstanceOf( WC_Order::class, $order );
		$this->assertCount( 0, $order->get_refunds() );
		$this->assertSame( '', $order->get_meta( '_wcpay_refund_status', true ) );
	}

	/**
	 * @testdox charge.refunded fails closed for negative refund amounts.
	 */
	public function test_charge_refunded_fails_closed_for_negative_refund_amount(): void {
		$order = $this->create_refundable_woopayments_order( '10.00' );

		try {
			$this->sut->process( $this->create_charge_refunded_event( $order, 1000, -400, 'succeeded' ) );
			$this->fail( 'Expected negative external refund amount to fail closed.' );
		} catch ( RuntimeException $exception ) {
			$this->assertStringContainsString( 'refund amount is not valid', $exception->getMessage() );
		}

		$order = wc_get_order( $order->get_id() );

		$this->assertInstanceOf( WC_Order::class, $order );
		$this->assertCount( 0, $order->get_refunds() );
		$this->assertSame( '', $order->get_meta( '_wcpay_refund_status', true ) );
	}

	/**
	 * @testdox charge.refunded fails closed for missing refund data.
	 */
	public function test_charge_refunded_fails_closed_for_missing_refund_data(): void {
		$order                                      = $this->create_refundable_woopayments_order( '10.00' );
		$event                                      = $this->create_charge_refunded_event( $order, 1000, 400, 'succeeded' );
		$event['data']['object']['refunds']['data'] = array();

		$this->expectException( RuntimeException::class );
		$this->expectExceptionMessage( 'missing refund data' );

		$this->sut->process( $event );
	}

	/**
	 * @testdox charge.refunded fails closed for missing refund IDs.
	 */
	public function test_charge_refunded_fails_closed_for_missing_refund_id(): void {
		$order = $this->create_refundable_woopayments_order( '10.00' );
		$event = $this->create_charge_refunded_event(
			$order,
			1000,
			400,
			'succeeded',
			array(
				'refunds' => array(
					'data' => array(
						array(
							'id' => '',
						),
					),
				),
			)
		);

		$this->expectException( RuntimeException::class );
		$this->expectExceptionMessage( 'missing required field: id' );

		$this->sut->process( $event );
	}

	/**
	 * @testdox charge.refunded fails closed for unknown charge IDs.
	 */
	public function test_charge_refunded_fails_closed_for_unknown_charge_id(): void {
		$order = $this->create_refundable_woopayments_order( '10.00' );
		$event = $this->create_charge_refunded_event( $order, 1000, 400, 'succeeded', array( 'id' => 'ch_unknown' ) );

		$this->expectException( RuntimeException::class );
		$this->expectExceptionMessage( 'Could not find WooPayments order via charge ID' );

		$this->sut->process( $event );
	}

	/**
	 * @testdox charge.refunded fails closed when the event order key mismatches.
	 */
	public function test_charge_refunded_fails_closed_for_mismatched_order_key(): void {
		$order = $this->create_refundable_woopayments_order( '10.00' );
		$event = $this->create_charge_refunded_event(
			$order,
			1000,
			400,
			'succeeded',
			array(
				'metadata' => array(
					'order_id'  => (string) $order->get_id(),
					'order_key' => 'wc_order_wrong_key',
				),
			)
		);

		try {
			$this->sut->process( $event );
			$this->fail( 'Expected mismatched order key to fail closed.' );
		} catch ( RuntimeException $exception ) {
			$this->assertStringContainsString( 'Could not find WooPayments order via charge ID', $exception->getMessage() );
		}

		$order = wc_get_order( $order->get_id() );

		$this->assertInstanceOf( WC_Order::class, $order );
		$this->assertCount( 0, $order->get_refunds() );
	}

	/**
	 * @testdox charge.refunded uses the shared order payment lock.
	 */
	public function test_charge_refunded_fails_closed_when_order_payment_is_locked(): void {
		$order = $this->create_refundable_woopayments_order( '10.00' );
		$store = wc_get_container()->get( OrderPaymentStore::class );
		$store->lock_order_payment( $order, 'existing_operation' );

		try {
			$this->sut->process( $this->create_charge_refunded_event( $order, 1000, 400, 'succeeded' ) );
			$this->fail( 'Expected locked order refund webhook to fail closed.' );
		} catch ( RuntimeException $exception ) {
			$this->assertStringContainsString( 'Could not claim WooPayments refund webhook lock', $exception->getMessage() );
		} finally {
			$store->unlock_order_payment( $order );
		}

		$order = wc_get_order( $order->get_id() );

		$this->assertInstanceOf( WC_Order::class, $order );
		$this->assertCount( 0, $order->get_refunds() );
	}

	/**
	 * @testdox charge.refund.updated marks matched failed refunds failed and deletes the local refund.
	 */
	public function test_charge_refund_updated_failed_marks_order_failed_and_deletes_refund(): void {
		$order     = $this->create_refundable_woopayments_order( '10.00' );
		$refund    = $this->create_local_refund( $order, 4.00, 'Existing refund' );
		$refund_id = $refund->get_id();
		$refund->update_meta_data( '_wcpay_refund_id', 're_123' );
		$refund->save_meta_data();
		$order->set_status( 'refunded' );
		$order->save();

		$this->sut->process(
			$this->create_refund_updated_event(
				array(
					'status'         => 'failed',
					'failure_reason' => 'lost_or_stolen_card',
				)
			)
		);

		$order = wc_get_order( $order->get_id() );

		$this->assertInstanceOf( WC_Order::class, $order );
		$this->assertSame( 'failed', $order->get_status() );
		$this->assertFalse( wc_get_order( $refund_id ), 'The matched local refund object should be deleted after the provider marks it failed.' );
		$this->assertNull( get_post( $refund_id ), 'The matched local refund post should be deleted after the provider marks it failed.' );
		$this->assertSame( 'failed', $order->get_meta( '_wcpay_refund_status', true ) );
		$this->assertOrderHasNoteContaining( $order, array( 'A refund of', 'unsuccessful', 'WooPayments', 're_123', 'lost or stolen' ) );
	}

	/**
	 * @testdox charge.refund.updated fires the refund deleted hook when deleting a matched local refund.
	 */
	public function test_charge_refund_updated_failed_fires_refund_deleted_hook(): void {
		$order      = $this->create_refundable_woopayments_order( '10.00' );
		$refund     = $this->create_local_refund( $order, 4.00, 'Existing refund' );
		$refund_id  = $refund->get_id();
		$hook_calls = array();
		$refund->update_meta_data( '_wcpay_refund_id', 're_123' );
		$refund->save_meta_data();
		$refund_deleted_callback = function ( int $deleted_refund_id, int $deleted_order_id ) use ( &$hook_calls ): void {
			$hook_calls[] = array( $deleted_refund_id, $deleted_order_id );
		};

		add_action(
			'woocommerce_refund_deleted',
			$refund_deleted_callback,
			10,
			2
		);

		try {
			$this->sut->process(
				$this->create_refund_updated_event(
					array(
						'status'         => 'failed',
						'failure_reason' => 'lost_or_stolen_card',
					)
				)
			);
		} finally {
			remove_action( 'woocommerce_refund_deleted', $refund_deleted_callback, 10 );
		}

		$this->assertSame( array( array( $refund_id, $order->get_id() ) ), $hook_calls );
	}

	/**
	 * @testdox charge.refund.updated repairs status metadata when a failed-refund note already exists.
	 */
	public function test_charge_refund_updated_failed_reconciles_existing_note_without_returning_early(): void {
		$order = $this->create_refundable_woopayments_order( '10.00' );
		$event = $this->create_refund_updated_event(
			array(
				'status'         => 'failed',
				'failure_reason' => 'lost_or_stolen_card',
			)
		);

		$this->sut->process( $event );

		$order = wc_get_order( $order->get_id() );
		$this->assertInstanceOf( WC_Order::class, $order );
		$order->delete_meta_data( '_wcpay_refund_status' );
		$order->set_status( 'refunded' );
		$order->save();

		$this->sut->process( $event );

		$order = wc_get_order( $order->get_id() );

		$this->assertInstanceOf( WC_Order::class, $order );
		$this->assertSame( 'failed', $order->get_status() );
		$this->assertSame( 'failed', $order->get_meta( '_wcpay_refund_status', true ) );
	}

	/**
	 * @testdox charge.refund.updated fails closed when the order payment lock is already held.
	 */
	public function test_charge_refund_updated_fails_closed_when_order_payment_is_locked(): void {
		$order  = $this->create_refundable_woopayments_order( '10.00' );
		$refund = $this->create_local_refund( $order, 4.00, 'Existing refund' );
		$refund->update_meta_data( '_wcpay_refund_id', 're_123' );
		$refund->save_meta_data();
		$store = wc_get_container()->get( OrderPaymentStore::class );
		$store->lock_order_payment( $order, 'existing_operation' );

		try {
			$this->sut->process(
				$this->create_refund_updated_event(
					array(
						'status'         => 'failed',
						'failure_reason' => 'lost_or_stolen_card',
					)
				)
			);
			$this->fail( 'Expected locked order refund update webhook to fail closed.' );
		} catch ( RuntimeException $exception ) {
			$this->assertStringContainsString( 'Could not claim WooPayments refund webhook lock', $exception->getMessage() );
		} finally {
			$store->unlock_order_payment( $order );
		}

		$order = wc_get_order( $order->get_id() );

		$this->assertInstanceOf( WC_Order::class, $order );
		$this->assertCount( 1, $order->get_refunds() );
		$this->assertSame( '', $order->get_meta( '_wcpay_refund_status', true ) );
	}

	/**
	 * @testdox charge.refund.updated records failed refunds without a matched local refund.
	 */
	public function test_charge_refund_updated_failed_without_matched_refund_adds_note(): void {
		$order = $this->create_refundable_woopayments_order( '10.00' );
		$this->set_woopayments_account_country( 'US' );

		$this->sut->process(
			$this->create_refund_updated_event(
				array(
					'status'         => 'failed',
					'failure_reason' => 'insufficient_funds',
				)
			)
		);

		$order = wc_get_order( $order->get_id() );

		$this->assertInstanceOf( WC_Order::class, $order );
		$this->assertCount( 0, $order->get_refunds() );
		$this->assertSame( 'failed', $order->get_meta( '_wcpay_refund_status', true ) );
		$this->assertOrderHasNoteContaining( $order, array( 'Refund of', 'failed', 'insufficient funds in your WooPayments balance', 'Future Refunds or Disputes (FROD) balance' ) );
	}

	/**
	 * @testdox charge.refund.updated uses the non-FROD insufficient-balance note for unsupported countries.
	 */
	public function test_charge_refund_updated_insufficient_funds_without_frod_support_uses_short_note(): void {
		$order = $this->create_refundable_woopayments_order( '10.00' );
		$this->set_woopayments_account_country( 'HK' );

		$this->sut->process(
			$this->create_refund_updated_event(
				array(
					'status'         => 'failed',
					'failure_reason' => 'insufficient_funds',
				)
			)
		);

		$order = wc_get_order( $order->get_id() );

		$this->assertInstanceOf( WC_Order::class, $order );
		$this->assertSame( 'failed', $order->get_meta( '_wcpay_refund_status', true ) );
		$this->assertOrderHasNoteContaining( $order, array( 'Refund of', 'failed', 'insufficient funds in your WooPayments balance' ) );
		$this->assertOrderLacksNoteContaining( $order, array( 'Future Refunds or Disputes (FROD) balance' ) );
	}

	/**
	 * @testdox charge.refund.updated records canceled refunds and deletes the local refund.
	 */
	public function test_charge_refund_updated_canceled_marks_order_failed_and_deletes_refund(): void {
		$order     = $this->create_refundable_woopayments_order( '10.00' );
		$refund    = $this->create_local_refund( $order, 4.00, 'Existing refund' );
		$refund_id = $refund->get_id();
		$refund->update_meta_data( '_wcpay_refund_id', 're_123' );
		$refund->save_meta_data();
		$order->set_status( 'refunded' );
		$order->save();

		$this->sut->process( $this->create_refund_updated_event( array( 'status' => 'canceled' ) ) );

		$order = wc_get_order( $order->get_id() );

		$this->assertInstanceOf( WC_Order::class, $order );
		$this->assertSame( 'failed', $order->get_status() );
		$this->assertFalse( wc_get_order( $refund_id ), 'The matched local refund object should be deleted after the provider marks it canceled.' );
		$this->assertNull( get_post( $refund_id ), 'The matched local refund post should be deleted after the provider marks it canceled.' );
		$this->assertSame( 'failed', $order->get_meta( '_wcpay_refund_status', true ) );
		$this->assertOrderHasNoteContaining( $order, array( 'A refund of', 'cancelled', 'WooPayments', 're_123' ) );
	}

	/**
	 * @testdox charge.refund.updated notes include explicit currency when native multi-currency has additional currencies.
	 */
	public function test_charge_refund_updated_uses_explicit_currency_in_failed_refund_notes(): void {
		update_option( 'wcpay_multi_currency_enabled_currencies', array( 'EUR' ) );
		$order = $this->create_refundable_woopayments_order( '10.00' );

		$this->sut->process(
			$this->create_refund_updated_event(
				array(
					'status'         => 'failed',
					'failure_reason' => 'lost_or_stolen_card',
				)
			)
		);

		$order = wc_get_order( $order->get_id() );

		$this->assertInstanceOf( WC_Order::class, $order );
		$this->assertOrderHasNoteContaining( $order, array( 'A refund of', 'USD', 'WooPayments', 're_123', 'lost or stolen' ) );
	}

	/**
	 * @testdox charge.refund.updated writes success metadata only for matched refunds.
	 */
	public function test_charge_refund_updated_succeeded_updates_matched_refund(): void {
		$order  = $this->create_refundable_woopayments_order( '10.00' );
		$refund = $this->create_local_refund( $order, 4.00, 'Existing refund' );
		$refund->update_meta_data( '_wcpay_refund_id', 're_123' );
		$refund->save_meta_data();

		$this->sut->process(
			$this->create_refund_updated_event(
				array(
					'status'              => 'succeeded',
					'balance_transaction' => 'txn_updated',
				)
			)
		);

		$order  = wc_get_order( $order->get_id() );
		$refund = wc_get_order( $refund->get_id() );

		$this->assertInstanceOf( WC_Order::class, $order );
		$this->assertInstanceOf( WC_Order_Refund::class, $refund );
		$this->assertSame( 'successful', $order->get_meta( '_wcpay_refund_status', true ) );
		$this->assertSame( 'txn_updated', $refund->get_meta( '_wcpay_refund_transaction_id', true ) );
		$this->assertOrderHasNoteContaining( $order, array( 'A refund of', 'was successfully processed using WooPayments', 're_123' ) );
	}

	/**
	 * @testdox charge.refund.updated ignores succeeded updates without matched refunds.
	 */
	public function test_charge_refund_updated_succeeded_without_matched_refund_is_noop(): void {
		$order = $this->create_refundable_woopayments_order( '10.00' );

		$this->sut->process( $this->create_refund_updated_event( array( 'status' => 'succeeded' ) ) );

		$order = wc_get_order( $order->get_id() );

		$this->assertInstanceOf( WC_Order::class, $order );
		$this->assertCount( 0, $order->get_refunds() );
		$this->assertSame( '', $order->get_meta( '_wcpay_refund_status', true ) );
		$this->assertOrderLacksNoteContaining( $order, array( 'A refund of', 're_123' ) );
	}

	/**
	 * @testdox charge.refund.updated fails closed for unknown statuses.
	 */
	public function test_charge_refund_updated_fails_closed_for_unknown_status(): void {
		$order = $this->create_refundable_woopayments_order( '10.00' );

		$this->expectException( RuntimeException::class );
		$this->expectExceptionMessage( 'Invalid refund update status' );

		$this->sut->process( $this->create_refund_updated_event( array( 'status' => 'requires_action' ) ) );
	}

	/**
	 * @testdox charge.refund.updated fails closed for missing required fields.
	 */
	public function test_charge_refund_updated_fails_closed_for_missing_required_fields(): void {
		$this->create_refundable_woopayments_order( '10.00' );

		$this->expectException( RuntimeException::class );
		$this->expectExceptionMessage( 'missing required field: status' );

		$this->sut->process( $this->create_refund_updated_event( array( 'status' => '' ) ) );
	}

	/**
	 * @testdox Delivery hooks fire for successful no-op events.
	 */
	public function test_delivery_hooks_fire_for_successful_noop_events(): void {
		$hook_calls = array();
		add_action(
			'woocommerce_payments_before_webhook_delivery',
			function ( string $event_type, array $event_body ) use ( &$hook_calls ): void {
				$hook_calls[] = array( 'before', $event_type, $event_body['id'] );
			},
			10,
			2
		);
		add_action(
			'woocommerce_payments_after_webhook_delivery',
			function ( string $event_type, array $event_body ) use ( &$hook_calls ): void {
				$hook_calls[] = array( 'after', $event_type, $event_body['id'] );
			},
			10,
			2
		);

		$this->sut->process( $this->create_payment_intent_event( 'customer.created', $this->create_woopayments_order() ) );

		$this->assertSame(
			array(
				array( 'before', 'customer.created', 'evt_123' ),
				array( 'after', 'customer.created', 'evt_123' ),
			),
			$hook_calls
		);
	}

	/**
	 * @testdox Delivery hook errors are logged through the WooPayments runtime logger seam.
	 */
	public function test_delivery_hook_errors_are_logged_through_runtime_logger(): void {
		$logger = new class() {
			/**
			 * Logged messages.
			 *
			 * @var string[]
			 */
			public array $messages = array();

			/**
			 * Record an error message.
			 *
			 * @param string              $message Error message.
			 * @param array<string,mixed> $context Error context.
			 */
			public function error( string $message, array $context = array() ): void {
				$this->messages[] = $message . ':' . ( $context['source'] ?? '' );
			}
		};

		$legacy_proxy = new class() extends LegacyProxy {
			/**
			 * Call a user function.
			 *
			 * @param string $function_name Function name.
			 * @param mixed  ...$parameters Function parameters.
			 * @return mixed
			 */
			public function call_function( $function_name, ...$parameters ) {
				if ( 'do_action' === $function_name ) {
					throw new RuntimeException( 'Delivery hook failed.' );
				}

				if ( 'wc_get_logger' === $function_name ) {
					throw new RuntimeException( 'Direct logger lookup should not be used.' );
				}

				return parent::call_function( $function_name, ...$parameters );
			}
		};

		$runtime = new WooPaymentsLegacyRuntime();
		$runtime->init( new LegacyRuntimeProxy( true, null, null, null, $logger ) );

		$sut = new WooPaymentsEventIngestor();
		$sut->init(
			wc_get_container()->get( OrderPaymentLifecycleService::class ),
			$legacy_proxy,
			$runtime,
			new class() extends WooPaymentsApiClient {
				/**
				 * Retrieve a WooPayments PaymentIntent.
				 *
				 * @param string $intent_id Intent ID.
				 * @return array<string,mixed>
				 */
				public function get_payment_intention( string $intent_id ): array {
					return array();
				}
			}
		);

		$sut->process( $this->create_payment_intent_event( 'customer.created', $this->create_woopayments_order() ) );

		$this->assertSame(
			array(
				'Delivery hook failed.:native-payments-webhook',
				'Delivery hook failed.:native-payments-webhook',
			),
			$logger->messages
		);
	}

	/**
	 * @testdox Webhook mode mismatch skips processing and delivery hooks.
	 */
	public function test_mode_mismatch_skips_processing_and_delivery_hooks(): void {
		add_filter( WooPaymentsEventIngestor::FILTER_LIVE_MODE, '__return_false' );
		$hook_calls = array();
		add_action(
			'woocommerce_payments_before_webhook_delivery',
			function () use ( &$hook_calls ): void {
				$hook_calls[] = 'before';
			}
		);
		$order = $this->create_woopayments_order();

		$this->sut->process( $this->create_payment_intent_event( 'payment_intent.succeeded', $order, array(), array( 'livemode' => true ) ) );

		$order = wc_get_order( $order->get_id() );

		$this->assertInstanceOf( WC_Order::class, $order );
		$this->assertSame( 'pending', $order->get_status() );
		$this->assertSame( array(), $hook_calls );
	}

	/**
	 * @testdox Mismatched order keys do not mutate the named order.
	 */
	public function test_mismatched_order_key_does_not_mutate_order(): void {
		$order = $this->create_woopayments_order();

		$this->sut->process(
			$this->create_payment_intent_event(
				'payment_intent.succeeded',
				$order,
				array(
					'metadata' => array(
						'order_id'  => (string) $order->get_id(),
						'order_key' => 'wc_order_wrong_key',
					),
				)
			)
		);

		$order = wc_get_order( $order->get_id() );

		$this->assertInstanceOf( WC_Order::class, $order );
		$this->assertSame( 'pending', $order->get_status() );
		$this->assertSame( '', $order->get_meta( '_intent_id', true ) );
	}

	/**
	 * @testdox Malformed events throw an invalid argument exception.
	 */
	public function test_malformed_event_throws_invalid_argument_exception(): void {
		$this->expectException( InvalidArgumentException::class );

		$this->sut->process(
			array(
				'id'   => 'evt_bad',
				'data' => array(),
			)
		);
	}

	/**
	 * Create a WooPayments order for ingestor tests.
	 *
	 * @return WC_Order
	 */
	private function create_woopayments_order(): WC_Order {
		$order = wc_create_order();
		$order->set_payment_method( OrderPaymentStore::GATEWAY_ID );
		$order->set_total( '10.00' );
		$order->save();

		return $order;
	}

	/**
	 * Create an ingestor with mocked account event collaborators.
	 *
	 * @param WooPaymentsAccountService $account_service Account service mock.
	 * @param WooPaymentsTokenService   $token_service   Token service mock.
	 * @return WooPaymentsEventIngestor
	 */
	private function create_ingestor_with_account_services( WooPaymentsAccountService $account_service, WooPaymentsTokenService $token_service ): WooPaymentsEventIngestor {
		update_option( 'woocommerce_woocommerce_payments_settings', array( 'test_mode' => 'yes' ) );

		$runtime = new WooPaymentsLegacyRuntime();
		$runtime->init( new LegacyRuntimeProxy( true ) );

		$sut = new WooPaymentsEventIngestor();
		$sut->init(
			wc_get_container()->get( OrderPaymentLifecycleService::class ),
			new LegacyProxy(),
			$runtime,
			new class() extends WooPaymentsApiClient {},
			null,
			null,
			$account_service,
			$token_service,
			new WooPaymentsRemoteNoteService()
		);

		return $sut;
	}

	/**
	 * Create an account lifecycle event.
	 *
	 * @param string $type       Event type.
	 * @param string $account_id Account ID.
	 * @return array<string,mixed>
	 */
	private function create_account_event( string $type, string $account_id = 'acct_123' ): array {
		return array(
			'id'       => 'evt_account',
			'type'     => $type,
			'livemode' => false,
			'data'     => array(
				'object' => array(
					'id' => $account_id,
				),
			),
		);
	}

	/**
	 * Create a WooPayments order with a captured charge for refund webhook tests.
	 *
	 * @param string $total Order total.
	 * @return WC_Order
	 */
	private function create_refundable_woopayments_order( string $total ): WC_Order {
		$order   = $this->create_woopayments_order();
		$product = \WC_Helper_Product::create_simple_product();
		$product->set_regular_price( $total );
		$product->set_price( $total );
		$product->save();
		$order->add_product(
			$product,
			1,
			array(
				'subtotal' => (float) $total,
				'total'    => (float) $total,
			)
		);
		$charge_id = 'ch_' . $order->get_id();
		$order->set_total( $total );
		$order->set_status( 'processing' );
		$order->update_meta_data( '_charge_id', $charge_id );
		$order->save();
		$this->last_refund_charge_id = $charge_id;

		return $order;
	}

	/**
	 * Create a local refund fixture.
	 *
	 * @param WC_Order $order  Order object.
	 * @param float    $amount Refund amount.
	 * @param string   $reason Refund reason.
	 * @return WC_Order_Refund
	 */
	private function create_local_refund( WC_Order $order, float $amount, string $reason ): WC_Order_Refund {
		$refund = wc_create_refund(
			array(
				'order_id'       => $order->get_id(),
				'amount'         => $amount,
				'reason'         => $reason,
				'refund_payment' => false,
			)
		);

		if ( ! $refund instanceof WC_Order_Refund ) {
			$this->fail( 'Expected local refund creation to return a WC_Order_Refund.' );
		}

		return $refund;
	}

	/**
	 * Set cached WooPayments account country data.
	 *
	 * @param string $country Account country.
	 */
	private function set_woopayments_account_country( string $country ): void {
		update_option(
			'wcpay_account_data',
			array(
				'data' => array(
					'account_id' => 'acct_123',
					'country'    => $country,
				),
			)
		);
	}

	/**
	 * Assert that an order has a note with the expected exact content.
	 *
	 * @param WC_Order $order    Order object.
	 * @param string   $expected Expected note content.
	 */
	private function assertOrderHasNote( WC_Order $order, string $expected ): void {
		$count = 0;
		$notes = wc_get_order_notes(
			array(
				'order_id' => $order->get_id(),
				'type'     => 'any',
			)
		);

		foreach ( $notes as $note ) {
			if ( $expected === $note->content ) {
				++$count;
			}
		}

		$this->assertGreaterThan( 0, $count, "Missing order note: {$expected}" );
	}

	/**
	 * Assert that an order has a note containing all expected fragments.
	 *
	 * @param WC_Order $order     Order object.
	 * @param string[] $fragments Expected note fragments.
	 */
	private function assertOrderHasNoteContaining( WC_Order $order, array $fragments ): void {
		$notes = wc_get_order_notes(
			array(
				'order_id' => $order->get_id(),
				'type'     => 'any',
			)
		);

		foreach ( $notes as $note ) {
			$content = (string) $note->content;
			foreach ( $fragments as $fragment ) {
				if ( false === strpos( $content, $fragment ) ) {
					continue 2;
				}
			}

			$this->addToAssertionCount( 1 );
			return;
		}

		$this->fail( 'Missing order note containing: ' . implode( ', ', $fragments ) . '. Notes: ' . wp_json_encode( wp_list_pluck( $notes, 'content' ) ) );
	}

	/**
	 * Assert that an order does not have a note containing all expected fragments.
	 *
	 * @param WC_Order $order     Order object.
	 * @param string[] $fragments Expected note fragments.
	 */
	private function assertOrderLacksNoteContaining( WC_Order $order, array $fragments ): void {
		$notes = wc_get_order_notes(
			array(
				'order_id' => $order->get_id(),
				'type'     => 'any',
			)
		);

		foreach ( $notes as $note ) {
			$content = (string) $note->content;
			foreach ( $fragments as $fragment ) {
				if ( false === strpos( $content, $fragment ) ) {
					continue 2;
				}
			}

			$this->fail( 'Unexpected order note containing: ' . implode( ', ', $fragments ) . '. Notes: ' . wp_json_encode( wp_list_pluck( $notes, 'content' ) ) );
		}

		$this->addToAssertionCount( 1 );
	}

	/**
	 * Seed stale dispute cache options.
	 */
	private function seed_dispute_cache_options(): void {
		foreach ( $this->get_dispute_cache_option_keys() as $key ) {
			update_option( $key, array( 'stale' => true ), false );
		}
	}

	/**
	 * Assert that dispute cache options were deleted.
	 */
	private function assert_dispute_cache_options_deleted(): void {
		foreach ( $this->get_dispute_cache_option_keys() as $key ) {
			$this->assertFalse( get_option( $key, false ), "Expected dispute cache option {$key} to be deleted." );
		}
	}

	/**
	 * Delete dispute cache options.
	 */
	private function delete_dispute_cache_options(): void {
		foreach ( $this->get_dispute_cache_option_keys() as $key ) {
			delete_option( $key );
		}
	}

	/**
	 * Get the reference WooPayments dispute cache option keys.
	 *
	 * @return string[]
	 */
	private function get_dispute_cache_option_keys(): array {
		return array(
			'wcpay_dispute_status_counts_cache',
			'wcpay_test_dispute_status_counts_cache',
			'wcpay_active_dispute_cache',
		);
	}

	/**
	 * Unset a nested field under the dispute event object.
	 *
	 * @param array<string,mixed> $event Dispute event.
	 * @param string[]            $path  Field path under data.object.
	 */
	private function unset_dispute_object_path( array &$event, array $path ): void {
		$target = &$event['data']['object'];
		$last   = array_pop( $path );
		foreach ( $path as $segment ) {
			$target = &$target[ $segment ];
		}

		unset( $target[ $last ] );
	}

	/**
	 * Create a dispute-shaped event.
	 *
	 * @param string              $type      Event type.
	 * @param string              $status    Dispute status.
	 * @param array<string,mixed> $overrides Object overrides.
	 * @return array<string,mixed>
	 */
	private function create_dispute_event( string $type, string $status, array $overrides = array() ): array {
		$object = array_replace_recursive(
			array(
				'id'               => 'du_123',
				'charge'           => 'ch_123',
				'amount'           => 5000,
				'reason'           => 'fraudulent',
				'status'           => $status,
				'evidence_details' => array(
					'due_by' => strtotime( '2026-07-01 00:00:00 UTC' ),
				),
			),
			$overrides
		);

		return array(
			'id'   => 'evt_dispute',
			'type' => $type,
			'data' => array(
				'object' => $object,
			),
		);
	}

	/**
	 * Create a charge.refunded event.
	 *
	 * @param WC_Order            $order         Order object.
	 * @param int                 $charge_amount Charge amount in provider minor units.
	 * @param int                 $refund_amount Refund amount in provider minor units.
	 * @param string              $refund_status Provider refund status.
	 * @param array<string,mixed> $overrides     Charge object overrides.
	 * @return array<string,mixed>
	 */
	private function create_charge_refunded_event( WC_Order $order, int $charge_amount, int $refund_amount, string $refund_status, array $overrides = array() ): array {
		$object = array_replace_recursive(
			array(
				'id'       => (string) $order->get_meta( '_charge_id', true ),
				'status'   => 'succeeded',
				'amount'   => $charge_amount,
				'currency' => 'usd',
				'captured' => true,
				'metadata' => array(
					'order_id'  => (string) $order->get_id(),
					'order_key' => $order->get_order_key(),
				),
				'refunds'  => array(
					'data' => array(
						array(
							'id'                  => 're_123',
							'status'              => $refund_status,
							'amount'              => $refund_amount,
							'currency'            => 'usd',
							'reason'              => 'requested_by_customer',
							'balance_transaction' => 'txn_123',
						),
					),
				),
			),
			$overrides
		);

		return array(
			'id'   => 'evt_refunded',
			'type' => 'charge.refunded',
			'data' => array(
				'object' => $object,
			),
		);
	}

	/**
	 * Create a charge.refund.updated event.
	 *
	 * @param array<string,mixed> $overrides Refund object overrides.
	 * @return array<string,mixed>
	 */
	private function create_refund_updated_event( array $overrides = array() ): array {
		$object = array_replace_recursive(
			array(
				'id'       => 're_123',
				'charge'   => $this->last_refund_charge_id,
				'amount'   => 400,
				'currency' => 'usd',
				'status'   => 'failed',
			),
			$overrides
		);

		return array(
			'id'   => 'evt_refund_updated',
			'type' => 'charge.refund.updated',
			'data' => array(
				'object' => $object,
			),
		);
	}

	/**
	 * Create a dispute summary API client.
	 *
	 * @param array<string,mixed> $summary Dispute summary.
	 * @return WooPaymentsApiClient
	 */
	private function create_dispute_summary_api_client( array $summary = array() ): WooPaymentsApiClient {
		return new class( $summary ) extends WooPaymentsApiClient {
			/**
			 * Dispute summary response.
			 *
			 * @var array<string,mixed>
			 */
			private array $summary;

			/**
			 * Constructor.
			 *
			 * @param array<string,mixed> $summary Dispute summary response.
			 */
			public function __construct( array $summary ) {
				$this->summary = $summary;
			}

			/**
			 * Retrieve a WooPayments dispute summary.
			 *
			 * @param string $dispute_id Dispute ID.
			 * @return array<string,mixed>
			 */
			public function get_dispute_summary( string $dispute_id ): array {
				return $this->summary;
			}
		};
	}

	/**
	 * Get the expected dispute URL.
	 *
	 * @param string $charge_id Charge ID.
	 * @return string
	 */
	private function get_expected_dispute_url( string $charge_id ): string {
		return Utils::wc_payments_settings_url(
			'/woopayments/transactions/details',
			array(
				'id' => $charge_id,
			)
		);
	}

	/**
	 * Create a payment-intent-shaped event.
	 *
	 * @param string              $type      Event type.
	 * @param WC_Order            $order     Order object.
	 * @param array<string,mixed> $overrides Object overrides.
	 * @param array<string,mixed> $event_overrides Event overrides.
	 * @return array<string,mixed>
	 */
	private function create_payment_intent_event( string $type, WC_Order $order, array $overrides = array(), array $event_overrides = array() ): array {
		$object = array_replace_recursive(
			array(
				'id'             => 'pi_123',
				'status'         => 'succeeded',
				'currency'       => 'usd',
				'amount'         => 1234,
				'payment_method' => 'pm_123',
				'metadata'       => array(
					'order_id'    => (string) $order->get_id(),
					'order_key'   => $order->get_order_key(),
					'ipp_channel' => 'mobile_pos',
				),
				'charges'        => array(
					'data' => array(
						array(
							'id'                     => 'ch_123',
							'payment_method'         => 'pm_123',
							'application_fee_amount' => 123,
							'payment_method_details' => array(
								'card' => array(
									'mandate' => 'mandate_123',
								),
							),
						),
					),
				),
			),
			$overrides
		);

		return array_replace_recursive(
			array(
				'id'   => 'evt_123',
				'type' => $type,
				'data' => array(
					'object' => $object,
				),
			),
			$event_overrides
		);
	}
}
