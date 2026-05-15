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
		delete_transient( 'wc_outofstock_count' );
		delete_transient( 'wc_low_stock_count' );

		parent::tearDown();
	}

	/**
	 * Invoke the private status_widget_stock_rows method via reflection and capture output.
	 *
	 * @return string Captured output.
	 */
	private function capture_status_widget_stock_rows(): string {
		$method = new ReflectionMethod( WC_Admin_Dashboard::class, 'status_widget_stock_rows' );
		$method->setAccessible( true );

		ob_start();
		$method->invoke( $this->sut, '', '' );
		return (string) ob_get_clean();
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
	 * @testdox Widget does not show when neither complete nor hidden.
	 */
	public function test_widget_does_not_show_when_neither_complete_nor_hidden(): void {
		delete_option( 'woocommerce_task_list_completed_lists' );
		delete_option( 'woocommerce_task_list_hidden_lists' );

		$this->assertFalse(
			$this->invoke_should_display_widget( $this->sut ),
			'Widget should not display when task list is neither complete nor hidden'
		);
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

	/**
	 * @testdox Out of stock widget counts a product with manage_stock disabled and stock_status outofstock.
	 *
	 * Regression test for https://github.com/woocommerce/woocommerce/issues/29698: when "Manage Stock"
	 * is disabled and the user manually flips the stock status to "Out of Stock", the widget query
	 * (which previously only looked at stock_quantity) missed the product because stock_quantity is
	 * NULL for unmanaged products.
	 */
	public function test_outofstock_widget_counts_unmanaged_outofstock_product(): void {
		$product = new WC_Product_Simple();
		$product->set_name( 'Unmanaged Out Of Stock Product' );
		$product->set_regular_price( '25' );
		$product->set_manage_stock( false );
		$product->set_stock_status( 'outofstock' );
		$product->set_status( 'publish' );
		$product->save();

		$output = $this->capture_status_widget_stock_rows();

		$this->assertStringContainsString(
			'1 product</strong> out of stock',
			$output,
			'Widget should count an unmanaged product whose stock_status was manually set to outofstock'
		);
	}

	/**
	 * @testdox Out of stock widget still counts products with stock_quantity <= 0 (managed stock path).
	 */
	public function test_outofstock_widget_counts_managed_zero_stock_product(): void {
		$product = new WC_Product_Simple();
		$product->set_name( 'Managed Zero Stock Product' );
		$product->set_regular_price( '10' );
		$product->set_manage_stock( true );
		$product->set_stock_quantity( 0 );
		$product->set_status( 'publish' );
		$product->save();

		$output = $this->capture_status_widget_stock_rows();

		$this->assertStringContainsString(
			'1 product</strong> out of stock',
			$output,
			'Widget should still count a managed product whose stock_quantity is zero'
		);
	}

	/**
	 * @testdox Out of stock widget does not count an in-stock product.
	 */
	public function test_outofstock_widget_does_not_count_instock_product(): void {
		$product = new WC_Product_Simple();
		$product->set_name( 'In Stock Product' );
		$product->set_regular_price( '15' );
		$product->set_manage_stock( false );
		$product->set_stock_status( 'instock' );
		$product->set_status( 'publish' );
		$product->save();

		$output = $this->capture_status_widget_stock_rows();

		$this->assertStringContainsString(
			'0 products</strong> out of stock',
			$output,
			'Widget should report zero out-of-stock products when the only product is in stock'
		);
	}
}
