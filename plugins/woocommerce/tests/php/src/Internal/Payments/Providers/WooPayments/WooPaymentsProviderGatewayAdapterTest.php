<?php
declare( strict_types = 1 );

namespace Automattic\WooCommerce\Tests\Internal\Payments\Providers\WooPayments;

use Automattic\WooCommerce\Internal\Payments\OrderPaymentStore;
use Automattic\WooCommerce\Internal\Payments\PaymentContext;
use Automattic\WooCommerce\Internal\Payments\PaymentOutcome;
use Automattic\WooCommerce\Internal\Payments\Providers\WooPayments\Api\WooPaymentsApiClient;
use Automattic\WooCommerce\Internal\Payments\Providers\WooPayments\Api\WooPaymentsApiException;
use Automattic\WooCommerce\Internal\Payments\Providers\WooPayments\WooPaymentsAccountService;
use Automattic\WooCommerce\Internal\Payments\Providers\WooPayments\WooPaymentsCustomerService;
use Automattic\WooCommerce\Internal\Payments\Providers\WooPayments\WooPaymentsExpressPaymentMethodTypes;
use Automattic\WooCommerce\Internal\Payments\Providers\WooPayments\WooPaymentsLegacyRuntime;
use Automattic\WooCommerce\Internal\Payments\Providers\WooPayments\WooPaymentsPaymentMethodDetailsService;
use Automattic\WooCommerce\Internal\Payments\Providers\WooPayments\WooPaymentsProviderGatewayAdapter;
use Automattic\WooCommerce\Internal\Payments\Providers\WooPayments\WooPaymentsTokenService;
use WC_Order;
use WC_Payment_Token_CC;
use WC_Unit_Test_Case;
use WP_Error;

/**
 * Tests for the WooPaymentsProviderGatewayAdapter class.
 */
class WooPaymentsProviderGatewayAdapterTest extends WC_Unit_Test_Case {

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
	}

	/**
	 * Tear down test fixtures.
	 */
	public function tearDown(): void {
		remove_all_filters( 'woocommerce_native_woopayments_is_recurring_payment' );
		remove_all_filters( 'woocommerce_native_woopayments_related_subscriptions_for_order' );
		remove_all_filters( 'wcpay_metadata_from_order' );
		delete_option( 'woocommerce_tax_based_on' );
		delete_option( 'woocommerce_calc_taxes' );
		update_option( 'woocommerce_currency', $this->original_currency );
		parent::tearDown();
	}

	/**
	 * @testdox Charge should normalize legacy confirmation redirects to customer-action outcomes.
	 */
	public function test_charge_normalizes_confirmation_redirect_to_customer_action(): void {
		$order   = $this->create_woopayments_order();
		$gateway = new RecordingLegacyGateway(
			array(
				'result'         => 'success',
				'redirect'       => '#wcpay-confirm-pi:123:secret:nonce',
				'payment_method' => 'pm_123',
			)
		);
		$sut     = $this->create_adapter( $gateway );

		$outcome = $sut->charge( PaymentContext::for_checkout( $order, OrderPaymentStore::GATEWAY_ID, 'pm_123' ), 'key_charge' );

		$this->assertSame( PaymentOutcome::STATUS_REQUIRES_CUSTOMER_ACTION, $outcome->get_status() );
		$this->assertSame( '#wcpay-confirm-pi:123:secret:nonce', $outcome->get_redirect_url() );
		$this->assertSame( 'pm_123', $outcome->get_payment_method_id() );
		$this->assertSame( $order->get_id(), $gateway->processed_order_id );
		$this->assertSame( 'key_charge', $gateway->last_idempotency_key );
	}

	/**
	 * @testdox Charge should normalize legacy offsite redirects to redirect outcomes.
	 */
	public function test_charge_normalizes_offsite_redirect_to_redirect_outcome(): void {
		$order = $this->create_woopayments_order();
		$sut   = $this->create_adapter(
			new RecordingLegacyGateway(
				array(
					'result'   => 'success',
					'redirect' => 'https://example.test/redirect',
				)
			)
		);

		$outcome = $sut->charge( PaymentContext::for_checkout( $order, OrderPaymentStore::GATEWAY_ID ), 'key_charge' );

		$this->assertSame( PaymentOutcome::STATUS_REQUIRES_REDIRECT, $outcome->get_status() );
		$this->assertSame( 'https://example.test/redirect', $outcome->get_redirect_url() );
	}

	/**
	 * @testdox Charge should preserve manual-capture outcomes written by the legacy gateway.
	 */
	public function test_charge_preserves_manual_capture_outcome_from_order_meta(): void {
		$order   = $this->create_woopayments_order();
		$gateway = new RecordingLegacyGateway(
			array(
				'result'   => 'success',
				'redirect' => $order->get_checkout_order_received_url(),
			)
		);

		$gateway->intent_id_to_write         = 'pi_manual';
		$gateway->intention_status_to_write  = 'requires_capture';
		$gateway->payment_method_id_to_write = 'pm_manual';

		$sut = $this->create_adapter( $gateway );

		$outcome = $sut->charge( PaymentContext::for_checkout( $order, OrderPaymentStore::GATEWAY_ID, 'pm_manual' ), 'key_charge' );

		$this->assertSame( PaymentOutcome::STATUS_AUTHORIZED, $outcome->get_status() );
		$this->assertSame( 'pi_manual', $outcome->get_provider_payment_id() );
		$this->assertSame( 'pm_manual', $outcome->get_payment_method_id() );
	}

	/**
	 * @testdox Charge should preserve pending successful legacy responses without completing the order.
	 */
	public function test_charge_preserves_pending_success_without_order_completion(): void {
		$order = $this->create_woopayments_order();
		$sut   = $this->create_adapter(
			new RecordingLegacyGateway(
				array(
					'result'   => 'success',
					'redirect' => '',
				)
			)
		);

		$outcome = $sut->charge( PaymentContext::for_checkout( $order, OrderPaymentStore::GATEWAY_ID ), 'key_charge' );

		$this->assertSame( PaymentOutcome::STATUS_PENDING_ASYNC, $outcome->get_status() );
		$this->assertArrayHasKey( 'checkout_redirect', $outcome->get_data() );
		$this->assertSame( '', $outcome->get_data()['checkout_redirect'] );
	}

	/**
	 * @testdox Charge should normalize legacy failures to failed outcomes.
	 */
	public function test_charge_normalizes_failure_to_failed_outcome(): void {
		$order = $this->create_woopayments_order();
		$sut   = $this->create_adapter(
			new RecordingLegacyGateway(
				array(
					'result' => 'fail',
				)
			)
		);

		$outcome = $sut->charge( PaymentContext::for_checkout( $order, OrderPaymentStore::GATEWAY_ID ), 'key_charge' );

		$this->assertSame( PaymentOutcome::STATUS_FAILED, $outcome->get_status() );
		$this->assertSame( 'legacy_process_payment_failed', $outcome->get_data()['error_code'] );
	}

	/**
	 * @testdox Charge should prefer the native positive-amount transport before the legacy gateway bridge.
	 */
	public function test_charge_prefers_native_positive_amount_transport_when_available(): void {
		$order            = $this->create_woopayments_order( '50.00' );
		$gateway          = new RecordingLegacyGateway( array( 'result' => 'success' ) );
		$api_client       = new class() extends WooPaymentsApiClient {
			/**
			 * Create and confirm a payment intention.
			 *
			 * @param array<string,mixed> $request_data Request data.
			 * @param string              $idempotency_key Idempotency key.
			 * @return array<string,mixed>
			 */
			public function create_and_confirm_payment_intention( array $request_data, string $idempotency_key ): array {
				if ( 5000 !== $request_data['amount']
					|| 'usd' !== $request_data['currency']
					|| 'cus_native' !== $request_data['customer']
					|| 'pm_request' !== $request_data['payment_method']
					|| array( 'card' ) !== $request_data['payment_method_types']
					|| 'key_charge' !== $idempotency_key ) {
					throw new \RuntimeException( 'Unexpected native charge request payload.' );
				}

				return array(
					'id'             => 'pi_native',
					'status'         => 'succeeded',
					'client_secret'  => 'secret_native',
					'customer'       => 'cus_native',
					'payment_method' => 'pm_native',
					'currency'       => 'usd',
					'charges'        => array(
						'total_count' => 1,
						'data'        => array(
							array(
								'id'                     => 'ch_native',
								'payment_method'         => 'pm_native',
								'payment_method_details' => array(
									'type' => 'card',
									'card' => array(
										'brand'   => 'visa',
										'funding' => 'credit',
										'last4'   => '4242',
										'network' => 'visa',
									),
								),
								'balance_transaction'    => array( 'id' => 'txn_native' ),
								'outcome'                => array( 'risk_level' => 'normal' ),
								'amount'                 => 5000,
								'currency'               => 'usd',
								'application_fee_amount' => 218,
								'fee_breakdown_v1'       => array(
									'totals' => array(
										'fee' => array(
											'amount'   => 175,
											'currency' => 'usd',
										),
										'net' => array(
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
			 * Retrieve a payment intention.
			 *
			 * @param string $intent_id PaymentIntent ID.
			 * @return array<string,mixed>
			 */
			public function get_payment_intention( string $intent_id ): array {
				if ( 'pi_native' !== $intent_id ) {
					throw new \RuntimeException( 'Unexpected native intent read.' );
				}

				return array(
					'id'      => 'pi_native',
					'status'  => 'succeeded',
					'charges' => array(
						'total_count' => 1,
						'data'        => array(
							array(
								'id'                     => 'ch_native',
								'payment_method_details' => array(
									'type' => 'card',
									'card' => array(
										'brand'   => 'visa',
										'funding' => 'credit',
										'last4'   => '4242',
										'network' => 'visa',
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
				);
			}

			/**
			 * Retrieve a payment timeline.
			 *
			 * @param string $intent_id PaymentIntent ID.
			 * @return array<string,mixed>
			 */
			public function get_timeline( string $intent_id ): array {
				if ( 'pi_native' !== $intent_id ) {
					throw new \RuntimeException( 'Unexpected native timeline read.' );
				}

				return array(
					'data' => array(
						array(
							'type'             => 'captured',
							'fee_breakdown_v1' => array(
								'rows'   => array(
									array(
										'key'      => 'base',
										'kind'     => 'fee',
										'amount'   => 175,
										'currency' => 'usd',
										'rate'     => array(
											'percentage' => 0.029,
											'fixed'      => 30,
											'fixed_currency' => 'usd',
										),
									),
								),
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
									'tax'         => array(
										'amount'   => 0,
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
				);
			}

			/**
			 * Tell whether the transport is available.
			 *
			 * @return bool
			 */
			public function is_available(): bool {
				return true;
			}
		};
		$customer_service = $this->getMockBuilder( WooPaymentsCustomerService::class )
			->disableOriginalConstructor()
			->onlyMethods( array( 'get_or_create_customer_id_for_order' ) )
			->getMock();

		$customer_service->expects( $this->once() )
			->method( 'get_or_create_customer_id_for_order' )
			->with( $this->isInstanceOf( WC_Order::class ) )
			->willReturn( 'cus_native' );

		$sut     = $this->create_adapter( $gateway, $api_client, $customer_service, null, $this->create_account_service( true ) );
		$outcome = $sut->charge( PaymentContext::for_checkout( $order, OrderPaymentStore::GATEWAY_ID, 'pm_request' ), 'key_charge' );
		$order   = wc_get_order( $order->get_id() );

		$this->assertInstanceOf( WC_Order::class, $order );
		$this->assertSame( PaymentOutcome::STATUS_COMPLETED, $outcome->get_status() );
		$this->assertSame( 'pi_native', $outcome->get_provider_payment_id() );
		$this->assertSame( 'pm_native', $outcome->get_payment_method_id() );
		$this->assertSame( 'cus_native', $outcome->get_customer_id() );
		$this->assertSame( 0, $gateway->processed_order_id );
		$this->assertSame( '4242', $order->get_meta( 'last4', true ) );
		$this->assertSame( 'visa', $order->get_meta( '_card_brand', true ) );
		$this->assertSame( 'Visa credit card', $order->get_payment_method_title() );
		$this->assertArrayHasKey( 'note', $outcome->get_data() );
		$this->assertStringContainsString( 'A test payment of', $outcome->get_data()['note'] );
		$this->assertStringContainsString( 'USD was processed using WooPayments', $outcome->get_data()['note'] );
		$this->assertStringContainsString( 'was processed using WooPayments in <strong>test mode</strong>', $outcome->get_data()['note'] );
		$this->assertStringContainsString( 'pi_native', $outcome->get_data()['note'] );
		$this->assertStringContainsString( 'page=wc-admin', $outcome->get_data()['note'] );
		$this->assertStringContainsString( 'id=pi_native', $outcome->get_data()['note'] );
		$this->assertStringNotContainsString( '/woopayments/transactions/details', $outcome->get_data()['note'] );
		$this->assertStringNotContainsString( 'transaction_id=txn_native', $outcome->get_data()['note'] );
		$this->assertSame( '1.75', $outcome->get_data()['meta']['_wcpay_transaction_fee'] );
		$this->assertSame( '48.25', $outcome->get_data()['meta']['_wcpay_net'] );
		$outcome_meta = $outcome->get_data()['meta'];
		$this->assertArrayHasKey( '_wcpay_fraud_outcome_status', $outcome_meta );
		$this->assertArrayHasKey( '_wcpay_fraud_meta_box_type', $outcome_meta );
		$this->assertSame( 'allow', $outcome_meta['_wcpay_fraud_outcome_status'] );
		$this->assertSame( 'allow', $outcome_meta['_wcpay_fraud_meta_box_type'] );
		$this->assertOrderDoesNotHaveNoteStartingWith( $order, '<strong>Fee details:</strong>' );
	}

	/**
	 * @testdox Charge should include settlement exchange-rate meta for converted-currency native charges.
	 */
	public function test_charge_includes_settlement_exchange_rate_meta_for_converted_currency_native_charge(): void {
		update_option( 'woocommerce_currency', 'USD' );
		$order = $this->create_woopayments_order( '40.00' );
		$order->set_currency( 'GBP' );
		$order->save();

		$gateway          = new RecordingLegacyGateway( array( 'result' => 'success' ) );
		$api_client       = new class() extends WooPaymentsApiClient {
			/**
			 * Create and confirm a payment intention.
			 *
			 * @param array<string,mixed> $request_data Request data.
			 * @param string              $idempotency_key Idempotency key.
			 * @return array<string,mixed>
			 */
			public function create_and_confirm_payment_intention( array $request_data, string $idempotency_key ): array {
				if ( 4000 !== $request_data['amount'] || 'gbp' !== $request_data['currency'] || 'key_charge' !== $idempotency_key ) {
					throw new \RuntimeException( 'Unexpected converted-currency charge request payload.' );
				}

				return array(
					'id'             => 'pi_converted',
					'status'         => 'succeeded',
					'client_secret'  => 'secret_converted',
					'customer'       => 'cus_native',
					'payment_method' => 'pm_native',
					'currency'       => 'gbp',
					'charges'        => array(
						'total_count' => 1,
						'data'        => array(
							array(
								'id'                     => 'ch_converted',
								'payment_method'         => 'pm_native',
								'balance_transaction'    => array(
									'id'            => 'txn_converted',
									'exchange_rate' => 1.33127,
								),
								'amount'                 => 4000,
								'currency'               => 'gbp',
								'application_fee_amount' => 156,
								'fee_breakdown_v1'       => array(
									'totals' => array(
										'fee' => array(
											'amount'   => 156,
											'currency' => 'usd',
											'rate'     => array(
												'percentage' => 0.039,
												'fixed' => 30,
												'fixed_currency' => 'usd',
											),
										),
										'net' => array(
											'amount'   => 5170,
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
			 * Tell whether the transport is available.
			 *
			 * @return bool
			 */
			public function is_available(): bool {
				return true;
			}
		};
		$customer_service = $this->getMockBuilder( WooPaymentsCustomerService::class )
			->disableOriginalConstructor()
			->onlyMethods( array( 'get_or_create_customer_id_for_order' ) )
			->getMock();

		$customer_service->method( 'get_or_create_customer_id_for_order' )->willReturn( 'cus_native' );

		$sut     = $this->create_adapter(
			$gateway,
			$api_client,
			$customer_service,
			null,
			$this->create_account_service(
				true,
				array(),
				array(
					'store_currencies' => array(
						'default' => 'usd',
					),
				)
			)
		);
		$outcome = $sut->charge( PaymentContext::for_checkout( $order, OrderPaymentStore::GATEWAY_ID, 'pm_request' ), 'key_charge' );

		$this->assertSame( PaymentOutcome::STATUS_COMPLETED, $outcome->get_status() );
		$this->assertSame( '1.33127', $outcome->get_data()['meta']['_wcpay_multi_currency_stripe_exchange_rate'] );
	}

	/**
	 * @testdox Charge should flag platform-created payment methods for WCPay.
	 */
	public function test_charge_flags_platform_created_payment_methods_for_wcpay(): void {
		$order            = $this->create_woopayments_order( '50.00' );
		$gateway          = new RecordingLegacyGateway( array( 'result' => 'success' ) );
		$api_client       = new class() extends WooPaymentsApiClient {
			/**
			 * Last request data.
			 *
			 * @var array<string,mixed>
			 */
			public array $last_request_data = array();

			/**
			 * Tell whether the transport is available.
			 *
			 * @return bool
			 */
			public function is_available(): bool {
				return true;
			}

			/**
			 * Create and confirm a payment intention.
			 *
			 * @param array<string,mixed> $request_data Request data.
			 * @param string              $idempotency_key Idempotency key.
			 * @return array<string,mixed>
			 */
			public function create_and_confirm_payment_intention( array $request_data, string $idempotency_key ): array {
				unset( $idempotency_key );
				$this->last_request_data = $request_data;

				return array(
					'id'             => 'pi_platform',
					'status'         => 'succeeded',
					'client_secret'  => 'secret_platform',
					'customer'       => 'cus_native',
					'payment_method' => 'pm_connected',
					'currency'       => 'usd',
					'charges'        => array(
						'total_count' => 0,
						'data'        => array(),
					),
				);
			}
		};
		$customer_service = $this->getMockBuilder( WooPaymentsCustomerService::class )
			->disableOriginalConstructor()
			->onlyMethods( array( 'get_or_create_customer_id_for_order' ) )
			->getMock();

		$customer_service->expects( $this->once() )
			->method( 'get_or_create_customer_id_for_order' )
			->willReturn( 'cus_native' );

		$sut     = $this->create_adapter( $gateway, $api_client, $customer_service );
		$outcome = $sut->charge(
			PaymentContext::for_checkout(
				$order,
				OrderPaymentStore::GATEWAY_ID,
				'pm_platform',
				array(),
				array( 'is_platform_payment_method' => true )
			),
			'key_charge'
		);

		$this->assertSame( PaymentOutcome::STATUS_COMPLETED, $outcome->get_status() );
		$this->assertSame( 'pm_platform', $api_client->last_request_data['payment_method'] );
		$this->assertTrue( $api_client->last_request_data['is_platform_payment_method'] );
	}

	/**
	 * @testdox Charge should send manual capture mode to native payment intents when enabled.
	 */
	public function test_charge_sends_manual_capture_method_to_native_payment_intents_when_enabled(): void {
		$order            = $this->create_woopayments_order( '50.00' );
		$gateway          = new RecordingLegacyGateway( array( 'result' => 'success' ) );
		$api_client       = new class() extends WooPaymentsApiClient {
			/**
			 * Last request data.
			 *
			 * @var array<string,mixed>
			 */
			public array $last_request_data = array();

			/**
			 * Tell whether the transport is available.
			 *
			 * @return bool
			 */
			public function is_available(): bool {
				return true;
			}

			/**
			 * Create and confirm a payment intention.
			 *
			 * @param array<string,mixed> $request_data Request data.
			 * @param string              $idempotency_key Idempotency key.
			 * @return array<string,mixed>
			 */
			public function create_and_confirm_payment_intention( array $request_data, string $idempotency_key ): array {
				unset( $idempotency_key );
				$this->last_request_data = $request_data;

				return array(
					'id'             => 'pi_manual_native',
					'status'         => 'requires_capture',
					'customer'       => 'cus_manual_native',
					'payment_method' => 'pm_manual_native',
					'currency'       => 'usd',
					'charges'        => array(
						'total_count' => 1,
						'data'        => array(
							array(
								'id'       => 'ch_manual_native',
								'captured' => false,
							),
						),
					),
				);
			}
		};
		$customer_service = $this->getMockBuilder( WooPaymentsCustomerService::class )
			->disableOriginalConstructor()
			->onlyMethods( array( 'get_or_create_customer_id_for_order' ) )
			->getMock();

		$customer_service->expects( $this->once() )
			->method( 'get_or_create_customer_id_for_order' )
			->willReturn( 'cus_manual_native' );

		$sut     = $this->create_adapter(
			$gateway,
			$api_client,
			$customer_service,
			null,
			$this->create_account_service( false, array( 'manual_capture' => 'yes' ) )
		);
		$outcome = $sut->charge( PaymentContext::for_checkout( $order, OrderPaymentStore::GATEWAY_ID, 'pm_manual_native' ), 'key_charge' );

		$this->assertSame( 'manual', $api_client->last_request_data['capture_method'] ?? null );
		$this->assertSame( PaymentOutcome::STATUS_AUTHORIZED, $outcome->get_status() );
	}

	/**
	 * @testdox Scheduled renewal charges should stay automatic when manual capture is enabled.
	 */
	public function test_charge_keeps_scheduled_renewal_capture_method_automatic_when_manual_capture_is_enabled(): void {
		$order            = $this->create_woopayments_order( '50.00' );
		$gateway          = new RecordingLegacyGateway( array( 'result' => 'success' ) );
		$api_client       = new class() extends WooPaymentsApiClient {
			/**
			 * Last request data.
			 *
			 * @var array<string,mixed>
			 */
			public array $last_request_data = array();

			/**
			 * Tell whether the transport is available.
			 *
			 * @return bool
			 */
			public function is_available(): bool {
				return true;
			}

			/**
			 * Create and confirm a payment intention.
			 *
			 * @param array<string,mixed> $request_data Request data.
			 * @param string              $idempotency_key Idempotency key.
			 * @return array<string,mixed>
			 */
			public function create_and_confirm_payment_intention( array $request_data, string $idempotency_key ): array {
				unset( $idempotency_key );
				$this->last_request_data = $request_data;

				return array(
					'id'             => 'pi_renewal_native',
					'status'         => 'succeeded',
					'customer'       => 'cus_renewal_native',
					'payment_method' => 'pm_renewal_native',
					'currency'       => 'usd',
					'charges'        => array(
						'total_count' => 1,
						'data'        => array(
							array(
								'id'       => 'ch_renewal_native',
								'captured' => true,
							),
						),
					),
				);
			}
		};
		$customer_service = $this->getMockBuilder( WooPaymentsCustomerService::class )
			->disableOriginalConstructor()
			->onlyMethods( array( 'get_or_create_customer_id_for_order' ) )
			->getMock();

		$customer_service->expects( $this->once() )
			->method( 'get_or_create_customer_id_for_order' )
			->willReturn( 'cus_renewal_native' );

		$sut     = $this->create_adapter(
			$gateway,
			$api_client,
			$customer_service,
			null,
			$this->create_account_service( false, array( 'manual_capture' => 'yes' ) )
		);
		$outcome = $sut->charge(
			PaymentContext::for_checkout(
				$order,
				OrderPaymentStore::GATEWAY_ID,
				'pm_renewal_native',
				array(),
				array( 'scheduled_subscription_payment' => true )
			),
			'key_charge'
		);

		$this->assertSame( 'automatic', $api_client->last_request_data['capture_method'] ?? null );
		$this->assertSame( PaymentOutcome::STATUS_COMPLETED, $outcome->get_status() );
	}

	/**
	 * @testdox Charge should use the Core-owned account service for native outcome mode metadata.
	 */
	public function test_charge_uses_account_service_mode_for_native_outcome_meta(): void {
		$order            = $this->create_woopayments_order();
		$gateway          = new RecordingLegacyGateway( array( 'result' => 'success' ) );
		$api_client       = new class() extends WooPaymentsApiClient {
			/**
			 * Tell whether the transport is available.
			 *
			 * @return bool
			 */
			public function is_available(): bool {
				return true;
			}

			/**
			 * Create and confirm a payment intention.
			 *
			 * @param array<string,mixed> $request_data Request data.
			 * @param string              $idempotency_key Idempotency key.
			 * @return array<string,mixed>
			 */
			public function create_and_confirm_payment_intention( array $request_data, string $idempotency_key ): array {
				unset( $request_data, $idempotency_key );

				return array(
					'id'             => 'pi_native',
					'status'         => 'succeeded',
					'customer'       => 'cus_native',
					'payment_method' => 'pm_native',
					'currency'       => 'usd',
					'charges'        => array(
						'total_count' => 0,
						'data'        => array(),
					),
				);
			}
		};
		$customer_service = $this->getMockBuilder( WooPaymentsCustomerService::class )
			->disableOriginalConstructor()
			->onlyMethods( array( 'get_or_create_customer_id_for_order' ) )
			->getMock();
		$account_service  = $this->create_account_service( true );

		$customer_service->expects( $this->once() )
			->method( 'get_or_create_customer_id_for_order' )
			->willReturn( 'cus_native' );

		$sut     = $this->create_adapter( $gateway, $api_client, $customer_service, null, $account_service );
		$outcome = $sut->charge( PaymentContext::for_checkout( $order, OrderPaymentStore::GATEWAY_ID, 'pm_request' ), 'key_charge' );

		$this->assertSame( PaymentOutcome::STATUS_COMPLETED, $outcome->get_status() );
		$this->assertSame( 'test', $outcome->get_data()['meta']['_wcpay_mode'] );
	}

	/**
	 * @testdox Charge should recreate and retry when the native transport reports a missing customer.
	 */
	public function test_charge_retries_after_missing_customer_by_recreating_customer(): void {
		$order            = $this->create_woopayments_order();
		$gateway          = new RecordingLegacyGateway( array( 'result' => 'success' ) );
		$api_client       = new class() extends WooPaymentsApiClient {
			/**
			 * Number of attempts.
			 *
			 * @var int
			 */
			private int $attempt = 0;

			/**
			 * Tell whether the transport is available.
			 *
			 * @return bool
			 */
			public function is_available(): bool {
				return true;
			}

			/**
			 * Create and confirm a payment intention.
			 *
			 * @param array<string,mixed> $request_data Request data.
			 * @param string              $idempotency_key Idempotency key.
			 * @return array<string,mixed>
			 */
			public function create_and_confirm_payment_intention( array $request_data, string $idempotency_key ): array {
				unset( $idempotency_key );
				++$this->attempt;

				if ( 1 === $this->attempt ) {
					if ( 'cus_missing' !== $request_data['customer'] ) {
						throw new \RuntimeException( 'First attempt must use the original customer.' );
					}

					throw new WooPaymentsApiException( 'No such customer: customer', 'resource_missing', 404 );
				}

				if ( 'cus_recreated' !== $request_data['customer'] ) {
					throw new \RuntimeException( 'Second attempt must use the recreated customer.' );
				}

				return array(
					'id'             => 'pi_retry',
					'status'         => 'succeeded',
					'client_secret'  => 'secret_retry',
					'customer'       => 'cus_recreated',
					'payment_method' => 'pm_retry',
					'currency'       => 'usd',
					'charges'        => array(
						'total_count' => 0,
						'data'        => array(),
					),
				);
			}
		};
		$customer_service = $this->getMockBuilder( WooPaymentsCustomerService::class )
			->disableOriginalConstructor()
			->onlyMethods( array( 'get_or_create_customer_id_for_order', 'recreate_customer_for_order' ) )
			->getMock();

		$customer_service->expects( $this->once() )
			->method( 'get_or_create_customer_id_for_order' )
			->willReturn( 'cus_missing' );
		$customer_service->expects( $this->once() )
			->method( 'recreate_customer_for_order' )
			->with( $this->isInstanceOf( WC_Order::class ) )
			->willReturn( 'cus_recreated' );

		$sut     = $this->create_adapter( $gateway, $api_client, $customer_service );
		$outcome = $sut->charge( PaymentContext::for_checkout( $order, OrderPaymentStore::GATEWAY_ID, 'pm_request' ), 'key_charge' );

		$this->assertSame( PaymentOutcome::STATUS_COMPLETED, $outcome->get_status() );
		$this->assertSame( 'cus_recreated', $outcome->get_customer_id() );
		$this->assertSame( 0, $gateway->processed_order_id );
	}

	/**
	 * @testdox Charge should preserve server-allowed express checkout payment method types.
	 */
	public function test_charge_preserves_allowed_express_payment_method_types(): void {
		$order            = $this->create_woopayments_order( '50.00' );
		$gateway          = new RecordingLegacyGateway( array( 'result' => 'success' ) );
		$api_client       = new class() extends WooPaymentsApiClient {
			/**
			 * Last request data.
			 *
			 * @var array<string,mixed>
			 */
			public array $last_request_data = array();

			/**
			 * Tell whether the transport is available.
			 *
			 * @return bool
			 */
			public function is_available(): bool {
				return true;
			}

			/**
			 * Create and confirm a payment intention.
			 *
			 * @param array<string,mixed> $request_data Request data.
			 * @param string              $idempotency_key Idempotency key.
			 * @return array<string,mixed>
			 */
			public function create_and_confirm_payment_intention( array $request_data, string $idempotency_key ): array {
				unset( $idempotency_key );
				$this->last_request_data = $request_data;

				return $this->successful_charge_response();
			}

			/**
			 * Create a successful charge response.
			 *
			 * @return array<string,mixed>
			 */
			private function successful_charge_response(): array {
				return array(
					'id'             => 'pi_express',
					'status'         => 'succeeded',
					'client_secret'  => 'secret_express',
					'customer'       => 'cus_native',
					'payment_method' => 'pm_express',
					'currency'       => 'usd',
					'charges'        => array(
						'total_count' => 1,
						'data'        => array(
							array(
								'id'                     => 'ch_express',
								'payment_method'         => 'pm_express',
								'payment_method_details' => array(
									'type' => 'card',
									'card' => array(
										'brand'   => 'visa',
										'funding' => 'credit',
										'last4'   => '4242',
										'network' => 'visa',
									),
								),
							),
						),
					),
				);
			}
		};
		$customer_service = $this->getMockBuilder( WooPaymentsCustomerService::class )
			->disableOriginalConstructor()
			->onlyMethods( array( 'get_or_create_customer_id_for_order' ) )
			->getMock();
		$customer_service->expects( $this->once() )
			->method( 'get_or_create_customer_id_for_order' )
			->willReturn( 'cus_native' );
		$account_service = $this->create_account_service(
			false,
			array(
				'express_checkout_checkout_methods' => array( 'payment_request', 'amazon_pay' ),
				'upe_available_payment_methods'     => array( 'card', 'amazon_pay' ),
				'upe_enabled_payment_method_ids'    => array( 'card', 'amazon_pay' ),
			),
			array(
				'ece_confirmation_tokens_disabled' => false,
			)
		);

		$sut = $this->create_adapter( $gateway, $api_client, $customer_service, null, $account_service );
		$sut->charge(
			PaymentContext::for_checkout(
				$order,
				OrderPaymentStore::GATEWAY_ID,
				'ctoken_express',
				array(),
				array( 'express_payment_method_types' => array( 'card', 'amazon_pay', 'unknown_method' ) )
			),
			'key_charge'
		);

		$this->assertSame( array( 'card', 'amazon_pay' ), $api_client->last_request_data['payment_method_types'] );
		$this->assertSame( 'ctoken_express', $api_client->last_request_data['confirmation_token'] );
		$this->assertArrayNotHasKey( 'payment_method', $api_client->last_request_data );
	}

	/**
	 * @testdox Charge should fail closed when submitted express checkout method types are not server-allowed.
	 */
	public function test_charge_rejects_unallowed_express_payment_method_types(): void {
		$order            = $this->create_woopayments_order( '50.00' );
		$gateway          = new RecordingLegacyGateway( array( 'result' => 'success' ) );
		$api_client       = new class() extends WooPaymentsApiClient {
			/**
			 * Last request data.
			 *
			 * @var array<string,mixed>
			 */
			public array $last_request_data = array();

			/**
			 * Tell whether the transport is available.
			 *
			 * @return bool
			 */
			public function is_available(): bool {
				return true;
			}

			/**
			 * Create and confirm a payment intention.
			 *
			 * @param array<string,mixed> $request_data Request data.
			 * @param string              $idempotency_key Idempotency key.
			 * @return array<string,mixed>
			 */
			public function create_and_confirm_payment_intention( array $request_data, string $idempotency_key ): array {
				unset( $idempotency_key );
				$this->last_request_data = $request_data;

				return array(
					'id'             => 'pi_card',
					'status'         => 'succeeded',
					'client_secret'  => 'secret_card',
					'customer'       => 'cus_native',
					'payment_method' => 'pm_card',
					'currency'       => 'usd',
					'charges'        => array(
						'total_count' => 0,
						'data'        => array(),
					),
				);
			}
		};
		$customer_service = $this->getMockBuilder( WooPaymentsCustomerService::class )
			->disableOriginalConstructor()
			->onlyMethods( array( 'get_or_create_customer_id_for_order' ) )
			->getMock();
		$customer_service->expects( $this->once() )
			->method( 'get_or_create_customer_id_for_order' )
			->willReturn( 'cus_native' );
		$account_service = $this->create_account_service(
			false,
			array(
				'express_checkout_checkout_methods' => array( 'payment_request', 'amazon_pay' ),
				'upe_available_payment_methods'     => array( 'card' ),
				'upe_enabled_payment_method_ids'    => array( 'card', 'amazon_pay' ),
			),
			array(
				'ece_confirmation_tokens_disabled' => false,
			)
		);

		$sut = $this->create_adapter( $gateway, $api_client, $customer_service, null, $account_service );
		$sut->charge(
			PaymentContext::for_checkout(
				$order,
				OrderPaymentStore::GATEWAY_ID,
				'ctoken_express',
				array(),
				array( 'express_payment_method_types' => array( 'card', 'amazon_pay' ) )
			),
			'key_charge'
		);

		$this->assertSame( array( 'card' ), $api_client->last_request_data['payment_method_types'] );
	}

	/**
	 * @testdox Charge should validate express checkout method types against the order currency.
	 */
	public function test_charge_validates_express_payment_method_types_against_order_currency(): void {
		$order = $this->create_woopayments_order( '50.00' );
		$order->set_currency( 'EUR' );
		$order->save();

		$gateway          = new RecordingLegacyGateway( array( 'result' => 'success' ) );
		$api_client       = new class() extends WooPaymentsApiClient {
			/**
			 * Last request data.
			 *
			 * @var array<string,mixed>
			 */
			public array $last_request_data = array();

			/**
			 * Tell whether the transport is available.
			 *
			 * @return bool
			 */
			public function is_available(): bool {
				return true;
			}

			/**
			 * Create and confirm a payment intention.
			 *
			 * @param array<string,mixed> $request_data Request data.
			 * @param string              $idempotency_key Idempotency key.
			 * @return array<string,mixed>
			 */
			public function create_and_confirm_payment_intention( array $request_data, string $idempotency_key ): array {
				unset( $idempotency_key );
				$this->last_request_data = $request_data;

				return array(
					'id'             => 'pi_currency',
					'status'         => 'succeeded',
					'client_secret'  => 'secret_currency',
					'customer'       => 'cus_native',
					'payment_method' => 'pm_currency',
					'currency'       => 'eur',
					'charges'        => array(
						'total_count' => 0,
						'data'        => array(),
					),
				);
			}
		};
		$customer_service = $this->getMockBuilder( WooPaymentsCustomerService::class )
			->disableOriginalConstructor()
			->onlyMethods( array( 'get_or_create_customer_id_for_order' ) )
			->getMock();
		$customer_service->expects( $this->once() )
			->method( 'get_or_create_customer_id_for_order' )
			->willReturn( 'cus_native' );
		$account_service = $this->create_account_service(
			false,
			array(
				'express_checkout_checkout_methods' => array( 'payment_request', 'amazon_pay' ),
				'upe_available_payment_methods'     => array( 'card', 'amazon_pay' ),
				'upe_enabled_payment_method_ids'    => array( 'card', 'amazon_pay' ),
			),
			array(
				'country'                          => 'US',
				'ece_confirmation_tokens_disabled' => false,
			)
		);

		$sut = $this->create_adapter( $gateway, $api_client, $customer_service, null, $account_service );
		$sut->charge(
			PaymentContext::for_checkout(
				$order,
				OrderPaymentStore::GATEWAY_ID,
				'ctoken_express',
				array(),
				array( 'express_payment_method_types' => array( 'card', 'amazon_pay' ) )
			),
			'key_charge'
		);

		$this->assertSame( array( 'card' ), $api_client->last_request_data['payment_method_types'] );
	}

	/**
	 * @testdox Charge should validate express checkout method types against checkout-context settings.
	 */
	public function test_charge_validates_express_payment_method_types_against_checkout_context_settings(): void {
		$order            = $this->create_woopayments_order( '50.00' );
		$gateway          = new RecordingLegacyGateway( array( 'result' => 'success' ) );
		$api_client       = new class() extends WooPaymentsApiClient {
			/**
			 * Last request data.
			 *
			 * @var array<string,mixed>
			 */
			public array $last_request_data = array();

			/**
			 * Tell whether the transport is available.
			 *
			 * @return bool
			 */
			public function is_available(): bool {
				return true;
			}

			/**
			 * Create and confirm a payment intention.
			 *
			 * @param array<string,mixed> $request_data Request data.
			 * @param string              $idempotency_key Idempotency key.
			 * @return array<string,mixed>
			 */
			public function create_and_confirm_payment_intention( array $request_data, string $idempotency_key ): array {
				unset( $idempotency_key );
				$this->last_request_data = $request_data;

				return array(
					'id'             => 'pi_checkout_context',
					'status'         => 'succeeded',
					'client_secret'  => 'secret_checkout_context',
					'customer'       => 'cus_native',
					'payment_method' => 'pm_checkout_context',
					'currency'       => 'usd',
					'charges'        => array(
						'total_count' => 0,
						'data'        => array(),
					),
				);
			}
		};
		$customer_service = $this->getMockBuilder( WooPaymentsCustomerService::class )
			->disableOriginalConstructor()
			->onlyMethods( array( 'get_or_create_customer_id_for_order' ) )
			->getMock();
		$customer_service->expects( $this->once() )
			->method( 'get_or_create_customer_id_for_order' )
			->willReturn( 'cus_native' );
		$account_service = $this->create_account_service(
			false,
			array(
				'express_checkout_cart_methods'     => array( 'payment_request', 'amazon_pay' ),
				'express_checkout_checkout_methods' => array( 'payment_request' ),
				'upe_available_payment_methods'     => array( 'card', 'amazon_pay' ),
				'upe_enabled_payment_method_ids'    => array( 'card', 'amazon_pay' ),
			),
			array(
				'ece_confirmation_tokens_disabled' => false,
			)
		);

		$sut = $this->create_adapter( $gateway, $api_client, $customer_service, null, $account_service );
		$sut->charge(
			PaymentContext::for_checkout(
				$order,
				OrderPaymentStore::GATEWAY_ID,
				'ctoken_express',
				array(),
				array(
					WooPaymentsExpressPaymentMethodTypes::PROVIDER_DATA_KEY    => array( 'card', 'amazon_pay' ),
					WooPaymentsExpressPaymentMethodTypes::PROVIDER_CONTEXT_KEY => 'checkout',
				)
			),
			'key_charge'
		);

		$this->assertSame( array( 'card' ), $api_client->last_request_data['payment_method_types'] );
	}

	/**
	 * @testdox Charge should preserve pay-for-order context while validating express checkout method types.
	 */
	public function test_charge_preserves_pay_for_order_context_for_express_payment_method_types(): void {
		update_option( 'woocommerce_tax_based_on', 'billing' );
		update_option( 'woocommerce_calc_taxes', 'yes' );

		$order            = $this->create_woopayments_order( '50.00' );
		$gateway          = new RecordingLegacyGateway( array( 'result' => 'success' ) );
		$api_client       = new class() extends WooPaymentsApiClient {
			/**
			 * Last request data.
			 *
			 * @var array<string,mixed>
			 */
			public array $last_request_data = array();

			/**
			 * Tell whether the transport is available.
			 *
			 * @return bool
			 */
			public function is_available(): bool {
				return true;
			}

			/**
			 * Create and confirm a payment intention.
			 *
			 * @param array<string,mixed> $request_data Request data.
			 * @param string              $idempotency_key Idempotency key.
			 * @return array<string,mixed>
			 */
			public function create_and_confirm_payment_intention( array $request_data, string $idempotency_key ): array {
				unset( $idempotency_key );
				$this->last_request_data = $request_data;

				return array(
					'id'             => 'pi_order_pay_context',
					'status'         => 'succeeded',
					'client_secret'  => 'secret_order_pay_context',
					'customer'       => 'cus_native',
					'payment_method' => 'pm_order_pay_context',
					'currency'       => 'usd',
					'charges'        => array(
						'total_count' => 0,
						'data'        => array(),
					),
				);
			}
		};
		$customer_service = $this->getMockBuilder( WooPaymentsCustomerService::class )
			->disableOriginalConstructor()
			->onlyMethods( array( 'get_or_create_customer_id_for_order' ) )
			->getMock();
		$customer_service->expects( $this->once() )
			->method( 'get_or_create_customer_id_for_order' )
			->willReturn( 'cus_native' );
		$account_service = $this->create_account_service(
			false,
			array(
				'express_checkout_checkout_methods' => array( 'payment_request', 'amazon_pay' ),
				'upe_available_payment_methods'     => array( 'card', 'amazon_pay' ),
				'upe_enabled_payment_method_ids'    => array( 'card', 'amazon_pay' ),
			),
			array(
				'ece_confirmation_tokens_disabled' => false,
			)
		);

		$sut = $this->create_adapter( $gateway, $api_client, $customer_service, null, $account_service );
		$sut->charge(
			PaymentContext::for_checkout(
				$order,
				OrderPaymentStore::GATEWAY_ID,
				'ctoken_express',
				array(),
				array(
					WooPaymentsExpressPaymentMethodTypes::PROVIDER_DATA_KEY    => array( 'card', 'amazon_pay' ),
					WooPaymentsExpressPaymentMethodTypes::PROVIDER_CONTEXT_KEY => 'pay_for_order',
				)
			),
			'key_charge'
		);

		$this->assertSame( array( 'card', 'amazon_pay' ), $api_client->last_request_data['payment_method_types'] );
	}

	/**
	 * @testdox Charge should resolve saved WooCommerce token IDs before native transport.
	 */
	public function test_charge_resolves_saved_payment_token_before_native_transport(): void {
		$user_id          = $this->factory()->user->create();
		$order            = $this->create_woopayments_order();
		$gateway          = new RecordingLegacyGateway( array( 'result' => 'success' ) );
		$saved_token      = $this->create_card_token( $user_id, 'pm_saved' );
		$api_client       = new class() extends WooPaymentsApiClient {
			/**
			 * Last request data.
			 *
			 * @var array<string,mixed>
			 */
			public array $last_request_data = array();

			/**
			 * Tell whether the transport is available.
			 *
			 * @return bool
			 */
			public function is_available(): bool {
				return true;
			}

			/**
			 * Create and confirm a payment intention.
			 *
			 * @param array<string,mixed> $request_data Request data.
			 * @param string              $idempotency_key Idempotency key.
			 * @return array<string,mixed>
			 */
			public function create_and_confirm_payment_intention( array $request_data, string $idempotency_key ): array {
				if ( 'pm_saved' !== $request_data['payment_method'] || 'key_charge' !== $idempotency_key ) {
					throw new \RuntimeException( 'Saved token was not resolved before the native charge request.' );
				}

				return array(
					'id'             => 'pi_saved',
					'status'         => 'succeeded',
					'customer'       => 'cus_native',
					'payment_method' => 'pm_saved',
					'currency'       => 'usd',
					'charges'        => array(
						'total_count' => 0,
						'data'        => array(),
					),
				);
			}
		};
		$customer_service = $this->getMockBuilder( WooPaymentsCustomerService::class )
			->disableOriginalConstructor()
			->onlyMethods( array( 'get_or_create_customer_id_for_order' ) )
			->getMock();

		$order->set_customer_id( $user_id );
		$order->add_payment_token( $saved_token );
		$order->save();

		$customer_service->expects( $this->once() )
			->method( 'get_or_create_customer_id_for_order' )
			->willReturn( 'cus_native' );

		$sut     = $this->create_adapter( $gateway, $api_client, $customer_service );
		$outcome = $sut->charge(
			PaymentContext::for_checkout(
				$order,
				OrderPaymentStore::GATEWAY_ID,
				'',
				array( 'payment_token' => (string) $saved_token->get_id() )
			),
			'key_charge'
		);
		$order   = wc_get_order( $order->get_id() );

		$this->assertInstanceOf( WC_Order::class, $order );
		$this->assertSame( PaymentOutcome::STATUS_COMPLETED, $outcome->get_status() );
		$this->assertSame( 'pm_saved', $outcome->get_payment_method_id() );
		$this->assertContains( $saved_token->get_id(), $order->get_payment_tokens(), 'Existing saved tokens should be linked to the paid order.' );
	}

	/**
	 * @testdox Scheduled subscription charges should use merchant-initiated recurring request shape.
	 */
	public function test_scheduled_subscription_charge_uses_merchant_initiated_recurring_request_shape(): void {
		$user_id                = $this->factory()->user->create();
		$order                  = $this->create_woopayments_order();
		$gateway                = new RecordingLegacyGateway( array( 'result' => 'success' ) );
		$saved_token            = $this->create_card_token( $user_id, 'pm_saved' );
		$metadata_payment_types = array();
		$api_client             = new class() extends WooPaymentsApiClient {
			/**
			 * Last request data.
			 *
			 * @var array<string,mixed>
			 */
			public array $last_request_data = array();

			/**
			 * Tell whether the transport is available.
			 *
			 * @return bool
			 */
			public function is_available(): bool {
				return true;
			}

			/**
			 * Create and confirm a payment intention.
			 *
			 * @param array<string,mixed> $request_data Request data.
			 * @param string              $idempotency_key Idempotency key.
			 * @return array<string,mixed>
			 */
			public function create_and_confirm_payment_intention( array $request_data, string $idempotency_key ): array {
				if ( 'pm_saved' !== $request_data['payment_method'] || 'key_renewal' !== $idempotency_key ) {
					throw new \RuntimeException( 'Saved token was not resolved before the native renewal request.' );
				}

				$this->last_request_data = $request_data;

				return array(
					'id'             => 'pi_renewal',
					'status'         => 'succeeded',
					'customer'       => 'cus_native',
					'payment_method' => 'pm_saved',
					'currency'       => 'usd',
					'charges'        => array(
						'total_count' => 0,
						'data'        => array(),
					),
				);
			}
		};
		$customer_service       = $this->getMockBuilder( WooPaymentsCustomerService::class )
			->disableOriginalConstructor()
			->onlyMethods( array( 'get_or_create_customer_id_for_order' ) )
			->getMock();
		$metadata_filter        = static function ( array $metadata, WC_Order $filtered_order, string $payment_type ) use ( &$metadata_payment_types, $order ): array {
			if ( $order->get_id() === $filtered_order->get_id() ) {
				$metadata_payment_types[] = $payment_type;
			}

			return $metadata;
		};

		$order->set_customer_id( $user_id );
		$order->add_payment_token( $saved_token );
		$order->save();

		$customer_service->expects( $this->once() )
			->method( 'get_or_create_customer_id_for_order' )
			->willReturn( 'cus_native' );

		add_filter( 'wcpay_metadata_from_order', $metadata_filter, 10, 3 );

		$sut     = $this->create_adapter( $gateway, $api_client, $customer_service );
		$outcome = $sut->charge(
			PaymentContext::for_checkout(
				$order,
				OrderPaymentStore::GATEWAY_ID,
				'',
				array(
					'payment_token'       => (string) $saved_token->get_id(),
					'save_payment_method' => false,
				),
				array(
					'scheduled_subscription_payment' => true,
					'renewal_mandate'                => 'mandate_native',
				)
			),
			'key_renewal'
		);

		$this->assertSame( PaymentOutcome::STATUS_COMPLETED, $outcome->get_status() );
		$this->assertTrue( $api_client->last_request_data['off_session'] );
		$this->assertSame( 'mandate_native', $api_client->last_request_data['mandate'] );
		$this->assertSame( 'recurring', $api_client->last_request_data['metadata']['payment_type'] );
		$this->assertSame( 'renewal', $api_client->last_request_data['metadata']['subscription_payment'] );
		$this->assertSame( 'regular_subscription', $api_client->last_request_data['metadata']['payment_context'] );
		$this->assertSame( array( 'recurring' ), $metadata_payment_types );
		$this->assertArrayNotHasKey( 'setup_future_usage', $api_client->last_request_data );
	}

	/**
	 * @testdox Charge should save and attach requested card tokens before returning a successful native outcome.
	 */
	public function test_charge_saves_and_attaches_requested_card_token(): void {
		$user_id          = $this->factory()->user->create();
		$order            = $this->create_woopayments_order();
		$gateway          = new RecordingLegacyGateway( array( 'result' => 'success' ) );
		$api_client       = new class() extends WooPaymentsApiClient {
			/**
			 * Last request data.
			 *
			 * @var array<string,mixed>
			 */
			public array $last_request_data = array();

			/**
			 * Tell whether the transport is available.
			 *
			 * @return bool
			 */
			public function is_available(): bool {
				return true;
			}

			/**
			 * Create and confirm a payment intention.
			 *
			 * @param array<string,mixed> $request_data Request data.
			 * @param string              $idempotency_key Idempotency key.
			 * @return array<string,mixed>
			 */
			public function create_and_confirm_payment_intention( array $request_data, string $idempotency_key ): array {
				unset( $idempotency_key );
				$this->last_request_data = $request_data;

				return array(
					'id'             => 'pi_native',
					'status'         => 'succeeded',
					'customer'       => 'cus_native',
					'payment_method' => 'pm_native',
					'currency'       => 'usd',
					'charges'        => array(
						'total_count' => 0,
						'data'        => array(),
					),
				);
			}
		};
		$customer_service = $this->getMockBuilder( WooPaymentsCustomerService::class )
			->disableOriginalConstructor()
			->onlyMethods( array( 'get_or_create_customer_id_for_order' ) )
			->getMock();
		$token_service    = $this->create_token_service(
			array(
				'pm_native' => array(
					'id'   => 'pm_native',
					'type' => 'card',
					'card' => array(
						'brand'     => 'visa',
						'last4'     => '4242',
						'exp_month' => 12,
						'exp_year'  => 2030,
					),
				),
			)
		);

		$order->set_customer_id( $user_id );
		$order->save();

		$customer_service->expects( $this->once() )
			->method( 'get_or_create_customer_id_for_order' )
			->willReturn( 'cus_native' );

		$sut     = $this->create_adapter( $gateway, $api_client, $customer_service, $token_service );
		$outcome = $sut->charge(
			PaymentContext::for_checkout(
				$order,
				OrderPaymentStore::GATEWAY_ID,
				'pm_request',
				array( 'save_payment_method' => true )
			),
			'key_charge'
		);
		$order   = wc_get_order( $order->get_id() );
		$tokens  = \WC_Payment_Tokens::get_customer_tokens( $user_id, OrderPaymentStore::GATEWAY_ID );
		$token   = reset( $tokens );

		$this->assertInstanceOf( WC_Order::class, $order );
		$this->assertSame( PaymentOutcome::STATUS_COMPLETED, $outcome->get_status() );
		$this->assertSame( 'off_session', $api_client->last_request_data['setup_future_usage'] );
		$this->assertInstanceOf( WC_Payment_Token_CC::class, $token );
		$this->assertSame( 'pm_native', $token->get_token() );
		$this->assertContains( $token->get_id(), $order->get_payment_tokens(), 'Saved cards should be linked to the paid order.' );
	}

	/**
	 * @testdox Charge should fail recurring orders when immediate native token saving fails.
	 */
	public function test_charge_fails_recurring_order_when_token_save_fails(): void {
		$user_id          = $this->factory()->user->create();
		$order            = $this->create_woopayments_order();
		$gateway          = new RecordingLegacyGateway( array( 'result' => 'success' ) );
		$api_client       = new class() extends WooPaymentsApiClient {
			/**
			 * Tell whether the transport is available.
			 *
			 * @return bool
			 */
			public function is_available(): bool {
				return true;
			}

			/**
			 * Create and confirm a payment intention.
			 *
			 * @param array<string,mixed> $request_data Request data.
			 * @param string              $idempotency_key Idempotency key.
			 * @return array<string,mixed>
			 */
			public function create_and_confirm_payment_intention( array $request_data, string $idempotency_key ): array {
				unset( $idempotency_key );
				$this->last_request_data = $request_data;

				return array(
					'id'             => 'pi_native',
					'status'         => 'succeeded',
					'customer'       => 'cus_native',
					'payment_method' => 'pm_native',
					'currency'       => 'usd',
					'charges'        => array(
						'total_count' => 0,
						'data'        => array(),
					),
				);
			}
		};
		$customer_service = $this->getMockBuilder( WooPaymentsCustomerService::class )
			->disableOriginalConstructor()
			->onlyMethods( array( 'get_or_create_customer_id_for_order' ) )
			->getMock();
		$token_service    = new class() extends WooPaymentsTokenService {
			// phpcs:disable Squiz.Commenting.FunctionComment.InvalidNoReturn -- This test double must fail token saving.
			/**
			 * Fail token creation.
			 *
			 * @param string $payment_method_id Provider payment method ID.
			 * @param int    $user_id           User ID.
			 * @return WC_Payment_Token_CC|null
			 */
			public function get_or_create_card_token_for_user( string $payment_method_id, int $user_id ): ?WC_Payment_Token_CC {
				unset( $payment_method_id, $user_id );

				throw new \RuntimeException( 'Token save failed.' );
			}
			// phpcs:enable Squiz.Commenting.FunctionComment.InvalidNoReturn
		};

		$order->set_customer_id( $user_id );
		$order->save();

		$customer_service->expects( $this->once() )
			->method( 'get_or_create_customer_id_for_order' )
			->willReturn( 'cus_native' );

		add_filter( 'woocommerce_native_woopayments_is_recurring_payment', '__return_true' );

		$sut     = $this->create_adapter( $gateway, $api_client, $customer_service, $token_service );
		$outcome = $sut->charge(
			PaymentContext::for_checkout(
				$order,
				OrderPaymentStore::GATEWAY_ID,
				'pm_request',
				array( 'save_payment_method' => false )
			),
			'key_charge'
		);

		$this->assertSame( PaymentOutcome::STATUS_FAILED, $outcome->get_status() );
		$this->assertSame( 'off_session', $api_client->last_request_data['setup_future_usage'] );
		$this->assertSame( 'wcpay_recurring_token_save_failed', $outcome->get_data()['error_code'] );
		$this->assertSame( 0, $gateway->processed_order_id );
	}

	/**
	 * @testdox Charge should set card display meta before returning a customer-action outcome.
	 */
	public function test_charge_sets_card_display_meta_before_returning_requires_action(): void {
		$order            = $this->create_woopayments_order();
		$gateway          = new RecordingLegacyGateway( array( 'result' => 'success' ) );
		$api_client       = new class() extends WooPaymentsApiClient {
			/**
			 * Tell whether the transport is available.
			 *
			 * @return bool
			 */
			public function is_available(): bool {
				return true;
			}

			/**
			 * Create and confirm a payment intention.
			 *
			 * @param array<string,mixed> $request_data Request data.
			 * @param string              $idempotency_key Idempotency key.
			 * @return array<string,mixed>
			 */
			public function create_and_confirm_payment_intention( array $request_data, string $idempotency_key ): array {
				unset( $request_data, $idempotency_key );

				return array(
					'id'             => 'pi_action',
					'status'         => 'requires_action',
					'client_secret'  => 'secret_action',
					'customer'       => 'cus_native',
					'payment_method' => 'pm_native',
					'currency'       => 'usd',
					'charges'        => array(
						'total_count' => 1,
						'data'        => array(
							array(
								'id'                     => 'ch_native',
								'payment_method_details' => array(
									'type' => 'card',
									'card' => array(
										'brand'   => 'visa',
										'funding' => 'credit',
										'last4'   => '4242',
										'network' => 'visa',
									),
								),
							),
						),
					),
				);
			}
		};
		$customer_service = $this->getMockBuilder( WooPaymentsCustomerService::class )
			->disableOriginalConstructor()
			->onlyMethods( array( 'get_or_create_customer_id_for_order' ) )
			->getMock();

		$customer_service->expects( $this->once() )
			->method( 'get_or_create_customer_id_for_order' )
			->willReturn( 'cus_native' );

		$sut     = $this->create_adapter( $gateway, $api_client, $customer_service );
		$outcome = $sut->charge( PaymentContext::for_checkout( $order, OrderPaymentStore::GATEWAY_ID, 'pm_request' ), 'key_charge' );
		$order   = wc_get_order( $order->get_id() );

		$this->assertInstanceOf( WC_Order::class, $order );
		$this->assertSame( PaymentOutcome::STATUS_REQUIRES_CUSTOMER_ACTION, $outcome->get_status() );
		$this->assertSame( 'ch_native', $outcome->get_data()['charge_id'] ?? null );
		$this->assertStringStartsWith( '#wcpay-confirm-pi:' . $order->get_id() . ':secret_action:', $outcome->get_redirect_url() );
		$this->assertSame( '4242', $order->get_meta( 'last4', true ) );
		$this->assertSame( 'visa', $order->get_meta( '_card_brand', true ) );
		$this->assertNotSame( '', $order->get_meta( '_wcpay_payment_method_details', true ) );
		$this->assertSame( 'Visa credit card', $order->get_payment_method_title() );
		$this->assertSame( 0, $gateway->processed_order_id );
	}

	/**
	 * @testdox Charge should prefer native SetupIntent transport for zero-total card checkout.
	 */
	public function test_charge_prefers_native_setup_intent_for_zero_total_checkout(): void {
		$order            = $this->create_woopayments_order( '0.00' );
		$gateway          = new RecordingLegacyGateway( array( 'result' => 'success' ) );
		$api_client       = new class() extends WooPaymentsApiClient {
			/**
			 * Tell whether the transport is available.
			 *
			 * @return bool
			 */
			public function is_available(): bool {
				return true;
			}

			/**
			 * Create and confirm a setup intention.
			 *
			 * @param array<string,mixed> $request_data Request data.
			 * @param string              $idempotency_key Idempotency key.
			 * @return array<string,mixed>
			 */
			public function create_and_confirm_setup_intention( array $request_data, string $idempotency_key ): array {
				if ( 'cus_native' !== $request_data['customer']
					|| 'pm_zero' !== $request_data['payment_method']
					|| array( 'card' ) !== $request_data['payment_method_types']
					|| 'key_setup' !== $idempotency_key ) {
					throw new \RuntimeException( 'Unexpected native setup intent request payload.' );
				}

				return array(
					'id'             => 'seti_native',
					'status'         => 'succeeded',
					'client_secret'  => 'seti_native_secret_abc',
					'customer'       => 'cus_native',
					'payment_method' => 'pm_native',
				);
			}
		};
		$customer_service = $this->getMockBuilder( WooPaymentsCustomerService::class )
			->disableOriginalConstructor()
			->onlyMethods( array( 'get_or_create_customer_id_for_order' ) )
			->getMock();

		$customer_service->expects( $this->once() )
			->method( 'get_or_create_customer_id_for_order' )
			->with( $this->isInstanceOf( WC_Order::class ) )
			->willReturn( 'cus_native' );

		$sut     = $this->create_adapter( $gateway, $api_client, $customer_service );
		$outcome = $sut->charge( PaymentContext::for_checkout( $order, OrderPaymentStore::GATEWAY_ID, 'pm_zero' ), 'key_setup' );
		$order   = wc_get_order( $order->get_id() );

		$this->assertInstanceOf( WC_Order::class, $order );
		$this->assertSame( PaymentOutcome::STATUS_COMPLETED, $outcome->get_status() );
		$this->assertSame( 'seti_native', $outcome->get_provider_payment_id() );
		$this->assertSame( 'pm_native', $outcome->get_payment_method_id() );
		$this->assertSame( 'cus_native', $outcome->get_customer_id() );
		$this->assertSame( 0, $gateway->processed_order_id );
		$this->assertSame( 'seti_native', $order->get_transaction_id() );
		$this->assertSame( 'pm_native', $order->get_meta( '_payment_method_id', true ) );
	}

	/**
	 * @testdox Zero-total card checkout should flag platform-created payment methods for WCPay.
	 */
	public function test_zero_total_charge_flags_platform_created_payment_methods_for_wcpay(): void {
		$order            = $this->create_woopayments_order( '0.00' );
		$gateway          = new RecordingLegacyGateway( array( 'result' => 'success' ) );
		$api_client       = new class() extends WooPaymentsApiClient {
			/**
			 * Last request data.
			 *
			 * @var array<string,mixed>
			 */
			public array $last_request_data = array();

			/**
			 * Tell whether the transport is available.
			 *
			 * @return bool
			 */
			public function is_available(): bool {
				return true;
			}

			/**
			 * Create and confirm a setup intention.
			 *
			 * @param array<string,mixed> $request_data Request data.
			 * @param string              $idempotency_key Idempotency key.
			 * @return array<string,mixed>
			 */
			public function create_and_confirm_setup_intention( array $request_data, string $idempotency_key ): array {
				unset( $idempotency_key );
				$this->last_request_data = $request_data;

				return array(
					'id'             => 'seti_platform',
					'status'         => 'succeeded',
					'client_secret'  => 'seti_platform_secret_abc',
					'customer'       => 'cus_native',
					'payment_method' => 'pm_connected',
				);
			}
		};
		$customer_service = $this->getMockBuilder( WooPaymentsCustomerService::class )
			->disableOriginalConstructor()
			->onlyMethods( array( 'get_or_create_customer_id_for_order' ) )
			->getMock();

		$customer_service->expects( $this->once() )
			->method( 'get_or_create_customer_id_for_order' )
			->willReturn( 'cus_native' );

		$sut     = $this->create_adapter( $gateway, $api_client, $customer_service );
		$outcome = $sut->charge(
			PaymentContext::for_checkout(
				$order,
				OrderPaymentStore::GATEWAY_ID,
				'pm_platform',
				array(),
				array( 'is_platform_payment_method' => true )
			),
			'key_setup'
		);

		$this->assertSame( PaymentOutcome::STATUS_COMPLETED, $outcome->get_status() );
		$this->assertSame( 'pm_platform', $api_client->last_request_data['payment_method'] );
		$this->assertTrue( $api_client->last_request_data['is_platform_payment_method'] );
	}

	/**
	 * @testdox Zero-total recurring charges should copy the saved token and provider metadata to related subscriptions.
	 */
	public function test_zero_total_recurring_charge_copies_saved_token_to_related_subscriptions(): void {
		$user_id          = $this->factory()->user->create();
		$order            = $this->create_woopayments_order( '0.00' );
		$subscription     = $this->create_woopayments_order( '10.00' );
		$gateway          = new RecordingLegacyGateway( array( 'result' => 'success' ) );
		$api_client       = new class() extends WooPaymentsApiClient {
			/**
			 * Tell whether the transport is available.
			 *
			 * @return bool
			 */
			public function is_available(): bool {
				return true;
			}

			/**
			 * Create and confirm a setup intention.
			 *
			 * @param array<string,mixed> $request_data Request data.
			 * @param string              $idempotency_key Idempotency key.
			 * @return array<string,mixed>
			 */
			public function create_and_confirm_setup_intention( array $request_data, string $idempotency_key ): array {
				unset( $request_data, $idempotency_key );

				return array(
					'id'             => 'seti_native',
					'status'         => 'succeeded',
					'client_secret'  => 'seti_native_secret_abc',
					'customer'       => 'cus_native',
					'payment_method' => 'pm_native',
				);
			}
		};
		$customer_service = $this->getMockBuilder( WooPaymentsCustomerService::class )
			->disableOriginalConstructor()
			->onlyMethods( array( 'get_or_create_customer_id_for_order' ) )
			->getMock();
		$token_service    = $this->create_token_service(
			array(
				'pm_native' => array(
					'id'   => 'pm_native',
					'type' => 'card',
					'card' => array(
						'brand'     => 'visa',
						'last4'     => '4242',
						'exp_month' => 12,
						'exp_year'  => 2030,
					),
				),
			)
		);

		$order->set_customer_id( $user_id );
		$order->save();
		$subscription->set_customer_id( $user_id );
		$subscription->save();

		add_filter( 'woocommerce_native_woopayments_is_recurring_payment', '__return_true' );
		add_filter(
			'woocommerce_native_woopayments_related_subscriptions_for_order',
			static function ( array $subscriptions, WC_Order $filtered_order ) use ( $order, $subscription ): array {
				return $order->get_id() === $filtered_order->get_id() ? array( $subscription ) : $subscriptions;
			},
			10,
			2
		);

		$customer_service->expects( $this->once() )
			->method( 'get_or_create_customer_id_for_order' )
			->willReturn( 'cus_native' );

		$sut     = $this->create_adapter( $gateway, $api_client, $customer_service, $token_service );
		$outcome = $sut->charge( PaymentContext::for_checkout( $order, OrderPaymentStore::GATEWAY_ID, 'pm_zero' ), 'key_setup' );
		$order   = wc_get_order( $order->get_id() );
		$tokens  = \WC_Payment_Tokens::get_customer_tokens( $user_id, OrderPaymentStore::GATEWAY_ID );
		$token   = reset( $tokens );

		$subscription = wc_get_order( $subscription->get_id() );

		$this->assertInstanceOf( WC_Order::class, $order );
		$this->assertInstanceOf( WC_Order::class, $subscription );
		$this->assertSame( PaymentOutcome::STATUS_COMPLETED, $outcome->get_status() );
		$this->assertInstanceOf( WC_Payment_Token_CC::class, $token );
		$this->assertContains( $token->get_id(), $order->get_payment_tokens(), 'Saved cards should be linked to the parent order.' );
		$this->assertContains( $token->get_id(), $subscription->get_payment_tokens(), 'Saved cards should be linked to related subscriptions.' );
		$this->assertSame( 'pm_native', $subscription->get_meta( '_payment_method_id', true ) );
		$this->assertSame( 'cus_native', $subscription->get_meta( '_stripe_customer_id', true ) );
	}

	/**
	 * @testdox Refund should normalize legacy success and errors.
	 */
	public function test_refund_normalizes_legacy_success_and_errors(): void {
		$order   = $this->create_woopayments_order();
		$gateway = new RecordingLegacyGateway( array( 'result' => 'success' ), true );
		$sut     = $this->create_adapter( $gateway );

		$success = $sut->refund( PaymentContext::for_refund( $order, OrderPaymentStore::GATEWAY_ID, 3.50, 'Adjustment' ), 'key_refund' );

		$gateway->refund_result = new WP_Error( 'refund_failed', 'Refund failed.' );
		$failure                = $sut->refund( PaymentContext::for_refund( $order, OrderPaymentStore::GATEWAY_ID, 3.50, 'Adjustment' ), 'key_refund' );

		$this->assertSame( PaymentOutcome::STATUS_COMPLETED, $success->get_status() );
		$this->assertSame( PaymentOutcome::STATUS_FAILED, $failure->get_status() );
		$this->assertSame( 'refund_failed', $failure->get_data()['error_code'] );
		$this->assertSame( 3.50, $gateway->refund_amount );
		$this->assertSame( 'Adjustment', $gateway->refund_reason );
		$this->assertSame( 'key_refund', $gateway->last_idempotency_key );
	}

	/**
	 * @testdox Refund should prefer the native transport before the legacy gateway bridge.
	 */
	public function test_refund_prefers_native_transport_when_available(): void {
		$order      = $this->create_woopayments_order();
		$gateway    = new RecordingLegacyGateway( array( 'result' => 'success' ), true );
		$api_client = $this->getMockBuilder( WooPaymentsApiClient::class )
			->disableOriginalConstructor()
			->onlyMethods( array( 'is_available', 'refund_charge' ) )
			->getMock();

		$order->update_meta_data( '_charge_id', 'ch_native' );
		$order->save();

		$api_client->expects( $this->once() )
			->method( 'is_available' )
			->willReturn( true );
		$api_client->expects( $this->once() )
			->method( 'refund_charge' )
			->with( 'ch_native', 350, 'Adjustment', $this->isType( 'string' ), 'key_refund' )
			->willReturn(
				array(
					'id'                  => 're_native',
					'status'              => 'pending',
					'balance_transaction' => array( 'id' => 'txn_refund' ),
				)
			);

		$sut     = $this->create_adapter( $gateway, $api_client );
		$outcome = $sut->refund( PaymentContext::for_refund( $order, OrderPaymentStore::GATEWAY_ID, 3.50, 'Adjustment' ), 'key_refund' );

		$this->assertSame( PaymentOutcome::STATUS_COMPLETED, $outcome->get_status() );
		$this->assertSame( 're_native', $outcome->get_provider_payment_id() );
		$this->assertSame( 'pending', $outcome->get_data()['refund_status'] );
		$this->assertSame( 'txn_refund', $outcome->get_data()['refund_balance_transaction_id'] );
		$this->assertSame( 'pending', $outcome->get_data()['order_meta']['_wcpay_refund_status'] );
		$this->assertSame( 're_native', $outcome->get_data()['refund_meta']['_wcpay_refund_id'] );
		$this->assertSame( 'txn_refund', $outcome->get_data()['refund_meta']['_wcpay_refund_transaction_id'] );
		$this->assertStringContainsString( 'is pending', $outcome->get_data()['refund_note'] );
		$this->assertStringContainsString( 're_native', $outcome->get_data()['refund_note'] );
		$this->assertNull( $gateway->refund_amount );
	}

	/**
	 * @testdox Refund should fail closed when the native transport returns a failed provider status.
	 */
	public function test_refund_fails_closed_for_failed_native_refund_status(): void {
		$order      = $this->create_woopayments_order();
		$gateway    = new RecordingLegacyGateway( array( 'result' => 'success' ), true );
		$api_client = $this->getMockBuilder( WooPaymentsApiClient::class )
			->disableOriginalConstructor()
			->onlyMethods( array( 'is_available', 'refund_charge' ) )
			->getMock();

		$order->update_meta_data( '_charge_id', 'ch_native' );
		$order->save();

		$api_client->expects( $this->once() )
			->method( 'is_available' )
			->willReturn( true );
		$api_client->expects( $this->once() )
			->method( 'refund_charge' )
			->with( 'ch_native', 350, 'Adjustment', $this->isType( 'string' ), 'key_refund' )
			->willReturn(
				array(
					'id'             => 're_failed',
					'status'         => 'failed',
					'failure_reason' => 'lost_or_stolen_card',
				)
			);

		$sut     = $this->create_adapter( $gateway, $api_client );
		$outcome = $sut->refund( PaymentContext::for_refund( $order, OrderPaymentStore::GATEWAY_ID, 3.50, 'Adjustment' ), 'key_refund' );

		$this->assertSame( PaymentOutcome::STATUS_FAILED, $outcome->get_status() );
		$this->assertSame( 're_failed', $outcome->get_provider_payment_id() );
		$this->assertSame( 'failed', $outcome->get_data()['refund_status'] );
		$this->assertSame( 'lost_or_stolen_card', $outcome->get_data()['error_code'] );
		$this->assertStringContainsString( 'failed', $outcome->get_data()['error_message'] );
		$this->assertStringContainsString( 'lost_or_stolen_card', $outcome->get_data()['error_message'] );
		$this->assertArrayNotHasKey( 'refund_meta', $outcome->get_data() );
		$this->assertArrayNotHasKey( 'order_meta', $outcome->get_data() );
		$this->assertNull( $gateway->refund_amount );
	}

	/**
	 * @testdox Capture should normalize legacy capture statuses.
	 */
	public function test_capture_normalizes_legacy_capture_statuses(): void {
		$order   = $this->create_woopayments_order();
		$gateway = new RecordingLegacyGateway(
			array( 'result' => 'success' ),
			true,
			array(
				'status' => 'succeeded',
				'id'     => 'pi_captured',
			)
		);
		$sut     = $this->create_adapter( $gateway );

		$outcome = $sut->capture( PaymentContext::for_capture( $order, OrderPaymentStore::GATEWAY_ID ), 'key_capture' );

		$this->assertSame( PaymentOutcome::STATUS_COMPLETED, $outcome->get_status() );
		$this->assertSame( 'pi_captured', $outcome->get_provider_payment_id() );
		$this->assertSame( 'key_capture', $gateway->last_idempotency_key );
	}

	/**
	 * @testdox Capture should prefer the native transport before the legacy gateway bridge.
	 */
	public function test_capture_prefers_native_transport_when_available(): void {
		$order      = $this->create_woopayments_order();
		$gateway    = new RecordingLegacyGateway( array( 'result' => 'success' ), true );
		$api_client = $this->getMockBuilder( WooPaymentsApiClient::class )
			->disableOriginalConstructor()
			->onlyMethods( array( 'is_available', 'capture_intention' ) )
			->getMock();

		$order->set_transaction_id( 'pi_capture' );
		$order->save();

		$api_client->expects( $this->once() )
			->method( 'is_available' )
			->willReturn( true );
		$api_client->expects( $this->once() )
			->method( 'capture_intention' )
			->with( 'pi_capture', 1000, array() )
			->willReturn(
				array(
					'id'     => 'pi_capture',
					'status' => 'succeeded',
				)
			);

		$sut     = $this->create_adapter( $gateway, $api_client );
		$outcome = $sut->capture( PaymentContext::for_capture( $order, OrderPaymentStore::GATEWAY_ID ), 'key_capture' );

		$this->assertSame( PaymentOutcome::STATUS_COMPLETED, $outcome->get_status() );
		$this->assertSame( '', $gateway->last_idempotency_key );
	}

	/**
	 * @testdox Capture should preserve authorized payment metadata on native capture failures.
	 */
	public function test_capture_preserves_authorized_meta_for_native_failure(): void {
		$order      = $this->create_woopayments_order();
		$gateway    = new RecordingLegacyGateway( array( 'result' => 'success' ), true );
		$api_client = $this->getMockBuilder( WooPaymentsApiClient::class )
			->disableOriginalConstructor()
			->onlyMethods( array( 'is_available', 'capture_intention' ) )
			->getMock();

		$order->set_transaction_id( 'pi_capture' );
		$order->save();

		$api_client->expects( $this->once() )
			->method( 'is_available' )
			->willReturn( true );
		$api_client->expects( $this->once() )
			->method( 'capture_intention' )
			->with( 'pi_capture', 1000, array() )
			->willReturn(
				array(
					'id'      => 'pi_capture',
					'status'  => 'requires_capture',
					'message' => 'The authorization could not be captured.',
					'charges' => array(
						'total_count' => 1,
						'data'        => array(
							array( 'id' => 'ch_capture' ),
						),
					),
				)
			);

		$sut     = $this->create_adapter( $gateway, $api_client );
		$outcome = $sut->capture( PaymentContext::for_capture( $order, OrderPaymentStore::GATEWAY_ID ), 'key_capture' );
		$data    = $outcome->get_data();

		$this->assertSame( PaymentOutcome::STATUS_FAILED, $outcome->get_status() );
		$this->assertSame( 'pi_capture', $outcome->get_provider_payment_id() );
		$this->assertSame( 'requires_capture', $data['meta']['_intention_status'] );
		$this->assertStringContainsString( 'A capture of', $data['note'] );
		$this->assertStringContainsString( 'failed', $data['note'] );
		$this->assertStringContainsString( 'WooPayments', $data['note'] );
		$this->assertStringContainsString( 'The authorization could not be captured.', $data['note'] );
		$this->assertSame( '', $gateway->last_idempotency_key );
	}

	/**
	 * @testdox Capture should include settlement exchange-rate meta for converted-currency native captures.
	 */
	public function test_capture_includes_settlement_exchange_rate_meta_for_converted_currency_native_capture(): void {
		update_option( 'woocommerce_currency', 'USD' );
		$order = $this->create_woopayments_order( '40.00' );
		$order->set_currency( 'GBP' );
		$order->set_transaction_id( 'pi_capture_converted' );
		$order->save();

		$gateway    = new RecordingLegacyGateway( array( 'result' => 'success' ), true );
		$api_client = $this->getMockBuilder( WooPaymentsApiClient::class )
			->disableOriginalConstructor()
			->onlyMethods( array( 'is_available', 'capture_intention' ) )
			->getMock();

		$api_client->expects( $this->once() )
			->method( 'is_available' )
			->willReturn( true );
		$api_client->expects( $this->once() )
			->method( 'capture_intention' )
			->with( 'pi_capture_converted', 4000, array() )
			->willReturn(
				array(
					'id'      => 'pi_capture_converted',
					'status'  => 'succeeded',
					'charges' => array(
						'total_count' => 1,
						'data'        => array(
							array(
								'id'                  => 'ch_capture_converted',
								'currency'            => 'gbp',
								'balance_transaction' => array(
									'id'            => 'txn_capture_converted',
									'exchange_rate' => 1.33127,
								),
								'fee_breakdown_v1'    => array(
									'totals' => array(
										'fee' => array(
											'amount'   => 156,
											'currency' => 'usd',
										),
										'net' => array(
											'amount'   => 5170,
											'currency' => 'usd',
										),
									),
								),
							),
						),
					),
				)
			);

		$sut     = $this->create_adapter(
			$gateway,
			$api_client,
			null,
			null,
			$this->create_account_service(
				true,
				array(),
				array(
					'store_currencies' => array(
						'default' => 'usd',
					),
				)
			)
		);
		$outcome = $sut->capture( PaymentContext::for_capture( $order, OrderPaymentStore::GATEWAY_ID ), 'key_capture' );

		$this->assertSame( PaymentOutcome::STATUS_COMPLETED, $outcome->get_status() );
		$this->assertSame( '1.33127', $outcome->get_data()['meta']['_wcpay_multi_currency_stripe_exchange_rate'] );
		$this->assertSame( '1.56', $outcome->get_data()['meta']['_wcpay_transaction_fee'] );
		$this->assertSame( '51.7', $outcome->get_data()['meta']['_wcpay_net'] );
		$this->assertSame( 'ch_capture_converted', $outcome->get_data()['meta']['_charge_id'] );
		$this->assertSame( 'txn_capture_converted', $outcome->get_data()['meta']['_wcpay_payment_transaction_id'] );
		$this->assertSame( 'gbp', $outcome->get_data()['meta']['_wcpay_intent_currency'] );
	}

	/**
	 * @testdox Capture should fall back to intent meta when the transaction id is missing.
	 */
	public function test_capture_uses_intent_meta_when_transaction_id_is_missing(): void {
		$order      = $this->create_woopayments_order();
		$gateway    = new RecordingLegacyGateway( array( 'result' => 'success' ), true );
		$api_client = $this->getMockBuilder( WooPaymentsApiClient::class )
			->disableOriginalConstructor()
			->onlyMethods( array( 'is_available', 'capture_intention' ) )
			->getMock();

		$order->update_meta_data( '_intent_id', 'pi_capture_meta' );
		$order->save();

		$api_client->expects( $this->once() )
			->method( 'is_available' )
			->willReturn( true );
		$api_client->expects( $this->once() )
			->method( 'capture_intention' )
			->with( 'pi_capture_meta', 1000, array() )
			->willReturn(
				array(
					'id'     => 'pi_capture_meta',
					'status' => 'succeeded',
				)
			);

		$sut     = $this->create_adapter( $gateway, $api_client );
		$outcome = $sut->capture( PaymentContext::for_capture( $order, OrderPaymentStore::GATEWAY_ID ), 'key_capture' );

		$this->assertSame( PaymentOutcome::STATUS_COMPLETED, $outcome->get_status() );
		$this->assertSame( '', $gateway->last_idempotency_key );
	}

	/**
	 * @testdox Cancel should normalize legacy canceled authorizations.
	 */
	public function test_cancel_normalizes_legacy_canceled_authorization(): void {
		$order   = $this->create_woopayments_order();
		$gateway = new RecordingLegacyGateway(
			array( 'result' => 'success' ),
			true,
			array(),
			array(
				'status' => 'canceled',
				'id'     => 'pi_canceled',
			)
		);
		$sut     = $this->create_adapter( $gateway );

		$outcome = $sut->cancel( PaymentContext::for_cancel( $order, OrderPaymentStore::GATEWAY_ID ), 'key_cancel' );

		$this->assertSame( PaymentOutcome::STATUS_CANCELED, $outcome->get_status() );
		$this->assertSame( 'pi_canceled', $outcome->get_provider_payment_id() );
		$this->assertSame( 'key_cancel', $gateway->last_idempotency_key );
	}

	/**
	 * @testdox Cancel should prefer the native transport before the legacy gateway bridge.
	 */
	public function test_cancel_prefers_native_transport_when_available(): void {
		$order      = $this->create_woopayments_order();
		$gateway    = new RecordingLegacyGateway( array( 'result' => 'success' ), true );
		$api_client = $this->getMockBuilder( WooPaymentsApiClient::class )
			->disableOriginalConstructor()
			->onlyMethods( array( 'is_available', 'cancel_intention' ) )
			->getMock();

		$order->set_transaction_id( 'pi_cancel' );
		$order->save();

		$api_client->expects( $this->once() )
			->method( 'is_available' )
			->willReturn( true );
		$api_client->expects( $this->once() )
			->method( 'cancel_intention' )
			->with( 'pi_cancel' )
			->willReturn(
				array(
					'id'     => 'pi_cancel',
					'status' => 'canceled',
				)
			);

		$sut     = $this->create_adapter( $gateway, $api_client );
		$outcome = $sut->cancel( PaymentContext::for_cancel( $order, OrderPaymentStore::GATEWAY_ID ), 'key_cancel' );

		$this->assertSame( PaymentOutcome::STATUS_CANCELED, $outcome->get_status() );
		$this->assertSame( '', $gateway->last_idempotency_key );
	}

	/**
	 * @testdox Cancel should fall back to intent meta when the transaction id is missing.
	 */
	public function test_cancel_uses_intent_meta_when_transaction_id_is_missing(): void {
		$order      = $this->create_woopayments_order();
		$gateway    = new RecordingLegacyGateway( array( 'result' => 'success' ), true );
		$api_client = $this->getMockBuilder( WooPaymentsApiClient::class )
			->disableOriginalConstructor()
			->onlyMethods( array( 'is_available', 'cancel_intention' ) )
			->getMock();

		$order->update_meta_data( '_intent_id', 'pi_cancel_meta' );
		$order->save();

		$api_client->expects( $this->once() )
			->method( 'is_available' )
			->willReturn( true );
		$api_client->expects( $this->once() )
			->method( 'cancel_intention' )
			->with( 'pi_cancel_meta' )
			->willReturn(
				array(
					'id'     => 'pi_cancel_meta',
					'status' => 'canceled',
				)
			);

		$sut     = $this->create_adapter( $gateway, $api_client );
		$outcome = $sut->cancel( PaymentContext::for_cancel( $order, OrderPaymentStore::GATEWAY_ID ), 'key_cancel' );

		$this->assertSame( PaymentOutcome::STATUS_CANCELED, $outcome->get_status() );
		$this->assertSame( '', $gateway->last_idempotency_key );
	}

	/**
	 * @testdox Operations should fail closed when no legacy gateway is available.
	 */
	public function test_operations_fail_closed_without_gateway(): void {
		$order = $this->create_woopayments_order();
		$sut   = $this->create_adapter( null );

		$outcome = $sut->charge( PaymentContext::for_checkout( $order, OrderPaymentStore::GATEWAY_ID ), 'key_charge' );

		$this->assertSame( PaymentOutcome::STATUS_FAILED, $outcome->get_status() );
		$this->assertSame( 'wcpay_gateway_unavailable', $outcome->get_data()['error_code'] );
	}

	/**
	 * @testdox Availability should reflect whether the legacy bridge has a gateway.
	 */
	public function test_availability_reflects_legacy_gateway_presence(): void {
		$this->assertTrue( $this->create_adapter( new RecordingLegacyGateway() )->is_available() );
		$this->assertFalse( $this->create_adapter( null )->is_available() );
	}

	/**
	 * @testdox Availability should preserve the legacy gateway availability check.
	 */
	public function test_availability_reflects_legacy_gateway_availability(): void {
		$gateway            = new RecordingLegacyGateway();
		$gateway->available = false;

		$this->assertFalse( $this->create_adapter( $gateway )->is_available() );
	}

	/**
	 * Create adapter with a fake legacy gateway.
	 *
	 * @param RecordingLegacyGateway|null     $gateway Legacy gateway.
	 * @param WooPaymentsApiClient|null       $api_client Native API client.
	 * @param WooPaymentsCustomerService|null $customer_service WooPayments customer service.
	 * @param WooPaymentsTokenService|null    $token_service WooPayments token service.
	 * @param WooPaymentsAccountService|null  $account_service WooPayments account service.
	 * @return WooPaymentsProviderGatewayAdapter
	 */
	private function create_adapter( ?RecordingLegacyGateway $gateway, ?WooPaymentsApiClient $api_client = null, ?WooPaymentsCustomerService $customer_service = null, ?WooPaymentsTokenService $token_service = null, ?WooPaymentsAccountService $account_service = null ): WooPaymentsProviderGatewayAdapter {
		$legacy_runtime = new WooPaymentsLegacyRuntime();
		$legacy_runtime->init( new LegacyProxyWithGateway( $gateway ) );
		$api_client = $api_client ?? $this->getMockBuilder( WooPaymentsApiClient::class )
			->disableOriginalConstructor()
			->onlyMethods( array( 'is_available' ) )
			->getMock();
		if ( $api_client instanceof \PHPUnit\Framework\MockObject\MockObject ) {
			$api_client->method( 'is_available' )->willReturn( false );
		}
		$customer_service = $customer_service ?? $this->getMockBuilder( WooPaymentsCustomerService::class )
			->disableOriginalConstructor()
			->getMock();
		$token_service    = $token_service ?? $this->create_token_service();
		$account_service  = $account_service ?? $this->create_account_service( false );

		$sut = new WooPaymentsProviderGatewayAdapter();
		$sut->init( $legacy_runtime, $api_client, $customer_service, $token_service, $account_service );

		return $sut;
	}

	/**
	 * Create a WooPayments account service mock.
	 *
	 * @param bool                $test_mode    Whether WooPayments should run in test mode.
	 * @param array<string,mixed> $settings     Gateway settings.
	 * @param array<string,mixed> $account_data Cached account data.
	 * @return WooPaymentsAccountService
	 */
	private function create_account_service( bool $test_mode, array $settings = array(), array $account_data = array() ): WooPaymentsAccountService {
		$account_service = $this->getMockBuilder( WooPaymentsAccountService::class )
			->disableOriginalConstructor()
			->onlyMethods( array( 'is_test_mode_enabled', 'get_mode', 'get_gateway_setting', 'get_cached_account_data', 'get_account_default_currency' ) )
			->getMock();

		$account_service->method( 'is_test_mode_enabled' )->willReturn( $test_mode );
		$account_service->method( 'get_mode' )->willReturn( $test_mode ? 'test' : 'live' );
		$account_service->method( 'get_gateway_setting' )->willReturnCallback(
			static fn( string $key, $fallback = null ) => array_key_exists( $key, $settings ) ? $settings[ $key ] : $fallback
		);
		$account_service->method( 'get_cached_account_data' )->willReturn(
			array_merge(
				array(
					'country'          => 'US',
					'payments_enabled' => true,
					'capabilities'     => array(
						'amazon_pay_payments' => 'active',
					),
					'fees'             => array(
						'amazon_pay' => array(
							'base' => array(
								'currency' => 'usd',
							),
						),
					),
				),
				$account_data
			)
		);
		$store_currencies = is_array( $account_data['store_currencies'] ?? null ) ? $account_data['store_currencies'] : array();
		$account_service->method( 'get_account_default_currency' )->willReturn( (string) ( $store_currencies['default'] ?? 'usd' ) );

		return $account_service;
	}

	/**
	 * Create a token service with fake payment method details.
	 *
	 * @param array<string,array<string,mixed>> $payment_method_details Payment method details keyed by ID.
	 * @return WooPaymentsTokenService
	 */
	private function create_token_service( array $payment_method_details = array() ): WooPaymentsTokenService {
		$details_service = new class( $payment_method_details ) extends WooPaymentsPaymentMethodDetailsService {
			/**
			 * Payment method details keyed by ID.
			 *
			 * @var array<string,array<string,mixed>>
			 */
			private array $payment_method_details;

			/**
			 * Constructor.
			 *
			 * @param array<string,array<string,mixed>> $payment_method_details Payment method details keyed by ID.
			 */
			public function __construct( array $payment_method_details ) {
				$this->payment_method_details = $payment_method_details;
			}

			/**
			 * Get payment method details.
			 *
			 * @param string $payment_method_id Payment method ID.
			 * @return array<string,mixed>
			 */
			public function get_payment_method_details( string $payment_method_id ): array {
				return $this->payment_method_details[ $payment_method_id ] ?? array();
			}
		};

		$token_service = new WooPaymentsTokenService();
		$token_service->init( $details_service );

		return $token_service;
	}

	/**
	 * Create a persisted WooPayments card token.
	 *
	 * @param int    $user_id           User ID.
	 * @param string $payment_method_id Provider payment method ID.
	 * @return WC_Payment_Token_CC
	 */
	private function create_card_token( int $user_id, string $payment_method_id ): WC_Payment_Token_CC {
		$token = new WC_Payment_Token_CC();
		$token->set_gateway_id( OrderPaymentStore::GATEWAY_ID );
		$token->set_user_id( $user_id );
		$token->set_token( $payment_method_id );
		$token->set_card_type( 'visa' );
		$token->set_last4( '4242' );
		$token->set_expiry_month( '12' );
		$token->set_expiry_year( '2030' );
		$token->save();

		return $token;
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
	 * Assert that an order does not have a note with the given prefix.
	 *
	 * @param WC_Order $order  Order object.
	 * @param string   $prefix Note prefix.
	 */
	private function assertOrderDoesNotHaveNoteStartingWith( WC_Order $order, string $prefix ): void {
		$notes = wc_get_order_notes(
			array(
				'order_id' => $order->get_id(),
				'type'     => 'any',
			)
		);

		foreach ( $notes as $note ) {
			$this->assertStringStartsNotWith( $prefix, (string) $note->content );
		}
	}

	/**
	 * Create a WooPayments order for adapter tests.
	 *
	 * @param string $total Order total.
	 * @return WC_Order
	 */
	private function create_woopayments_order( string $total = '10.00' ): WC_Order {
		$order = wc_create_order();
		$order->set_payment_method( OrderPaymentStore::GATEWAY_ID );
		$order->set_total( $total );
		$order->save();

		return $order;
	}
}
