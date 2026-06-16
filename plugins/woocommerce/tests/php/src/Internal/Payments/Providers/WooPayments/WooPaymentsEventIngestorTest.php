<?php
declare( strict_types = 1 );

namespace Automattic\WooCommerce\Tests\Internal\Payments\Providers\WooPayments;

use Automattic\WooCommerce\Internal\Payments\OrderPaymentStore;
use Automattic\WooCommerce\Internal\Payments\OrderPaymentLifecycleService;
use Automattic\WooCommerce\Internal\Payments\Providers\WooPayments\Api\WooPaymentsApiClient;
use Automattic\WooCommerce\Internal\Payments\Providers\WooPayments\WooPaymentsEventIngestor;
use Automattic\WooCommerce\Internal\Payments\Providers\WooPayments\WooPaymentsLegacyRuntime;
use Automattic\WooCommerce\Proxies\LegacyProxy;
use InvalidArgumentException;
use RuntimeException;
use WC_Order;
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
	 * Set up test fixtures.
	 */
	public function setUp(): void {
		parent::setUp();
		$this->sut = wc_get_container()->get( WooPaymentsEventIngestor::class );
	}

	/**
	 * Tear down test fixtures.
	 */
	public function tearDown(): void {
		remove_all_filters( WooPaymentsEventIngestor::FILTER_LIVE_MODE );
		remove_all_actions( 'woocommerce_payments_before_webhook_delivery' );
		remove_all_actions( 'woocommerce_payments_after_webhook_delivery' );
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
	 * @testdox Known unmigrated WooPayments event types fail closed.
	 */
	public function test_known_unhandled_woopayments_event_type_fails_closed(): void {
		$order = $this->create_woopayments_order();

		$this->expectException( RuntimeException::class );

		$this->sut->process( $this->create_payment_intent_event( 'charge.refunded', $order ) );
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
