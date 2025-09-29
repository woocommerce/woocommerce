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
	 * Restore test environment after class completion.
	 */
	public static function tearDownAfterClass(): void {
		parent::tearDownAfterClass();

		// Reinstall WooCommerce to ensure test environment is clean.
		WC_Install::install();

		// Reload capabilities after install, see https://core.trac.wordpress.org/ticket/28374.
		if ( version_compare( $GLOBALS['wp_version'], '4.7', '<' ) ) {
			$GLOBALS['wp_roles']->reinit();
		} else {
			$GLOBALS['wp_roles'] = null; // phpcs:ignore WordPress.WP.GlobalVariablesOverride.Prohibited
			wp_roles();
		}
	}

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
		// Clean existing install first.
		self::uninstall();

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
		self::uninstall();

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
		// Make WC_Install::get_schema() accessible.
		$wc_install = new \ReflectionClass( WC_Install::class );
		$get_schema = $wc_install->getMethod( 'get_schema' );
		$get_schema->setAccessible( true );
		$schema = $get_schema->invoke( null );
		preg_match_all( '/CREATE TABLE (.*?)\s*\(/i', $schema, $matches, PREG_PATTERN_ORDER );

		$this->assertNotEmpty( $matches );
		$this->assertNotEmpty( $matches[1] );

		$tables_from_schema = $matches[1];
		$tables_to_remove   = WC_Install::get_tables();
		$diff               = array_diff( $tables_from_schema, $tables_to_remove );

		$this->assertEmpty(
			$diff,
			sprintf(
				'The following table(s) were returned from WC_Install::get_schema() but are not listed in WC_Install::get_tables(): %s',
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
	 * Uninstall the plugin.
	 */
	private static function uninstall() {
		if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
			define( 'WP_UNINSTALL_PLUGIN', true );
			define( 'WC_REMOVE_ALL_DATA', true );
		}

		include dirname( dirname( dirname( dirname( __DIR__ ) ) ) ) . '/uninstall.php';
		delete_transient( 'wc_installing' );
	}
}
