<?php
declare( strict_types = 1 );

/**
 * Tests for the download permission row rendered inside the order downloads meta box.
 *
 * @package WooCommerce\Tests\Admin
 */
class Html_Order_Download_Permission_Test extends WC_Unit_Test_Case {

	/**
	 * @testdox Should keep the "View report" link on the legacy downloads report while Analytics is enabled.
	 */
	public function test_view_report_links_to_legacy_downloads_report_when_analytics_is_enabled(): void {
		update_option( 'woocommerce_analytics_enabled', 'yes' );

		$product  = WC_Helper_Product::create_simple_product();
		$download = new WC_Customer_Download();
		$download->set_product_id( $product->get_id() );
		$download->set_download_id( 'file-id' );
		$download->set_user_email( 'customer@example.com' );
		$download->save();

		$file_count = 'File 1';
		$loop       = 0;

		ob_start();
		include WC_ABSPATH . 'includes/admin/meta-boxes/views/html-order-download-permission.php';
		$output = (string) ob_get_clean();

		// Analytics has no per-permission downloads view, so this link is a documented exception.
		$this->assertStringContainsString(
			'wp-admin/admin.php?page=wc-reports&#038;tab=orders&#038;report=downloads&#038;permission_id=' . $download->get_id() . '"',
			$output,
			'The download log link should target the legacy downloads report for this permission.'
		);
	}
}
