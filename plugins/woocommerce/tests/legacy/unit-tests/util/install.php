<?php
/**
 * Tests for the WC_Install class.
 *
 * @package WooCommerce\Tests\Util
 */

/**
 * Class WC_Tests_Install.
 *
 * @covers WC_Install
 */
class WC_Tests_Install extends WC_Unit_Test_Case {

	/**
	 * Test check version.
	 */
	public function test_check_version() {
		update_option( 'woocommerce_version', ( (float) WC()->version - 1 ) );
		WC_Install::check_version();

		$this->assertTrue( did_action( 'woocommerce_updated' ) === 1 );

		update_option( 'woocommerce_version', WC()->version );
		WC_Install::check_version();

		$this->assertTrue( did_action( 'woocommerce_updated' ) === 1 );

		update_option( 'woocommerce_version', (float) WC()->version + 1 );
		WC_Install::check_version();

		$this->assertTrue(
			did_action( 'woocommerce_updated' ) === 1,
			'WC_Install::check_version() should not call install routine when the WC version stored in the database is bigger than the version in the code as downgrades are not supported.'
		);
	}

	/**
	 * Test - install.
	public function test_install() {
		// clean existing install first.
		if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
			define( 'WP_UNINSTALL_PLUGIN', true );
			define( 'WC_REMOVE_ALL_DATA', true );
		}

		include dirname( dirname( dirname( dirname( dirname( __FILE__ ) ) ) ) ) . '/uninstall.php';
		delete_transient( 'wc_installing' );

		WC_Install::install();

		$this->assertEquals( WC()->version, get_option( 'woocommerce_version' ) );
	}
	 *
	 **/

	/**
	 * Test - create pages.
	 */
	public function test_create_pages() {
		// Clear options.
		delete_option( 'woocommerce_shop_page_id' );
		delete_option( 'woocommerce_cart_page_id' );
		delete_option( 'woocommerce_checkout_page_id' );
		delete_option( 'woocommerce_myaccount_page_id' );
		delete_option( 'woocommerce_refund_returns_page_id' );

		WC_Install::create_pages();

		$this->assertGreaterThan( 0, get_option( 'woocommerce_shop_page_id' ) );
		$this->assertGreaterThan( 0, get_option( 'woocommerce_cart_page_id' ) );
		$this->assertGreaterThan( 0, get_option( 'woocommerce_checkout_page_id' ) );
		$this->assertGreaterThan( 0, get_option( 'woocommerce_myaccount_page_id' ) );
		$this->assertGreaterThan( 0, get_option( 'woocommerce_refund_returns_page_id' ) );

		// Delete pages.
		wp_delete_post( get_option( 'woocommerce_shop_page_id' ), true );
		wp_delete_post( get_option( 'woocommerce_cart_page_id' ), true );
		wp_delete_post( get_option( 'woocommerce_checkout_page_id' ), true );
		wp_delete_post( get_option( 'woocommerce_myaccount_page_id' ), true );
		wp_delete_post( get_option( 'woocommerce_refund_returns_page_id' ), true );

		// Clear options.
		delete_option( 'woocommerce_shop_page_id' );
		delete_option( 'woocommerce_cart_page_id' );
		delete_option( 'woocommerce_checkout_page_id' );
		delete_option( 'woocommerce_myaccount_page_id' );
		delete_option( 'woocommerce_refund_returns_page_id' );

		WC_Install::create_pages();

		$this->assertGreaterThan( 0, get_option( 'woocommerce_shop_page_id' ) );
		$this->assertGreaterThan( 0, get_option( 'woocommerce_cart_page_id' ) );
		$this->assertGreaterThan( 0, get_option( 'woocommerce_checkout_page_id' ) );
		$this->assertGreaterThan( 0, get_option( 'woocommerce_myaccount_page_id' ) );
		$this->assertGreaterThan( 0, get_option( 'woocommerce_refund_returns_page_id' ) );
	}

	/**
	 * Test - create roles.
	 */
	public function test_create_roles() {
		// Clean existing install first.
		if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
			define( 'WP_UNINSTALL_PLUGIN', true );
			define( 'WC_REMOVE_ALL_DATA', true );
		}
		include dirname( dirname( dirname( dirname( dirname( __FILE__ ) ) ) ) ) . '/uninstall.php';

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
	 * Make sure the list of tables returned by WC_Install::get_tables() and used when uninstalling the plugin
	 * or deleting a site in a multi site install is not missing any of the WC tables. If a table is added to
	 * WC_Install:get_schema() but not to WC_Install::get_tables(), this test will fail.
	 *
	 * @group core-only
	 */
	public function test_get_tables() {
		global $wpdb;

		$tables = $wpdb->get_col(
			"SHOW TABLES WHERE `Tables_in_{$wpdb->dbname}` LIKE '{$wpdb->prefix}woocommerce\_%'
			OR `Tables_in_{$wpdb->dbname}` LIKE '{$wpdb->prefix}wc\_%'"
		);
		$result = WC_Install::get_tables();
		$diff   = array_diff( $result, $tables );

		$this->assertEmpty(
			$diff,
			sprintf(
				'The following table(s) were returned from WC_Install::get_tables() but do not exist: %s',
				implode( ', ', $diff )
			)
		);
	}

	/**
	 * Test - get tables should apply the woocommerce_install_get_tables filter.
	 */
	public function test_get_tables_enables_filter() {
		$this->assertNotContains( 'some_table_name', WC_Install::get_tables() );

		add_filter(
			'woocommerce_install_get_tables',
			function ( $tables ) {
				$tables[] = 'some_table_name';

				return $tables;
			}
		);

		$this->assertContains( 'some_table_name', WC_Install::get_tables() );
	}
}
