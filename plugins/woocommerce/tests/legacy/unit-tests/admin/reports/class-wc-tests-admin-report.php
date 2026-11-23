<?php
/**
 * Class WC_Tests_Admin_Report file.
 *
 * @package WooCommerce\Tests\Admin\Reports
 */

use Automattic\WooCommerce\Enums\OrderInternalStatus;
use Automattic\WooCommerce\Enums\OrderStatus;

/**
 * Tests for the WC_Admin_Report class.
 */
class WC_Tests_Admin_Report extends WC_Unit_Test_Case {

	/**
	 * Load the necessary files, as they're not automatically loaded by WooCommerce.
	 *
	 */
	public static function setUpBeforeClass(): void {
		parent::setUpBeforeClass();

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
		$order->set_status( OrderStatus::COMPLETED );
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
		$order->set_status( OrderStatus::COMPLETED );
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
		$order->set_status( OrderStatus::COMPLETED );
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

		$this->assertEquals( OrderInternalStatus::COMPLETED, $data->post_status );
	}

	/**
	 * Test: get_order_report_data
	 */
	public function test_get_order_report_data_for_order_items() {
		$product = WC_Helper_Product::create_simple_product();
		$order   = WC_Helper_Order::create_order( 0, $product->get_id() );
		$order->set_status( OrderStatus::COMPLETED );
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

	/**
	 * @testdox Currency tooltip fragments preserve filtered symbols as valid JavaScript strings.
	 */
	public function test_get_currency_tooltip_encodes_filtered_symbol(): void {
		$currency_symbol = "'\"\\</script>€雪";
		$currency_pos    = get_option( 'woocommerce_currency_pos', null );
		$positions       = array(
			'right'       => array( 'append_tooltip', $currency_symbol ),
			'right_space' => array( 'append_tooltip', '&nbsp;' . $currency_symbol ),
			'left'        => array( 'prepend_tooltip', $currency_symbol ),
			'left_space'  => array( 'prepend_tooltip', $currency_symbol . '&nbsp;' ),
		);
		$report          = new WC_Admin_Report();
		$filter          = static function () use ( $currency_symbol ): string {
			return $currency_symbol;
		};

		add_filter( 'woocommerce_currency_symbol', $filter );
		try {
			foreach ( $positions as $position => $expected ) {
				update_option( 'woocommerce_currency_pos', $position );
				$fragment = $report->get_currency_tooltip();

				$this->assertSame( 1, preg_match( '/^(append_tooltip|prepend_tooltip): (.+)$/s', $fragment, $matches ) );
				$this->assertSame( $expected[0], $matches[1] );
				$this->assertSame( $expected[1], json_decode( $matches[2], true, 512, JSON_THROW_ON_ERROR ) );
			}
		} finally {
			remove_filter( 'woocommerce_currency_symbol', $filter );
			if ( null === $currency_pos ) {
				delete_option( 'woocommerce_currency_pos' );
			} else {
				update_option( 'woocommerce_currency_pos', $currency_pos );
			}
		}
	}
}
