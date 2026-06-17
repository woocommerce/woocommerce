<?php
declare( strict_types = 1 );

namespace Automattic\WooCommerce\Tests\Internal\Payments\Providers\WooPayments;

use ActionScheduler_Store;
use Automattic\WooCommerce\Internal\Payments\NativePaymentsRuntimeArbiter;
use Automattic\WooCommerce\Internal\Payments\Providers\WooPayments\Api\WooPaymentsApiClient;
use Automattic\WooCommerce\Internal\Payments\Providers\WooPayments\WooPaymentsAccountService;
use Automattic\WooCommerce\Internal\Payments\Providers\WooPayments\WooPaymentsActionSchedulerService;
use Automattic\WooCommerce\Internal\Payments\Providers\WooPayments\WooPaymentsOperationalQueueService;
use Automattic\WooCommerce\Internal\Payments\Providers\WooPayments\WooPaymentsOrderDataService;
use Automattic\WooCommerce\Tests\Internal\Payments\StaticNativeRuntimeArbiter;
use WC_Order;
use WC_Unit_Test_Case;

/**
 * Tests for the WooPaymentsOperationalQueueService class.
 */
class WooPaymentsOperationalQueueServiceTest extends WC_Unit_Test_Case {

	/**
	 * Created services whose hooks must be removed after each test.
	 *
	 * @var WooPaymentsOperationalQueueService[]
	 */
	private array $services = array();

	/**
	 * Tear down test fixtures.
	 */
	public function tearDown(): void {
		foreach ( $this->services as $service ) {
			$this->remove_operational_hooks( $service );
		}

		remove_all_filters( 'wcpay_test_mode' );
		if ( function_exists( 'as_unschedule_all_actions' ) ) {
			as_unschedule_all_actions( WooPaymentsOperationalQueueService::STORE_SETUP_SYNC_ACTION, null, WooPaymentsActionSchedulerService::GROUP_ID );
		}
		parent::tearDown();
	}

	/**
	 * @testdox Operational queue hooks are registered when native owns runtime.
	 */
	public function test_registers_preserved_operational_hooks_when_native_owns_runtime(): void {
		$service = $this->create_service( new StaticNativeRuntimeArbiter( true ) );

		$service->register();

		$this->assertSame( 10, has_action( 'wcpay_store_setup_sync', array( $service, 'handle_wcpay_store_setup_sync' ) ) );
		$this->assertSame( 10, has_action( 'woocommerce_woocommerce_payments_updated', array( $service, 'handle_wcpay_store_setup_sync' ) ) );
		$this->assertSame( 10, has_action( 'wcpay_update_saved_payment_method', array( $service, 'handle_wcpay_update_saved_payment_method' ) ) );
		$this->assertSame( 10, has_action( 'wcpay_add_fee_breakdown_to_order_notes', array( $service, 'handle_wcpay_add_fee_breakdown_to_order_notes' ) ) );
		$this->assertSame( 10, has_action( 'wcpay_update_compatibility_data', array( $service, 'handle_wcpay_update_compatibility_data' ) ) );
		$this->assertSame( 10, has_action( 'woocommerce_payments_account_refreshed', array( $service, 'schedule_compatibility_data_update' ) ) );
		$this->assertSame( 10, has_action( 'after_switch_theme', array( $service, 'schedule_compatibility_data_update' ) ) );
		$this->assertFalse( has_action( 'wcpay_instant_deposit_reminder', array( $service, 'handle_wcpay_instant_deposit_reminder' ) ) );
		$this->assertFalse( has_action( 'wcpay_post_kyc_activation_email_send', array( $service, 'handle_wcpay_post_kyc_activation_email_send' ) ) );
	}

	/**
	 * @testdox Operational queue hooks are not registered when plugin owns runtime.
	 */
	public function test_registers_no_operational_hooks_when_plugin_owns_runtime(): void {
		$service = $this->create_service( new StaticNativeRuntimeArbiter( false ) );

		$service->register();

		$this->assertFalse( has_action( 'wcpay_store_setup_sync', array( $service, 'handle_wcpay_store_setup_sync' ) ) );
		$this->assertFalse( has_action( 'wcpay_update_saved_payment_method', array( $service, 'handle_wcpay_update_saved_payment_method' ) ) );
		$this->assertFalse( has_action( 'wcpay_add_fee_breakdown_to_order_notes', array( $service, 'handle_wcpay_add_fee_breakdown_to_order_notes' ) ) );
		$this->assertFalse( has_action( 'wcpay_update_compatibility_data', array( $service, 'handle_wcpay_update_compatibility_data' ) ) );
	}

	/**
	 * @testdox Store setup sync sends a WooPayments-compatible snapshot through the API client.
	 */
	public function test_store_setup_sync_sends_store_snapshot(): void {
		update_option(
			'woocommerce_woocommerce_payments_settings',
			array(
				'enabled'                           => 'yes',
				'test_mode'                         => 'yes',
				'upe_available_payment_methods'     => array( 'card', 'ideal' ),
				'upe_enabled_payment_method_ids'    => array( 'card' ),
				'manual_capture'                    => 'yes',
				'enable_logging'                    => 'yes',
				'saved_cards'                       => 'yes',
				'express_checkout_product_methods'  => array( 'payment_request', 'woopay' ),
				'express_checkout_cart_methods'     => array( 'payment_request' ),
				'express_checkout_checkout_methods' => array( 'woopay' ),
			)
		);

		$api_client = $this->create_api_client( array( 'is_available', 'send_store_setup' ) );
		$api_client->method( 'is_available' )->willReturn( true );
		$api_client->expects( $this->once() )
			->method( 'send_store_setup' )
			->with(
				$this->callback(
					function ( array $snapshot ): bool {
						return true === $snapshot['gateway']['enabled']
							&& true === $snapshot['gateway']['test_mode']
							&& array( 'card', 'ideal' ) === $snapshot['payment_methods']['available']
							&& array( 'card' ) === $snapshot['payment_methods']['enabled']
							&& array( 'ideal' ) === $snapshot['payment_methods']['disabled']
							&& in_array( 'card_payments', $snapshot['provider_capabilities']['enabled'], true )
							&& in_array( 'ideal_payments', $snapshot['provider_capabilities']['disabled'], true )
							&& true === $snapshot['manual_capture_enabled']
							&& true === $snapshot['debug_log_enabled']
							&& true === $snapshot['payment_request']['enabled']
							&& array( 'product', 'cart' ) === $snapshot['payment_request']['enabled_locations']
							&& true === $snapshot['woopay']['enabled']
							&& array( 'product', 'checkout' ) === $snapshot['woopay']['enabled_locations'];
					}
				)
			)
			->willReturn( array( 'result' => 'success' ) );

		$this->create_service( new StaticNativeRuntimeArbiter( true ), new RecordingActionSchedulerService(), $api_client )->handle_wcpay_store_setup_sync();
	}

	/**
	 * @testdox Recurring store setup sync is scheduled once in the WooPayments group.
	 */
	public function test_schedule_recurring_actions_schedules_store_setup_sync_once(): void {
		$service = $this->create_service( new StaticNativeRuntimeArbiter( true ) );

		$service->schedule_recurring_actions();
		$service->schedule_recurring_actions();

		$this->assertCount(
			1,
			as_get_scheduled_actions(
				array(
					'hook'   => WooPaymentsOperationalQueueService::STORE_SETUP_SYNC_ACTION,
					'group'  => WooPaymentsActionSchedulerService::GROUP_ID,
					'status' => ActionScheduler_Store::STATUS_PENDING,
				)
			)
		);
	}

	/**
	 * @testdox Compatibility updates are debounced through the preserved Action Scheduler hook.
	 */
	public function test_schedule_compatibility_data_update_schedules_delayed_job(): void {
		$scheduler = new RecordingActionSchedulerService();
		$service   = $this->create_service( new StaticNativeRuntimeArbiter( true ), $scheduler );

		$service->schedule_compatibility_data_update();

		$this->assertSame( 'wcpay_update_compatibility_data', $scheduler->scheduled_jobs[0]['hook'] );
		$this->assertSame( array(), $scheduler->scheduled_jobs[0]['args'] );
		$this->assertGreaterThanOrEqual( time() + MINUTE_IN_SECONDS, $scheduler->scheduled_jobs[0]['timestamp'] );
	}

	/**
	 * @testdox Compatibility hook sends the WooPayments-compatible payload through the API client.
	 */
	public function test_update_compatibility_data_sends_payload(): void {
		$api_client = $this->create_api_client( array( 'update_compatibility_data' ) );
		$api_client->expects( $this->once() )
			->method( 'update_compatibility_data' )
			->with(
				$this->callback(
					function ( array $payload ): bool {
						return isset(
							$payload['woopayments_version'],
							$payload['woocommerce_version'],
							$payload['woocommerce_permalinks'],
							$payload['woocommerce_shop'],
							$payload['woocommerce_cart'],
							$payload['woocommerce_checkout'],
							$payload['blog_theme'],
							$payload['active_plugins'],
							$payload['post_types_count']
						);
					}
				)
			)
			->willReturn( array( 'result' => 'success' ) );

		$this->create_service( new StaticNativeRuntimeArbiter( true ), new RecordingActionSchedulerService(), $api_client )->handle_wcpay_update_compatibility_data();
	}

	/**
	 * @testdox Saved-payment-method jobs preserve test-mode context and update billing details.
	 */
	public function test_update_saved_payment_method_updates_billing_details_with_test_mode_context(): void {
		$order = wc_create_order();
		$this->assertInstanceOf( WC_Order::class, $order );
		$order->set_billing_first_name( 'Ada' );
		$order->set_billing_last_name( 'Lovelace' );
		$order->set_billing_email( 'ada@example.com' );
		$order->set_billing_country( 'US' );
		$order->save();

		$api_client = $this->create_api_client( array( 'update_payment_method' ) );
		$api_client->expects( $this->once() )
			->method( 'update_payment_method' )
			->with(
				'pm_123',
				$this->callback(
					function ( array $payload ): bool {
						return true === $this->is_wcpay_test_mode()
							&& 'Ada Lovelace' === $payload['billing_details']['name']
							&& 'ada@example.com' === $payload['billing_details']['email'];
					}
				)
			)
			->willReturn( array( 'result' => 'success' ) );

		$this->create_service( new StaticNativeRuntimeArbiter( true ), new RecordingActionSchedulerService(), $api_client )->handle_wcpay_update_saved_payment_method( 'pm_123', $order->get_id(), true );

		$this->assertFalse( $this->is_wcpay_test_mode() );
	}

	/**
	 * @testdox Fee-breakdown jobs render captured timeline events as order notes.
	 */
	public function test_add_fee_breakdown_to_order_notes_renders_captured_timeline_event(): void {
		$order = wc_create_order();
		$this->assertInstanceOf( WC_Order::class, $order );
		$order->save();

		$api_client = $this->create_api_client( array( 'get_timeline' ) );
		$api_client->expects( $this->once() )
			->method( 'get_timeline' )
			->with( 'pi_123' )
			->willReturn(
				array(
					'data' => array(
						array( 'type' => 'authorized' ),
						$this->get_captured_timeline_event(),
					),
				)
			);

		$this->create_service( new StaticNativeRuntimeArbiter( true ), new RecordingActionSchedulerService(), $api_client )->handle_wcpay_add_fee_breakdown_to_order_notes( $order->get_id(), 'pi_123', true );

		$notes = wc_get_order_notes( array( 'order_id' => $order->get_id() ) );
		$this->assertSame( $this->get_expected_fee_note(), $notes[0]->content );
		$this->assertFalse( $this->is_wcpay_test_mode() );
	}

	/**
	 * @testdox Fee-breakdown jobs skip malformed timeline data without adding an empty note.
	 */
	public function test_add_fee_breakdown_to_order_notes_skips_malformed_timeline(): void {
		$order = wc_create_order();
		$this->assertInstanceOf( WC_Order::class, $order );
		$order->save();

		$api_client = $this->create_api_client( array( 'get_timeline' ) );
		$api_client->method( 'get_timeline' )->willReturn( array( 'data' => array( array( 'type' => 'authorized' ) ) ) );

		$this->create_service( new StaticNativeRuntimeArbiter( true ), new RecordingActionSchedulerService(), $api_client )->handle_wcpay_add_fee_breakdown_to_order_notes( $order->get_id(), 'pi_123', false );

		$this->assertSame( array(), wc_get_order_notes( array( 'order_id' => $order->get_id() ) ) );
	}

	/**
	 * Create an operational queue service.
	 *
	 * @param NativePaymentsRuntimeArbiter           $arbiter            Runtime arbiter.
	 * @param WooPaymentsActionSchedulerService|null $scheduler          Scheduler service.
	 * @param WooPaymentsApiClient|null              $api_client         API client.
	 * @param WooPaymentsAccountService|null         $account_service    Account service.
	 * @param WooPaymentsOrderDataService|null       $order_data_service Order data service.
	 * @return WooPaymentsOperationalQueueService
	 */
	private function create_service(
		NativePaymentsRuntimeArbiter $arbiter,
		?WooPaymentsActionSchedulerService $scheduler = null,
		?WooPaymentsApiClient $api_client = null,
		?WooPaymentsAccountService $account_service = null,
		?WooPaymentsOrderDataService $order_data_service = null
	): WooPaymentsOperationalQueueService {
		$service = new WooPaymentsOperationalQueueService();
		$service->init(
			$arbiter,
			$scheduler ?? new RecordingActionSchedulerService(),
			$api_client ?? $this->create_api_client( array() ),
			$account_service ?? $this->create_account_service(),
			$order_data_service ?? wc_get_container()->get( WooPaymentsOrderDataService::class )
		);

		$this->services[] = $service;

		return $service;
	}

	/**
	 * Create a WooPayments API client mock.
	 *
	 * @param string[] $methods Mocked method names.
	 * @return WooPaymentsApiClient
	 */
	private function create_api_client( array $methods ): WooPaymentsApiClient {
		$builder = $this->getMockBuilder( WooPaymentsApiClient::class )
			->disableOriginalConstructor();

		if ( ! empty( $methods ) ) {
			$builder->onlyMethods( $methods );
		}

		return $builder->getMock();
	}

	/**
	 * Create an account service mock.
	 *
	 * @return WooPaymentsAccountService
	 */
	private function create_account_service(): WooPaymentsAccountService {
		$account_service = $this->getMockBuilder( WooPaymentsAccountService::class )
			->disableOriginalConstructor()
			->onlyMethods( array( 'is_test_mode_enabled', 'is_test_mode_onboarding_enabled' ) )
			->getMock();

		$account_service->method( 'is_test_mode_enabled' )->willReturn( true );
		$account_service->method( 'is_test_mode_onboarding_enabled' )->willReturn( true );

		return $account_service;
	}

	/**
	 * Get a captured timeline event with a fee breakdown.
	 *
	 * @return array<string,mixed>
	 */
	private function get_captured_timeline_event(): array {
		return array(
			'type'             => 'captured',
			'fee_breakdown_v1' => array(
				'totals'  => array(
					'fee'         => array(
						'amount'   => 293,
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
	 * Check the current WooPayments test mode filter value.
	 *
	 * @return bool
	 */
	private function is_wcpay_test_mode(): bool {
		/**
		 * Filters whether the current WooPayments request runs in test mode.
		 *
		 * @since 11.0.0
		 */
		return (bool) apply_filters( 'wcpay_test_mode', false );
	}

	/**
	 * Remove registered operational hooks for a service.
	 *
	 * @param WooPaymentsOperationalQueueService $service Service instance.
	 */
	private function remove_operational_hooks( WooPaymentsOperationalQueueService $service ): void {
		remove_action( 'wcpay_store_setup_sync', array( $service, 'handle_wcpay_store_setup_sync' ) );
		remove_action( 'woocommerce_woocommerce_payments_updated', array( $service, 'handle_wcpay_store_setup_sync' ) );
		remove_action( 'wcpay_update_saved_payment_method', array( $service, 'handle_wcpay_update_saved_payment_method' ) );
		remove_action( 'wcpay_add_fee_breakdown_to_order_notes', array( $service, 'handle_wcpay_add_fee_breakdown_to_order_notes' ) );
		remove_action( 'wcpay_update_compatibility_data', array( $service, 'handle_wcpay_update_compatibility_data' ) );
		remove_action( 'woocommerce_payments_account_refreshed', array( $service, 'schedule_compatibility_data_update' ) );
		remove_action( 'after_switch_theme', array( $service, 'schedule_compatibility_data_update' ) );
		remove_action( 'action_scheduler_ensure_recurring_actions', array( $service, 'schedule_recurring_actions' ) );
	}
}
