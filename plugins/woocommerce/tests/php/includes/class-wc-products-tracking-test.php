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

	public function tearDown(): void {
		update_option( 'woocommerce_allow_tracking', 'no' );
		parent::tearDown();
	}

	public function test_product_add_publish(): void {
		$product = new WC_Product_Simple();
		$product->save();
		$this->assertRecordedTracksEvent( 'wcadmin_product_add_publish' );
	}

	public function test_product_update(): void {
		$product = new WC_Product_Simple();
		$product->save();
		$product->set_name( 'New name' );
		$product->save();
		$this->assertRecordedTracksEvent( 'wcadmin_product_edit' );
	}

	public function test_products_view(): void {
		$_GET['post_type'] = 'product';
		do_action( 'load-edit.php' );
		$this->assertRecordedTracksEvent( 'wcadmin_products_view' );
	}

	public function test_products_search(): void {
		$_GET['post_type'] = 'product';
		$_GET['s']         = 'test';
		do_action( 'load-edit.php' );
		$this->assertRecordedTracksEvent( 'wcadmin_products_search' );
	}

}
