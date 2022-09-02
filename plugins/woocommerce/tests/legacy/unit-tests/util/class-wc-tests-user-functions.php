<?php
/**
 * Unit tests for the user functions.
 *
 * @package WooCommerce\Tests\Util
 * @since 3.4.6
 */

/**
 * Core function unit tests.
 */
class WC_Tests_User_Functions extends WC_Unit_Test_Case {

	/**
	 * Test the logic of wc_modify_editable_roles.
	 *
	 * @since 3.4.6
	 */
	public function test_wc_modify_editable_roles() {
		$password = wp_generate_password();

		$admin_id = wp_insert_user(
			array(
				'user_login' => 'test_admin',
				'user_pass'  => $password,
				'user_email' => 'admin@example.com',
				'role'       => 'administrator',
			)
		);

		$editor_id = wp_insert_user(
			array(
				'user_login' => 'test_editor',
				'user_pass'  => $password,
				'user_email' => 'editor@example.com',
				'role'       => 'editor',
			)
		);

		$manager_id = wp_insert_user(
			array(
				'user_login' => 'test_manager',
				'user_pass'  => $password,
				'user_email' => 'manager@example.com',
				'role'       => 'shop_manager',
			)
		);

		// Admins should be able to edit anyone.
		wp_set_current_user( $admin_id );
		$admin_editable_roles = array_keys( get_editable_roles() );
		$this->assertContains( 'administrator', $admin_editable_roles );
		$this->assertContains( 'editor', $admin_editable_roles );
		$this->assertContains( 'shop_manager', $admin_editable_roles );
		$this->assertContains( 'customer', $admin_editable_roles );

		// Editors should be able to edit non-admins.
		wp_set_current_user( $editor_id );
		$editor_editable_roles = array_keys( get_editable_roles() );
		$this->assertNotContains( 'administrator', $editor_editable_roles );
		$this->assertContains( 'editor', $editor_editable_roles );
		$this->assertContains( 'shop_manager', $editor_editable_roles );
		$this->assertContains( 'customer', $editor_editable_roles );

		// Shop manager should only be able to edit customers.
		wp_set_current_user( $manager_id );
		$manager_editable_roles = array_keys( get_editable_roles() );
		$this->assertEquals( array( 'customer' ), $manager_editable_roles );
	}

	/**
	 * Test the logic of wc_modify_map_meta_cap.
	 *
	 * @since 3.4.6
	 */
	public function test_wc_modify_map_meta_cap() {
		$password = wp_generate_password();

		$admin_id = wp_insert_user(
			array(
				'user_login' => 'test_admin',
				'user_pass'  => $password,
				'user_email' => 'admin@example.com',
				'role'       => 'administrator',
			)
		);

		$editor_id = wp_insert_user(
			array(
				'user_login' => 'test_editor',
				'user_pass'  => $password,
				'user_email' => 'editor@example.com',
				'role'       => 'editor',
			)
		);

		$manager_id = wp_insert_user(
			array(
				'user_login' => 'test_manager',
				'user_pass'  => $password,
				'user_email' => 'manager@example.com',
				'role'       => 'shop_manager',
			)
		);

		$customer_id = wp_insert_user(
			array(
				'user_login' => 'test_customer',
				'user_pass'  => $password,
				'user_email' => 'customer@example.com',
				'role'       => 'customer',
			)
		);

		// Admins should be able to edit or promote anyone.
		wp_set_current_user( $admin_id );
		$caps = map_meta_cap( 'edit_user', $admin_id, $editor_id );
		$this->assertEquals( array( 'edit_users' ), $caps );
		$caps = map_meta_cap( 'promote_user', $admin_id, $manager_id );
		$this->assertEquals( array( 'promote_users' ), $caps );

		// Shop managers should only be able to edit themselves or customers.
		wp_set_current_user( $manager_id );
		$caps = map_meta_cap( 'edit_user', $manager_id, $admin_id );
		$this->assertContains( 'do_not_allow', $caps );
		$caps = map_meta_cap( 'edit_user', $manager_id, $editor_id );
		$this->assertContains( 'do_not_allow', $caps );
		$caps = map_meta_cap( 'edit_user', $manager_id, $customer_id );
		$this->assertEquals( array( 'edit_users' ), $caps );
	}

	/**
	 * Test wc_shop_manager_has_capability function.
	 *
	 * @since 3.5.4
	 */
	public function test_wc_shop_manager_has_capability() {
		$password = wp_generate_password();

		$manager_id = wp_insert_user(
			array(
				'user_login' => 'test_manager',
				'user_pass'  => $password,
				'user_email' => 'manager@example.com',
				'role'       => 'shop_manager',
			)
		);
		$manager    = new WP_User( $manager_id );

		$editor_id = wp_insert_user(
			array(
				'user_login' => 'test_editor',
				'user_pass'  => $password,
				'user_email' => 'editor@example.com',
				'role'       => 'editor',
			)
		);
		$editor    = new WP_User( $editor_id );

		// Test capabilities translation is working correctly and only gives shop managers capabilities.
		$this->assertTrue( $manager->has_cap( 'edit_users' ) );
		$this->assertFalse( $editor->has_cap( 'edit_users' ) );

		// Unhook the capability translation function to simulate WooCommerce getting deactivated.
		remove_filter( 'user_has_cap', 'wc_shop_manager_has_capability' );

		$this->assertFalse( $manager->has_cap( 'edit_users' ) );

		// Rehook function.
		add_filter( 'user_has_cap', 'wc_shop_manager_has_capability', 10, 4 );
	}
}
