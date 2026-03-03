<?php
declare( strict_types = 1 );

/**
 * Tests for the WC_Admin_Dashboard class.
 *
 * @package WooCommerce\Tests\Admin
 */

/**
 * WC_Admin_Dashboard_Test
 */
class WC_Admin_Dashboard_Test extends WC_Unit_Test_Case {

	/**
	 * Admin user ID.
	 *
	 * @var int
	 */
	private $admin_user;

	/**
	 * Set up test fixtures.
	 */
	public function setUp(): void {
		parent::setUp();

		$password         = wp_generate_password( 8, false, false );
		$this->admin_user = wp_insert_user(
			array(
				'user_login' => "test_admin$password",
				'user_pass'  => $password,
				'user_email' => "admin$password@example.com",
				'role'       => 'administrator',
			)
		);
		wp_set_current_user( $this->admin_user );
	}

	/**
	 * Tear down test fixtures.
	 */
	public function tearDown(): void {
		delete_option( 'woocommerce_task_list_completed_lists' );
		delete_option( 'woocommerce_task_list_hidden' );
		delete_option( 'woocommerce_task_list_hidden_lists' );
		delete_option( 'woocommerce_task_list_complete' );
		remove_all_filters( 'pre_option_woocommerce_task_list_complete' );
		remove_all_filters( 'pre_option_woocommerce_task_list_hidden' );

		parent::tearDown();
	}

	/**
	 * Invoke the private should_display_widget method via reflection.
	 *
	 * @param WC_Admin_Dashboard $dashboard Dashboard instance.
	 * @return bool
	 */
	private function invoke_should_display_widget( WC_Admin_Dashboard $dashboard ): bool {
		$method = new ReflectionMethod( WC_Admin_Dashboard::class, 'should_display_widget' );
		$method->setAccessible( true );
		return $method->invoke( $dashboard );
	}

	/**
	 * @testdox Widget shows when task list is complete via the bridge.
	 */
	public function test_widget_shows_when_task_list_complete(): void {
		add_filter(
			'pre_option_woocommerce_task_list_complete',
			function () {
				return 'yes';
			}
		);

		$dashboard = new WC_Admin_Dashboard();

		$this->assertTrue(
			$this->invoke_should_display_widget( $dashboard ),
			'Widget should display when task list is complete'
		);
	}

	/**
	 * @testdox Widget shows when task list is hidden via the bridge.
	 */
	public function test_widget_shows_when_task_list_hidden(): void {
		add_filter(
			'pre_option_woocommerce_task_list_hidden',
			function () {
				return 'yes';
			}
		);

		$dashboard = new WC_Admin_Dashboard();

		$this->assertTrue(
			$this->invoke_should_display_widget( $dashboard ),
			'Widget should display when task list is hidden'
		);
	}

	/**
	 * @testdox Widget does not show when neither complete nor hidden.
	 */
	public function test_widget_does_not_show_when_neither_complete_nor_hidden(): void {
		delete_option( 'woocommerce_task_list_completed_lists' );
		delete_option( 'woocommerce_task_list_hidden_lists' );

		$dashboard = new WC_Admin_Dashboard();

		$this->assertFalse(
			$this->invoke_should_display_widget( $dashboard ),
			'Widget should not display when task list is neither complete nor hidden'
		);
	}

	/**
	 * @testdox Widget does not show without proper capabilities.
	 */
	public function test_widget_does_not_show_without_capabilities(): void {
		add_filter(
			'pre_option_woocommerce_task_list_complete',
			function () {
				return 'yes';
			}
		);

		$password = wp_generate_password( 8, false, false );
		$author   = wp_insert_user(
			array(
				'user_login' => "test_author$password",
				'user_pass'  => $password,
				'user_email' => "author$password@example.com",
				'role'       => 'subscriber',
			)
		);
		wp_set_current_user( $author );

		$dashboard = new WC_Admin_Dashboard();

		$this->assertFalse(
			$this->invoke_should_display_widget( $dashboard ),
			'Widget should not display for users without proper capabilities'
		);
	}
}
