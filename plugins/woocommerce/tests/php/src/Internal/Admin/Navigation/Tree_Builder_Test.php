<?php

namespace Automattic\WooCommerce\Tests\Internal\Admin\Navigation;

use Automattic\WooCommerce\Internal\Admin\Navigation\Tree_Builder;

/**
 * @covers \Automattic\WooCommerce\Internal\Admin\Navigation\Tree_Builder
 */
class Tree_Builder_Test extends \WC_Unit_Test_Case {

	/**
	 * Given the default tree and no extra $menu/$submenu entries, the builder
	 * returns the default tree unchanged (minus any slugs whose underlying
	 * registration is absent — none here, so all retained).
	 */
	public function test_default_tree_passes_through_unchanged() {
		$default = array(
			'woocommerce' => array( 'parent' => null, 'title' => 'WooCommerce', 'position' => 2 ),
			'wc-admin'    => array( 'parent' => 'woocommerce', 'title' => 'Home', 'position' => 10 ),
		);

		// Simulate WP having registered both pages.
		$raw_menu    = array(
			array( 'WooCommerce', 'read', 'woocommerce', '', '' ),
		);
		$raw_submenu = array(
			'woocommerce' => array(
				array( 'Home', 'read', 'wc-admin' ),
			),
		);

		$builder = new Tree_Builder();
		$tree    = $builder->build( $default, $raw_menu, $raw_submenu );

		$this->assertArrayHasKey( 'woocommerce', $tree );
		$this->assertArrayHasKey( 'wc-admin', $tree );
		$this->assertSame( 'woocommerce', $tree['wc-admin']['parent'] );
	}

	/**
	 * Slugs declared in the default tree but not registered by any plugin are
	 * silently skipped (not errors).
	 */
	public function test_unregistered_slugs_are_skipped() {
		$default = array(
			'woocommerce'           => array( 'parent' => null, 'title' => 'WooCommerce', 'position' => 2 ),
			'woocommerce-payments'  => array( 'parent' => 'woocommerce', 'title' => 'WooPayments', 'position' => 20 ),
		);

		$raw_menu    = array( array( 'WooCommerce', 'read', 'woocommerce', '', '' ) );
		$raw_submenu = array();

		$builder = new Tree_Builder();
		$tree    = $builder->build( $default, $raw_menu, $raw_submenu );

		$this->assertArrayHasKey( 'woocommerce', $tree );
		$this->assertArrayNotHasKey( 'woocommerce-payments', $tree );
	}
}
