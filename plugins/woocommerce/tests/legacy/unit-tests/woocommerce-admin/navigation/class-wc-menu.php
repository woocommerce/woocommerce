<?php
/**
 * Menu tests
 *
 * @package WooCommerce\Admin\Tests\Navigation
 */

use Automattic\WooCommerce\Admin\Features\Navigation\Menu;


/**
 * Class WC_Admin_Tests_Navigation_Menu
 */
class WC_Admin_Tests_Navigation_Menu extends WC_Unit_Test_Case {

	/**
	 * @var Menu
	 */
	private $instance;

	/**
	 * setUp
	 */
	public function setUp(): void {
		parent::setUp();
		$this->instance = new Menu();

		$this->user = $this->factory->user->create(
			array(
				'role' => 'administrator',
			)
		);
		wp_set_current_user( $this->user );
	}

	/**
	 * Test that the correct callback is returned given a string.
	 */
	public function test_get_callback_url() {
		// Full URLs should return the same full URL.
		$full_url = 'http://mycustomurl.com';
		$callback = $this->instance->get_callback_url( $full_url );
		$this->assertEquals( $full_url, $callback );

		// Files that exist should return the same callback.
		$callback = $this->instance->get_callback_url( 'edit.php' );
		$this->assertEquals( 'edit.php', $callback );
		$callback = $this->instance->get_callback_url( 'edit.php?custom_arg=1' );
		$this->assertEquals( 'edit.php?custom_arg=1', $callback );

		// Custom callbacks should return the callback as an admin page.
		$callback = $this->instance->get_callback_url( 'my-page' );
		$this->assertEquals( 'admin.php?page=my-page', $callback );
	}

	/**
	 * Test the ability to retrieve a parent key.
	 */
	public function test_get_parent_key() {
		global $submenu;
		$submenu['my-parent-page'] = array( // phpcs:ignore WordPress.WP.GlobalVariablesOverride.Prohibited
			array(
				'my-child-item',
				'manage_woocommerce',
				'my-child-item',
			),
		);

		// Items that are already parents should not return parent keys.
		$parent_key = $this->instance->get_parent_key( 'my-parent-page' );
		$this->assertNull( $parent_key );

		// Fake items should not return parent keys.
		$parent_key = $this->instance->get_parent_key( 'not-a-page' );
		$this->assertNull( $parent_key );

		// Children should return their parent's key.
		$parent_key = $this->instance->get_parent_key( 'my-child-item' );
		$this->assertEquals( 'my-parent-page', $parent_key );
	}

	/**
	 * Test adding a menu item.
	 */
	public function test_add_plugin_items() {
		$item = array(
			'id'         => 'test-plugin-item',
			'title'      => 'Test Plugin Item',
			'capability' => 'manage_woocommerce',
			'url'        => 'my-test-page',
		);
		$this->instance->add_plugin_item( $item );

		$items = $this->instance->get_items();
		$this->assertEquals( $item['id'], $items['test-plugin-item']['id'] );
		$this->assertEquals( 'Test Plugin Item', $items['test-plugin-item']['title'] );
	}

	/**
	 * Test adding an existing menu ID.
	 */
	public function test_add_dupliacte_plugin_items() {
		$item = array(
			'id'         => 'test-duplicate-item',
			'title'      => 'Test Duplicate Item',
			'capability' => 'manage_woocommerce',
			'url'        => 'my-duplicate-page',
		);
		$this->instance->add_plugin_item( $item );

		// Test that the duplicate ID does not replace the item.
		$item['title'] = 'Test Updated Title';
		$this->instance->add_plugin_item( $item );
		$items = $this->instance->get_items();
		$this->assertEquals( 'Test Duplicate Item', $items['test-duplicate-item']['title'] );
	}

	/**
	 * Test adding a plugin category.
	 */
	public function test_add_plugin_category() {
		$this->instance->add_plugin_category(
			array(
				'id'         => 'test-plugin-category',
				'title'      => 'Test Plugin Category',
				'capability' => 'manage_woocommerce',
			)
		);

		$this->instance->add_plugin_item(
			array(
				'id'         => 'test-plugin-child',
				'title'      => 'Test Plugin Child',
				'parent'     => 'test-plugin-category',
				'capability' => 'manage_woocommerce',
				'url'        => 'my-test-child',
			)
		);

		$items = $this->instance->get_items();
		$this->assertEquals( 'test-plugin-category', $items['test-plugin-category']['id'] );
		$this->assertEquals( 'test-plugin-child', $items['test-plugin-child']['id'] );
	}

	/**
	 * Test that a plugin item's menu ID gets properly set.
	 */
	public function test_plugin_menus() {
		$this->instance->add_plugin_item(
			array(
				'id'         => 'test-plugin-menu',
				'title'      => 'Test Plugin Category',
				'capability' => 'manage_woocommerce',
			)
		);

		$items = $this->instance->get_items();
		$this->assertEquals( 'plugins', $items['test-plugin-menu']['menuId'] );

		$this->instance->add_plugin_item(
			array(
				'id'         => 'test-plugin-bad-menu',
				'title'      => 'Test Plugin Category',
				'capability' => 'manage_woocommerce',
				'menuId'     => 'primary',
			)
		);

		$items = $this->instance->get_items();
		$this->assertEquals( 'plugins', $items['test-plugin-bad-menu']['menuId'] );
	}

	/**
	 * Test that menu mapping by category works as expected.
	 */
	public function test_get_mapped_menu_items() {
		$this->instance->add_plugin_category(
			array(
				'id'         => 'test-mapped-category',
				'title'      => 'Test Mapped Category',
				'capability' => 'manage_woocommerce',
			)
		);

		$this->instance->add_plugin_item(
			array(
				'id'         => 'test-mapped-item-c',
				'title'      => 'Test Mapped Item C',
				'parent'     => 'test-mapped-category',
				'capability' => 'manage_woocommerce',
				'order'      => 2,
			)
		);

		$this->instance->add_plugin_item(
			array(
				'id'         => 'test-mapped-item-b',
				'title'      => 'Test Mapped Item B',
				'parent'     => 'test-mapped-category',
				'capability' => 'manage_woocommerce',
				'order'      => 1,
			)
		);

		$this->instance->add_plugin_item(
			array(
				'id'         => 'test-mapped-item-a',
				'title'      => 'Test Mapped Item A',
				'parent'     => 'test-mapped-category',
				'capability' => 'manage_woocommerce',
				'order'      => 1,
			)
		);

		$this->instance->add_plugin_item(
			array(
				'id'         => 'test-mapped-permission',
				'title'      => 'Should not be included',
				'parent'     => 'test-mapped-category',
				'capability' => 'no_permission',
			)
		);

		$map = $this->instance->get_mapped_menu_items();

		$this->assertCount( count( $this->instance::MENU_IDS ), $map['test-mapped-category'] );
		foreach ( $this->instance::MENU_IDS as $menu_id ) {
			$this->assertArrayHasKey( $menu_id, $map['test-mapped-category'] );
		}
		$this->assertEquals( 'test-mapped-item-a', $map['test-mapped-category']['plugins'][0]['id'] );
		$this->assertEquals( 'test-mapped-item-b', $map['test-mapped-category']['plugins'][1]['id'] );
		$this->assertEquals( 'test-mapped-item-c', $map['test-mapped-category']['plugins'][2]['id'] );
	}

	/**
	 * Test adding a setting item.
	 */
	public function add_setting_item() {
		$this->instance->add_setting_item(
			array(
				'id'    => 'test-setting-item',
				'title' => 'Test Setting Item',
			)
		);

		$this->instance->add_setting_item(
			array(
				'id'     => 'test-setting-item-bad-parent',
				'title'  => 'Test Bad Parent',
				'parent' => 'woocommerce',
			)
		);

		$this->instance->add_setting_item(
			array(
				'id'     => 'test-setting-item-bad-menu',
				'title'  => 'Test Bad Menu',
				'menuId' => 'primary',
			)
		);

		$items = $this->instance->get_items();
		$this->assertArrayHasKey( $menu_id, $map['test-setting-item'] );
		$this->assertArrayNotHasKey( $menu_id, $map['test-setting-item-bad-parent'] );
		$this->assertArrayNotHasKey( $menu_id, $map['test-setting-item-bad-menu'] );
	}

	/**
	 * Test if adding a menu item can be checked via the callback.
	 */
	public function test_has_callback() {
		$item = array(
			'test-callback-item',
			'manage_woocommerce',
			'test-callback-item',
		);

		$this->assertFalse( $this->instance->has_callback( $item ) );

		$this->instance->add_plugin_item(
			array(
				'id'         => 'test-callback-item',
				'title'      => 'Test Callback Item',
				'capability' => 'manage_woocommerce',
				'url'        => 'test-callback-item',
			)
		);

		$this->assertTrue( $this->instance->has_callback( $item ) );
	}
}
