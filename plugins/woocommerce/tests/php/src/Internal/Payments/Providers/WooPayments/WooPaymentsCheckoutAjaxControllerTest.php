<?php
declare( strict_types = 1 );

namespace Automattic\WooCommerce\Tests\Internal\Payments\Providers\WooPayments;

use Automattic\WooCommerce\Internal\Payments\NativePaymentsRuntimeArbiter;
use Automattic\WooCommerce\Internal\Payments\OrderPaymentLifecycleService;
use Automattic\WooCommerce\Internal\Payments\OrderPaymentStore;
use Automattic\WooCommerce\Internal\Payments\Providers\WooPayments\Api\WooPaymentsApiClient;
use Automattic\WooCommerce\Internal\Payments\Providers\WooPayments\WooPaymentsCheckoutAjaxController;
use Automattic\WooCommerce\Internal\Payments\Providers\WooPayments\WooPaymentsCustomerService;
use WC_Order;
use WC_Unit_Test_Case;

/**
 * Tests for the WooPaymentsCheckoutAjaxController class.
 */
class WooPaymentsCheckoutAjaxControllerTest extends WC_Unit_Test_Case {

	/**
	 * Tear down test fixtures.
	 */
	public function tearDown(): void {
		wp_set_current_user( 0 );
		parent::tearDown();
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
	 * @return WooPaymentsCheckoutAjaxController
	 */
	private function create_controller( WooPaymentsApiClient $api_client, ?WooPaymentsCustomerService $customer_service = null ): WooPaymentsCheckoutAjaxController {
		$arbiter = $this->createMock( NativePaymentsRuntimeArbiter::class );
		$arbiter->method( 'should_native_register' )->willReturn( true );

		if ( null === $customer_service ) {
			$customer_service = $this->createMock( WooPaymentsCustomerService::class );
		}

		$sut = new WooPaymentsCheckoutAjaxController();
		$sut->init(
			$arbiter,
			$api_client,
			$customer_service,
			wc_get_container()->get( OrderPaymentLifecycleService::class )
		);

		return $sut;
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
