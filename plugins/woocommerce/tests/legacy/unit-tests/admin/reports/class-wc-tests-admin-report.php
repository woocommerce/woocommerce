<?php
/**
 * Class WC_Tests_Admin_Report file.
 *
 * @package WooCommerce\Tests\Admin\Reports
 */

/**
 * Tests for the WC_Admin_Report class.
 */
class WC_Tests_Admin_Report extends WC_Unit_Test_Case {

	/**
	 * Load the necessary files, as they're not automatically loaded by WooCommerce.
	 *
	 */
	public static function setUpBeforeClass() {
		include_once WC_Unit_Tests_Bootstrap::instance()->plugin_dir . '/includes/admin/reports/class-wc-admin-report.php';
	}

	/**
	 * Clear cached report data.
	 *
	 * @before
	 */
	public function clear_transients() {
		delete_transient( 'wc_admin_report' );
	}

	/**
	 * Test: get_order_report_data
	 */
	public function test_get_order_report_data() {
		$order = WC_Helper_Order::create_order();
		$order->set_status( 'completed' );
		$order->save();

		$report = new WC_Admin_Report();
		$data   = $report->get_order_report_data(
			array(
				'data' => array(
					'ID' => array(
						'type'     => 'post_data',
						'function' => 'COUNT',
						'name'     => 'total_orders',
					),
				),
			)
		);

		$this->assertEquals( 1, $data->total_orders, 'Expected to see one completed order in the report.' );
		WC_Admin_Report::maybe_update_transients();
		$this->assertNotEmpty( get_transient( 'wc_admin_report' ), 'Results should be cached in a transient.' );
	}

	/**
	 * Test: get_order_report_data
	 */
	public function test_get_order_report_data_returns_empty_string_if_data_is_empty() {
		$report = new WC_Admin_Report();

		add_filter( 'woocommerce_reports_get_order_report_data_args', '__return_empty_string' );

		$this->assertEmpty( $report->get_order_report_data() );
	}

	/**
	 * Test: get_order_report_data
	 */
	public function test_get_order_report_data_for_post_meta() {
		$order = WC_Helper_Order::create_order();
		$order->set_status( 'completed' );
		$order->save();

		$report = new WC_Admin_Report();
		$data   = $report->get_order_report_data(
			array(
				'data' => array(
					'_billing_first_name' => array(
						'type'     => 'meta',
						'function' => null,
						'name'     => 'customer_name',
					),
				),
			)
		);

		$this->assertEquals( $order->get_billing_first_name(), $data->customer_name );
	}

	/**
	 * Test: get_order_report_data
	 */
	public function test_get_order_report_data_for_parent_meta() {
		$order  = WC_Helper_Order::create_order();
		$refund = wc_create_refund(
			array(
				'order_id' => $order->get_id(),
			)
		);

		$report = new WC_Admin_Report();
		$data   = $report->get_order_report_data(
			array(
				'data' => array(
					'_order_total' => array(
						'type'     => 'parent_meta',
						'function' => '',
						'name'     => 'total_refund',
					),
				),
			)
		);

		$this->assertEquals( $order->get_total(), $data->total_refund );
	}

	/**
	 * Test: get_order_report_data
	 */
	public function test_get_order_report_data_for_post_data() {
		$order = WC_Helper_Order::create_order();
		$order->set_status( 'completed' );
		$order->save();

		$report = new WC_Admin_Report();
		$data   = $report->get_order_report_data(
			array(
				'data' => array(
					'post_status' => array(
						'type'     => 'post_data',
						'function' => null,
						'name'     => 'post_status',
					),
				),
			)
		);

		$this->assertEquals( 'wc-completed', $data->post_status );
	}

	/**
	 * Test: get_order_report_data
	 */
	public function test_get_order_report_data_for_order_items() {
		$product = WC_Helper_Product::create_simple_product();
		$order   = WC_Helper_Order::create_order( 0, $product->get_id() );
		$order->set_status( 'completed' );
		$order->save();

		$report = new WC_Admin_Report();
		$data   = $report->get_order_report_data(
			array(
				'data' => array(
					'order_item_name' => array(
						'type'     => 'order_item',
						'function' => null,
						'name'     => 'name',
					),
				),
			)
		);

		$this->assertEquals( $product->get_name(), $data->name );
	}
}
