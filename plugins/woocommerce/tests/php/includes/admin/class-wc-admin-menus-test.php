<?php
/**
 * Unit tests for the WC_Admin_Menus class.
 *
 * @package WooCommerce\Tests\Admin
 */

/**
 * Class WC_Admin_Menus_Test
 */
class WC_Admin_Menus_Test extends \WC_Unit_Test_Case {

	/**
	 * The System Under Test.
	 *
	 * @var WC_Admin_Menus
	 */
	private $sut;

	/**
	 * Set up test fixtures.
	 */
	public function setUp(): void {
		parent::setUp();

		$bootstrap = \WC_Unit_Tests_Bootstrap::instance();
		require_once $bootstrap->plugin_dir . '/includes/admin/class-wc-admin-menus.php';

		$this->sut = new WC_Admin_Menus();
	}

	/**
	 * @testdox Should not duplicate the product menu item when reordering the admin menu.
	 */
	public function test_menu_order_does_not_duplicate_product_entry(): void {
		$menu_order = array(
			'index.php',
			'separator1',
			'edit.php',
			'upload.php',
			'edit.php?post_type=page',
			'separator-woocommerce',
			'woocommerce',
			'edit.php?post_type=product',
			'separator2',
			'themes.php',
		);

		$result = $this->sut->menu_order( $menu_order );

		$product_occurrences = count(
			array_keys( $result, 'edit.php?post_type=product', true )
		);

		$this->assertSame(
			1,
			$product_occurrences,
			'The product menu entry should appear exactly once after reordering.'
		);
	}

	/**
	 * @testdox Should place the WooCommerce separator and product menu immediately before the WooCommerce item.
	 */
	public function test_menu_order_places_woocommerce_block_in_expected_position(): void {
		$menu_order = array(
			'index.php',
			'edit.php',
			'separator-woocommerce',
			'woocommerce',
			'edit.php?post_type=product',
			'themes.php',
		);

		$result = $this->sut->menu_order( $menu_order );

		$woocommerce_index = array_search( 'woocommerce', $result, true );
		$separator_index   = array_search( 'separator-woocommerce', $result, true );
		$product_index     = array_search( 'edit.php?post_type=product', $result, true );

		$this->assertNotFalse( $woocommerce_index, 'WooCommerce menu item should be present.' );
		$this->assertSame(
			$woocommerce_index - 1,
			$separator_index,
			'The WooCommerce separator should appear directly before the WooCommerce menu item.'
		);
		$this->assertSame(
			$woocommerce_index + 1,
			$product_index,
			'The product menu item should appear directly after the WooCommerce menu item.'
		);
	}

	/**
	 * @testdox Should still inject the product menu item when the input does not include it.
	 */
	public function test_menu_order_injects_product_when_absent(): void {
		$menu_order = array(
			'index.php',
			'separator-woocommerce',
			'woocommerce',
			'themes.php',
		);

		$result = $this->sut->menu_order( $menu_order );

		$product_occurrences = count(
			array_keys( $result, 'edit.php?post_type=product', true )
		);

		$this->assertSame(
			1,
			$product_occurrences,
			'The product menu entry should be added exactly once even when not in the input order.'
		);
	}
}
