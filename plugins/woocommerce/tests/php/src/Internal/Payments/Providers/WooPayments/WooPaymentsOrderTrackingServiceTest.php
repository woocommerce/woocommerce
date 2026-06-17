<?php
declare( strict_types = 1 );

namespace Automattic\WooCommerce\Tests\Internal\Payments\Providers\WooPayments;

use Automattic\WooCommerce\Internal\Payments\NativePaymentsRuntimeArbiter;
use Automattic\WooCommerce\Internal\Payments\OrderPaymentStore;
use Automattic\WooCommerce\Internal\Payments\Providers\WooPayments\Api\WooPaymentsApiClient;
use Automattic\WooCommerce\Internal\Payments\Providers\WooPayments\WooPaymentsAccountService;
use Automattic\WooCommerce\Internal\Payments\Providers\WooPayments\WooPaymentsActionSchedulerService;
use Automattic\WooCommerce\Internal\Payments\Providers\WooPayments\WooPaymentsOrderTrackingService;
use Automattic\WooCommerce\Tests\Internal\Payments\StaticNativeRuntimeArbiter;
use WC_Order;
use WC_Unit_Test_Case;

/**
 * Tests for the WooPaymentsOrderTrackingService class.
 */
class WooPaymentsOrderTrackingServiceTest extends WC_Unit_Test_Case {

	/**
	 * Created services whose hooks must be removed after each test.
	 *
	 * @var WooPaymentsOrderTrackingService[]
	 */
	private array $services = array();

	/**
	 * Tear down test fixtures.
	 */
	public function tearDown(): void {
		foreach ( $this->services as $service ) {
			$this->remove_tracking_hooks( $service );
		}

		remove_all_filters( WooPaymentsOrderTrackingService::FILTER_FRAUD_SERVICES_CONFIG );
		parent::tearDown();
	}

	/**
	 * @testdox Should register preserved order-tracking hooks when native owns runtime.
	 */
	public function test_registers_preserved_order_tracking_hooks_when_native_owns_runtime(): void {
		$service = $this->create_service( new StaticNativeRuntimeArbiter( true ) );

		$service->register();

		$this->assertSame( 10, has_action( 'woocommerce_update_order', array( $service, 'handle_woocommerce_update_order' ) ) );
		$this->assertSame( 10, has_action( 'wcpay_track_new_order', array( $service, 'handle_wcpay_track_new_order' ) ) );
		$this->assertSame( 10, has_action( 'wcpay_track_update_order', array( $service, 'handle_wcpay_track_update_order' ) ) );
	}

	/**
	 * @testdox Should not register order-tracking hooks when plugin owns runtime.
	 */
	public function test_registers_no_order_tracking_hooks_when_plugin_owns_runtime(): void {
		$service = $this->create_service( new StaticNativeRuntimeArbiter( false ) );

		$service->register();

		$this->assertFalse( has_action( 'woocommerce_update_order', array( $service, 'handle_woocommerce_update_order' ) ) );
		$this->assertFalse( has_action( 'wcpay_track_new_order', array( $service, 'handle_wcpay_track_new_order' ) ) );
		$this->assertFalse( has_action( 'wcpay_track_update_order', array( $service, 'handle_wcpay_track_update_order' ) ) );
	}

	/**
	 * @testdox Should schedule a preserved new-order tracking action for untracked WooPayments orders.
	 */
	public function test_schedules_new_order_tracking_for_untracked_woopayments_orders(): void {
		$scheduler = new RecordingActionSchedulerService();
		$service   = $this->create_service( new StaticNativeRuntimeArbiter( true ), $scheduler );
		$order     = $this->create_order(
			OrderPaymentStore::GATEWAY_ID,
			array(
				'_payment_method_id' => 'pm_123',
				'_wcpay_mode'        => 'test',
			)
		);

		$service->handle_woocommerce_update_order( $order->get_id(), $order );

		$this->assertSame(
			array(
				array(
					'hook' => 'wcpay_track_new_order',
					'args' => array( 'order_id' => $order->get_id() ),
				),
			),
			$scheduler->scheduled_jobs
		);
	}

	/**
	 * @testdox Should schedule a preserved new-order tracking action for split-UPE WooPayments orders.
	 */
	public function test_schedules_new_order_tracking_for_prefixed_woopayments_orders(): void {
		$scheduler = new RecordingActionSchedulerService();
		$service   = $this->create_service( new StaticNativeRuntimeArbiter( true ), $scheduler );
		$order     = $this->create_order(
			OrderPaymentStore::GATEWAY_ID_PREFIX . 'card',
			array(
				'_payment_method_id' => 'pm_123',
				'_wcpay_mode'        => 'test',
			)
		);

		$service->handle_woocommerce_update_order( $order->get_id(), $order );

		$this->assertSame(
			array(
				array(
					'hook' => 'wcpay_track_new_order',
					'args' => array( 'order_id' => $order->get_id() ),
				),
			),
			$scheduler->scheduled_jobs
		);
	}

	/**
	 * @testdox Should schedule a preserved update-order tracking action for previously tracked WooPayments orders.
	 */
	public function test_schedules_update_order_tracking_for_tracked_woopayments_orders(): void {
		$scheduler = new RecordingActionSchedulerService();
		$service   = $this->create_service( new StaticNativeRuntimeArbiter( true ), $scheduler );
		$order     = $this->create_order(
			OrderPaymentStore::GATEWAY_ID,
			array(
				'_payment_method_id'           => 'pm_123',
				'_new_order_tracking_complete' => 'yes',
			)
		);

		$service->handle_woocommerce_update_order( $order->get_id(), $order );

		$this->assertSame(
			array(
				array(
					'hook' => 'wcpay_track_update_order',
					'args' => array( 'order_id' => $order->get_id() ),
				),
			),
			$scheduler->scheduled_jobs
		);
	}

	/**
	 * @testdox Should skip scheduling when the order is not a WooPayments order.
	 */
	public function test_skips_scheduling_for_non_woopayments_orders(): void {
		$scheduler = new RecordingActionSchedulerService();
		$service   = $this->create_service( new StaticNativeRuntimeArbiter( true ), $scheduler );
		$order     = $this->create_order(
			'cod',
			array(
				'_payment_method_id' => 'pm_123',
			)
		);

		$service->handle_woocommerce_update_order( $order->get_id(), $order );

		$this->assertSame( array(), $scheduler->scheduled_jobs );
	}

	/**
	 * @testdox Should skip scheduling when the provider payment method ID is missing.
	 */
	public function test_skips_scheduling_without_payment_method_id(): void {
		$scheduler = new RecordingActionSchedulerService();
		$service   = $this->create_service( new StaticNativeRuntimeArbiter( true ), $scheduler );
		$order     = $this->create_order( OrderPaymentStore::GATEWAY_ID );

		$service->handle_woocommerce_update_order( $order->get_id(), $order );

		$this->assertSame( array(), $scheduler->scheduled_jobs );
	}

	/**
	 * @testdox Should skip scheduling when Sift tracking is disabled.
	 */
	public function test_skips_scheduling_when_sift_is_disabled(): void {
		$disable_sift = static function (): array {
			return array( 'stripe' => array() );
		};
		$scheduler    = new RecordingActionSchedulerService();
		$service      = $this->create_service( new StaticNativeRuntimeArbiter( true ), $scheduler );
		$order        = $this->create_order(
			OrderPaymentStore::GATEWAY_ID,
			array(
				'_payment_method_id' => 'pm_123',
			)
		);

		add_filter( WooPaymentsOrderTrackingService::FILTER_FRAUD_SERVICES_CONFIG, $disable_sift );

		$service->handle_woocommerce_update_order( $order->get_id(), $order );

		$this->assertSame( array(), $scheduler->scheduled_jobs );
	}

	/**
	 * @testdox Should skip scheduling while a preserved order-tracking action is running.
	 */
	public function test_skips_scheduling_during_order_tracking_actions(): void {
		$scheduler = new RecordingActionSchedulerService();
		$service   = $this->create_service( new StaticNativeRuntimeArbiter( true ), $scheduler );
		$order     = $this->create_order(
			OrderPaymentStore::GATEWAY_ID,
			array(
				'_payment_method_id' => 'pm_123',
			)
		);

		global $wp_current_filter;
		// phpcs:ignore WordPress.WP.GlobalVariablesOverride.Prohibited -- Needed to simulate doing_action() in a unit test.
		$wp_current_filter[] = 'wcpay_track_new_order';

		try {
			$service->handle_woocommerce_update_order( $order->get_id(), $order );
		} finally {
			array_pop( $wp_current_filter );
		}

		$this->assertSame( array(), $scheduler->scheduled_jobs );
	}

	/**
	 * @testdox Should track new orders through the native API and mark creation tracking complete.
	 */
	public function test_track_new_order_posts_order_data_and_marks_complete(): void {
		$order      = $this->create_order(
			OrderPaymentStore::GATEWAY_ID,
			array(
				'_payment_method_id'  => 'pm_123',
				'_stripe_customer_id' => 'cus_123',
				'_wcpay_mode'         => 'test',
			)
		);
		$api_client = $this->create_api_client();
		$api_client->expects( $this->once() )
			->method( 'track_order' )
			->with(
				$this->callback(
					function ( array $order_data ) use ( $order ): bool {
						return $order->get_id() === $order_data['id']
							&& 'pm_123' === $order_data['_payment_method_id']
							&& 'cus_123' === $order_data['_stripe_customer_id']
							&& 'test' === $order_data['_wcpay_mode'];
					}
				),
				false
			)
			->willReturn( array( 'result' => 'success' ) );
		$service = $this->create_service( new StaticNativeRuntimeArbiter( true ), new RecordingActionSchedulerService(), $api_client, $this->create_account_service( true ) );

		$result = $service->track_new_order_action( $order->get_id() );

		$updated_order = wc_get_order( $order->get_id() );
		$this->assertTrue( $result );
		$this->assertInstanceOf( WC_Order::class, $updated_order );
		$this->assertSame( 'yes', $updated_order->get_meta( '_new_order_tracking_complete', true ) );
	}

	/**
	 * @testdox Should track order updates without rewriting the creation tracking marker.
	 */
	public function test_track_update_order_posts_update_without_rewriting_marker(): void {
		$order      = $this->create_order(
			OrderPaymentStore::GATEWAY_ID,
			array(
				'_payment_method_id'           => 'pm_123',
				'_new_order_tracking_complete' => 'already-marked',
			)
		);
		$api_client = $this->create_api_client();
		$api_client->expects( $this->once() )
			->method( 'track_order' )
			->with( $this->isType( 'array' ), true )
			->willReturn( array( 'result' => 'success' ) );
		$service = $this->create_service( new StaticNativeRuntimeArbiter( true ), new RecordingActionSchedulerService(), $api_client );

		$result = $service->track_update_order_action( $order->get_id() );

		$updated_order = wc_get_order( $order->get_id() );
		$this->assertTrue( $result );
		$this->assertInstanceOf( WC_Order::class, $updated_order );
		$this->assertSame( 'already-marked', $updated_order->get_meta( '_new_order_tracking_complete', true ) );
	}

	/**
	 * @testdox Should skip tracking when the order mode does not match the current WooPayments mode.
	 */
	public function test_track_order_skips_mode_mismatch(): void {
		$order      = $this->create_order(
			OrderPaymentStore::GATEWAY_ID,
			array(
				'_payment_method_id' => 'pm_123',
				'_wcpay_mode'        => 'prod',
			)
		);
		$api_client = $this->create_api_client();
		$api_client->expects( $this->never() )->method( 'track_order' );
		$service = $this->create_service( new StaticNativeRuntimeArbiter( true ), new RecordingActionSchedulerService(), $api_client, $this->create_account_service( true ) );

		$this->assertFalse( $service->track_new_order_action( $order->get_id() ) );
	}

	/**
	 * @testdox Should accept plugin and native production mode values while tracking live orders.
	 *
	 * @dataProvider production_mode_provider
	 *
	 * @param string $order_mode Persisted order mode.
	 */
	public function test_track_order_accepts_production_mode_aliases( string $order_mode ): void {
		$order      = $this->create_order(
			OrderPaymentStore::GATEWAY_ID,
			array(
				'_payment_method_id' => 'pm_123',
				'_wcpay_mode'        => $order_mode,
			)
		);
		$api_client = $this->create_api_client();
		$api_client->expects( $this->once() )
			->method( 'track_order' )
			->willReturn( array( 'result' => 'success' ) );
		$service = $this->create_service( new StaticNativeRuntimeArbiter( true ), new RecordingActionSchedulerService(), $api_client, $this->create_account_service( false ) );

		$this->assertTrue( $service->track_new_order_action( $order->get_id() ) );
	}

	/**
	 * Provide production order mode aliases.
	 *
	 * @return array<string,array{string}>
	 */
	public function production_mode_provider(): array {
		return array(
			'plugin production mode' => array( 'prod' ),
			'native live mode'       => array( 'live' ),
		);
	}

	/**
	 * Create an order with payment metadata.
	 *
	 * @param string              $payment_method Payment method.
	 * @param array<string,mixed> $meta           Order meta.
	 * @return WC_Order
	 */
	private function create_order( string $payment_method, array $meta = array() ): WC_Order {
		$order = wc_create_order();
		$this->assertInstanceOf( WC_Order::class, $order );

		$order->set_payment_method( $payment_method );
		$order->set_total( 12.34 );

		foreach ( $meta as $key => $value ) {
			$order->update_meta_data( $key, $value );
		}

		$order->save();

		return $order;
	}

	/**
	 * Create an order-tracking service.
	 *
	 * @param NativePaymentsRuntimeArbiter      $arbiter         Runtime arbiter.
	 * @param WooPaymentsActionSchedulerService $scheduler       Scheduler service.
	 * @param WooPaymentsApiClient|null         $api_client      API client.
	 * @param WooPaymentsAccountService|null    $account_service Account service.
	 * @return WooPaymentsOrderTrackingService
	 */
	private function create_service(
		NativePaymentsRuntimeArbiter $arbiter,
		WooPaymentsActionSchedulerService $scheduler = new RecordingActionSchedulerService(),
		?WooPaymentsApiClient $api_client = null,
		?WooPaymentsAccountService $account_service = null
	): WooPaymentsOrderTrackingService {
		$service = new WooPaymentsOrderTrackingService();
		$service->init(
			$arbiter,
			$scheduler,
			$api_client ?? $this->create_api_client(),
			$account_service ?? $this->create_account_service( true )
		);

		$this->services[] = $service;

		return $service;
	}

	/**
	 * Create a WooPayments API client mock.
	 *
	 * @return WooPaymentsApiClient
	 */
	private function create_api_client(): WooPaymentsApiClient {
		return $this->getMockBuilder( WooPaymentsApiClient::class )
			->disableOriginalConstructor()
			->onlyMethods( array( 'track_order' ) )
			->getMock();
	}

	/**
	 * Create a WooPayments account service mock.
	 *
	 * @param bool $test_mode Whether WooPayments should run in test mode.
	 * @return WooPaymentsAccountService
	 */
	private function create_account_service( bool $test_mode ): WooPaymentsAccountService {
		$account_service = $this->getMockBuilder( WooPaymentsAccountService::class )
			->disableOriginalConstructor()
			->onlyMethods( array( 'is_test_mode_enabled' ) )
			->getMock();

		$account_service->method( 'is_test_mode_enabled' )->willReturn( $test_mode );

		return $account_service;
	}

	/**
	 * Remove registered order-tracking hooks for a service.
	 *
	 * @param WooPaymentsOrderTrackingService $service Service instance.
	 */
	private function remove_tracking_hooks( WooPaymentsOrderTrackingService $service ): void {
		remove_action( 'woocommerce_update_order', array( $service, 'handle_woocommerce_update_order' ) );
		remove_action( 'wcpay_track_new_order', array( $service, 'handle_wcpay_track_new_order' ) );
		remove_action( 'wcpay_track_update_order', array( $service, 'handle_wcpay_track_update_order' ) );
	}
}
