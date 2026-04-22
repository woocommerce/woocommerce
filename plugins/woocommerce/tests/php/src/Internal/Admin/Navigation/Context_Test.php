<?php

namespace Automattic\WooCommerce\Tests\Internal\Admin\Navigation;

use Automattic\WooCommerce\Internal\Admin\Navigation\Context;

/**
 * @covers \Automattic\WooCommerce\Internal\Admin\Navigation\Context
 */
class Context_Test extends \WC_Unit_Test_Case {

	/**
	 * @var string|null
	 */
	private $pagenow_backup;

	public function setUp(): void {
		parent::setUp();
		$this->pagenow_backup = $GLOBALS['pagenow'] ?? null;
	}

	public function tearDown(): void {
		unset( $_GET['page'], $_GET['post_type'], $_GET['path'], $_GET['tab'], $_GET['taxonomy'] );
		if ( null === $this->pagenow_backup ) {
			unset( $GLOBALS['pagenow'] );
		} else {
			$GLOBALS['pagenow'] = $this->pagenow_backup;
		}
		parent::tearDown();
	}

	public function test_current_request_in_tree_is_woo_context() {
		$GLOBALS['pagenow'] = 'admin.php';
		$_GET['page']       = 'wc-settings';
		$tree               = array(
			'woocommerce' => array( 'parent' => null, 'title' => 'WooCommerce', 'position' => 2 ),
			'wc-settings' => array( 'parent' => 'woocommerce', 'title' => 'Settings', 'position' => 90 ),
		);

		$this->assertTrue( Context::is_woo_page( $tree ) );
	}

	public function test_current_request_not_in_tree_is_not_woo_context() {
		$GLOBALS['pagenow'] = 'admin.php';
		$_GET['page']       = 'non-woo-page';
		$tree               = array(
			'woocommerce' => array( 'parent' => null, 'title' => 'WooCommerce', 'position' => 2 ),
		);

		$this->assertFalse( Context::is_woo_page( $tree ) );
	}

	public function test_wc_admin_path_is_matched_against_tree_keys() {
		$GLOBALS['pagenow'] = 'admin.php';
		$_GET['page']       = 'wc-admin';
		$_GET['path']       = '/analytics/overview';
		$tree               = array(
			'woocommerce'                       => array( 'parent' => null, 'title' => 'WooCommerce', 'position' => 2 ),
			'wc-admin&path=/analytics/overview' => array( 'parent' => 'woocommerce', 'title' => 'Analytics', 'position' => 40 ),
		);

		$this->assertSame( 'wc-admin&path=/analytics/overview', Context::resolve_current_slug( $tree ) );
	}

	public function test_product_post_type_is_matched_against_tree_keys() {
		$GLOBALS['pagenow']  = 'edit.php';
		$_GET['post_type']   = 'product';
		$tree                = array(
			'woocommerce'                => array( 'parent' => null, 'title' => 'WooCommerce', 'position' => 2 ),
			'edit.php?post_type=product' => array( 'parent' => 'woocommerce', 'title' => 'Products', 'position' => 30 ),
		);

		$this->assertSame( 'edit.php?post_type=product', Context::resolve_current_slug( $tree ) );
	}

	public function test_taxonomy_page_matches_full_url_slug() {
		// e.g. /wp-admin/edit-tags.php?taxonomy=product_brand&post_type=product
		$GLOBALS['pagenow']  = 'edit-tags.php';
		$_GET['taxonomy']    = 'product_brand';
		$_GET['post_type']   = 'product';
		$tree                = array(
			'woocommerce'                                              => array( 'parent' => null, 'title' => 'WooCommerce', 'position' => 2 ),
			'edit.php?post_type=product'                               => array( 'parent' => 'woocommerce', 'title' => 'Products', 'position' => 30 ),
			'edit-tags.php?taxonomy=product_brand&post_type=product'   => array( 'parent' => 'edit.php?post_type=product', 'title' => 'Brands', 'position' => 2010 ),
		);

		$this->assertTrue( Context::is_woo_page( $tree ) );
		$this->assertSame( 'edit-tags.php?taxonomy=product_brand&post_type=product', Context::resolve_current_slug( $tree ) );
	}

	public function test_most_specific_match_wins() {
		// On /wp-admin/admin.php?page=wc-settings&tab=general, both the
		// Settings parent and the General tab match. The tab (more params)
		// should win.
		$GLOBALS['pagenow']  = 'admin.php';
		$_GET['page']        = 'wc-settings';
		$_GET['tab']         = 'general';
		$tree                = array(
			'woocommerce'              => array( 'parent' => null, 'title' => 'WooCommerce', 'position' => 2 ),
			'wc-settings'              => array( 'parent' => 'woocommerce', 'title' => 'Settings', 'position' => 90 ),
			'wc-settings&tab=general'  => array( 'parent' => 'wc-settings', 'title' => 'General', 'position' => 30 ),
		);

		$this->assertSame( 'wc-settings&tab=general', Context::resolve_current_slug( $tree ) );
	}
}
