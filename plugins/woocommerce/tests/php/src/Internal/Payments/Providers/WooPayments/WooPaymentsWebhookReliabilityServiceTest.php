<?php
declare( strict_types = 1 );

namespace Automattic\WooCommerce\Tests\Internal\Payments\Providers\WooPayments;

use Automattic\WooCommerce\Internal\Payments\NativePaymentsRuntimeArbiter;
use Automattic\WooCommerce\Internal\Payments\Providers\WooPayments\WooPaymentsActionSchedulerService;
use Automattic\WooCommerce\Internal\Payments\Providers\WooPayments\WooPaymentsEventIngestor;
use Automattic\WooCommerce\Internal\Payments\Providers\WooPayments\WooPaymentsFailedEventStore;
use Automattic\WooCommerce\Internal\Payments\Providers\WooPayments\WooPaymentsFailedEventsProvider;
use Automattic\WooCommerce\Internal\Payments\Providers\WooPayments\WooPaymentsWebhookReliabilityService;
use WC_Unit_Test_Case;

/**
 * Tests for the WooPaymentsWebhookReliabilityService class.
 */
class WooPaymentsWebhookReliabilityServiceTest extends WC_Unit_Test_Case {

	/**
	 * The System Under Test.
	 *
	 * @var WooPaymentsWebhookReliabilityService
	 */
	private $sut;

	/**
	 * Set up test fixtures.
	 */
	public function setUp(): void {
		parent::setUp();
		$this->sut = wc_get_container()->get( WooPaymentsWebhookReliabilityService::class );
		$this->remove_reliability_hooks();
	}

	/**
	 * Tear down test fixtures.
	 */
	public function tearDown(): void {
		$this->remove_reliability_hooks();
		remove_all_filters( NativePaymentsRuntimeArbiter::FILTER_NATIVE_ENABLED );
		$this->reset_legacy_proxy_mocks();
		delete_transient( 'wcpay_failed_event_' . md5( 'evt_1' ) );
		delete_transient( 'wcpay_failed_event_' . md5( 'evt_process' ) );
		parent::tearDown();
	}

	/**
	 * @testdox Reliability hooks are not registered when the plugin owns runtime.
	 */
	public function test_registers_no_actions_when_plugin_owns_runtime(): void {
		$this->fake_plugin( true );
		add_filter( NativePaymentsRuntimeArbiter::FILTER_NATIVE_ENABLED, '__return_true' );

		$this->sut->register();

		$this->assertFalse( has_action( 'woocommerce_payments_account_refreshed', array( $this->sut, 'maybe_schedule_fetch_events' ) ) );
		$this->assertFalse( has_action( 'wcpay_webhook_fetch_events', array( $this->sut, 'fetch_events_and_schedule_processing_jobs' ) ) );
		$this->assertFalse( has_action( 'wcpay_webhook_process_event', array( $this->sut, 'process_event' ) ) );
	}

	/**
	 * @testdox Preserved reliability hooks are registered when native owns runtime.
	 */
	public function test_registers_preserved_actions_when_native_owns_runtime(): void {
		$this->fake_plugin( false );
		add_filter( NativePaymentsRuntimeArbiter::FILTER_NATIVE_ENABLED, '__return_true' );

		$this->sut->register();

		$this->assertSame( 10, has_action( 'woocommerce_payments_account_refreshed', array( $this->sut, 'maybe_schedule_fetch_events' ) ) );
		$this->assertSame( 10, has_action( 'wcpay_webhook_fetch_events', array( $this->sut, 'fetch_events_and_schedule_processing_jobs' ) ) );
		$this->assertSame( 10, has_action( 'wcpay_webhook_process_event', array( $this->sut, 'process_event' ) ) );
	}

	/**
	 * @testdox Account refresh flags schedule the preserved failed-event fetch action.
	 */
	public function test_account_refresh_flag_schedules_fetch_job(): void {
		$scheduler = new RecordingActionSchedulerService();
		$service   = $this->create_service(
			$scheduler,
			wc_get_container()->get( WooPaymentsFailedEventStore::class ),
			new StaticFailedEventsProvider(),
			new RecordingEventIngestor()
		);

		$service->maybe_schedule_fetch_events( array( 'has_more_failed_events' => true ) );

		$this->assertSame(
			array(
				array(
					'hook' => 'wcpay_webhook_fetch_events',
					'args' => array(),
				),
			),
			$scheduler->scheduled_jobs
		);
	}

	/**
	 * @testdox Fetching failed events stores payloads and schedules processing jobs.
	 */
	public function test_fetch_events_stores_events_and_schedules_processing_jobs(): void {
		$scheduler = new RecordingActionSchedulerService();
		$store     = wc_get_container()->get( WooPaymentsFailedEventStore::class );
		$event     = array(
			'id'   => 'evt_1',
			'type' => 'payment_intent.succeeded',
		);
		$service   = $this->create_service(
			$scheduler,
			$store,
			new StaticFailedEventsProvider(
				array(
					'data'     => array( $event, array( 'type' => 'missing.id' ) ),
					'has_more' => false,
				)
			),
			new RecordingEventIngestor()
		);

		$service->fetch_events_and_schedule_processing_jobs();

		$this->assertSame( $event, $store->get_event( 'evt_1' ) );
		$this->assertSame(
			array(
				array(
					'hook' => 'wcpay_webhook_process_event',
					'args' => array( 'event_id' => 'evt_1' ),
				),
			),
			$scheduler->scheduled_jobs
		);
	}

	/**
	 * @testdox Fetching failed events schedules a follow-up fetch when the provider has more.
	 */
	public function test_fetch_events_schedules_follow_up_when_provider_has_more(): void {
		$scheduler = new RecordingActionSchedulerService();
		$service   = $this->create_service(
			$scheduler,
			wc_get_container()->get( WooPaymentsFailedEventStore::class ),
			new StaticFailedEventsProvider(
				array(
					'data'     => array(),
					'has_more' => true,
				)
			),
			new RecordingEventIngestor()
		);

		$service->fetch_events_and_schedule_processing_jobs();

		$this->assertSame(
			array(
				array(
					'hook' => 'wcpay_webhook_fetch_events',
					'args' => array(),
				),
			),
			$scheduler->scheduled_jobs
		);
	}

	/**
	 * @testdox Processing a failed event consumes the transient and invokes the ingestor.
	 */
	public function test_process_event_consumes_transient_and_invokes_ingestor(): void {
		$store    = wc_get_container()->get( WooPaymentsFailedEventStore::class );
		$ingestor = new RecordingEventIngestor();
		$event    = array(
			'id'   => 'evt_process',
			'type' => 'payment_intent.succeeded',
		);
		$service  = $this->create_service( new RecordingActionSchedulerService(), $store, new StaticFailedEventsProvider(), $ingestor );

		$store->set_event( 'evt_process', $event );
		$service->process_event( 'evt_process' );

		$this->assertNull( $store->get_event( 'evt_process' ) );
		$this->assertSame( array( $event ), $ingestor->processed_events );
	}

	/**
	 * @testdox Processing a missing failed event payload is skipped.
	 */
	public function test_process_event_skips_missing_payload(): void {
		$ingestor = new RecordingEventIngestor();
		$service  = $this->create_service(
			new RecordingActionSchedulerService(),
			wc_get_container()->get( WooPaymentsFailedEventStore::class ),
			new StaticFailedEventsProvider(),
			$ingestor
		);

		$service->process_event( 'evt_missing' );

		$this->assertSame( array(), $ingestor->processed_events );
	}

	/**
	 * Remove reliability hooks for this SUT.
	 */
	private function remove_reliability_hooks(): void {
		remove_action( 'woocommerce_payments_account_refreshed', array( $this->sut, 'maybe_schedule_fetch_events' ) );
		remove_action( 'wcpay_webhook_fetch_events', array( $this->sut, 'fetch_events_and_schedule_processing_jobs' ) );
		remove_action( 'wcpay_webhook_process_event', array( $this->sut, 'process_event' ) );
	}

	/**
	 * Control every WooPayments-plugin detection signal in a single mock registration.
	 *
	 * @param bool $active Whether the WooPayments plugin should appear active.
	 */
	private function fake_plugin( bool $active ): void {
		$entry = NativePaymentsRuntimeArbiter::PLUGIN_FILE;
		$this->register_legacy_proxy_function_mocks(
			array(
				'get_option'      => function ( $name, $default_value = false ) use ( $active, $entry ) {
					if ( 'active_plugins' === $name ) {
						return $active ? array( $entry ) : array();
					}
					return get_option( $name, $default_value );
				},
				'get_site_option' => function ( $name, $default_value = false ) {
					if ( 'active_sitewide_plugins' === $name ) {
						return array();
					}
					return get_site_option( $name, $default_value );
				},
				'class_exists'    => function ( $class_name, $autoload = true ) use ( $active ) {
					if ( 'WC_Payments' === ltrim( (string) $class_name, '\\' ) ) {
						return $active;
					}
					return class_exists( $class_name, $autoload );
				},
			)
		);
	}

	/**
	 * Create a reliability service with supplied collaborators.
	 *
	 * @param WooPaymentsActionSchedulerService $scheduler Scheduler service.
	 * @param WooPaymentsFailedEventStore       $store     Failed event store.
	 * @param WooPaymentsFailedEventsProvider   $provider  Failed events provider.
	 * @param WooPaymentsEventIngestor          $ingestor  Event ingestor.
	 * @return WooPaymentsWebhookReliabilityService
	 */
	private function create_service(
		WooPaymentsActionSchedulerService $scheduler,
		WooPaymentsFailedEventStore $store,
		WooPaymentsFailedEventsProvider $provider,
		WooPaymentsEventIngestor $ingestor
	): WooPaymentsWebhookReliabilityService {
		$service = new WooPaymentsWebhookReliabilityService();
		$service->init( wc_get_container()->get( NativePaymentsRuntimeArbiter::class ), $scheduler, $store, $provider, $ingestor );

		return $service;
	}
}
