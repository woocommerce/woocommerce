<?php

/**
 * Unit tests for the WooCommerce class.
 */
class WooCommerce_Test extends \WC_Unit_Test_Case {
	/**
	 * Setup test data. Called before every test.
	 */
	public function setUp(): void {
		parent::setUp();

		$this->user = $this->factory->user->create(
			array(
				'role' => 'administrator',
			)
		);
		wp_set_current_user( $this->user );
	}

	/**
	 * Test that the $api property is defined, public and initialized correctly.
	 */
	public function test_api_property(): void {
		$property = new ReflectionProperty( WooCommerce::class, 'api' );

		$this->assertTrue( $property->isPublic() );
		$this->assertInstanceOf( WC_API::class, $property->getValue( WC() ) );
	}

	/**
	 * Tear down.
	 */
	public function tearDown(): void {
		parent::tearDown();

		delete_option( 'woocommerce_coming_soon' );
		delete_option( 'woocommerce_store_pages_only' );
		delete_option( 'woocommerce_private_link' );
		delete_option( 'woocommerce_share_key' );
	}

	/**
	 * Test for add_lys_default_values method on fresh installation.
	 */
	public function test_add_lys_default_values_on_fresh_installation() {
		update_option( 'fresh_site', '1' );

		$this->set_current_action( 'woocommerce_newly_installed' );
		( WooCommerce::instance() )->add_lys_default_values();

		$this->assertEquals( 'yes', get_option( 'woocommerce_coming_soon' ) );
		$this->assertEquals( 'no', get_option( 'woocommerce_store_pages_only' ) );
		$this->assertEquals( 'no', get_option( 'woocommerce_private_link' ) );
		$this->assertNotEmpty( get_option( 'woocommerce_share_key' ) );
		$this->assertMatchesRegularExpression( '/^[a-zA-Z0-9]{32}$/', get_option( 'woocommerce_share_key' ) );
	}

	/**
	 * Test for add_lys_default_values method on WooCommerce update.
	 */
	public function test_add_lys_default_values_on_woocommerce_update() {
		update_option( 'fresh_site', '0' );

		$this->set_current_action( 'woocommerce_updated' );
		( WooCommerce::instance() )->add_lys_default_values();

		$this->assertEquals( 'no', get_option( 'woocommerce_coming_soon' ) );
		$this->assertEquals( 'yes', get_option( 'woocommerce_store_pages_only' ) );
		$this->assertEquals( 'no', get_option( 'woocommerce_private_link' ) );
		$this->assertNotEmpty( get_option( 'woocommerce_share_key' ) );
		$this->assertMatchesRegularExpression( '/^[a-zA-Z0-9]{32}$/', get_option( 'woocommerce_share_key' ) );
	}

	/**
	 * Test for add_lys_default_values method when options are already set.
	 *
	 */
	public function test_add_lys_default_values_when_options_are_already_set() {
		update_option( 'fresh_site', '0' );
		update_option( 'woocommerce_coming_soon', 'yes' );
		update_option( 'woocommerce_store_pages_only', 'no' );
		update_option( 'woocommerce_private_link', 'yes' );
		update_option( 'woocommerce_share_key', 'test' );

		$this->set_current_action( 'woocommerce_updated' );
		( WooCommerce::instance() )->add_lys_default_values();

		$this->assertEquals( 'yes', get_option( 'woocommerce_coming_soon' ) );
		$this->assertEquals( 'no', get_option( 'woocommerce_store_pages_only' ) );
		$this->assertEquals( 'no', get_option( 'woocommerce_private_link' ) );
		$this->assertEquals( 'test', get_option( 'woocommerce_share_key' ) );
	}

	/**
	 * Helper method to set the current action for testing.
	 *
	 * @param string $action The action to set.
	 */
	private function set_current_action( $action ) {
		global $wp_current_filter;
		$wp_current_filter[] = $action; // phpcs:ignore WordPress.WP.GlobalVariablesOverride.Prohibited
	}
}
