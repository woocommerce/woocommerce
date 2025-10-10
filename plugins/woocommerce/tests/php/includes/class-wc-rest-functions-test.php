<?php
declare( strict_types = 1);

// phpcs:disable Squiz.Classes.ClassFileName.NoMatch -- backcompat nomenclature.
// phpcs:disable Squiz.Commenting.VariableComment.Missing -- Factory properties are inherited from parent class.
// phpcs:disable WordPress.NamingConventions.ValidVariableName.UsedPropertyNotSnakeCase -- Factory properties use camelCase.

/**
 * Tests for wc-rest-functions.php.
 * Class WC_Rest_Functions_Test.
 */
class WC_Rest_Functions_Test extends WC_REST_Unit_Test_Case {

	/**
	 * Set up test environment before each test
	 */
	public function setUp(): void {
		parent::setUp();

		$GLOBALS['wp']             = new stdClass(); // phpcs:ignore WordPress.WP.GlobalVariablesOverride.Prohibited
		$GLOBALS['wp']->query_vars = array(); // phpcs:ignore WordPress.WP.GlobalVariablesOverride.Prohibited
	}

	/**
	 * Clean up after each test
	 */
	public function tearDown(): void {
		parent::tearDown();

		unset( $GLOBALS['wp'] ); // phpcs:ignore WordPress.WP.GlobalVariablesOverride.Prohibited
	}

	/**
	 * @testDox All namespaces are loaded for unknown path.
	 */
	public function test_wc_rest_should_load_namespace_unknown() {
		$this->assertTrue( wc_rest_should_load_namespace( 'wc/v1', 'wc/unknown' ) );
		$this->assertTrue( wc_rest_should_load_namespace( 'wc-analytics', 'wc/unknown' ) );
		$this->assertTrue( wc_rest_should_load_namespace( 'wc-telemetry', 'wc/unknown' ) );
		$this->assertTrue( wc_rest_should_load_namespace( 'wc-random', 'wc/unknown' ) );
	}

	/**
	 * @testDox Only required namespace is loaded for known path.
	 */
	public function test_wc_rest_should_load_namespace_known() {
		$this->assertFalse( wc_rest_should_load_namespace( 'wc/v1', 'wc/v2' ) );
		$this->assertFalse( wc_rest_should_load_namespace( 'wc-analytics', 'wc/v2' ) );
		$this->assertTrue( wc_rest_should_load_namespace( 'wc/v2', 'wc/v2' ) );
	}

	/**
	 * @testDox Test wc_rest_should_load_namespace known works with preload.
	 */
	public function test_wc_rest_should_load_namespace_known_works_with_preload() {
		$memo = rest_preload_api_request( array(), '/wc/store/v1/cart' );
		$this->assertArrayHasKey( '/wc/store/v1/cart', $memo );
	}

	/**
	 * @testDox Test wc_rest_should_load_namespace filter.
	 */
	public function test_wc_rest_should_load_namespace_filter() {
		$this->assertFalse( wc_rest_should_load_namespace( 'wc/v1', 'wc/v2' ) );
		add_filter( 'wc_rest_should_load_namespace', '__return_true' );
		$this->assertTrue( wc_rest_should_load_namespace( 'wc/v1', 'wc/v2' ) );
		remove_filter( 'wc_rest_should_load_namespace', '__return_true' );
	}

	/**
	 * @testDox Test wc_rest_check_post_permissions with different contexts.
	 */
	public function test_wc_rest_check_post_permissions_contexts() {
		// Test with no user logged in - should return false for all contexts.
		$this->assertFalse( wc_rest_check_post_permissions( 'shop_order', 'read' ) );
		$this->assertFalse( wc_rest_check_post_permissions( 'shop_order', 'create' ) );
		$this->assertFalse( wc_rest_check_post_permissions( 'shop_order', 'edit' ) );
		$this->assertFalse( wc_rest_check_post_permissions( 'shop_order', 'delete' ) );
		$this->assertFalse( wc_rest_check_post_permissions( 'shop_order', 'batch' ) );

		// Test with admin user.
		$admin_user = $this->factory->user->create( array( 'role' => 'administrator' ) );
		wp_set_current_user( $admin_user );

		$this->assertTrue( wc_rest_check_post_permissions( 'shop_order', 'read' ) );
		$this->assertTrue( wc_rest_check_post_permissions( 'shop_order', 'create' ) );
		$this->assertTrue( wc_rest_check_post_permissions( 'shop_order', 'edit' ) );
		$this->assertTrue( wc_rest_check_post_permissions( 'shop_order', 'delete' ) );
		$this->assertTrue( wc_rest_check_post_permissions( 'shop_order', 'batch' ) );

		wp_set_current_user( 0 );
	}

	/**
	 * @testDox Test wc_rest_check_post_permissions with different post types.
	 */
	public function test_wc_rest_check_post_permissions_post_types() {
		$admin_user = $this->factory->user->create( array( 'role' => 'administrator' ) );
		wp_set_current_user( $admin_user );

		// Test with valid post types.
		$this->assertTrue( wc_rest_check_post_permissions( 'shop_order', 'read' ) );
		$this->assertTrue( wc_rest_check_post_permissions( 'product', 'read' ) );
		$this->assertTrue( wc_rest_check_post_permissions( 'post', 'read' ) );

		// Test with revision post type - should always return false.
		$this->assertFalse( wc_rest_check_post_permissions( 'revision', 'read' ) );
		$this->assertFalse( wc_rest_check_post_permissions( 'revision', 'edit' ) );

		// Test with invalid post type.
		$this->assertFalse( wc_rest_check_post_permissions( 'invalid_post_type', 'read' ) );

		wp_set_current_user( 0 );
	}

	/**
	 * @testDox Test wc_rest_check_post_permissions with object-level permissions.
	 */
	public function test_wc_rest_check_post_permissions_object_id() {
		$admin_user = $this->factory->user->create( array( 'role' => 'administrator' ) );
		wp_set_current_user( $admin_user );

		// Create a test post.
		$post_id = $this->factory->post->create( array( 'post_type' => 'shop_order' ) );

		// Test with object ID for different contexts.
		$this->assertTrue( wc_rest_check_post_permissions( 'shop_order', 'read', $post_id ) );
		$this->assertTrue( wc_rest_check_post_permissions( 'shop_order', 'edit', $post_id ) );
		$this->assertTrue( wc_rest_check_post_permissions( 'shop_order', 'delete', $post_id ) );

		// Test with non-existent object ID.
		// Should return false based on https://github.com/WordPress/wordpress-develop/blob/6.8.3/src/wp-includes/capabilities.php#L302.
		$this->assertFalse( wc_rest_check_post_permissions( 'shop_order', 'read', 99999 ) );

		wp_set_current_user( 0 );
	}

	/**
	 * @testDox Test wc_rest_check_post_permissions with different user roles.
	 */
	public function test_wc_rest_check_post_permissions_user_roles() {
		// Test with subscriber (no capabilities).
		$subscriber = $this->factory->user->create( array( 'role' => 'subscriber' ) );
		wp_set_current_user( $subscriber );

		$this->assertFalse( wc_rest_check_post_permissions( 'shop_order', 'read' ) );
		$this->assertFalse( wc_rest_check_post_permissions( 'shop_order', 'create' ) );
		$this->assertFalse( wc_rest_check_post_permissions( 'shop_order', 'edit' ) );

		// Test with editor (has some capabilities).
		$editor = $this->factory->user->create( array( 'role' => 'editor' ) );
		wp_set_current_user( $editor );

		$this->assertTrue( wc_rest_check_post_permissions( 'post', 'read' ) );
		$this->assertTrue( wc_rest_check_post_permissions( 'post', 'create' ) );
		$this->assertTrue( wc_rest_check_post_permissions( 'post', 'edit' ) );

		// Editor should not have shop_order capabilities.
		$this->assertFalse( wc_rest_check_post_permissions( 'shop_order', 'read' ) );

		// Test with shop_manager.
		$shop_manager = $this->factory->user->create( array( 'role' => 'shop_manager' ) );
		wp_set_current_user( $shop_manager );

		$this->assertTrue( wc_rest_check_post_permissions( 'shop_order', 'read' ) );
		$this->assertTrue( wc_rest_check_post_permissions( 'shop_order', 'create' ) );
		$this->assertTrue( wc_rest_check_post_permissions( 'shop_order', 'edit' ) );

		wp_set_current_user( 0 );
	}
}
