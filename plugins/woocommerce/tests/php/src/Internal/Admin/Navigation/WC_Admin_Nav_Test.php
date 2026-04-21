<?php

namespace Automattic\WooCommerce\Tests\Internal\Admin\Navigation;

use Automattic\WooCommerce\Internal\Admin\Navigation\WC_Admin_Nav;

/**
 * @covers \Automattic\WooCommerce\Internal\Admin\Navigation\WC_Admin_Nav
 */
class WC_Admin_Nav_Test extends \WC_Unit_Test_Case {

	public function test_add_inserts_node_with_parent() {
		$tree = array(
			'woocommerce' => array( 'parent' => null, 'title' => 'WooCommerce', 'position' => 2 ),
		);
		WC_Admin_Nav::add( $tree, 'my-plugin', array( 'parent' => 'woocommerce', 'title' => 'My Plugin' ) );

		$this->assertArrayHasKey( 'my-plugin', $tree );
		$this->assertSame( 'woocommerce', $tree['my-plugin']['parent'] );
		$this->assertSame( 'My Plugin', $tree['my-plugin']['title'] );
		$this->assertSame( 10, $tree['my-plugin']['position'], 'add() defaults position to 10' );
	}

	public function test_move_changes_parent() {
		$tree = array(
			'woocommerce' => array( 'parent' => null,          'title' => 'WooCommerce', 'position' => 2 ),
			'wc-status'   => array( 'parent' => 'woocommerce', 'title' => 'Status',      'position' => 99 ),
			'wc-settings' => array( 'parent' => 'woocommerce', 'title' => 'Settings',    'position' => 90 ),
		);
		WC_Admin_Nav::move( $tree, 'wc-status', 'wc-settings' );

		$this->assertSame( 'wc-settings', $tree['wc-status']['parent'] );
	}

	public function test_remove_deletes_node() {
		$tree = array(
			'woocommerce' => array( 'parent' => null,          'title' => 'WooCommerce', 'position' => 2 ),
			'wc-addons'   => array( 'parent' => 'woocommerce', 'title' => 'Extensions',  'position' => 95 ),
		);
		WC_Admin_Nav::remove( $tree, 'wc-addons' );

		$this->assertArrayNotHasKey( 'wc-addons', $tree );
	}

	public function test_rename_changes_title_only() {
		$tree = array(
			'wc-admin' => array( 'parent' => 'woocommerce', 'title' => 'Home', 'position' => 10 ),
		);
		WC_Admin_Nav::rename( $tree, 'wc-admin', 'Dashboard' );

		$this->assertSame( 'Dashboard', $tree['wc-admin']['title'] );
		$this->assertSame( 'woocommerce', $tree['wc-admin']['parent'] );
	}

	public function test_add_is_idempotent() {
		$tree = array( 'woocommerce' => array( 'parent' => null, 'title' => 'WooCommerce', 'position' => 2 ) );
		WC_Admin_Nav::add( $tree, 'my-plugin', array( 'parent' => 'woocommerce', 'title' => 'First' ) );
		WC_Admin_Nav::add( $tree, 'my-plugin', array( 'parent' => 'woocommerce', 'title' => 'Second' ) );

		$this->assertSame( 'Second', $tree['my-plugin']['title'], 'Second add overwrites first' );
	}
}
