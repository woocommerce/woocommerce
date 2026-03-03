<?php

declare(strict_types=1);

namespace Automattic\WooCommerce\Tests\Admin;

use Automattic\WooCommerce\Admin\PageController;
use WC_Unit_Test_Case;

/**
 * Unit tests for PageController redirect functionality.
 *
 * @covers \Automattic\WooCommerce\Admin\PageController
 */
class PageControllerTest extends WC_Unit_Test_Case {
	/**
	 * PageController instance.
	 *
	 * @var PageController
	 */
	private $sut;

	/**
	 * Admin user ID.
	 *
	 * @var int
	 */
	private $admin_user_id;

	/**
	 * Shop manager user ID.
	 *
	 * @var int
	 */
	private $shop_manager_user_id;

	/**
	 * Customer user ID.
	 *
	 * @var int
	 */
	private $customer_user_id;

	/**
	 * Backup object of $GLOBALS['current_screen'].
	 *
	 * @var object
	 */
	private $current_screen_backup;

	/**
	 * Holds the URL of the last attempted redirect.
	 *
	 * @var string
	 */
	private $redirected_to = '';

	/**
	 * Set things up before each test case.
	 *
	 * @return void
	 */
	public function setUp(): void {
		// Mock screen.
		$this->current_screen_backup = $GLOBALS['current_screen'] ?? null;
		$GLOBALS['current_screen']   = $this->get_screen_mock(); // phpcs:ignore WordPress.WP.GlobalVariablesOverride.Prohibited
		if ( ! did_action( 'current_screen' ) ) {
			do_action( 'current_screen', $GLOBALS['current_screen'] ); // phpcs:ignore WooCommerce.Commenting.CommentHooks.MissingHookComment
		}

		parent::setUp();

		// Create test users with different capabilities.
		$this->admin_user_id        = $this->factory->user->create( array( 'role' => 'administrator' ) );
		$this->shop_manager_user_id = $this->factory->user->create( array( 'role' => 'shop_manager' ) );
		$this->customer_user_id     = $this->factory->user->create( array( 'role' => 'customer' ) );

		$this->sut = PageController::get_instance();

		// Start watching for redirects.
		$this->redirected_to = '';
		add_filter( 'wp_redirect', array( $this, 'watch_and_anull_redirects' ) );
	}

	/**
	 * Tear down after each test case.
	 *
	 * @return void
	 */
	public function tearDown(): void {
		// Remove redirect listener.
		remove_filter( 'wp_redirect', array( $this, 'watch_and_anull_redirects' ) );

		// Clean up users.
		wp_delete_user( $this->admin_user_id );
		wp_delete_user( $this->shop_manager_user_id );
		wp_delete_user( $this->customer_user_id );

		// Reset global state.
		unset( $_GET['page'], $_GET['task'], $_GET['connection-return'] );

		// Restore screen backup.
		if ( $this->current_screen_backup ) {
			$GLOBALS['current_screen'] = $this->current_screen_backup; // phpcs:ignore WordPress.WP.GlobalVariablesOverride.Prohibited
		}

		parent::tearDown();
	}

	/**
	 * Captures the attempted redirect location, and stops the redirect from taking place.
	 *
	 * @param string $url Redirect location.
	 *
	 * @throws \WPAjaxDieContinueException To prevent exit() from being called after redirect.
	 * @return void
	 */
	public function watch_and_anull_redirects( string $url ) {
		$this->redirected_to = $url;
		// Throw exception to prevent exit() from being called after wp_safe_redirect().
		throw new \WPAjaxDieContinueException();
	}

	/**
	 * Supplies the URL of the last attempted redirect, then resets ready for the next test.
	 *
	 * @return string
	 */
	private function get_redirect_attempt(): string {
		$return              = $this->redirected_to;
		$this->redirected_to = '';
		return $return;
	}

	/**
	 * Trigger the redirect method and catch the exception to prevent exit().
	 * Temporarily defines WP_ADMIN for this specific call only.
	 *
	 * @return void
	 */
	private function trigger_redirect_check(): void {
		try {
			$this->sut->maybe_redirect_payment_tasks_to_settings();
		} catch ( \WPAjaxDieContinueException $e ) {
			// Expected - this prevents exit() from killing the test.
			unset( $e );
		}
	}

	/**
	 * Test redirect happens for basic task=payments request.
	 */
	public function test_redirect_for_payments_task(): void {
		// Set up admin user.
		wp_set_current_user( $this->admin_user_id );

		// Set up request.
		$_GET['page'] = 'wc-admin';
		$_GET['task'] = 'payments';

		// Trigger redirect.
		$this->trigger_redirect_check();

		// Verify redirect occurred.
		$redirect_url = $this->get_redirect_attempt();
		$this->assertNotEmpty( $redirect_url, 'A redirect should occur for the payments task.' );
		$this->assertEquals(
			admin_url( 'admin.php?page=wc-settings&tab=checkout&from=WCADMIN_PAYMENT_TASK' ),
			$redirect_url,
			'Redirect URL should match expected settings page URL.'
		);
	}

	/**
	 * Test redirect happens for task=woocommerce-payments request.
	 */
	public function test_redirect_for_woocommerce_payments_task(): void {
		// Set up admin user.
		wp_set_current_user( $this->admin_user_id );

		// Set up request.
		$_GET['page'] = 'wc-admin';
		$_GET['task'] = 'woocommerce-payments';

		// Trigger redirect.
		$this->trigger_redirect_check();

		// Verify redirect occurred.
		$redirect_url = $this->get_redirect_attempt();
		$this->assertNotEmpty( $redirect_url, 'A redirect should occur for the woocommerce-payments task.' );
		$this->assertEquals(
			admin_url( 'admin.php?page=wc-settings&tab=checkout&from=WCADMIN_PAYMENT_TASK' ),
			$redirect_url,
			'Redirect URL should match expected settings page URL.'
		);
	}

	/**
	 * Test no redirect when connection-return parameter is present.
	 */
	public function test_no_redirect_with_connection_return_param(): void {
		// Set up admin user.
		wp_set_current_user( $this->admin_user_id );

		// Set up request with connection-return parameter.
		$_GET['page']              = 'wc-admin';
		$_GET['task']              = 'payments';
		$_GET['connection-return'] = '1';

		// Trigger redirect check.
		$this->trigger_redirect_check();

		// Verify no redirect occurred.
		$this->assertEmpty(
			$this->get_redirect_attempt(),
			'No redirect should occur when connection-return parameter is present.'
		);
	}

	/**
	 * Test no redirect when id parameter is present.
	 */
	public function test_no_redirect_with_id_param(): void {
		// Set up admin user.
		wp_set_current_user( $this->admin_user_id );

		// Set up request with id parameter.
		$_GET['page'] = 'wc-admin';
		$_GET['task'] = 'payments';
		$_GET['id']   = 'some-gateway';

		// Trigger redirect check.
		$this->trigger_redirect_check();

		// Verify no redirect occurred.
		$this->assertEmpty(
			$this->get_redirect_attempt(),
			'No redirect should occur when id parameter is present.'
		);
	}

	/**
	 * Test no redirect when gateway_id parameter is present.
	 */
	public function test_no_redirect_with_gateway_id_param(): void {
		// Set up admin user.
		wp_set_current_user( $this->admin_user_id );

		// Set up request with gateway_id parameter.
		$_GET['page']       = 'wc-admin';
		$_GET['task']       = 'payments';
		$_GET['gateway_id'] = 'stripe';

		// Trigger redirect check.
		$this->trigger_redirect_check();

		// Verify no redirect occurred.
		$this->assertEmpty(
			$this->get_redirect_attempt(),
			'No redirect should occur when gateway_id parameter is present.'
		);
	}

	/**
	 * Test no redirect when gateway-id parameter is present.
	 */
	public function test_no_redirect_with_gateway_hyphen_id_param(): void {
		// Set up admin user.
		wp_set_current_user( $this->admin_user_id );

		// Set up request with gateway-id parameter.
		$_GET['page']       = 'wc-admin';
		$_GET['task']       = 'payments';
		$_GET['gateway-id'] = 'stripe';

		// Trigger redirect check.
		$this->trigger_redirect_check();

		// Verify no redirect occurred.
		$this->assertEmpty(
			$this->get_redirect_attempt(),
			'No redirect should occur when gateway-id parameter is present.'
		);
	}

	/**
	 * Test no redirect when method parameter is present.
	 */
	public function test_no_redirect_with_method_param(): void {
		// Set up admin user.
		wp_set_current_user( $this->admin_user_id );

		// Set up request with method parameter.
		$_GET['page']   = 'wc-admin';
		$_GET['task']   = 'payments';
		$_GET['method'] = 'card';

		// Trigger redirect check.
		$this->trigger_redirect_check();

		// Verify no redirect occurred.
		$this->assertEmpty(
			$this->get_redirect_attempt(),
			'No redirect should occur when method parameter is present.'
		);
	}

	/**
	 * Test no redirect when success parameter is present.
	 */
	public function test_no_redirect_with_success_param(): void {
		// Set up admin user.
		wp_set_current_user( $this->admin_user_id );

		// Set up request with success parameter.
		$_GET['page']    = 'wc-admin';
		$_GET['task']    = 'payments';
		$_GET['success'] = '1';

		// Trigger redirect check.
		$this->trigger_redirect_check();

		// Verify no redirect occurred.
		$this->assertEmpty(
			$this->get_redirect_attempt(),
			'No redirect should occur when success parameter is present.'
		);
	}

	/**
	 * Test no redirect when error parameter is present.
	 */
	public function test_no_redirect_with_error_param(): void {
		// Set up admin user.
		wp_set_current_user( $this->admin_user_id );

		// Set up request with error parameter.
		$_GET['page']  = 'wc-admin';
		$_GET['task']  = 'payments';
		$_GET['error'] = 'some-error';

		// Trigger redirect check.
		$this->trigger_redirect_check();

		// Verify no redirect occurred.
		$this->assertEmpty(
			$this->get_redirect_attempt(),
			'No redirect should occur when error parameter is present.'
		);
	}

	/**
	 * Test no redirect when _wpnonce parameter is present.
	 */
	public function test_no_redirect_with_wpnonce_param(): void {
		// Set up admin user.
		wp_set_current_user( $this->admin_user_id );

		// Set up request with _wpnonce parameter.
		$_GET['page']     = 'wc-admin';
		$_GET['task']     = 'payments';
		$_GET['_wpnonce'] = wp_create_nonce( 'test-action' );

		// Trigger redirect check.
		$this->trigger_redirect_check();

		// Verify no redirect occurred.
		$this->assertEmpty(
			$this->get_redirect_attempt(),
			'No redirect should occur when _wpnonce parameter is present.'
		);
	}

	/**
	 * Test no redirect for users without manage_woocommerce capability.
	 */
	public function test_no_redirect_without_manage_woocommerce_capability(): void {
		// Set up customer user (no manage_woocommerce capability).
		wp_set_current_user( $this->customer_user_id );

		// Set up request.
		$_GET['page'] = 'wc-admin';
		$_GET['task'] = 'payments';

		// Trigger redirect check.
		$this->trigger_redirect_check();

		// Verify no redirect occurred.
		$this->assertEmpty(
			$this->get_redirect_attempt(),
			'No redirect should occur for users without manage_woocommerce capability.'
		);
	}

	/**
	 * Test redirect works for shop_manager role.
	 */
	public function test_redirect_works_for_shop_manager(): void {
		// Set up shop manager user.
		wp_set_current_user( $this->shop_manager_user_id );

		// Set up request.
		$_GET['page'] = 'wc-admin';
		$_GET['task'] = 'payments';

		// Trigger redirect.
		$this->trigger_redirect_check();

		// Verify redirect occurred.
		$redirect_url = $this->get_redirect_attempt();
		$this->assertNotEmpty( $redirect_url, 'A redirect should occur for shop_manager users.' );
		$this->assertEquals(
			admin_url( 'admin.php?page=wc-settings&tab=checkout&from=WCADMIN_PAYMENT_TASK' ),
			$redirect_url,
			'Redirect URL should match expected settings page URL for shop_manager.'
		);
	}

	/**
	 * Test no redirect when not on wc-admin page.
	 */
	public function test_no_redirect_when_not_on_wc_admin_page(): void {
		// Set up admin user.
		wp_set_current_user( $this->admin_user_id );

		// Set up request without wc-admin page.
		$_GET['page'] = 'wc-settings';
		$_GET['task'] = 'payments';

		// Trigger redirect check.
		$this->trigger_redirect_check();

		// Verify no redirect occurred.
		$this->assertEmpty(
			$this->get_redirect_attempt(),
			'No redirect should occur when not on wc-admin page.'
		);
	}

	/**
	 * Test no redirect when task parameter is missing.
	 */
	public function test_no_redirect_when_task_param_missing(): void {
		// Set up admin user.
		wp_set_current_user( $this->admin_user_id );

		// Set up request without task parameter.
		$_GET['page'] = 'wc-admin';

		// Trigger redirect check.
		$this->trigger_redirect_check();

		// Verify no redirect occurred.
		$this->assertEmpty(
			$this->get_redirect_attempt(),
			'No redirect should occur when task parameter is missing.'
		);
	}

	/**
	 * Test no redirect for non-payment tasks.
	 */
	public function test_no_redirect_for_non_payment_tasks(): void {
		// Set up admin user.
		wp_set_current_user( $this->admin_user_id );

		// Set up request with different task.
		$_GET['page'] = 'wc-admin';
		$_GET['task'] = 'products';

		// Trigger redirect check.
		$this->trigger_redirect_check();

		// Verify no redirect occurred.
		$this->assertEmpty(
			$this->get_redirect_attempt(),
			'No redirect should occur for non-payment tasks.'
		);
	}

	/**
	 * Test woocommerce-payments task redirects even with special parameters.
	 *
	 * The woocommerce-payments task should always redirect, unlike the generic payments task.
	 */
	public function test_woocommerce_payments_redirects_with_special_params(): void {
		// Set up admin user.
		wp_set_current_user( $this->admin_user_id );

		// Set up request with special parameters.
		$_GET['page']              = 'wc-admin';
		$_GET['task']              = 'woocommerce-payments';
		$_GET['connection-return'] = '1';

		// Trigger redirect.
		$this->trigger_redirect_check();

		// Verify redirect occurred even with special params.
		$redirect_url = $this->get_redirect_attempt();
		$this->assertNotEmpty( $redirect_url, 'woocommerce-payments task should redirect even with special parameters.' );
		$this->assertEquals(
			admin_url( 'admin.php?page=wc-settings&tab=checkout&from=WCADMIN_PAYMENT_TASK' ),
			$redirect_url,
			'Redirect URL should match expected settings page URL for woocommerce-payments task.'
		);
	}

	/**
	 * Test redirect URL contains expected parameters.
	 */
	public function test_redirect_url_contains_expected_parameters(): void {
		// Set up admin user.
		wp_set_current_user( $this->admin_user_id );

		// Set up request.
		$_GET['page'] = 'wc-admin';
		$_GET['task'] = 'payments';

		// Trigger redirect.
		$this->trigger_redirect_check();

		// Get redirect URL.
		$redirect_url = $this->get_redirect_attempt();

		// Parse URL to verify parameters.
		$parsed_url = wp_parse_url( $redirect_url );
		parse_str( $parsed_url['query'], $params );

		// Verify parameters.
		$this->assertEquals( 'wc-settings', $params['page'], 'Redirect should go to wc-settings page.' );
		$this->assertEquals( 'checkout', $params['tab'], 'Redirect should go to checkout tab.' );
		$this->assertEquals( 'WCADMIN_PAYMENT_TASK', $params['from'], 'Redirect should include from parameter.' );
	}

	/**
	 * Returns an object mocking what we need from \WP_Screen.
	 *
	 * @return object
	 */
	private function get_screen_mock() {
		$screen_mock = $this->getMockBuilder( \stdClass::class )->setMethods( array( 'in_admin', 'add_option' ) )->getMock();
		$screen_mock->method( 'in_admin' )->willReturn( true );
		foreach ( array( 'id', 'base', 'action', 'post_type' ) as $key ) {
			$screen_mock->{$key} = '';
		}

		return $screen_mock;
	}
}
