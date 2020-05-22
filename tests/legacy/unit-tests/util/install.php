<?php
/**
 * Tests for the WC_Install class.
 *
 * @package WooCommerce\Tests\Util
 */

// This comment exists to prevent a Squiz.Commenting.FileComment.Missing from phpcs.

require_once __DIR__ . '/helpers/class-wc-install-for-testing.php';

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
	 */
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

	/**
	 * Test - create pages.
	 */
	public function test_create_pages() {
		// Clear options.
		delete_option( 'woocommerce_shop_page_id' );
		delete_option( 'woocommerce_cart_page_id' );
		delete_option( 'woocommerce_checkout_page_id' );
		delete_option( 'woocommerce_myaccount_page_id' );

		WC_Install::create_pages();

		$this->assertGreaterThan( 0, get_option( 'woocommerce_shop_page_id' ) );
		$this->assertGreaterThan( 0, get_option( 'woocommerce_cart_page_id' ) );
		$this->assertGreaterThan( 0, get_option( 'woocommerce_checkout_page_id' ) );
		$this->assertGreaterThan( 0, get_option( 'woocommerce_myaccount_page_id' ) );

		// Delete pages.
		wp_delete_post( get_option( 'woocommerce_shop_page_id' ), true );
		wp_delete_post( get_option( 'woocommerce_cart_page_id' ), true );
		wp_delete_post( get_option( 'woocommerce_checkout_page_id' ), true );
		wp_delete_post( get_option( 'woocommerce_myaccount_page_id' ), true );

		// Clear options.
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

	/**
	 * @testdox Update should complete if there are no errors when altering database schema.
	 */
	public function test_update_is_done_if_no_db_schema_change_errors() {
		global $wpdb;

		delete_transient( 'wc_installing' );
		update_option( 'woocommerce_version', WC()->version );
		update_option( 'woocommerce_db_version', WC()->version );
		$old_version = WC()->version;

		$auto_update_db_hook = function( $default_value ) {
			return true;
		};

		add_filter( 'woocommerce_enable_auto_update_db', $auto_update_db_hook );

		try {
			WC()->version = '1000.0';
			WC_Install_For_Testing::set_schema( 'CREATE TABLE foobar (id INT NOT NULL)' );
			WC_Install_For_Testing::set_update_callbacks( array( '1000.0' => array( 'do_some_data_update' ) ) );

			WC_Install_For_Testing::install();

			$this->assertEquals( '1000.0', get_option( 'woocommerce_version' ) );

			$query_result = $wpdb->query( 'SELECT COUNT(*) FROM foobar' );
			$this->assertNotSame( false, $query_result );

			$expected_scheduled = array( 'do_some_data_update' );
			$this->assertEquals( $expected_scheduled, WC_Install_For_Testing::$scheduled_callbacks );

			$this->assertNull( WC_Install_For_Testing::$error_notice_html );
		} finally {
			WC()->version                                = $old_version;
			WC_Install_For_Testing::$scheduled_callbacks = array();

			$wpdb->query( 'DROP TABLE foobar' );

			remove_filter( 'woocommerce_enable_auto_update_db', $auto_update_db_hook );
		}
	}

	/**
	 * @testxdox Update shouldn't complete (remaining schema changes are skipped, no update functions are scheduled, Woo version isn't updated) if there are errors when altering database schema.
	 */
	public function test_update_is_not_done_if_there_are_db_schema_change_errors() {
		global $wpdb;

		delete_transient( 'wc_installing' );
		update_option( 'woocommerce_version', WC()->version );
		update_option( 'woocommerce_db_version', WC()->version );
		$old_version = WC()->version;

		$auto_update_db_hook = function( $default_value ) {
			return true;
		};

		$create_table_attempts = array();
		$query_hook            = function( $query ) use ( &$create_table_attempts ) {
			if ( preg_match( '|CREATE .* TABLE ([^ ]*)|', $query, $matches ) ) {
				$create_table_attempts[] = $matches[1];
			}
			return $query;
		};

		add_filter( 'woocommerce_enable_auto_update_db', $auto_update_db_hook );
		add_filter( 'query', $query_hook );

		try {
			WC()->version = '1000.0';
			WC_Install_For_Testing::set_schema( "CREATE TABLE foobar (id INT);\nCREATE TABLE fizzbuzz (id INT);" );
			WC_Install_For_Testing::set_update_callbacks( array( '1000.0' => array( 'do_some_needed_update' ) ) );

			$wpdb->query( "CREATE USER 'theuser'@'%' IDENTIFIED BY 'thepassword'" );
			$wpdb->query( "GRANT SELECT,UPDATE,INSERT,DELETE ON *.* TO 'theuser'" );

			$wpdb_for_restricted_user = new wpdb( 'theuser', 'thepassword', $wpdb->dbname, $wpdb->dbhost );
			$wpdb_for_restricted_user->suppress_errors();
			WC_Install_For_Testing::$wpdb_instance = $wpdb_for_restricted_user;

			WC_Install_For_Testing::install();

			$wpdb->suppress_errors();

			// Table has effectively not been created.
			$query_result = $wpdb->query( 'SELECT COUNT(*) FROM foobar' );
			$this->assertSame( false, $query_result );

			// Remaining schema change statements have been skipped.
			$this->assertEquals( array( 'foobar' ), $create_table_attempts );

			// Woo version has not been changed.
			$this->assertEquals( $old_version, get_option( 'woocommerce_version' ) );

			// No update functions have been scheduled.
			$this->assertEmpty( WC_Install_For_Testing::$scheduled_callbacks );

			// An error notice has been generated.
			$expected_notice = '<div class="error"><p>WooCommerce database schema update failed, update not completed. Table: <code>foobar</code>, error: <code>Something went wrong in the database</code></p></div>';
			$this->assertEquals( $expected_notice, WC_Install_For_Testing::$error_notice_html );
		} finally {
			WC()->version                                = $old_version;
			WC_Install_For_Testing::$scheduled_callbacks = array();

			$wpdb->query( 'DROP TABLE foobar' );
			$wpdb->query( 'DROP TABLE fizzbuzz' );

			$wpdb->suppress_errors( false );

			$wpdb->query( "DROP USER 'theuser'" );

			remove_filter( 'woocommerce_enable_auto_update_db', $auto_update_db_hook );
			remove_filter( 'query', $query_hook );
		}
	}
}
