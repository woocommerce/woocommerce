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
		$this->assertTrue( has_action( 'woocommerce_delete_shop_order_transients', array( \WC_Admin_Reports::class, 'delete_legacy_reports_transients' ), 10 ) );
		$this->assertTrue( has_action( 'woocommerce_delete_legacy_report_transients' ) );

		// Cleanup.
		as_unschedule_action( 'woocommerce_delete_legacy_report_transients', null, 'woocommerce' );

		// Verify the defer-workflow.
		$this->assertFalse( as_has_scheduled_action( 'woocommerce_delete_legacy_report_transients', null, 'woocommerce' ) );
		\WC_Admin_Reports::delete_legacy_reports_transients( 0 );
		$this->assertTrue( as_has_scheduled_action( 'woocommerce_delete_legacy_report_transients', null, 'woocommerce' ) );

		// Cleanup.
		as_unschedule_action( 'woocommerce_delete_legacy_report_transients', null, 'woocommerce' );

		// Verify the purge-workflow.
		do_action( 'woocommerce_delete_legacy_report_transients', 0, true ); // phpcs:ignore WooCommerce.Commenting.CommentHooks.HookCommentWrongStyle
		$this->assertFalse( as_has_scheduled_action( 'woocommerce_delete_legacy_report_transients', null, 'woocommerce' ) );
	}
}
