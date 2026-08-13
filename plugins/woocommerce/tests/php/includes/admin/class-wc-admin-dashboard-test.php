<?php
declare( strict_types = 1 );

use Automattic\WooCommerce\Enums\ProductStockStatus;

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
		delete_option( 'woocommerce_enable_reviews' );
		delete_transient( 'wc_low_stock_count' );
		delete_transient( 'wc_outofstock_count' );

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
	 * Invoke the private status_widget_stock_rows method and return its output.
	 *
	 * @return string
	 */
	private function capture_status_widget_stock_rows(): string {
		delete_transient( 'wc_low_stock_count' );
		delete_transient( 'wc_outofstock_count' );

		$method = new ReflectionMethod( WC_Admin_Dashboard::class, 'status_widget_stock_rows' );
		$method->setAccessible( true );

		ob_start();
		$method->invoke( $this->sut, '', '' );
		$html = ob_get_clean();

		return false === $html ? '' : $html;
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
	 * @testdox Widget shows when task list is incomplete.
	 */
	public function test_widget_shows_when_task_list_is_incomplete(): void {
		delete_option( 'woocommerce_task_list_completed_lists' );
		delete_option( 'woocommerce_task_list_hidden_lists' );

		$this->assertTrue(
			$this->invoke_should_display_widget( $this->sut ),
			'Widget should display even when the task list is incomplete'
		);
	}

	/**
	 * @testdox WooCommerce widgets are registered high in the normal dashboard column in their current order.
	 */
	public function test_init_registers_woocommerce_widgets_in_high_normal_context_in_current_order(): void {
		global $wp_meta_boxes;

		require_once ABSPATH . 'wp-admin/includes/dashboard.php';
		set_current_screen( 'dashboard' );
		update_option( 'woocommerce_enable_reviews', 'yes' );
		unset( $wp_meta_boxes['dashboard'] );

		add_meta_box(
			'wc_admin_dashboard_setup',
			'WooCommerce Setup',
			'__return_empty_string',
			'dashboard',
			'normal',
			'high'
		);
		$this->sut->init();

		$this->assertArrayHasKey( 'wc_admin_dashboard_setup', $wp_meta_boxes['dashboard']['normal']['high'] );
		$this->assertArrayHasKey( 'woocommerce_dashboard_status', $wp_meta_boxes['dashboard']['normal']['high'] );
		$this->assertArrayHasKey( 'woocommerce_dashboard_recent_reviews', $wp_meta_boxes['dashboard']['normal']['high'] );

		$widget_order = array_values(
			array_intersect(
				array_keys( $wp_meta_boxes['dashboard']['normal']['high'] ),
				array(
					'wc_admin_dashboard_setup',
					'woocommerce_dashboard_status',
					'woocommerce_dashboard_recent_reviews',
				)
			)
		);

		$this->assertSame(
			array(
				'wc_admin_dashboard_setup',
				'woocommerce_dashboard_status',
				'woocommerce_dashboard_recent_reviews',
			),
			$widget_order
		);
	}

	/**
	 * @testdox Recent reviews widget is not registered when product reviews are disabled.
	 */
	public function test_init_does_not_register_recent_reviews_widget_when_reviews_are_disabled(): void {
		global $wp_meta_boxes;

		require_once ABSPATH . 'wp-admin/includes/dashboard.php';
		set_current_screen( 'dashboard' );
		update_option( 'woocommerce_enable_reviews', 'no' );
		$had_comments_support = post_type_supports( 'product', 'comments' );
		add_post_type_support( 'product', 'comments' );
		unset( $wp_meta_boxes['dashboard'] );

		try {
			$this->sut->init();

			$this->assertArrayHasKey( 'woocommerce_dashboard_status', $wp_meta_boxes['dashboard']['normal']['high'] );
			$this->assertArrayNotHasKey( 'woocommerce_dashboard_recent_reviews', $wp_meta_boxes['dashboard']['normal']['high'] );
		} finally {
			if ( ! $had_comments_support ) {
				remove_post_type_support( 'product', 'comments' );
			}
		}
	}

	/**
	 * @testdox Recent reviews widget is registered when product reviews are enabled.
	 */
	public function test_init_registers_recent_reviews_widget_when_reviews_are_enabled(): void {
		global $wp_meta_boxes;

		require_once ABSPATH . 'wp-admin/includes/dashboard.php';
		set_current_screen( 'dashboard' );
		update_option( 'woocommerce_enable_reviews', 'yes' );
		$had_comments_support = post_type_supports( 'product', 'comments' );
		add_post_type_support( 'product', 'comments' );
		unset( $wp_meta_boxes['dashboard'] );

		try {
			$this->sut->init();

			$this->assertArrayHasKey( 'woocommerce_dashboard_recent_reviews', $wp_meta_boxes['dashboard']['normal']['high'] );
		} finally {
			if ( ! $had_comments_support ) {
				remove_post_type_support( 'product', 'comments' );
			}
		}
	}

	/**
	 * @testdox Recent reviews widget uses the enabled default when the reviews setting has not been saved.
	 */
	public function test_init_registers_recent_reviews_widget_when_reviews_setting_is_missing(): void {
		global $wp_meta_boxes;

		require_once ABSPATH . 'wp-admin/includes/dashboard.php';
		set_current_screen( 'dashboard' );
		delete_option( 'woocommerce_enable_reviews' );
		$had_comments_support = post_type_supports( 'product', 'comments' );
		add_post_type_support( 'product', 'comments' );
		unset( $wp_meta_boxes['dashboard'] );

		try {
			$this->sut->init();

			$this->assertArrayHasKey( 'woocommerce_dashboard_recent_reviews', $wp_meta_boxes['dashboard']['normal']['high'] );
		} finally {
			if ( ! $had_comments_support ) {
				remove_post_type_support( 'product', 'comments' );
			}
		}
	}

	/**
	 * @testdox Status widget loading placeholder renders the spinner above the loading text.
	 */
	public function test_status_widget_loading_placeholder_renders_stacked_loader(): void {
		wp_deregister_script( 'wc-flot' );

		ob_start();
		$this->sut->status_widget();
		$html = ob_get_clean();

		$this->assertTrue( wp_script_is( 'wc-flot', 'registered' ) );
		$this->assertStringContainsString( 'class="wc-dashboard-widget-loading wc-status-widget-loading"', $html );
		$this->assertStringContainsString( 'aria-busy="true"', $html );
		$this->assertStringContainsString( '<p><span class="spinner is-active"></span><span class="wc-dashboard-widget-loading__text">Loading status data...</span></p>', $html );
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
		$this->assertStringContainsString( '<p><span class="spinner is-active"></span><span class="wc-dashboard-widget-loading__text">Loading reviews data...</span></p>', $html );
	}

	/**
	 * @testdox Status widget out-of-stock count includes unmanaged out-of-stock products.
	 */
	public function test_status_widget_out_of_stock_count_includes_unmanaged_out_of_stock_products(): void {
		WC_Helper_Product::create_simple_product(
			true,
			array(
				'manage_stock' => false,
				'stock_status' => ProductStockStatus::OUT_OF_STOCK,
			)
		);

		$html = $this->capture_status_widget_stock_rows();

		$this->assertStringContainsString( 'Out of stock <strong>1 product</strong>', $html );
	}

	/**
	 * @testdox Status widget out-of-stock count includes managed products with no stock.
	 */
	public function test_status_widget_out_of_stock_count_includes_managed_products_with_no_stock(): void {
		$product = WC_Helper_Product::create_simple_product(
			true,
			array(
				'manage_stock'   => true,
				'stock_quantity' => 0,
			)
		);

		$html = $this->capture_status_widget_stock_rows();

		$this->assertSame( ProductStockStatus::OUT_OF_STOCK, $product->get_stock_status( 'edit' ) );
		$this->assertStringContainsString( 'Out of stock <strong>1 product</strong>', $html );
	}

	/**
	 * @testdox Status widget out-of-stock count excludes managed products on backorder.
	 */
	public function test_status_widget_out_of_stock_count_excludes_managed_products_on_backorder(): void {
		$product = WC_Helper_Product::create_simple_product(
			true,
			array(
				'manage_stock'   => true,
				'stock_quantity' => 0,
				'backorders'     => 'yes',
			)
		);

		$html = $this->capture_status_widget_stock_rows();

		$this->assertSame( ProductStockStatus::ON_BACKORDER, $product->get_stock_status( 'edit' ) );
		$this->assertStringContainsString( 'Out of stock <strong>0 products</strong>', $html );
	}

	/**
	 * @testdox Status widget out-of-stock count excludes unmanaged in-stock products.
	 */
	public function test_status_widget_out_of_stock_count_excludes_unmanaged_in_stock_products(): void {
		WC_Helper_Product::create_simple_product(
			true,
			array(
				'manage_stock' => false,
				'stock_status' => ProductStockStatus::IN_STOCK,
			)
		);

		$html = $this->capture_status_widget_stock_rows();

		$this->assertStringContainsString( 'Out of stock <strong>0 products</strong>', $html );
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
