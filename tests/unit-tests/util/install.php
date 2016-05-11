<?php

/**
 * Class WC_Tests_Install.
 * @package WooCommerce\Tests\Util
 *
 * @todo determine if this should be in Util or separate namespace
 */
class WC_Tests_Install extends WC_Unit_Test_Case {

	/**
	 * Test check version.
	 */
	public function test_check_version() {
		update_option( 'woocommerce_version', WC()->version - 1 );
		update_option( 'woocommerce_db_version', WC()->version );
		WC_Install::check_version();

		$this->assertTrue( did_action( 'woocommerce_updated' ) === 1 );

		update_option( 'woocommerce_version', WC()->version );
		update_option( 'woocommerce_db_version', WC()->version );
		WC_Install::check_version();

		$this->assertTrue( did_action( 'woocommerce_updated' ) === 1 );
	}

	/**
	 * Test - install.
	 */
	public function test_install() {
		// clean existing install first
		if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
			define( 'WP_UNINSTALL_PLUGIN', true );
			update_option( 'woocommerce_status_options', array( 'uninstall_data' => 1 ) );
		}

		include( dirname( dirname( dirname( dirname( __FILE__ ) ) ) ) . '/uninstall.php' );

		WC_Install::install();

		$this->assertTrue( get_option( 'woocommerce_version' ) === WC()->version );
	}

	/**
	 * Test - create pages.
	 */
	public function test_create_pages() {
		// Clear options
		delete_option( 'woocommerce_shop_page_id' );
		delete_option( 'woocommerce_cart_page_id' );
		delete_option( 'woocommerce_checkout_page_id' );
		delete_option( 'woocommerce_myaccount_page_id' );

		WC_Install::create_pages();

		$this->assertGreaterThan( 0, get_option( 'woocommerce_shop_page_id' ) );
		$this->assertGreaterThan( 0, get_option( 'woocommerce_cart_page_id' ) );
		$this->assertGreaterThan( 0, get_option( 'woocommerce_checkout_page_id' ) );
		$this->assertGreaterThan( 0, get_option( 'woocommerce_myaccount_page_id' ) );

		// Delete pages
		wp_delete_post( get_option( 'woocommerce_shop_page_id' ), true );
		wp_delete_post( get_option( 'woocommerce_cart_page_id' ), true );
		wp_delete_post( get_option( 'woocommerce_checkout_page_id' ), true );
		wp_delete_post( get_option( 'woocommerce_myaccount_page_id' ), true );

		// Clear options
		delete_option( 'woocommerce_shop_page_id' );
		delete_option( 'woocommerce_cart_page_id' );
		delete_option( 'woocommerce_checkout_page_id' );
		delete_option( 'woocommerce_myaccount_page_id' );

		WC_Install::create_pages();

		$this->assertGreaterThan( 0, get_option( 'woocommerce_shop_page_id' ) );
		$this->assertGreaterThan( 0, get_option( 'woocommerce_cart_page_id' ) );
		$this->assertGreaterThan( 0, get_option( 'woocommerce_checkout_page_id' ) );
		$this->assertGreaterThan( 0, get_option( 'woocommerce_myaccount_page_id' ) );
	}

	/**
	 * Test - create roles.
	 */
	public function test_create_roles() {
		// Clean existing install first
		if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
			define( 'WP_UNINSTALL_PLUGIN', true );
			update_option( 'woocommerce_status_options', array( 'uninstall_data' => 1 ) );
		}
		include( dirname( dirname( dirname( dirname( __FILE__ ) ) ) ) . '/uninstall.php' );

		WC_Install::create_roles();

		$this->assertNotNull( get_role( 'customer' ) );
		$this->assertNotNull( get_role( 'shop_manager' ) );
	}

	/**
	 * Test - remove roles.
	 */
	public function test_remove_roles() {
		WC_Install::remove_roles();

		$this->assertNull( get_role( 'customer' ) );
		$this->assertNull( get_role( 'shop_manager' ) );
	}

	/**
	 * Test - in_plugin_update_message.
	 */
	public function test_in_plugin_update_message() {
		ob_start();
		WC_install::in_plugin_update_message( array( 'Version' => '2.0.0', 'new_version' => '2.0.0' ) );
		$result = ob_get_clean();
		$this->assertTrue( is_string( $result ) );
	}

}
