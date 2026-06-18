<?php
declare( strict_types = 1 );

namespace Automattic\WooCommerce\Tests\Internal\Payments\Providers\WooPayments;

use Automattic\WooCommerce\Internal\Payments\NativePaymentsRuntimeArbiter;
use Automattic\WooCommerce\Internal\Payments\OrderPaymentLifecycleService;
use Automattic\WooCommerce\Internal\Payments\OrderPaymentStore;
use Automattic\WooCommerce\Internal\Payments\Providers\WooPayments\Api\WooPaymentsApiClient;
use Automattic\WooCommerce\Internal\Payments\Providers\WooPayments\WooPaymentsAccountService;
use Automattic\WooCommerce\Internal\Payments\Providers\WooPayments\WooPaymentsCheckoutAjaxController;
use Automattic\WooCommerce\Internal\Payments\Providers\WooPayments\WooPaymentsCustomerService;
use Automattic\WooCommerce\Internal\Payments\Providers\WooPayments\WooPaymentsPaymentMethodDetailsService;
use Automattic\WooCommerce\Internal\Payments\Providers\WooPayments\WooPaymentsTokenService;
use WC_Order;
use WC_Payment_Token_CC;
use WC_Payment_Tokens;
use WC_Unit_Test_Case;

/**
 * Tests for the WooPaymentsCheckoutAjaxController class.
 */
class WooPaymentsCheckoutAjaxControllerTest extends WC_Unit_Test_Case {

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
		remove_all_actions( 'wp_ajax_update_order_status' );
		remove_all_actions( 'wp_ajax_nopriv_update_order_status' );
		remove_all_actions( 'wp_ajax_create_setup_intent' );
		remove_all_filters( 'woocommerce_native_woopayments_is_recurring_payment' );
		remove_all_filters( 'woocommerce_native_woopayments_related_subscriptions_for_order' );
		wp_set_current_user( 0 );
		update_option( 'woocommerce_currency', $this->original_currency );
		parent::tearDown();
	}

	/**
	 * @testdox Should register AJAX callbacks even when transport readiness is still deferred.
	 */
	public function test_registers_callbacks_when_transport_is_unavailable_at_boot(): void {
		$api_client = new class() extends WooPaymentsApiClient {
			/**
			 * Tell whether the transport is available.
			 *
			 * @return bool
			 */
			public function is_available(): bool {
				return false;
			}
		};
		$sut        = $this->create_controller( $api_client );

		$sut->register();

		$this->assertSame( 10, has_action( 'wp_ajax_update_order_status', array( $sut, 'handle_update_order_status' ) ) );
		$this->assertSame( 10, has_action( 'wp_ajax_nopriv_update_order_status', array( $sut, 'handle_update_order_status' ) ) );
		$this->assertSame( 10, has_action( 'wp_ajax_create_setup_intent', array( $sut, 'handle_create_setup_intent' ) ) );
	}

	/**
	 * @testdox Order-status callback should complete a zero-total order from the native SetupIntent.
	 */
	public function test_update_order_status_completes_zero_total_setup_intent(): void {
		$order = $this->create_woopayments_order( '0.00' );
		$order->update_meta_data( '_intent_id', 'seti_native' );
		$order->save();

		$api_client = new class() extends WooPaymentsApiClient {
			/**
			 * Tell whether the transport is available.
			 *
			 * @return bool
			 */
			public function is_available(): bool {
				return true;
			}

			/**
			 * Retrieve a SetupIntent.
			 *
			 * @param string $setup_intent_id SetupIntent ID.
			 * @return array<string,mixed>
			 */
			public function get_setup_intention( string $setup_intent_id ): array {
				if ( 'seti_native' !== $setup_intent_id ) {
					throw new \RuntimeException( 'Unexpected setup intent ID.' );
				}

				return array(
					'id'             => 'seti_native',
					'status'         => 'succeeded',
					'customer'       => 'cus_native',
					'payment_method' => 'pm_native',
				);
			}
		};
		$sut        = $this->create_controller( $api_client );
		$response   = $sut->get_update_order_status_response(
			array(
				'_ajax_nonce' => wp_create_nonce( 'wcpay_update_order_status_nonce' ),
				'order_id'    => $order->get_id(),
				'intent_id'   => 'seti_native',
			)
		);
		$order      = wc_get_order( $order->get_id() );

		$this->assertInstanceOf( WC_Order::class, $order );
		$this->assertArrayHasKey( 'return_url', $response );
		$this->assertSame( 200, $response['status_code'] );
		$this->assertSame( 'completed', $order->get_status() );
		$this->assertSame( 'seti_native', $order->get_meta( '_intent_id', true ) );
		$this->assertSame( 'pm_native', $order->get_meta( '_payment_method_id', true ) );
		$this->assertSame( 'cus_native', $order->get_meta( '_stripe_customer_id', true ) );
		$this->assert_order_has_no_note_containing( $order, 'A payment of' );
		$this->assert_order_has_no_note_containing( $order, 'A test payment of' );
	}

	/**
	 * @testdox Order-status callback should use the Core-owned account service for lifecycle mode metadata.
	 */
	public function test_update_order_status_uses_account_service_mode_for_lifecycle_meta(): void {
		$order = $this->create_woopayments_order( '50.00' );
		$order->update_meta_data( '_intent_id', 'pi_native' );
		$order->save();

		$api_client = new class() extends WooPaymentsApiClient {
			/**
			 * Tell whether the transport is available.
			 *
			 * @return bool
			 */
			public function is_available(): bool {
				return true;
			}

			/**
			 * Retrieve a PaymentIntent.
			 *
			 * @param string $intent_id PaymentIntent ID.
			 * @return array<string,mixed>
			 */
			public function get_payment_intention( string $intent_id ): array {
				if ( 'pi_native' !== $intent_id ) {
					throw new \RuntimeException( 'Unexpected payment intent ID.' );
				}

				return array(
					'id'             => 'pi_native',
					'status'         => 'succeeded',
					'currency'       => 'usd',
					'amount'         => 5000,
					'customer'       => 'cus_native',
					'payment_method' => 'pm_native',
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
		};
		$sut        = $this->create_controller( $api_client, null, null, $this->create_account_service( true ) );

		$response = $sut->get_update_order_status_response(
			array(
				'_ajax_nonce' => wp_create_nonce( 'wcpay_update_order_status_nonce' ),
				'order_id'    => $order->get_id(),
				'intent_id'   => 'pi_native',
			)
		);
		$order    = wc_get_order( $order->get_id() );

		$this->assertInstanceOf( WC_Order::class, $order );
		$this->assertSame( 200, $response['status_code'] );
		$this->assertSame( 'test', $order->get_meta( '_wcpay_mode', true ) );
		$this->assertSame( '1.75', $order->get_meta( '_wcpay_transaction_fee', true ) );
		$this->assertSame( '48.25', $order->get_meta( '_wcpay_net', true ) );
		$this->assertSame( 'allow', $order->get_meta( '_wcpay_fraud_outcome_status', true ) );
		$this->assertSame( 'allow', $order->get_meta( '_wcpay_fraud_meta_box_type', true ) );
		$this->assertSame( 'Visa credit card', $order->get_payment_method_title() );
		$this->assertSame( '4242', $order->get_meta( 'last4', true ) );
		$this->assertSame( 'visa', $order->get_meta( '_card_brand', true ) );
		$this->assertStringContainsString( '"last4":"4242"', (string) $order->get_meta( '_wcpay_payment_method_details', true ) );
		$this->assert_order_has_note_containing( $order, 'A test payment of' );
		$this->assert_order_has_note_containing( $order, 'was processed using WooPayments in <strong>test mode</strong>' );
		$this->assert_order_has_note_containing( $order, 'pi_native' );
	}

	/**
	 * @testdox Order-status callback should persist settlement exchange-rate meta for converted-currency native charges.
	 */
	public function test_update_order_status_persists_settlement_exchange_rate_meta_for_converted_currency_charge(): void {
		update_option( 'woocommerce_currency', 'USD' );
		$order = $this->create_woopayments_order( '40.00' );
		$order->set_currency( 'GBP' );
		$order->update_meta_data( '_intent_id', 'pi_converted' );
		$order->save();

		$api_client = new class() extends WooPaymentsApiClient {
			/**
			 * Tell whether the transport is available.
			 *
			 * @return bool
			 */
			public function is_available(): bool {
				return true;
			}

			/**
			 * Retrieve a PaymentIntent.
			 *
			 * @param string $intent_id PaymentIntent ID.
			 * @return array<string,mixed>
			 */
			public function get_payment_intention( string $intent_id ): array {
				if ( 'pi_converted' !== $intent_id ) {
					throw new \RuntimeException( 'Unexpected payment intent ID.' );
				}

				return array(
					'id'             => 'pi_converted',
					'status'         => 'succeeded',
					'currency'       => 'gbp',
					'amount'         => 4000,
					'customer'       => 'cus_native',
					'payment_method' => 'pm_native',
					'charges'        => array(
						'total_count' => 1,
						'data'        => array(
							array(
								'id'                  => 'ch_converted',
								'payment_method'      => 'pm_native',
								'balance_transaction' => array(
									'id'            => 'txn_converted',
									'exchange_rate' => 1.33127,
								),
								'amount'              => 4000,
								'currency'            => 'gbp',
							),
						),
					),
				);
			}
		};
		$sut        = $this->create_controller( $api_client, null, null, $this->create_account_service( true, 'usd' ) );

		$response = $sut->get_update_order_status_response(
			array(
				'_ajax_nonce' => wp_create_nonce( 'wcpay_update_order_status_nonce' ),
				'order_id'    => $order->get_id(),
				'intent_id'   => 'pi_converted',
			)
		);
		$order    = wc_get_order( $order->get_id() );

		$this->assertInstanceOf( WC_Order::class, $order );
		$this->assertSame( 200, $response['status_code'] );
		$this->assertSame( '1.33127', $order->get_meta( '_wcpay_multi_currency_stripe_exchange_rate', true ) );
	}

	/**
	 * @testdox Order-status callback should reject mismatched intent IDs before transport reads.
	 */
	public function test_update_order_status_rejects_mismatched_intent_id(): void {
		$order = $this->create_woopayments_order( '0.00' );
		$order->update_meta_data( '_intent_id', 'seti_expected' );
		$order->save();

		$api_client = new class() extends WooPaymentsApiClient {
			/**
			 * Tell whether the transport is available.
			 *
			 * @return bool
			 */
			public function is_available(): bool {
				return true;
			}

			// phpcs:disable Squiz.Commenting.FunctionComment.InvalidNoReturn -- This test double must fail if the transport is called.
			/**
			 * Retrieve a SetupIntent.
			 *
			 * @param string $setup_intent_id SetupIntent ID.
			 * @return array<string,mixed>
			 */
			public function get_setup_intention( string $setup_intent_id ): array {
				unset( $setup_intent_id );
				throw new \RuntimeException( 'Intent mismatch should not call the transport.' );
			}
			// phpcs:enable Squiz.Commenting.FunctionComment.InvalidNoReturn
		};
		$sut        = $this->create_controller( $api_client );
		$response   = $sut->get_update_order_status_response(
			array(
				'_ajax_nonce' => wp_create_nonce( 'wcpay_update_order_status_nonce' ),
				'order_id'    => $order->get_id(),
				'intent_id'   => 'seti_other',
			)
		);

		$this->assertArrayHasKey( 'error', $response );
		$this->assertSame( 409, $response['status_code'] );
	}

	/**
	 * @testdox Order-status callback should reject non-authorized intent statuses after syncing the order.
	 */
	public function test_update_order_status_rejects_non_authorized_intent_status(): void {
		$order = $this->create_woopayments_order( '10.00' );
		$order->update_meta_data( '_intent_id', 'pi_requires_payment_method' );
		$order->save();

		$api_client = new class() extends WooPaymentsApiClient {
			/**
			 * Tell whether the transport is available.
			 *
			 * @return bool
			 */
			public function is_available(): bool {
				return true;
			}

			/**
			 * Retrieve a PaymentIntent.
			 *
			 * @param string $intent_id PaymentIntent ID.
			 * @return array<string,mixed>
			 */
			public function get_payment_intention( string $intent_id ): array {
				if ( 'pi_requires_payment_method' !== $intent_id ) {
					throw new \RuntimeException( 'Unexpected payment intent ID.' );
				}

				return array(
					'id'             => 'pi_requires_payment_method',
					'status'         => 'requires_payment_method',
					'currency'       => 'usd',
					'customer'       => 'cus_native',
					'payment_method' => 'pm_native',
				);
			}
		};
		$sut        = $this->create_controller( $api_client );
		$response   = $sut->get_update_order_status_response(
			array(
				'_ajax_nonce' => wp_create_nonce( 'wcpay_update_order_status_nonce' ),
				'order_id'    => $order->get_id(),
				'intent_id'   => 'pi_requires_payment_method',
			)
		);
		$order      = wc_get_order( $order->get_id() );

		$this->assertInstanceOf( WC_Order::class, $order );
		$this->assertArrayHasKey( 'error', $response );
		$this->assertArrayNotHasKey( 'return_url', $response );
		$this->assertSame( 409, $response['status_code'] );
		$this->assertSame( 'failed', $order->get_status() );
		$this->assertSame( 'requires_payment_method', $order->get_meta( '_intention_status', true ) );
	}

	/**
	 * @testdox Order-status callback should save requested cards before completing the order.
	 */
	public function test_update_order_status_saves_requested_card_token_before_completing_order(): void {
		$user_id = $this->factory()->user->create();
		wp_set_current_user( $user_id );

		$order = $this->create_woopayments_order( '10.00' );
		$order->set_customer_id( $user_id );
		$order->update_meta_data( '_intent_id', 'pi_native' );
		$order->save();

		$api_client    = new class() extends WooPaymentsApiClient {
			/**
			 * Tell whether the transport is available.
			 *
			 * @return bool
			 */
			public function is_available(): bool {
				return true;
			}

			/**
			 * Retrieve a PaymentIntent.
			 *
			 * @param string $intent_id PaymentIntent ID.
			 * @return array<string,mixed>
			 */
			public function get_payment_intention( string $intent_id ): array {
				if ( 'pi_native' !== $intent_id ) {
					throw new \RuntimeException( 'Unexpected payment intent ID.' );
				}

				return array(
					'id'             => 'pi_native',
					'status'         => 'succeeded',
					'currency'       => 'usd',
					'customer'       => 'cus_native',
					'payment_method' => 'pm_native',
				);
			}
		};
		$token_service = $this->create_token_service(
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
		$sut           = $this->create_controller( $api_client, null, $token_service );

		$status_at_token_attach = '';
		$record_order_status    = function ( int $order_id ) use ( &$status_at_token_attach ): void {
			$order = wc_get_order( $order_id );

			$status_at_token_attach = $order instanceof WC_Order ? $order->get_status() : '';
		};
		add_action( 'woocommerce_payment_token_added_to_order', $record_order_status, 10, 1 );

		try {
			$response = $sut->get_update_order_status_response(
				array(
					'_ajax_nonce'                => wp_create_nonce( 'wcpay_update_order_status_nonce' ),
					'order_id'                   => $order->get_id(),
					'intent_id'                  => 'pi_native',
					'should_save_payment_method' => 'true',
				)
			);
		} finally {
			remove_action( 'woocommerce_payment_token_added_to_order', $record_order_status, 10 );
		}

		$order  = wc_get_order( $order->get_id() );
		$tokens = array_values( WC_Payment_Tokens::get_customer_tokens( $user_id, OrderPaymentStore::GATEWAY_ID ) );
		$token  = $tokens[0] ?? null;

		$this->assertInstanceOf( WC_Order::class, $order );
		$this->assertSame( 200, $response['status_code'] );
		$this->assertSame( 'completed', $order->get_status() );
		$this->assertNotSame( 'completed', $status_at_token_attach, 'The token must be attached before the lifecycle service completes the order.' );
		$this->assertInstanceOf( WC_Payment_Token_CC::class, $token );
		$this->assertSame( 'pm_native', $token->get_token() );
		$this->assertContains( $token->get_id(), $order->get_payment_tokens() );
	}

	/**
	 * @testdox Order-status callback should copy saved token details to related subscriptions.
	 */
	public function test_update_order_status_copies_saved_card_token_to_related_subscriptions(): void {
		$user_id      = $this->factory()->user->create();
		$order        = $this->create_woopayments_order( '10.00' );
		$subscription = $this->create_woopayments_order( '10.00' );
		wp_set_current_user( $user_id );

		$order->set_customer_id( $user_id );
		$order->update_meta_data( '_intent_id', 'pi_native' );
		$order->save();
		$subscription->set_customer_id( $user_id );
		$subscription->save();

		$api_client    = new class() extends WooPaymentsApiClient {
			/**
			 * Tell whether the transport is available.
			 *
			 * @return bool
			 */
			public function is_available(): bool {
				return true;
			}

			/**
			 * Retrieve a PaymentIntent.
			 *
			 * @param string $intent_id PaymentIntent ID.
			 * @return array<string,mixed>
			 */
			public function get_payment_intention( string $intent_id ): array {
				if ( 'pi_native' !== $intent_id ) {
					throw new \RuntimeException( 'Unexpected payment intent ID.' );
				}

				return array(
					'id'             => 'pi_native',
					'status'         => 'succeeded',
					'currency'       => 'usd',
					'customer'       => 'cus_native',
					'payment_method' => 'pm_native',
				);
			}
		};
		$token_service = $this->create_token_service(
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
		$sut           = $this->create_controller( $api_client, null, $token_service );

		add_filter( 'woocommerce_native_woopayments_is_recurring_payment', '__return_true' );
		add_filter(
			'woocommerce_native_woopayments_related_subscriptions_for_order',
			static function ( array $subscriptions, WC_Order $filtered_order ) use ( $order, $subscription ): array {
				return $order->get_id() === $filtered_order->get_id() ? array( $subscription ) : $subscriptions;
			},
			10,
			2
		);

		$response = $sut->get_update_order_status_response(
			array(
				'_ajax_nonce'                => wp_create_nonce( 'wcpay_update_order_status_nonce' ),
				'order_id'                   => $order->get_id(),
				'intent_id'                  => 'pi_native',
				'should_save_payment_method' => 'true',
			)
		);
		$order    = wc_get_order( $order->get_id() );
		$tokens   = array_values( WC_Payment_Tokens::get_customer_tokens( $user_id, OrderPaymentStore::GATEWAY_ID ) );
		$token    = $tokens[0] ?? null;

		$subscription = wc_get_order( $subscription->get_id() );

		$this->assertInstanceOf( WC_Order::class, $order );
		$this->assertInstanceOf( WC_Order::class, $subscription );
		$this->assertSame( 200, $response['status_code'] );
		$this->assertInstanceOf( WC_Payment_Token_CC::class, $token );
		$this->assertContains( $token->get_id(), $order->get_payment_tokens(), 'Saved cards should be linked to the parent order.' );
		$this->assertContains( $token->get_id(), $subscription->get_payment_tokens(), 'Saved cards should be linked to related subscriptions.' );
		$this->assertSame( 'pm_native', $subscription->get_meta( '_payment_method_id', true ) );
		$this->assertSame( 'cus_native', $subscription->get_meta( '_stripe_customer_id', true ) );
	}

	/**
	 * @testdox Order-status callback should block recurring orders when token saving fails.
	 */
	public function test_update_order_status_blocks_recurring_order_when_token_save_fails(): void {
		$user_id = $this->factory()->user->create();
		wp_set_current_user( $user_id );

		$order = $this->create_woopayments_order( '10.00' );
		$order->set_customer_id( $user_id );
		$order->update_meta_data( '_intent_id', 'pi_native' );
		$order->save();

		$api_client    = new class() extends WooPaymentsApiClient {
			/**
			 * Tell whether the transport is available.
			 *
			 * @return bool
			 */
			public function is_available(): bool {
				return true;
			}

			/**
			 * Retrieve a PaymentIntent.
			 *
			 * @param string $intent_id PaymentIntent ID.
			 * @return array<string,mixed>
			 */
			public function get_payment_intention( string $intent_id ): array {
				if ( 'pi_native' !== $intent_id ) {
					throw new \RuntimeException( 'Unexpected payment intent ID.' );
				}

				return array(
					'id'             => 'pi_native',
					'status'         => 'succeeded',
					'currency'       => 'usd',
					'customer'       => 'cus_native',
					'payment_method' => 'pm_native',
				);
			}
		};
		$token_service = new class() extends WooPaymentsTokenService {
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
		$sut           = $this->create_controller( $api_client, null, $token_service );

		add_filter( 'woocommerce_native_woopayments_is_recurring_payment', '__return_true' );

		$response = $sut->get_update_order_status_response(
			array(
				'_ajax_nonce'                => wp_create_nonce( 'wcpay_update_order_status_nonce' ),
				'order_id'                   => $order->get_id(),
				'intent_id'                  => 'pi_native',
				'should_save_payment_method' => 'false',
			)
		);
		$order    = wc_get_order( $order->get_id() );

		$this->assertInstanceOf( WC_Order::class, $order );
		$this->assertSame( 409, $response['status_code'] );
		$this->assertSame( 'Unable to save payment method for subscription. Please try again or use a different payment method.', $response['error']['message'] );
		$this->assertNotSame( 'completed', $order->get_status() );
		$this->assertSame( '', $order->get_meta( '_intention_status', true ), 'Recurring orders should not be completed when their required token cannot be saved.' );
	}

	/**
	 * @testdox Setup-intent callback should return the native SetupIntent response envelope.
	 */
	public function test_create_setup_intent_returns_native_response_envelope(): void {
		$user_id = $this->factory()->user->create();
		wp_set_current_user( $user_id );

		$api_client = new class() extends WooPaymentsApiClient {
			/**
			 * Tell whether the transport is available.
			 *
			 * @return bool
			 */
			public function is_available(): bool {
				return true;
			}

			/**
			 * Create and confirm a SetupIntent.
			 *
			 * @param array<string,mixed> $request_data Request data.
			 * @param string              $idempotency_key Idempotency key.
			 * @return array<string,mixed>
			 */
			public function create_and_confirm_setup_intention( array $request_data, string $idempotency_key ): array {
				if ( 'cus_user' !== $request_data['customer']
					|| 'pm_card' !== $request_data['payment_method']
					|| array( 'card' ) !== $request_data['payment_method_types']
					|| '' === $idempotency_key ) {
					throw new \RuntimeException( 'Unexpected setup intent payload.' );
				}

				return array(
					'id'            => 'seti_user',
					'status'        => 'succeeded',
					'client_secret' => 'seti_user_secret_abc',
				);
			}
		};

		$customer_service = $this->getMockBuilder( WooPaymentsCustomerService::class )
			->disableOriginalConstructor()
			->onlyMethods( array( 'get_or_create_customer_id_for_user' ) )
			->getMock();
		$customer_service->expects( $this->once() )
			->method( 'get_or_create_customer_id_for_user' )
			->with( $user_id )
			->willReturn( 'cus_user' );

		$sut      = $this->create_controller( $api_client, $customer_service );
		$response = $sut->get_create_setup_intent_response(
			array(
				'_ajax_nonce'          => wp_create_nonce( 'wcpay_create_setup_intent_nonce' ),
				'wcpay-payment-method' => 'pm_card',
			)
		);

		$this->assertTrue( $response['success'] );
		$this->assertSame( 200, $response['status_code'] );
		$this->assertSame(
			array(
				'id'            => 'seti_user',
				'status'        => 'succeeded',
				'client_secret' => 'seti_user_secret_abc',
			),
			$response['data']
		);
	}

	/**
	 * Create a checkout AJAX controller.
	 *
	 * @param WooPaymentsApiClient            $api_client       API client.
	 * @param WooPaymentsCustomerService|null $customer_service Customer service.
	 * @param WooPaymentsTokenService|null    $token_service    Token service.
	 * @param WooPaymentsAccountService|null  $account_service  Account service.
	 * @return WooPaymentsCheckoutAjaxController
	 */
	private function create_controller( WooPaymentsApiClient $api_client, ?WooPaymentsCustomerService $customer_service = null, ?WooPaymentsTokenService $token_service = null, ?WooPaymentsAccountService $account_service = null ): WooPaymentsCheckoutAjaxController {
		$arbiter = $this->createMock( NativePaymentsRuntimeArbiter::class );
		$arbiter->method( 'should_native_register' )->willReturn( true );

		if ( null === $customer_service ) {
			$customer_service = $this->createMock( WooPaymentsCustomerService::class );
		}

		if ( null === $token_service ) {
			$token_service = $this->create_token_service();
		}

		if ( null === $account_service ) {
			$account_service = $this->create_account_service( false );
		}

		$sut = new WooPaymentsCheckoutAjaxController();
		$sut->init(
			$arbiter,
			$api_client,
			$customer_service,
			wc_get_container()->get( OrderPaymentLifecycleService::class ),
			$token_service,
			$account_service
		);

		return $sut;
	}

	/**
	 * Create a WooPayments account service mock.
	 *
	 * @param bool   $test_mode                Whether WooPayments should run in test mode.
	 * @param string $account_default_currency Account default currency.
	 * @return WooPaymentsAccountService
	 */
	private function create_account_service( bool $test_mode, string $account_default_currency = 'usd' ): WooPaymentsAccountService {
		$account_service = $this->getMockBuilder( WooPaymentsAccountService::class )
			->disableOriginalConstructor()
			->onlyMethods( array( 'get_mode', 'is_test_mode_enabled', 'get_account_default_currency' ) )
			->getMock();

		$account_service->method( 'get_mode' )->willReturn( $test_mode ? 'test' : 'live' );
		$account_service->method( 'is_test_mode_enabled' )->willReturn( $test_mode );
		$account_service->method( 'get_account_default_currency' )->willReturn( $account_default_currency );

		return $account_service;
	}

	/**
	 * Create a token service test double.
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

		$sut = new WooPaymentsTokenService();
		$sut->init( $details_service );

		return $sut;
	}

	/**
	 * Assert that an order has a note containing the expected content.
	 *
	 * @param WC_Order $order    Order object.
	 * @param string   $expected Expected note content.
	 */
	private function assert_order_has_note_containing( WC_Order $order, string $expected ): void {
		$notes = wc_get_order_notes(
			array(
				'order_id' => $order->get_id(),
				'type'     => 'any',
			)
		);

		foreach ( $notes as $note ) {
			if ( false !== strpos( (string) $note->content, $expected ) ) {
				$this->addToAssertionCount( 1 );
				return;
			}
		}

		$this->fail( "Missing order note containing: {$expected}" );
	}

	/**
	 * Assert that an order has no note containing the expected content.
	 *
	 * @param WC_Order $order    Order object.
	 * @param string   $expected Expected note content.
	 */
	private function assert_order_has_no_note_containing( WC_Order $order, string $expected ): void {
		$notes = wc_get_order_notes(
			array(
				'order_id' => $order->get_id(),
				'type'     => 'any',
			)
		);

		foreach ( $notes as $note ) {
			if ( false !== strpos( (string) $note->content, $expected ) ) {
				$this->fail( "Unexpected order note containing: {$expected}" );
			}
		}

		$this->addToAssertionCount( 1 );
	}

	/**
	 * Create a WooPayments test order.
	 *
	 * @param string $total Order total.
	 * @return WC_Order
	 */
	private function create_woopayments_order( string $total ): WC_Order {
		$order = new WC_Order();
		$order->set_payment_method( OrderPaymentStore::GATEWAY_ID );
		$order->set_currency( 'USD' );
		$order->set_total( $total );
		$order->save();

		return $order;
	}
}
