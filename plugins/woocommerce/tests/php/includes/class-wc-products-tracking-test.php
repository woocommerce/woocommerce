<?php

/**
 * Class WC_Products_Tracking_Test.
 */
class WC_Products_Tracking_Test extends \WC_Unit_Test_Case {
	/**
	 * Set up test
	 *
	 * @return void
	 */
	public function setUp(): void {
		include_once WC_ABSPATH . 'includes/tracks/events/class-wc-products-tracking.php';
		update_option( 'woocommerce_allow_tracking', 'yes' );
		$products_tracking = new WC_Products_Tracking();
		$products_tracking->init();
		parent::setUp();
	}

	/**
	 * Teardown test
	 *
	 * @return void
	 */
	public function tearDown(): void {
		update_option( 'woocommerce_allow_tracking', 'no' );
		parent::tearDown();
	}

	/**
	 * Test wcadmin_product_add_publish tracks event
	 *
	 */
	public function test_product_add_publish(): void {
		$product = new WC_Product_Simple();
		$product->save();
		$this->assertRecordedTracksEvent( 'wcadmin_product_add_publish' );
	}

	/**
	 * Test wcadmin_product_edit tracks event
	 *
	 */
	public function test_product_update(): void {
		$product = new WC_Product_Simple();
		$product->save();
		$product->set_name( 'New name' );
		$product->save();
		$this->assertRecordedTracksEvent( 'wcadmin_product_edit' );
	}

	/**
	 * Test wcadmin_products_view tracks event
	 */
	public function test_products_view(): void {
		$_GET['post_type'] = 'product';
		// phpcs:disable WordPress.NamingConventions.ValidHookName.UseUnderscores
		// phpcs:ignore WooCommerce.Commenting.CommentHooks.MissingHookComment
		do_action( 'load-edit.php' );
		$this->assertRecordedTracksEvent( 'wcadmin_products_view' );
	}

	/**
	 * Test wcadmin_products_search tracks event
	 */
	public function test_products_search(): void {
		$_GET['post_type'] = 'product';
		$_GET['s']         = 'test';
		/* phpcs:ignore WooCommerce.Commenting.CommentHooks.MissingHookComment */
		do_action( 'load-edit.php' );
		$this->assertRecordedTracksEvent( 'wcadmin_products_search' );
	}

}
