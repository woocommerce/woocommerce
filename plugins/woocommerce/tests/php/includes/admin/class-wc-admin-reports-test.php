<?php

declare( strict_types = 1);

/**
 * Tests for `WC_Admin_Reports` class.
 */
final class WC_Admin_Reports_Test extends WC_Unit_Test_Case {
	/**
	 * Verify the workflows execution in `delete_legacy_reports_transients`.
	 */
	public function test_delete_legacy_reports_transients(): void {
		// Verify the integration point invocation.
		$this->assertSame( 10, has_action( 'woocommerce_delete_shop_order_transients', array( \WC_Admin_Reports::class, 'delete_legacy_reports_transients' ) ) );
		$this->assertTrue( has_action( 'woocommerce_delete_legacy_report_transients' ) );

		// Verify the defer-workflow: nov verifying for pending AS action as other tests already triggered the deferred workflow.
		// Accordingly, we can only verify that we entered into defer-workflow + rely on manual testing for this PR.
		set_transient( 'wc_admin_report', 'Verify defer' );
		\WC_Admin_Reports::delete_legacy_reports_transients( 0, true );
		$this->assertSame( 'Verify defer', get_transient( 'wc_admin_report' ) );

		// Verify the purge-workflow.
		set_transient( 'wc_admin_report', 'Verify deletion' );
		do_action( 'woocommerce_delete_legacy_report_transients', 0, false );
		$this->assertFalse( get_transient( 'wc_admin_report' ) );
	}

	/**
	 * @testdox Should keep pointing the legacy status widget reports at legacy Reports for code that still calls them.
	 */
	public function test_replace_dashboard_status_widget_reports_uses_legacy_report_links(): void {
		$reports = \WC_Admin_Reports::replace_dashboard_status_widget_reports( array() );

		$this->assertSame( 'admin.php?page=wc-reports&tab=orders&range=month', $reports['net_sales_link'] );
		$this->assertSame( 'admin.php?page=wc-reports&tab=orders&report=sales_by_product&range=month&product_ids=', $reports['top_seller_link'] );
		$this->assertSame( 'admin.php?page=wc-reports&tab=stock&report=low_in_stock', $reports['lowstock_link'] );
		$this->assertSame( 'admin.php?page=wc-reports&tab=stock&report=out_of_stock', $reports['outofstock_link'] );
	}

	/**
	 * @testdox Should only hook the after status widget parameter, not the legacy sales data, into the dashboard status widget.
	 */
	public function test_register_hook_handlers_does_not_replace_dashboard_status_widget_reports(): void {
		\WC_Admin_Reports::register_hook_handlers();

		$this->assertFalse( has_filter( 'woocommerce_dashboard_status_widget_reports', array( \WC_Admin_Reports::class, 'replace_dashboard_status_widget_reports' ) ) );
		$this->assertSame( 10, has_filter( 'woocommerce_after_dashboard_status_widget_parameter', array( \WC_Admin_Reports::class, 'get_status_widget_report_instance' ) ) );
	}

	/**
	 * @testdox Should pass a legacy report to the after status widget action only when something listens to it.
	 */
	public function test_status_widget_report_instance_is_only_created_for_listeners(): void {
		remove_all_actions( 'woocommerce_after_dashboard_status_widget' );

		$this->assertNull( \WC_Admin_Reports::get_status_widget_report_instance( null ), 'No report should be created without listeners.' );

		add_action( 'woocommerce_after_dashboard_status_widget', '__return_null' );
		$reports = \WC_Admin_Reports::get_status_widget_report_instance( null );

		$this->assertIsObject( $reports, 'Listeners should still receive a legacy report.' );
		$this->assertSame( 'WC_Admin_Report', get_class( $reports ) );
	}
}
