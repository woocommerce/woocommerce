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

		// Verify the defer-workflow.
		\WC_Admin_Reports::delete_legacy_reports_transients( 0 );
		$this->assertCount(
			1,
			as_get_scheduled_actions(
				array(
					'hook'  => 'woocommerce_delete_legacy_report_transients',
					'group' => 'woocommerce',
				),
				'ids'
			)
		);

		// Verify the purge-workflow.
		do_action( 'woocommerce_delete_legacy_report_transients', 0, true ); // phpcs:ignore WooCommerce.Commenting.CommentHooks.HookCommentWrongStyle
		$this->assertFalse( as_has_scheduled_action( 'woocommerce_delete_legacy_report_transients', null, 'woocommerce' ) );
	}
}
