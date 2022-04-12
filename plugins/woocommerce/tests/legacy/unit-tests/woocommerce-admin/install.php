<?php
/**
 * Install tests
 *
 * @package WooCommerce\Admin\Tests
 */

/**
 * Tests for \Automattic\WooCommerce\Internal\Admin\Install class.
 */
class WC_Admin_Tests_Install extends WP_UnitTestCase {

	const VERSION_OPTION = 'woocommerce_admin_version';

	/**
	 * Integration test for database table creation.
	 *
	 * @group database
	 */
	public function test_create_tables() {
		global $wpdb;

		// Remove the Test Suite’s use of temporary tables https://wordpress.stackexchange.com/a/220308.
		remove_filter( 'query', array( $this, '_create_temporary_tables' ) );
		remove_filter( 'query', array( $this, '_drop_temporary_tables' ) );

		// List of tables created by Install::create_tables.
		$tables = array(
			"{$wpdb->prefix}wc_order_stats",
			"{$wpdb->prefix}wc_order_product_lookup",
			"{$wpdb->prefix}wc_order_tax_lookup",
			"{$wpdb->prefix}wc_order_coupon_lookup",
			"{$wpdb->prefix}wc_admin_notes",
			"{$wpdb->prefix}wc_admin_note_actions",
			"{$wpdb->prefix}wc_customer_lookup",
			"{$wpdb->prefix}wc_category_lookup",
		);

		// Remove any existing tables in the environment.
		$query = 'DROP TABLE IF EXISTS ' . implode( ',', $tables );
		$wpdb->query( $query ); // phpcs:ignore.

		WC_Install::create_tables();
		$result = $wpdb->get_col( "SHOW TABLES LIKE '{$wpdb->prefix}%'" );

		// Check all the tables exist.
		foreach ( $tables as $table ) {
			$this->assertContains( $table, $result );
		}
	}


	/**
	 * Run maybe_update_db_version and confirm the expected jobs are pushed to the queue.
	 *
	 * @dataProvider db_update_version_provider
	 *
	 * @param string $db_update_version WC version to test.
	 * @param int    $expected_jobs_count # of expected jobs.
	 *
	 * @return void
	 */
	public function test_running_db_updates( $db_update_version, $expected_jobs_count ) {
		update_option( 'woocommerce_db_version', $db_update_version );
		add_filter(
			'woocommerce_enable_auto_update_db',
			function() {
				return true;
			}
		);

		$class  = new ReflectionClass( WC_Install::class );
		$method = $class->getMethod( 'maybe_update_db_version' );
		$method->setAccessible( true );
		$method->invoke( $class );

		$pending_jobs = WC_Helper_Queue::get_all_pending();
		$pending_jobs = array_filter(
			$pending_jobs,
			function( $pending_job ) {
				return $pending_job->get_hook() === 'woocommerce_run_update_callback';
			}
		);

		$this->assertCount( $expected_jobs_count, $pending_jobs );
	}


	/**
	 * Ensure that a DB version callback is defined when there are updates.
	 */
	public function test_db_update_callbacks_exist() {
		$all_callbacks = \WC_Install::get_db_update_callbacks();

		foreach ( $all_callbacks as $version => $version_callbacks ) {
			// Verify all callbacks have been defined.
			foreach ( $version_callbacks as $version_callback ) {
				if ( strpos( $version_callback, 'wc_admin_update' ) === 0 ) {
					$this->assertTrue(
						function_exists( $version_callback ),
						"Callback {$version_callback}() is not defined."
					);
				}
			}
		}
	}

	/**
	 * By the time we hit this test method, we should have the following cron jobs.
	 * - wc_admin_daily
	 * - generate_category_lookup_table
	 *
	 * @return void
	 */
	public function test_cron_job_creation() {
		$this->assertNotFalse( wp_next_scheduled( 'wc_admin_daily' ) );
		$this->assertNotFalse( wp_next_scheduled( 'generate_category_lookup_table' ) );
	}

	/**
	 * Data provider that returns DB Update version string and # of expected pending jobs.
	 *
	 * @return array[]
	 */
	public function db_update_version_provider() {
		return array(
			// [DB Update version string, # of expected pending jobs]
			array( '3.9.0', 34 ),
			array( '4.0.0', 27 ),
			array( '4.4.0', 22 ),
			array( '4.5.0', 20 ),
			array( '5.0.0', 16 ),
			array( '5.6.0', 14 ),
			array( '6.0.0', 7 ),
			array( '6.3.0', 4 ),
			array( '6.4.0', 0 ),
		);
	}

	/**
	 * Test missed DB version number update.
	 * See: https:// github.com/woocommerce/woocommerce-admin/issues/5058
	 */
	public function test_missed_version_number_update() {
		$this->markTestSkipped( 'We no longer update WooCommerce Admin versions' );
		$old_version = '1.6.0'; // This should get updated to later versions as we add more migrations.

		// Simulate an upgrade from an older version.
		update_option( self::VERSION_OPTION, '1.6.0' );
		WC_Install::install();
		WC_Helper_Queue::run_all_pending();

		// Simulate a collision/failure in version updating.
		update_option( self::VERSION_OPTION, '1.6.0' );

		// The next update check should force update the skipped version number.
		WC_Install::install();
		$this->assertTrue( version_compare( $old_version, get_option( self::VERSION_OPTION ), '<' ) );

		// The following update check should bump the version to the current (no migrations left).
		WC_Install::install();
		$this->assertEquals( get_option( self::VERSION_OPTION ), WC_ADMIN_VERSION_NUMBER );
	}

	/**
	 * Test the following options are created.
	 *
	 * - woocommerce_admin_install_timestamp
	 *
	 * @return void
	 */
	public function test_options_are_set() {
		delete_transient( 'wc_installing' );
		WC_Install::install();
		$options = array( 'woocommerce_admin_install_timestamp' );
		foreach ( $options as $option ) {
			$this->assertNotFalse( get_option( $option ) );
		}
	}

	/**
	 * Test woocommerce_admin_installed action.
	 * @return void
	 */
	public function test_woocommerce_admin_installed_action() {
		delete_transient( 'wc_installing' );
		WC_Install::install();
		$this->assertTrue( did_action( 'woocommerce_admin_installed' ) > 0 );
	}

	/**
	 * Test woocommerce_updated action gets fired.
	 *
	 * @return void
	 */
	public function test_woocommerce_updated_action() {
		$versions     = array_keys( WC_Install::get_db_update_callbacks() );
		$prev_version = $versions[ count( $versions ) - 2 ];
		update_option( 'woocommerce_version', $prev_version );
		WC_Install::check_version();
		$this->assertTrue( did_action( 'woocommerce_updated' ) > 0 );
	}

	/**
	 * Test woocommerce_newly_installed action gets fired.
	 * @return void
	 */
	public function test_woocommerce_newly_installed_action() {
		delete_option( 'woocommerce_version' );
		WC_Install::check_version();
		$this->assertTrue( did_action( 'woocommerce_newly_installed' ) > 0 );
	}

}
