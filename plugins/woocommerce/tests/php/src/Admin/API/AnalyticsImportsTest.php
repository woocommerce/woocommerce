<?php

declare( strict_types = 1 );

namespace Automattic\WooCommerce\Tests\Admin\API;

use Automattic\WooCommerce\Admin\Features\Features;
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
		delete_option( OrdersScheduler::FAILED_ORDER_IMPORTS_OPTION );
		parent::tearDown();
	}

	/**
	 * Clear all scheduled batch import actions.
	 */
	private function clear_scheduled_actions() {
		$hook = OrdersScheduler::get_action( OrdersScheduler::PROCESS_PENDING_ORDERS_BATCH_ACTION );
		as_unschedule_all_actions( $hook );
		as_unschedule_all_actions( OrdersScheduler::get_action( 'import' ) );
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

	/**
	 * @testdox Retry-failed schedules an import action per failed order and keeps IDs until success.
	 */
	public function test_retry_failed_schedules_import_actions(): void {
		wp_set_current_user( $this->admin_user );
		$order = \WC_Helper_Order::create_order();
		OrdersScheduler::record_failed_order_import( $order->get_id() );
		// Saving the order schedules an immediate-mode import action; clear it
		// so the test verifies the endpoint scheduled the action itself.
		$this->clear_scheduled_actions();

		$request  = new WP_REST_Request( 'POST', self::ENDPOINT . '/retry-failed' );
		$response = $this->server->dispatch( $request );
		$data     = $response->get_data();

		$this->assertSame( 200, $response->get_status() );
		$this->assertTrue( $data['success'] );
		$this->assertSame( 1, $data['retried_count'] );

		$hook = OrdersScheduler::get_action( 'import' );
		$this->assertNotFalse(
			as_next_scheduled_action( $hook, array( $order->get_id() ), OrdersScheduler::$group ),
			'A single-order import action should be scheduled'
		);

		$failed = OrdersScheduler::get_failed_order_imports();
		$this->assertContains( $order->get_id(), $failed['ids'], 'ID stays recorded until the import succeeds' );
	}

	/**
	 * @testdox Retry-failed prunes orders that no longer exist.
	 */
	public function test_retry_failed_prunes_deleted_orders(): void {
		wp_set_current_user( $this->admin_user );
		OrdersScheduler::record_failed_order_import( 99999999 );

		$request  = new WP_REST_Request( 'POST', self::ENDPOINT . '/retry-failed' );
		$response = $this->server->dispatch( $request );
		$data     = $response->get_data();

		$this->assertSame( 200, $response->get_status() );
		$this->assertSame( 0, $data['retried_count'] );
		$this->assertTrue( $data['success'] );
		$this->assertSame( 1, $data['pruned_count'] );

		$failed = OrdersScheduler::get_failed_order_imports();
		$this->assertNotContains( 99999999, $failed['ids'], 'Deleted orders can never succeed and should be pruned' );
	}

	/**
	 * @testdox Retry-failed returns an error when there are no failed orders.
	 */
	public function test_retry_failed_returns_error_when_no_failed_orders(): void {
		wp_set_current_user( $this->admin_user );

		$request  = new WP_REST_Request( 'POST', self::ENDPOINT . '/retry-failed' );
		$response = $this->server->dispatch( $request );

		$this->assertSame( 400, $response->get_status() );
	}

	/**
	 * @testdox Retry-failed requires the manage_woocommerce capability.
	 */
	public function test_retry_failed_requires_permission(): void {
		wp_set_current_user( $this->customer_user );

		$request  = new WP_REST_Request( 'POST', self::ENDPOINT . '/retry-failed' );
		$response = $this->server->dispatch( $request );

		$this->assertSame( 403, $response->get_status() );
	}

	/**
	 * @testdox Retry-failed does not schedule duplicate import actions on repeated requests.
	 */
	public function test_retry_failed_is_idempotent_for_pending_actions(): void {
		wp_set_current_user( $this->admin_user );
		$order = \WC_Helper_Order::create_order();
		OrdersScheduler::record_failed_order_import( $order->get_id() );
		// Saving the order schedules an immediate-mode import action; clear it
		// so the first request is the one that schedules the action.
		$this->clear_scheduled_actions();

		$request         = new WP_REST_Request( 'POST', self::ENDPOINT . '/retry-failed' );
		$first_response  = $this->server->dispatch( $request );
		$second_response = $this->server->dispatch( $request );

		$pending = WC()->queue()->search(
			array(
				'hook'     => OrdersScheduler::get_action( 'import' ),
				'search'   => '[' . $order->get_id() . ']',
				'status'   => 'pending',
				'per_page' => 10,
			)
		);
		$this->assertCount( 1, $pending, 'Repeated retry requests must not duplicate pending import actions' );

		// The response must not claim new work was scheduled on the second request.
		$this->assertSame( 1, $first_response->get_data()['retried_count'] );
		$this->assertSame( 0, $second_response->get_data()['retried_count'] );
		$this->assertSame( 1, $second_response->get_data()['already_scheduled_count'] );
		$this->assertSame(
			'Re-import is already scheduled for the previously failed orders.',
			$second_response->get_data()['message']
		);
	}

	/**
	 * @testdox Retry-failed returns an error when no order could be scheduled for re-import.
	 */
	public function test_retry_failed_surfaces_scheduling_errors(): void {
		wp_set_current_user( $this->admin_user );
		$order = \WC_Helper_Order::create_order();
		OrdersScheduler::record_failed_order_import( $order->get_id() );
		// Saving the order schedules an immediate-mode import action; clear it
		// so the endpoint reaches the (synchronous, throwing) scheduling path.
		$this->clear_scheduled_actions();

		// Force schedule_action() to run the import synchronously, and make
		// the import itself throw, so the scheduling attempt errors.
		add_filter( 'woocommerce_analytics_disable_action_scheduling', '__return_true' );
		$throwing_filter = function () {
			throw new \DivisionByZeroError( 'Division by zero' );
		};
		add_filter( 'woocommerce_analytics_is_test_order', $throwing_filter );

		try {
			$request  = new WP_REST_Request( 'POST', self::ENDPOINT . '/retry-failed' );
			$response = $this->server->dispatch( $request );
		} finally {
			remove_filter( 'woocommerce_analytics_is_test_order', $throwing_filter );
			remove_filter( 'woocommerce_analytics_disable_action_scheduling', '__return_true' );
		}

		$this->assertSame( 500, $response->get_status(), 'A fully failed retry must not report success' );
		$this->assertSame( 'woocommerce_rest_analytics_retry_failed', $response->get_data()['code'] );

		$failed = OrdersScheduler::get_failed_order_imports();
		$this->assertContains( $order->get_id(), $failed['ids'], 'Orders that failed again stay recorded' );
	}

	/**
	 * @testdox Status endpoint includes failed import counts in both modes.
	 */
	public function test_status_includes_failed_counts(): void {
		wp_set_current_user( $this->admin_user );
		update_option( OrdersScheduler::SCHEDULED_IMPORT_OPTION, 'no' );
		update_option(
			OrdersScheduler::FAILED_ORDER_IMPORTS_OPTION,
			array(
				'ids'      => array( 11, 22 ),
				'overflow' => 3,
			),
			false
		);

		$request  = new WP_REST_Request( 'GET', self::ENDPOINT . '/status' );
		$response = $this->server->dispatch( $request );
		$data     = $response->get_data();

		$this->assertSame( 200, $response->get_status() );
		$this->assertSame( 2, $data['failed_count'] );
		$this->assertSame( 3, $data['failed_overflow_count'] );
	}

	/**
	 * @testdox Status endpoint reports zero failed imports for a malformed option value.
	 */
	public function test_status_handles_malformed_failed_imports_option(): void {
		wp_set_current_user( $this->admin_user );
		update_option( OrdersScheduler::FAILED_ORDER_IMPORTS_OPTION, 'yes', false );

		$request  = new WP_REST_Request( 'GET', self::ENDPOINT . '/status' );
		$response = $this->server->dispatch( $request );
		$data     = $response->get_data();

		$this->assertSame( 200, $response->get_status() );
		$this->assertSame( 0, $data['failed_count'] );
		$this->assertSame( 0, $data['failed_overflow_count'] );
	}
}
