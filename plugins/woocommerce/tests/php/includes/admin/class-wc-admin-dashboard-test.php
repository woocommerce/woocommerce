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
	 * The system under test.
	 *
	 * @var WC_Admin_Dashboard
	 */
	private WC_Admin_Dashboard $sut;

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
		$this->sut = new WC_Admin_Dashboard();
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
	 * @testdox Widget shows when task list is complete.
	 */
	public function test_widget_shows_when_task_list_complete(): void {
		// Uses pre_option filter because WC_INSTALLING is true in test env,
		// which causes the DeprecatedOptions bridge to bail out.
		add_filter( 'pre_option_woocommerce_task_list_complete', fn() => 'yes' );

		$this->assertTrue(
			$this->invoke_should_display_widget( $this->sut ),
			'Widget should display when task list is complete'
		);
	}

	/**
	 * @testdox Widget shows when task list is hidden.
	 */
	public function test_widget_shows_when_task_list_hidden(): void {
		add_filter( 'pre_option_woocommerce_task_list_hidden', fn() => 'yes' );

		$this->assertTrue(
			$this->invoke_should_display_widget( $this->sut ),
			'Widget should display when task list is hidden'
		);
	}

	/**
	 * @testdox Widget shows when task list is incomplete and store is pre-launch.
	 */
	public function test_widget_shows_when_task_list_is_incomplete_and_store_is_pre_launch(): void {
		delete_option( 'woocommerce_task_list_completed_lists' );
		delete_option( 'woocommerce_task_list_hidden_lists' );
		update_option( 'woocommerce_coming_soon', 'yes' );

		$this->assertTrue(
			$this->invoke_should_display_widget( $this->sut ),
			'Widget should display even when the task list is incomplete and the store is pre-launch'
		);
	}

	/**
	 * @testdox Widget shows when store has launched and task list is incomplete.
	 */
	public function test_widget_shows_when_store_has_launched_and_task_list_incomplete(): void {
		update_option( 'woocommerce_coming_soon', 'no' );
		delete_option( 'woocommerce_task_list_completed_lists' );
		delete_option( 'woocommerce_task_list_hidden_lists' );

		$this->assertTrue(
			$this->invoke_should_display_widget( $this->sut ),
			'Widget should display when store has launched even if task list is incomplete'
		);
	}

	/**
	 * @testdox Status and reviews widgets are registered high in the normal dashboard column.
	 */
	public function test_init_registers_status_and_reviews_widgets_in_high_normal_context(): void {
		global $wp_meta_boxes;

		require_once ABSPATH . 'wp-admin/includes/dashboard.php';
		set_current_screen( 'dashboard' );
		unset( $wp_meta_boxes['dashboard'] );

		$this->sut->init();

		$this->assertArrayHasKey( 'woocommerce_dashboard_status', $wp_meta_boxes['dashboard']['normal']['high'] );
		$this->assertArrayHasKey( 'woocommerce_dashboard_recent_reviews', $wp_meta_boxes['dashboard']['normal']['high'] );

		$widget_order  = array_keys( $wp_meta_boxes['dashboard']['normal']['high'] );
		$status_index  = array_search( 'woocommerce_dashboard_status', $widget_order, true );
		$reviews_index = array_search( 'woocommerce_dashboard_recent_reviews', $widget_order, true );

		$this->assertNotFalse( $status_index );
		$this->assertNotFalse( $reviews_index );
		$this->assertLessThan( $reviews_index, $status_index );
	}

	/**
	 * @testdox Status widget loading placeholder renders the spinner above the loading text.
	 */
	public function test_status_widget_loading_placeholder_renders_stacked_loader(): void {
		ob_start();
		$this->sut->status_widget();
		$html = ob_get_clean();

		$this->assertStringContainsString( 'class="wc-dashboard-widget-loading wc-status-widget-loading"', $html );
		$this->assertStringContainsString( 'aria-busy="true"', $html );
		$this->assertMatchesRegularExpression( '/<p><span class="spinner is-active"><\/span><span class="wc-dashboard-widget-loading__text">Loading status data\.\.\.<\/span><\/p>/', $html );
	}

	/**
	 * @testdox Recent reviews widget loading placeholder renders the spinner above the loading text.
	 */
	public function test_recent_reviews_widget_loading_placeholder_renders_stacked_loader(): void {
		ob_start();
		$this->sut->recent_reviews();
		$html = ob_get_clean();

		$this->assertStringContainsString( 'class="wc-dashboard-widget-loading wc-recent-reviews-widget-loading"', $html );
		$this->assertStringContainsString( 'aria-busy="true"', $html );
		$this->assertMatchesRegularExpression( '/<p><span class="spinner is-active"><\/span><span class="wc-dashboard-widget-loading__text">Loading reviews data\.\.\.<\/span><\/p>/', $html );
	}

	/**
	 * @testdox Status widget order rows render labels before counts.
	 */
	public function test_status_widget_order_rows_render_labels_before_counts(): void {
		$method = new ReflectionMethod( WC_Admin_Dashboard::class, 'status_widget_order_rows' );
		$method->setAccessible( true );

		ob_start();
		$method->invoke( $this->sut );
		$html = ob_get_clean();

		$this->assertStringContainsString( 'Awaiting processing <strong>0 orders</strong>', $html );
		$this->assertStringContainsString( 'On-hold <strong>0 orders</strong>', $html );
	}

	/**
	 * @testdox Widget does not show without proper capabilities.
	 */
	public function test_widget_does_not_show_without_capabilities(): void {
		add_filter( 'pre_option_woocommerce_task_list_complete', fn() => 'yes' );

		$password   = wp_generate_password( 8, false, false );
		$subscriber = wp_insert_user(
			array(
				'user_login' => "test_subscriber$password",
				'user_pass'  => $password,
				'user_email' => "subscriber$password@example.com",
				'role'       => 'subscriber',
			)
		);
		wp_set_current_user( $subscriber );

		$this->assertFalse(
			$this->invoke_should_display_widget( $this->sut ),
			'Widget should not display for users without proper capabilities'
		);
	}
}
