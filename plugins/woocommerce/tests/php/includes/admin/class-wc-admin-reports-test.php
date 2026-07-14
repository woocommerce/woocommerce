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
		do_action( 'woocommerce_delete_legacy_report_transients', 0, false ); // phpcs:ignore WooCommerce.Commenting.CommentHooks.MissingHookComment
		$this->assertFalse( get_transient( 'wc_admin_report' ) );
	}
}
