<?php // phpcs:ignore Generic.PHP.RequireStrictTypes.MissingDeclaration
/**
 * Tests for wc-template-functions.php.
 *
 * @package WooCommerce\Tests\Functions
 */

/**
 * Class WC_Template_Functions_Test
 */
class WC_Template_Functions_Test extends \WC_Unit_Test_Case {

	/**
	 * `wc_get_gallery_image_html` should return an empty string when the attachment
	 * cannot be resolved (e.g. dummy/imported data referencing a missing image), so
	 * that no broken `<img src="" />` markup is rendered on the product page.
	 *
	 * Regression test for woo#31646 / RSMAPGJ-301.
	 */
	public function test_wc_get_gallery_image_html_returns_empty_for_missing_attachment() {
		// An attachment ID that definitely does not exist in the test DB.
		$missing_attachment_id = 999999;

		$html = wc_get_gallery_image_html( $missing_attachment_id );

		$this->assertSame( '', $html );
	}

	/**
	 * The new `woocommerce_gallery_image_html_missing_attachment` filter should let
	 * themes/plugins substitute a placeholder when the attachment cannot be resolved.
	 */
	public function test_wc_get_gallery_image_html_missing_attachment_filter() {
		$missing_attachment_id = 999998;

		$callback = function ( $html, $attachment_id, $main_image ) use ( $missing_attachment_id ) {
			$this->assertSame( '', $html );
			$this->assertSame( $missing_attachment_id, $attachment_id );
			$this->assertTrue( $main_image );
			return '<div class="custom-fallback"></div>';
		};

		add_filter( 'woocommerce_gallery_image_html_missing_attachment', $callback, 10, 3 );

		$html = wc_get_gallery_image_html( $missing_attachment_id, true );

		remove_filter( 'woocommerce_gallery_image_html_missing_attachment', $callback, 10 );

		$this->assertSame( '<div class="custom-fallback"></div>', $html );
	}
}
