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
	 * @testdox Registered page route patterns match supported current requests.
	 *
	 * @dataProvider data_provider_test_registered_page_route_pattern_matches_current_request
	 *
	 * @param string $registered_path Registered route pattern.
	 * @param string $request_uri      Current request URI.
	 */
	public function test_registered_page_route_pattern_matches_current_request( string $registered_path, string $request_uri ): void {
		$this->assert_registered_page_for_request(
			'route-pattern-page',
			$request_uri,
			array(
				array(
					'id'   => 'route-pattern-page',
					'path' => $registered_path,
				),
			)
		);
	}

	/**
	 * Data provider for supported route pattern matches.
	 *
	 * @return array[]
	 */
	public static function data_provider_test_registered_page_route_pattern_matches_current_request(): array {
		return array(
			'path parameter'                => array( '/route-params/:itemName', '/wp-admin/admin.php?page=wc-admin&path=%2Froute-params%2Fsample' ),
			'case-insensitive static path'  => array( '/route-params/:itemName', '/wp-admin/admin.php?page=wc-admin&path=%2FROUTE-PARAMS%2Fsample' ),
			'trailing slash in pattern'     => array( '/route-params/:itemName/', '/wp-admin/admin.php?page=wc-admin&path=%2Froute-params%2Fsample' ),
			'repeated request slashes'      => array( '/route-params/:itemName', '/wp-admin/admin.php?page=wc-admin&path=%2Froute-params%2Fsample%2F%2F' ),
			'wildcard base path'            => array( '/route-wildcard/*', '/wp-admin/admin.php?page=wc-admin&path=%2Froute-wildcard' ),
			'wildcard base trailing slash'  => array( '/route-wildcard/*', '/wp-admin/admin.php?page=wc-admin&path=%2Froute-wildcard%2F' ),
			'wildcard descendant path'      => array( '/route-wildcard/*', '/wp-admin/admin.php?page=wc-admin&path=%2Froute-wildcard%2Ftheme%2Falpha' ),
			'parameter and wildcard base'   => array( '/route-patterns/:itemId/*', '/wp-admin/admin.php?page=wc-admin&path=%2Froute-patterns%2F123' ),
			'parameter and wildcard child'  => array( '/route-patterns/:itemId/*', '/wp-admin/admin.php?page=wc-admin&path=%2Froute-patterns%2F123%2Fdetails' ),
			'parameter and wildcard nested' => array( '/route-patterns/:itemId/*', '/wp-admin/admin.php?page=wc-admin&path=%2Froute-patterns%2F123%2Fdetails%2Fedit' ),
		);
	}

	/**
	 * @testdox Registered page route patterns reject unsupported or unrelated current requests.
	 *
	 * @dataProvider data_provider_test_registered_page_route_pattern_does_not_match_current_request
	 *
	 * @param string $registered_path Registered route pattern.
	 * @param string $request_uri      Current request URI.
	 */
	public function test_registered_page_route_pattern_does_not_match_current_request( string $registered_path, string $request_uri ): void {
		$result = $this->get_registered_page_result_for_request(
			$request_uri,
			array(
				array(
					'id'   => 'route-pattern-page',
					'path' => $registered_path,
				),
			)
		);

		$this->assertFalse( $result['current_page'] );
		$this->assertFalse( $result['is_registered_page'] );
	}

	/**
	 * Data provider for unsupported or unrelated route pattern requests.
	 *
	 * @return array[]
	 */
	public static function data_provider_test_registered_page_route_pattern_does_not_match_current_request(): array {
		return array(
			'parameter with extra segment'               => array( '/route-params/:itemName', '/wp-admin/admin.php?page=wc-admin&path=%2Froute-params%2Fsample%2Fdetails' ),
			'partial parameter segment'                  => array( '/route-patterns/:itemId/prefix-:suffix', '/wp-admin/admin.php?page=wc-admin&path=%2Froute-patterns%2F123%2Fprefix-value' ),
			'non-terminal wildcard'                      => array( '/route-patterns/:itemId/*/details', '/wp-admin/admin.php?page=wc-admin&path=%2Froute-patterns%2F123%2Fone%2Fdetails' ),
			'parameter and wildcard missing parameter'   => array( '/route-patterns/:itemId/*', '/wp-admin/admin.php?page=wc-admin&path=%2Froute-patterns' ),
			'static wildcard followed by trailing slash' => array( '/route-patterns/*/', '/wp-admin/admin.php?page=wc-admin&path=%2Froute-patterns%2F123%2Fdetails' ),
			'wildcard followed by trailing slash'        => array( '/route-patterns/:itemId/*/', '/wp-admin/admin.php?page=wc-admin&path=%2Froute-patterns%2F123%2Fdetails' ),
			'parameter and wildcard different root'      => array( '/route-patterns/:itemId/*', '/wp-admin/admin.php?page=other-page-root&path=%2Froute-patterns%2F123%2Fdetails' ),
			'different page root'                        => array( '/route-params/:itemName', '/wp-admin/admin.php?page=other-page-root&path=%2Froute-params%2Fsample' ),
		);
	}

	/**
	 * @testdox Registered pages with invalid filtered paths do not match or throw.
	 *
	 * @dataProvider data_provider_test_registered_page_with_invalid_filtered_path_does_not_match
	 *
	 * @param mixed $invalid_path Invalid filtered path.
	 */
	public function test_registered_page_with_invalid_filtered_path_does_not_match( $invalid_path ): void {
		$filter = function ( $options ) use ( $invalid_path ) {
			if ( 'invalid-filtered-path-page' === ( $options['id'] ?? null ) ) {
				$options['path'] = $invalid_path;
			}

			return $options;
		};

		add_filter( 'woocommerce_navigation_connect_page_options', $filter );

		try {
			$result = $this->get_publicly_registered_page_result_for_request(
				'/wp-admin/admin.php?page=wc-admin&path=%2Funrelated',
				function () {
					wc_admin_register_page(
						array(
							'id'     => 'invalid-filtered-path-page',
							'parent' => 'woocommerce',
							'title'  => 'Invalid filtered path page',
							'path'   => '/invalid-filtered-path',
						)
					);
				}
			);
		} finally {
			remove_filter( 'woocommerce_navigation_connect_page_options', $filter );
		}

		$this->assertFalse( $result['current_page'] );
		$this->assertFalse( $result['is_registered_page'] );
	}

	/**
	 * Data provider for invalid filtered registered-page paths.
	 *
	 * @return array[]
	 */
	public static function data_provider_test_registered_page_with_invalid_filtered_path_does_not_match(): array {
		return array(
			'array path' => array( array( 'invalid' ) ),
			'null path'  => array( null ),
		);
	}

	/**
	 * @testdox Exact registered pages win over earlier route patterns.
	 *
	 * @dataProvider data_provider_test_registered_page_exact_path_takes_precedence_over_route_pattern
	 *
	 * @param string $request_uri Current request URI.
	 */
	public function test_registered_page_exact_path_takes_precedence_over_route_pattern( string $request_uri ): void {
		$this->assert_registered_page_for_request(
			'route-exact-page',
			$request_uri,
			array(
				array(
					'id'   => 'route-param-page',
					'path' => '/route-patterns/:itemId',
				),
				array(
					'id'   => 'route-exact-page',
					'path' => '/route-patterns/settings',
				),
			)
		);
	}

	/**
	 * Data provider for static route precedence requests.
	 *
	 * @return array[]
	 */
	public static function data_provider_test_registered_page_exact_path_takes_precedence_over_route_pattern(): array {
		return array(
			'exact request'            => array( '/wp-admin/admin.php?page=wc-admin&path=%2Froute-patterns%2Fsettings' ),
			'case-normalized request'  => array( '/wp-admin/admin.php?page=wc-admin&path=%2FROUTE-PATTERNS%2Fsettings' ),
			'slash-normalized request' => array( '/wp-admin/admin.php?page=wc-admin&path=%2Froute-patterns%2Fsettings%2F%2F' ),
		);
	}

	/**
	 * @testdox More specific registered route patterns win over wildcard patterns.
	 */
	public function test_registered_page_specific_route_pattern_takes_precedence_over_wildcard(): void {
		$this->assert_registered_page_for_request(
			'route-details-page',
			'/wp-admin/admin.php?page=wc-admin&path=%2Froute-patterns%2F123%2Fdetails',
			array(
				array(
					'id'   => 'route-wildcard-page',
					'path' => '/route-patterns/*',
				),
				array(
					'id'   => 'route-details-page',
					'path' => '/route-patterns/:itemId/details',
				),
			)
		);
	}

	/**
	 * @testdox Trailing-slash routes use React Router specificity.
	 */
	public function test_registered_page_trailing_slash_route_uses_react_router_specificity(): void {
		$this->assert_registered_page_for_request(
			'route-trailing-slash-page',
			'/wp-admin/admin.php?page=wc-admin&path=%2Froute-order%2F%2F',
			array(
				array(
					'id'   => 'route-without-trailing-slash-page',
					'path' => '/route-order',
				),
				array(
					'id'   => 'route-trailing-slash-page',
					'path' => '/route-order/',
				),
			)
		);
	}

	/**
	 * @testdox Equal specificity registered route patterns use registration order.
	 */
	public function test_registered_page_equal_specificity_route_patterns_use_registration_order(): void {
		$this->assert_registered_page_for_request(
			'route-earlier-param-page',
			'/wp-admin/admin.php?page=wc-admin&path=%2Froute-patterns%2F123',
			array(
				array(
					'id'   => 'route-earlier-param-page',
					'path' => '/route-patterns/:earlierId',
				),
				array(
					'id'   => 'route-later-param-page',
					'path' => '/route-patterns/:laterId',
				),
			)
		);
	}

	/**
	 * @testdox Fallback-selected route patterns do not create linked current-page breadcrumbs.
	 */
	public function test_route_pattern_fallback_uses_text_for_current_page_breadcrumbs(): void {
		$result = $this->get_publicly_registered_page_result_for_request(
			'/wp-admin/admin.php?page=wc-admin&path=%2Fbreadcrumb%2F123',
			function () {
				wc_admin_register_page(
					array(
						'id'     => 'route-pattern-breadcrumb-page',
						'parent' => 'woocommerce',
						'title'  => array( 'Parent crumb', 'Current crumb' ),
						'path'   => '/breadcrumb/:itemId',
					)
				);
			},
			true
		);

		$this->assertSame(
			array(
				array( 'admin.php?page=' . PageController::PAGE_ROOT, 'WooCommerce' ),
				'Parent crumb',
				'Current crumb',
			),
			$result['breadcrumbs']
		);
	}

	/**
	 * @testdox Exact route-template requests retain trunk's linked current-page breadcrumb.
	 */
	public function test_exact_route_pattern_request_retains_linked_current_page_breadcrumb(): void {
		$result = $this->get_publicly_registered_page_result_for_request(
			'/wp-admin/admin.php?page=wc-admin&path=%2Fbreadcrumb%2F%3AitemId',
			function () {
				wc_admin_register_page(
					array(
						'id'     => 'exact-route-pattern-breadcrumb-page',
						'parent' => 'woocommerce',
						'title'  => array( 'Parent crumb', 'Current crumb' ),
						'path'   => '/breadcrumb/:itemId',
					)
				);
			},
			true
		);

		$this->assertSame(
			array(
				array( 'admin.php?page=' . PageController::PAGE_ROOT, 'WooCommerce' ),
				array( 'wc-admin&path=/breadcrumb/:itemId', 'Parent crumb' ),
				'Current crumb',
			),
			$result['breadcrumbs']
		);
	}

	/**
	 * @testdox Fallback-selected pages do not create linked route-pattern parent breadcrumbs.
	 */
	public function test_route_pattern_fallback_uses_text_for_patterned_parent_breadcrumb(): void {
		$result = $this->get_publicly_registered_page_result_for_request(
			'/wp-admin/admin.php?page=wc-admin&path=%2Fbreadcrumb%2Fsettings%2F123',
			function () {
				$this->register_patterned_parent_and_child( '/breadcrumb/:section/:itemId' );
			},
			true
		);

		$this->assertSame(
			array(
				array( 'admin.php?page=' . PageController::PAGE_ROOT, 'WooCommerce' ),
				'Pattern parent',
				'Child page',
			),
			$result['breadcrumbs']
		);
	}

	/**
	 * @testdox Exact child requests retain trunk's linked route-pattern parent breadcrumb.
	 */
	public function test_exact_child_request_retains_linked_patterned_parent_breadcrumb(): void {
		$result = $this->get_publicly_registered_page_result_for_request(
			'/wp-admin/admin.php?page=wc-admin&path=%2Fbreadcrumb%2Fstatic',
			function () {
				$this->register_patterned_parent_and_child( '/breadcrumb/static' );
			},
			true
		);

		$this->assertSame(
			array(
				array( 'admin.php?page=' . PageController::PAGE_ROOT, 'WooCommerce' ),
				array( 'admin.php?page=wc-admin&path=/breadcrumb/:section', 'Pattern parent' ),
				'Child page',
			),
			$result['breadcrumbs']
		);
	}

	/**
	 * @testdox Fallback-selected pages use text for parents with invalid filtered paths.
	 *
	 * @dataProvider data_provider_test_route_pattern_fallback_with_invalid_parent_path
	 *
	 * @param mixed $invalid_path Invalid filtered parent path.
	 */
	public function test_route_pattern_fallback_uses_text_for_invalid_parent_path( $invalid_path ): void {
		$result = $this->get_publicly_registered_page_result_for_request(
			'/wp-admin/admin.php?page=wc-admin&path=%2Fbreadcrumb%2F123',
			function () use ( $invalid_path ) {
				wc_admin_register_page(
					array(
						'id'     => 'invalid-path-parent',
						'parent' => 'woocommerce',
						'title'  => 'Invalid path parent',
						'path'   => '/breadcrumb-parent',
					)
				);
				wc_admin_register_page(
					array(
						'id'     => 'invalid-path-child',
						'parent' => 'invalid-path-parent',
						'title'  => 'Child page',
						'path'   => '/breadcrumb/:itemId',
					)
				);

				$filter = function ( $options ) use ( $invalid_path ) {
					if ( 'invalid-path-parent' === ( $options['id'] ?? null ) ) {
						$options['path'] = $invalid_path;
					}

					return $options;
				};

				add_filter( 'woocommerce_navigation_connect_page_options', $filter );

				try {
					wc_admin_connect_page(
						array(
							'id'      => 'invalid-path-parent',
							'parent'  => 'woocommerce',
							'title'   => 'Invalid path parent',
							'path'    => '/breadcrumb-parent',
							'js_page' => true,
						)
					);
				} finally {
					remove_filter( 'woocommerce_navigation_connect_page_options', $filter );
				}
			},
			true
		);

		$this->assertSame(
			array(
				array( 'admin.php?page=' . PageController::PAGE_ROOT, 'WooCommerce' ),
				'Invalid path parent',
				'Child page',
			),
			$result['breadcrumbs']
		);
	}

	/**
	 * Data provider for invalid filtered parent paths.
	 *
	 * @return array[]
	 */
	public static function data_provider_test_route_pattern_fallback_with_invalid_parent_path(): array {
		return array(
			'array path' => array( array( 'invalid' ) ),
			'null path'  => array( null ),
		);
	}

	/**
	 * Registers a patterned parent and a child page.
	 *
	 * @param string $child_path Child page path.
	 */
	private function register_patterned_parent_and_child( string $child_path ): void {
		wc_admin_register_page(
			array(
				'id'     => 'patterned-breadcrumb-parent',
				'parent' => 'woocommerce',
				'title'  => 'Pattern parent',
				'path'   => '/breadcrumb/:section',
			)
		);
		wc_admin_register_page(
			array(
				'id'     => 'patterned-breadcrumb-child',
				'parent' => 'patterned-breadcrumb-parent',
				'title'  => 'Child page',
				'path'   => $child_path,
			)
		);
	}

	/**
	 * Gets the PageController result for a simulated admin request.
	 *
	 * @param string $request_uri Request URI.
	 * @param array  $pages       Pages to register.
	 * @return array{current_page: array|bool, is_registered_page: bool}
	 */
	private function get_registered_page_result_for_request( string $request_uri, array $pages ): array {
		$registered_pages = array();

		foreach ( $pages as $page ) {
			$registered_pages[ $page['id'] ] = array(
				'id'      => $page['id'],
				'path'    => PageController::PAGE_ROOT . '&path=' . $page['path'],
				'js_page' => true,
			);
		}

		return $this->run_with_page_controller_request_state(
			$request_uri,
			$registered_pages,
			function () {
				$this->sut->determine_current_page();

				return array(
					'current_page'       => $this->sut->get_current_page(),
					'is_registered_page' => wc_admin_is_registered_page(),
				);
			}
		);
	}

	/**
	 * Gets the PageController result after registering pages through the public API.
	 *
	 * @param string   $request_uri   Request URI.
	 * @param callable $register_pages Page registration callback.
	 * @param bool     $get_breadcrumbs Whether to include generated breadcrumbs.
	 * @return array{current_page: array|bool, is_registered_page: bool, breadcrumbs: array|null}
	 */
	private function get_publicly_registered_page_result_for_request( string $request_uri, callable $register_pages, bool $get_breadcrumbs = false ): array {
		$menu_global_names     = array( 'menu', 'submenu', '_wp_submenu_nopriv', '_registered_pages', '_parent_pages', '_wp_real_parent_file' );
		$original_menu_globals = array();

		foreach ( $menu_global_names as $global_name ) {
			$original_menu_globals[ $global_name ] = array(
				'exists' => array_key_exists( $global_name, $GLOBALS ),
				'value'  => $GLOBALS[ $global_name ] ?? null,
			);
		}

		try {
			return $this->run_with_page_controller_request_state(
				$request_uri,
				array(),
				function () use ( $register_pages, $get_breadcrumbs ) {
					$register_pages();
					$this->sut->determine_current_page();

					return array(
						'current_page'       => $this->sut->get_current_page(),
						'is_registered_page' => wc_admin_is_registered_page(),
						'breadcrumbs'        => $get_breadcrumbs ? wc_admin_get_breadcrumbs() : null,
					);
				}
			);
		} finally {
			foreach ( $original_menu_globals as $global_name => $global_state ) {
				if ( $global_state['exists'] ) {
					$GLOBALS[ $global_name ] = $global_state['value'];
				} else {
					unset( $GLOBALS[ $global_name ] );
				}
			}
		}
	}

	/**
	 * Runs a callback with isolated PageController state for a request.
	 *
	 * @template T of array
	 * @param string   $request_uri Request URI.
	 * @param array    $pages       Initial registered pages.
	 * @param callable $callback    Callback to run with the isolated state.
	 * @phpstan-param callable(): T $callback
	 * @return array Callback result.
	 * @phpstan-return T
	 */
	private function run_with_page_controller_request_state( string $request_uri, array $pages, callable $callback ): array {
		$reflection            = new \ReflectionClass( $this->sut );
		$pages_property        = $reflection->getProperty( 'pages' );
		$current_page_property = $reflection->getProperty( 'current_page' );
		$route_match_property  = $reflection->getProperty( 'current_page_is_route_pattern_match' );
		$pages_property->setAccessible( true );
		$current_page_property->setAccessible( true );
		$route_match_property->setAccessible( true );

		$original_pages        = $pages_property->getValue( $this->sut );
		$original_current_page = $current_page_property->getValue( $this->sut );
		$original_route_match  = $route_match_property->getValue( $this->sut );
		$original_request_uri  = $_SERVER['REQUEST_URI'] ?? null; // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.MissingUnslash, WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- Test cleanup restores the raw original request URI.

		try {
			$pages_property->setValue( $this->sut, $pages );
			$current_page_property->setValue( $this->sut, null );
			$route_match_property->setValue( $this->sut, false );
			$_SERVER['REQUEST_URI'] = $request_uri;

			return $callback();
		} finally {
			$pages_property->setValue( $this->sut, $original_pages );
			$current_page_property->setValue( $this->sut, $original_current_page );
			$route_match_property->setValue( $this->sut, $original_route_match );

			if ( null === $original_request_uri ) {
				unset( $_SERVER['REQUEST_URI'] );
			} else {
				$_SERVER['REQUEST_URI'] = $original_request_uri;
			}
		}
	}

	/**
	 * Asserts that a simulated request matches the expected registered page.
	 *
	 * @param string $expected_page_id Expected page ID.
	 * @param string $request_uri      Request URI.
	 * @param array  $pages            Pages to register.
	 */
	private function assert_registered_page_for_request( string $expected_page_id, string $request_uri, array $pages ): void {
		$result       = $this->get_registered_page_result_for_request( $request_uri, $pages );
		$current_page = $result['current_page'];

		$this->assertTrue( $result['is_registered_page'], 'A matching page should be reported through wc_admin_is_registered_page().' );
		$this->assertIsArray( $current_page, 'A matching registered page should be detected.' );
		if ( ! is_array( $current_page ) ) {
			return;
		}
		$this->assertSame( $expected_page_id, $current_page['id'] );
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
