<?php

declare( strict_types = 1 );

namespace Automattic\WooCommerce\Tests\Admin\API;

use Automattic\WooCommerce\Internal\Admin\Schedulers\OrdersScheduler;
use WC_REST_Unit_Test_Case;
use WP_REST_Request;

/**
 * AnalyticsImports API controller test.
 *
 * @class AnalyticsImportsTest
 */
class AnalyticsImportsTest extends WC_REST_Unit_Test_Case {
	/**
	 * Endpoint.
	 *
	 * @var string
	 */
	const ENDPOINT = '/wc-analytics/imports';

	/**
	 * Administrator user.
	 *
	 * @var int
	 */
	protected $admin_user;

	/**
	 * Shop manager user.
	 *
	 * @var int
	 */
	protected $shop_manager_user;

	/**
	 * Customer user.
	 *
	 * @var int
	 */
	protected $customer_user;

	/**
	 * Set up.
	 */
	public function setUp(): void {
		parent::setUp();

		// Create test users.
		$this->admin_user = $this->factory->user->create(
			array(
				'role' => 'administrator',
			)
		);

		$this->shop_manager_user = $this->factory->user->create(
			array(
				'role' => 'shop_manager',
			)
		);

		$this->customer_user = $this->factory->user->create(
			array(
				'role' => 'customer',
			)
		);

		// Clear any scheduled actions.
		$this->clear_scheduled_actions();
	}

	/**
	 * Tear down.
	 */
	public function tearDown(): void {
		$this->clear_scheduled_actions();
		delete_option( OrdersScheduler::SCHEDULED_IMPORT_OPTION );
		delete_option( OrdersScheduler::LAST_PROCESSED_ORDER_DATE_OPTION );
		parent::tearDown();
	}

	/**
	 * Clear all scheduled batch import actions.
	 */
	private function clear_scheduled_actions() {
		$hook = OrdersScheduler::get_action( OrdersScheduler::PROCESS_PENDING_ORDERS_BATCH_ACTION );
		as_unschedule_all_actions( $hook );
	}

	/**
	 * Test status endpoint returns correct mode for immediate import.
	 *
	 * @return void
	 */
	public function test_status_returns_immediate_mode(): void {
		wp_set_current_user( $this->admin_user );

		// Set to immediate mode (scheduled disabled).
		update_option( OrdersScheduler::SCHEDULED_IMPORT_OPTION, 'no' );

		$request  = new WP_REST_Request( 'GET', self::ENDPOINT . '/status' );
		$response = $this->server->dispatch( $request );
		$data     = $response->get_data();

		$this->assertSame( 200, $response->get_status() );
		$this->assertArrayHasKey( 'mode', $data );
		$this->assertSame( 'immediate', $data['mode'] );
		$this->assertArrayHasKey( 'last_processed_date', $data );
		$this->assertNull( $data['last_processed_date'] );
		$this->assertArrayHasKey( 'next_scheduled', $data );
		$this->assertNull( $data['next_scheduled'] );
		$this->assertArrayHasKey( 'import_in_progress_or_due', $data );
		$this->assertNull( $data['import_in_progress_or_due'] );
	}

	/**
	 * Test status endpoint returns correct mode for scheduled import.
	 *
	 * @return void
	 */
	public function test_status_returns_scheduled_mode(): void {
		wp_set_current_user( $this->admin_user );

		// Set to scheduled mode (scheduled enabled).
		update_option( OrdersScheduler::SCHEDULED_IMPORT_OPTION, 'yes' );
		update_option( OrdersScheduler::LAST_PROCESSED_ORDER_DATE_OPTION, '2025-11-26 05:30:00' );

		$request  = new WP_REST_Request( 'GET', self::ENDPOINT . '/status' );
		$response = $this->server->dispatch( $request );
		$data     = $response->get_data();

		$this->assertSame( 200, $response->get_status() );
		$this->assertArrayHasKey( 'mode', $data );
		$this->assertSame( 'scheduled', $data['mode'] );
		$this->assertArrayHasKey( 'last_processed_date', $data );
		$this->assertIsString( $data['last_processed_date'] );
		$this->assertArrayHasKey( 'import_in_progress_or_due', $data );
		$this->assertIsBool( $data['import_in_progress_or_due'] );
	}

	/**
	 * Test status endpoint converts datetime to site timezone.
	 *
	 * @return void
	 */
	public function test_status_converts_datetime_to_site_timezone(): void {
		wp_set_current_user( $this->admin_user );

		// Set to scheduled mode.
		update_option( OrdersScheduler::SCHEDULED_IMPORT_OPTION, 'yes' );

		// Set last processed date in GMT.
		$gmt_date = '2025-11-26 05:30:00';
		update_option( OrdersScheduler::LAST_PROCESSED_ORDER_DATE_OPTION, $gmt_date );

		$request  = new WP_REST_Request( 'GET', self::ENDPOINT . '/status' );
		$response = $this->server->dispatch( $request );
		$data     = $response->get_data();

		$this->assertSame( 200, $response->get_status() );

		// Verify the date was converted from GMT to site timezone.
		$expected_date = get_date_from_gmt( $gmt_date, 'Y-m-d H:i:s' );
		$this->assertSame( $expected_date, $data['last_processed_date'] );
	}

	/**
	 * Test status endpoint requires manage_woocommerce capability.
	 *
	 * @return void
	 */
	public function test_status_requires_permission(): void {
		wp_set_current_user( $this->customer_user );

		$request  = new WP_REST_Request( 'GET', self::ENDPOINT . '/status' );
		$response = $this->server->dispatch( $request );

		$this->assertSame( 403, $response->get_status() );
	}

	/**
	 * Test shop manager can access status endpoint.
	 *
	 * @return void
	 */
	public function test_shop_manager_can_access_status(): void {
		wp_set_current_user( $this->shop_manager_user );

		$request  = new WP_REST_Request( 'GET', self::ENDPOINT . '/status' );
		$response = $this->server->dispatch( $request );

		$this->assertSame( 200, $response->get_status() );
	}

	/**
	 * Test trigger endpoint successfully triggers batch import.
	 *
	 * @return void
	 */
	public function test_trigger_successfully_triggers_import(): void {
		wp_set_current_user( $this->admin_user );

		// Set to scheduled mode.
		update_option( OrdersScheduler::SCHEDULED_IMPORT_OPTION, 'yes' );
		// Clear any scheduled actions that may have been created when setting the option.
		$this->clear_scheduled_actions();

		$request  = new WP_REST_Request( 'POST', self::ENDPOINT . '/trigger' );
		$response = $this->server->dispatch( $request );
		$data     = $response->get_data();

		$this->assertSame( 200, $response->get_status() );
		$this->assertArrayHasKey( 'success', $data );
		$this->assertTrue( $data['success'] );
		$this->assertArrayHasKey( 'message', $data );
		$this->assertIsString( $data['message'] );
	}

	/**
	 * Test trigger endpoint returns error in immediate mode.
	 *
	 * @return void
	 */
	public function test_trigger_fails_in_immediate_mode(): void {
		wp_set_current_user( $this->admin_user );

		// Set to immediate mode (scheduled disabled).
		update_option( OrdersScheduler::SCHEDULED_IMPORT_OPTION, 'no' );

		$request  = new WP_REST_Request( 'POST', self::ENDPOINT . '/trigger' );
		$response = $this->server->dispatch( $request );

		$this->assertSame( 400, $response->get_status() );

		$data = $response->get_data();
		$this->assertArrayHasKey( 'code', $data );
		$this->assertSame( 'woocommerce_rest_analytics_import_immediate_mode', $data['code'] );
	}

	/**
	 * Test trigger endpoint requires manage_woocommerce capability.
	 *
	 * @return void
	 */
	public function test_trigger_requires_permission(): void {
		wp_set_current_user( $this->customer_user );

		$request  = new WP_REST_Request( 'POST', self::ENDPOINT . '/trigger' );
		$response = $this->server->dispatch( $request );

		$this->assertSame( 403, $response->get_status() );
	}

	/**
	 * Test shop manager can trigger import.
	 *
	 * @return void
	 */
	public function test_shop_manager_can_trigger_import(): void {
		wp_set_current_user( $this->shop_manager_user );

		// Set to scheduled mode.
		update_option( OrdersScheduler::SCHEDULED_IMPORT_OPTION, 'yes' );
		// Clear any scheduled actions that may have been created when setting the option.
		$this->clear_scheduled_actions();

		$request  = new WP_REST_Request( 'POST', self::ENDPOINT . '/trigger' );
		$response = $this->server->dispatch( $request );

		$this->assertSame( 200, $response->get_status() );
	}
}
