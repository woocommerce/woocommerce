<?php
/**
 * PageController tests
 *
 * @package WooCommerce\Admin\Tests\PageController
 */

use \Automattic\WooCommerce\Admin\PageController;

/**
 * WC_Admin_Tests_Page_Controller Class
 *
 * @package WooCommerce\Admin\Tests\PageController
 */
class WC_Admin_Tests_Page_Controller extends WP_UnitTestCase {

	/**
	 * Test get_breadcrumbs()
	 */
	public function test_get_breadcrumbs_no_parent() {

		// orders page registration data.
		$orders_page = array(
			'id'        => 'woocommerce-orders',
			'screen_id' => 'edit-shop_order',
			'path'      => add_query_arg( 'post_type', 'shop_order', 'edit.php' ),
			'title'     => array( 'Orders' ),
		);

		$controller = PageController::get_instance();

		// Connect existing pages to wc-admin.
		$controller->connect_page( $orders_page );

		// Need to set current screen to use "get_current_screen()".
		set_current_screen( 'edit-shop_order' );

		// Set the private current_page variable to order page.
		$reflection = new \ReflectionClass( $controller );
		$property   = $reflection->getProperty( 'current_page' );
		$property->setAccessible( true );
		$property->setValue( $controller, $orders_page );

		$breadcrumbs = $controller->get_breadcrumbs();

		$this->assertEquals(
			2,
			count( $breadcrumbs ),
			'Orders page should have 2 breadcrumbs items.'
		);

		$this->assertEquals(
			array(
				'admin.php?page=wc-admin',
				'WooCommerce',
			),
			$breadcrumbs[0],
			'Orders home breadcrumb should be WooCommerce.'
		);

		$this->assertEquals(
			'Orders',
			$breadcrumbs[1],
			'Orders current breadcrumb should be a simple "Orders" string.'
		);
	}

	/**
	 * Test get_breadcrumbs()
	 */
	public function test_get_breadcrumbs_with_parent() {

		// coupon page registration data.
		$coupon_page = array(
			'id'        => 'woocommerce-coupons',
			'parent'    => 'woocommerce-marketing',
			'screen_id' => 'edit-shop_coupon',
			'path'      => add_query_arg( 'post_type', 'shop_coupon', 'edit.php' ),
			'title'     => array( 'Coupons' ),
		);

		// marketing page registration data.
		$marketing_page = array(
			'id'       => 'woocommerce-marketing',
			'title'    => 'Marketing',
			'path'     => '/marketing',
			'icon'     => 'dashicons-megaphone',
			'position' => 58,
		);

		$controller = PageController::get_instance();

		// Connect existing pages to wc-admin.
		$controller->connect_page( $coupon_page );

		// Register wc-admin JS page.
		$controller->register_page( $marketing_page );

		// Need to set current screen to use "get_current_screen()".
		set_current_screen( 'edit-shop_coupon' );

		// Set the private current_page variable to coupon page.
		$reflection = new \ReflectionClass( $controller );
		$property   = $reflection->getProperty( 'current_page' );
		$property->setAccessible( true );
		$property->setValue( $controller, $coupon_page );

		$breadcrumbs = $controller->get_breadcrumbs();

		$this->assertEquals(
			3,
			count( $breadcrumbs ),
			'Coupons page should have 3 breadcrumbs items.'
		);

		$this->assertEquals(
			array(
				'admin.php?page=wc-admin',
				'WooCommerce',
			),
			$breadcrumbs[0],
			'Coupons home breadcrumb should be WooCommerce.'
		);

		$this->assertEquals(
			array(
				'admin.php?page=wc-admin&path=/marketing',
				'Marketing',
			),
			$breadcrumbs[1],
			'Coupons parent should be Marketing.'
		);

		$this->assertEquals(
			'Coupons',
			$breadcrumbs[2],
			'Coupons current breadcrumb should be a simple "Coupons" string.'
		);
	}
}
