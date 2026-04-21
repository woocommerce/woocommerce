<?php

namespace Automattic\WooCommerce\Tests\Internal\Admin\Navigation;

use Automattic\WooCommerce\Internal\Admin\Navigation\Context;

/**
 * @covers \Automattic\WooCommerce\Internal\Admin\Navigation\Context
 */
class Context_Test extends \WC_Unit_Test_Case {

	public function tearDown(): void {
		unset( $_GET['page'], $_GET['post_type'], $_GET['path'] );
		parent::tearDown();
	}

	public function test_current_request_in_tree_is_woo_context() {
		$_GET['page'] = 'wc-settings';
		$tree         = array(
			'woocommerce' => array( 'parent' => null,          'title' => 'WooCommerce', 'position' => 2  ),
			'wc-settings' => array( 'parent' => 'woocommerce', 'title' => 'Settings',    'position' => 90 ),
		);

		$this->assertTrue( Context::is_woo_page( $tree ) );
	}

	public function test_current_request_not_in_tree_is_not_woo_context() {
		$_GET['page'] = 'non-woo-page';
		$tree         = array(
			'woocommerce' => array( 'parent' => null, 'title' => 'WooCommerce', 'position' => 2 ),
		);

		$this->assertFalse( Context::is_woo_page( $tree ) );
	}

	public function test_wc_admin_path_is_matched_against_tree_keys() {
		$_GET['page'] = 'wc-admin';
		$_GET['path'] = '/analytics/overview';
		$tree         = array(
			'woocommerce'                       => array( 'parent' => null,          'title' => 'WooCommerce', 'position' => 2  ),
			'wc-admin&path=/analytics/overview' => array( 'parent' => 'woocommerce', 'title' => 'Analytics',   'position' => 40 ),
		);

		$this->assertTrue( Context::is_woo_page( $tree ) );
	}

	public function test_product_post_type_is_matched_against_tree_keys() {
		$_GET['post_type'] = 'product';
		$tree              = array(
			'woocommerce'                => array( 'parent' => null,          'title' => 'WooCommerce', 'position' => 2  ),
			'edit.php?post_type=product' => array( 'parent' => 'woocommerce', 'title' => 'Products',    'position' => 30 ),
		);

		$this->assertTrue( Context::is_woo_page( $tree ) );
	}
}
