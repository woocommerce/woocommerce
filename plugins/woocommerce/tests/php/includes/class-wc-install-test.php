<?php

use Automattic\WooCommerce\Admin\Notes\Note;

/**
 * Class WC_Install_Test.
 */
class WC_Install_Test extends \WC_Unit_Test_Case {

	/**
	 * Test if verify base table can detect missing table and adds/remove a notice.
	 */
	public function test_verify_base_tables_adds_and_remove_notice() {
		global $wpdb;

		// Remove drop filter because we do want to drop temp table if it exists.
		// This filter was added to only allow dropping temporary tables which will then be rollbacked after the test.
		remove_filter( 'query', array( $this, '_drop_temporary_tables' ) );

		$original_table_name = "{$wpdb->prefix}wc_tax_rate_classes";
		$changed_table_name  = "{$wpdb->prefix}wc_tax_rate_classes_2";
		$clear_query         = 'DROP TABLE IF EXISTS %s;';
		$rename_table_query  = 'RENAME TABLE %s to %s;';

		// Workaround to call a private function.
		$schema = function () {
			return static::get_schema();
		};

		// Rename a base table to simulate it as non-existing.
		dbDelta( $schema->call( new \WC_Install() ) ); // Restore correct state.
		$wpdb->query( sprintf( $clear_query, $changed_table_name ) ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
		$wpdb->query( sprintf( $rename_table_query, $original_table_name, $changed_table_name ) ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared

		$missing_tables = \WC_Install::verify_base_tables();

		$wpdb->query( sprintf( $rename_table_query, $changed_table_name, $original_table_name ) ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
		add_filter( 'query', array( $this, '_drop_temporary_tables' ) );

		$this->assertContains( $original_table_name, $missing_tables );
		$this->assertContains( 'base_tables_missing', \WC_Admin_Notices::get_notices() );

		// Ideally, no missing table anymore because we have switched back table name.
		$missing_tables = \WC_Install::verify_base_tables();

		$this->assertNotContains( $original_table_name, $missing_tables );
		$this->assertNotContains( 'base_tables_missing', \WC_Admin_Notices::get_notices() );
	}


	/**
	 * Test if verify base table can fix the table as well.
	 */
	public function test_verify_base_tables_fix_tables() {
		global $wpdb;

		// Remove drop filter because we do want to drop temp table if it exists.
		// This filter was added to only allow dropping temporary tables which will then be rollbacked after the test.
		remove_filter( 'query', array( $this, '_drop_temporary_tables' ) );

		$original_table_name = "{$wpdb->prefix}wc_tax_rate_classes";
		$changed_table_name  = "{$wpdb->prefix}wc_tax_rate_classes_2";
		$clear_query         = 'DROP TABLE IF EXISTS %s;';
		$rename_table_query  = 'RENAME TABLE %s to %s;';

		// Workaround to call a private function.
		$schema = function () {
			return static::get_schema();
		};

		// Rename a base table to simulate it as non-existing.
		dbDelta( $schema->call( new \WC_Install() ) ); // Restore correct state.
		$wpdb->query( sprintf( $clear_query, $changed_table_name ) ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
		$wpdb->query( sprintf( $rename_table_query, $original_table_name, $changed_table_name ) ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared

		$missing_tables = \WC_Install::verify_base_tables( true, true );

		$wpdb->query( sprintf( $clear_query, $original_table_name ) ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
		$wpdb->query( sprintf( $rename_table_query, $changed_table_name, $original_table_name ) ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
		add_filter( 'query', array( $this, '_drop_temporary_tables' ) );

		// Ideally, no missing table because verify base tables created the table as well.
		$this->assertNotContains( $original_table_name, $missing_tables );
		$this->assertNotContains( 'base_tables_missing', \WC_Admin_Notices::get_notices() );
	}

	/**
	 * Test that premium support link is shown only when wccom is connected.
	 */
	public function test_plugin_row_meta() {
		// Simulate connection break.
		delete_option( 'woocommerce_helper_data' );
		$plugin_row_data = \WC_Install::plugin_row_meta( array(), WC_PLUGIN_BASENAME );

		$this->assertNotContains( 'premium_support', array_keys( $plugin_row_data ) );

		update_option( 'woocommerce_helper_data', array( 'auth' => 'random token' ) );
		$plugin_row_data = \WC_Install::plugin_row_meta( array(), WC_PLUGIN_BASENAME );
		$this->assertContains( 'premium_support', array_keys( $plugin_row_data ) );
	}

	/**
	 * Test that dbDelta is a noop on an installed site.
	 */
	public function test_dbDelta_is_a_noop() {
		$db_delta_result = WC_Install::create_tables();
		$this->assertEmpty( $db_delta_result );
	}

	/**
	 * Test that delete_obsolete_notes deletes notes.
	 */
	public function test_delete_obsolete_notes_deletes_notes() {
		$data_store = \WC_Data_Store::load( 'admin-note' );

		$note_name = 'wc-admin-welcome-note';

		$note = new Note();
		$note->set_name( $note_name );
		$note->set_status( Note::E_WC_ADMIN_NOTE_UNACTIONED );
		$note->add_action( 'test-action', 'Primary Action', 'https://example.com', Note::E_WC_ADMIN_NOTE_UNACTIONED, true );
		$note->add_action( 'test-action-2', 'Action 2', 'https://example.com' );
		$data_store->create( $note );

		$this->assertEquals( 1, count( $data_store->get_notes_with_name( $note_name ) ) );

		WC_Install::delete_obsolete_notes();

		$this->assertEmpty( $data_store->get_notes_with_name( $note_name ) );

	}

	/**
	 * Test that delete_obsolete_notes doesn't delete other notes.
	 */
	public function test_delete_obsolete_notes_deletes_only_selected_notes() {
		$data_store = \WC_Data_Store::load( 'admin-note' );

		$note_name = 'wc-admin-welcome-note';

		$note = new Note();
		$note->set_name( $note_name );
		$note->set_status( Note::E_WC_ADMIN_NOTE_UNACTIONED );
		$note->add_action( 'test-action', 'Primary Action', 'https://example.com', Note::E_WC_ADMIN_NOTE_UNACTIONED, true );
		$note->add_action( 'test-action-2', 'Action 2', 'https://example.com' );
		$data_store->create( $note );

		$note_name_2 = 'wc-admin-welcome-note-from-the-queen';

		$note_2 = new Note();
		$note_2->set_name( $note_name_2 );
		$note_2->set_status( Note::E_WC_ADMIN_NOTE_UNACTIONED );
		$note_2->add_action( 'test-action', 'Primary Action', 'https://example.com', Note::E_WC_ADMIN_NOTE_UNACTIONED, true );
		$note_2->add_action( 'test-action-2', 'Action 2', 'https://example.com' );
		$data_store->create( $note_2 );

		$this->assertEquals( '2', $data_store->get_notes_count( array( Note::E_WC_ADMIN_NOTE_INFORMATIONAL ), array() ) );

		WC_Install::delete_obsolete_notes();

		$this->assertEmpty( $data_store->get_notes_with_name( $note_name ) );
		$this->assertEquals( '1', $data_store->get_notes_count( array( Note::E_WC_ADMIN_NOTE_INFORMATIONAL ), array() ) );

		$data_store->delete( $note_2 );
	}

	/**
	 * Test that maybe_set_store_id only sets an ID when it isn't already present.
	 */
	public function test_maybe_set_store_id() {

		// simulate a store ID not being set.
		delete_option( \WC_Install::STORE_ID_OPTION );
		\WC_Install::maybe_set_store_id();
		$store_id = get_option( \WC_Install::STORE_ID_OPTION );
		// uuid4 is 36 characters long.
		$this->assertSame( 36, strlen( $store_id ) );

		// simulate a store ID already being set.
		\WC_Install::maybe_set_store_id();
		$existing_store_id = get_option( \WC_Install::STORE_ID_OPTION );
		$this->assertSame( $store_id, $existing_store_id );
		// cleanup.
		delete_option( \WC_Install::STORE_ID_OPTION );
	}

	/**
	 * Documents the expected behavior of `WC_Install::is_new_install()`, and describes certain characteristics such as
	 * a lazy approach to invoking post counts.
	 *
	 * @return void
	 */
	public function test_is_new_install(): void {
		// Determining if we are in a new install is based on the following three factors.
		$version       = null;
		$shop_id       = null;
		$post_count    = 0;
		$counted_posts = false;

		$supply_version = function () use ( &$version ) {
			return $version;
		};

		$supply_shop_id = function () use ( &$shop_id ) {
			return $shop_id;
		};

		$supply_post_count = function () use ( &$post_count ) {
			$counted_posts = true;
			return $post_count;
		};

		// Make it straightforward to test different values for our key variables.
		add_filter( 'option_woocommerce_version', $supply_version );
		add_filter( 'woocommerce_get_shop_page_id', $supply_shop_id );
		add_filter( 'wp_count_posts', $supply_post_count );

		$this->assertTrue( WC_Install::is_new_install(), 'We are in a new install if the WC version is null.' );

		$shop_id = 1;
		$this->assertTrue( WC_Install::is_new_install(), 'We are in a new install if the WC version is null (even if the shop ID is set).' );

		$post_count = 1;
		$this->assertTrue( WC_Install::is_new_install(), 'We are in a new install if the WC version is null (even if the shop ID is set and we have one or more products).' );

		$version = '9.0.0';
		$this->assertFalse( WC_Install::is_new_install(), 'We are not in a new install if the WC version is set, we have a shop ID and we have one or more products.' );

		$shop_id = null;
		$this->assertFalse( WC_Install::is_new_install(), 'We are not in a new install if the WC version is set and we have one or more products (even if the shop ID is not set).' );

		$post_count = 0;
		$this->assertTrue( WC_Install::is_new_install(), 'We are in a new install if the WC version is set but the shop ID is not set and we do not have any products.' );

		$counted_posts = false;
		$version       = '9.0.0';
		$shop_id       = 10;
		WC_Install::is_new_install();
		$this->assertFalse( $counted_posts, 'For established stores (version and shop ID both set), we do not need to count the number of existing products.' );

		// Cleanup.
		remove_filter( 'option_woocommerce_db_version', $supply_version );
		remove_filter( 'woocommerce_get_shop_page_id', $supply_shop_id );
		remove_filter( 'wp_count_posts', $supply_post_count );
	}

	/**
	 * Tests that database updates are scheduled automatically (or not) depending on whether auto-updates are enabled.
	 *
	 * @testWith [true]
	 *           [false]
	 *           [null]
	 *
	 * @since 9.9.0
	 *
	 * @param bool|null $auto_update Whether to enable auto-updates (TRUE) or not. NULL means use the defaults.
	 */
	public function test_db_auto_updates( ?bool $auto_update = null ): void {
		$options = array( 'woocommerce_db_version', 'woocommerce_version' );

		if ( ! is_null( $auto_update ) ) {
			add_filter( 'woocommerce_enable_auto_update_db', fn() => $auto_update );
		}

		foreach ( $options as $option_name ) {
			update_option( $option_name, '9.4.0' );
		}

		// Trigger version check.
		\WC_Install::check_version();

		// Did we schedule anything automatically?
		$update_scheduled = ! is_null( WC()->queue()->get_next( 'woocommerce_run_update_callback', null, 'woocommerce-db-updates' ) );

		if ( $auto_update || is_null( $auto_update ) ) {
			$this->assertTrue( $update_scheduled );
		} else {
			$this->assertFalse( $update_scheduled );
		}
	}

	/**
	 * Ensures that the versions in `WC_Install::$db_update_callbacks` are correct.
	 */
	public function test_db_update_callbacks_versions(): void {
		$callbacks = \WC_Install::get_db_update_callbacks();
		$versions  = array_keys( $callbacks );
		usort( $versions, 'version_compare' );

		// Array must be sorted by version.
		$this->assertSame(
			$versions,
			array_keys( $callbacks ),
			'WC_Install::$db_update_callbacks must be sorted by version.',
		);

		// Greatest version can't be ahead of current stable (except, possibly, for its suffix).
		$this->assertTrue(
			empty( $versions ) || version_compare( preg_replace( '/-.*$/', '', end( $versions ) ), WC()->stable_version(), '<=' ),
			'WC_Install::$db_update_callbacks must not contain versions that are ahead of current stable (except, possibly, for suffix).',
		);
	}

	/**
	 * Test that order stats table schema includes fulfillment_status column for new installations with fulfillments feature enabled.
	 *
	 * @return void
	 */
	public function test_order_stats_schema_includes_fulfillment_status_for_new_install_with_fulfillments_feature_enabled(): void {
		// Mock is_new_install to return true.
		$version = null;
		$shop_id = null;

		$supply_version = function () use ( &$version ) {
			return $version;
		};

		$supply_shop_id = function () use ( &$shop_id ) {
			return $shop_id;
		};

		$supply_feature_enabled = function () {
			return 'yes';
		};

		add_filter( 'option_woocommerce_version', $supply_version );
		add_filter( 'woocommerce_get_shop_page_id', $supply_shop_id );
		add_filter( 'pre_option_woocommerce_feature_fulfillments_enabled', $supply_feature_enabled );

		// Verify that is_new_install returns true.
		$this->assertTrue( WC_Install::is_new_install(), 'is_new_install should return true for testing new installation.' );

		// Get the schema using reflection to call private method.
		$get_order_stats_schema = function ( $collate ) {
			return static::get_order_stats_table_schema( $collate );
		};
		$schema                 = $get_order_stats_schema->call( new \WC_Install(), '' );

		// Assert that the schema includes fulfillment_status column.
		$this->assertStringContainsString( 'fulfillment_status varchar(50) DEFAULT NULL,', $schema, 'Schema should include fulfillment_status column for new installations.' );

		// Assert that the schema includes fulfillment_status index.
		$this->assertStringContainsString( 'KEY fulfillment_status (fulfillment_status),', $schema, 'Schema should include fulfillment_status index for new installations.' );

		// Cleanup.
		remove_filter( 'option_woocommerce_version', $supply_version );
		remove_filter( 'woocommerce_get_shop_page_id', $supply_shop_id );
		remove_filter( 'pre_option_woocommerce_feature_fulfillments_enabled', $supply_feature_enabled );
	}

	/**
	 * Test that order stats table schema does not includes fulfillment_status column for new installations without fulfillments feature enabled.
	 *
	 * @return void
	 */
	public function test_order_stats_schema_does_not_include_fulfillment_status_for_new_install_without_fulfillments_feature_enabled(): void {
		// Mock is_new_install to return true.
		$version = null;
		$shop_id = null;

		$supply_version = function () use ( &$version ) {
			return $version;
		};

		$supply_shop_id = function () use ( &$shop_id ) {
			return $shop_id;
		};

		add_filter( 'option_woocommerce_version', $supply_version );
		add_filter( 'woocommerce_get_shop_page_id', $supply_shop_id );

		// Verify that is_new_install returns true.
		$this->assertTrue( WC_Install::is_new_install(), 'is_new_install should return true for testing new installation.' );

		// Get the schema using reflection to call private method.
		$get_order_stats_schema = function ( $collate ) {
			return static::get_order_stats_table_schema( $collate );
		};
		$schema                 = $get_order_stats_schema->call( new \WC_Install(), '' );

		// Assert that the schema does NOT include fulfillment_status column.
		$this->assertStringNotContainsString( 'fulfillment_status varchar(50) DEFAULT NULL,', $schema, 'Schema should NOT include fulfillment_status column for new installations without fulfillments feature enabled.' );

		// Assert that the schema does NOT include fulfillment_status index.
		$this->assertStringNotContainsString( 'KEY fulfillment_status (fulfillment_status),', $schema, 'Schema should NOT include fulfillment_status index for new installations without fulfillments feature enabled.' );

		// Cleanup.
		remove_filter( 'option_woocommerce_version', $supply_version );
		remove_filter( 'woocommerce_get_shop_page_id', $supply_shop_id );
	}

	/**
	 * Test that order stats table schema excludes fulfillment_status column for existing installations without the column.
	 *
	 * @return void
	 */
	public function test_order_stats_schema_excludes_fulfillment_status_for_existing_install_without_column(): void {
		// Mock is_new_install to return false.
		$version = '9.0.0';
		$shop_id = 10;

		$supply_version = function () use ( &$version ) {
			return $version;
		};

		$supply_shop_id = function () use ( &$shop_id ) {
			return $shop_id;
		};

		add_filter( 'option_woocommerce_version', $supply_version );
		add_filter( 'woocommerce_get_shop_page_id', $supply_shop_id );

		// Mock has_fulfillment_status_column to return false (column does not exist).
		$supply_column_status = function () {
			return 'no';
		};

		add_filter( 'pre_option_' . \Automattic\WooCommerce\Admin\API\Reports\Orders\Stats\DataStore::OPTION_ORDER_STATS_TABLE_HAS_COLUMN_ORDER_FULFILLMENT_STATUS, $supply_column_status );

		// Verify that is_new_install returns false.
		$this->assertFalse( WC_Install::is_new_install(), 'is_new_install should return false for testing existing installation.' );

		// Get the schema using reflection to call private method.
		$get_order_stats_schema = function ( $collate ) {
			return static::get_order_stats_table_schema( $collate );
		};
		$schema                 = $get_order_stats_schema->call( new \WC_Install(), '' );

		// Assert that the schema does NOT include fulfillment_status column.
		$this->assertStringNotContainsString( 'fulfillment_status', $schema, 'Schema should NOT include fulfillment_status column for existing installations without the column.' );

		// Cleanup.
		remove_filter( 'option_woocommerce_version', $supply_version );
		remove_filter( 'woocommerce_get_shop_page_id', $supply_shop_id );
		remove_filter( 'pre_option_' . \Automattic\WooCommerce\Admin\API\Reports\Orders\Stats\DataStore::OPTION_ORDER_STATS_TABLE_HAS_COLUMN_ORDER_FULFILLMENT_STATUS, $supply_column_status );
	}

	/**
	 * Test that order stats table schema includes fulfillment_status column for existing installations with the column.
	 *
	 * @return void
	 */
	public function test_order_stats_schema_includes_fulfillment_status_for_existing_install_with_column(): void {
		// Mock is_new_install to return false.
		$version = '9.0.0';
		$shop_id = 10;

		$supply_version = function () use ( &$version ) {
			return $version;
		};

		$supply_shop_id = function () use ( &$shop_id ) {
			return $shop_id;
		};

		add_filter( 'option_woocommerce_version', $supply_version );
		add_filter( 'woocommerce_get_shop_page_id', $supply_shop_id );

		// Mock has_fulfillment_status_column to return true (column exists).
		$supply_column_status = function () {
			return 'yes';
		};

		add_filter( 'pre_option_' . \Automattic\WooCommerce\Admin\API\Reports\Orders\Stats\DataStore::OPTION_ORDER_STATS_TABLE_HAS_COLUMN_ORDER_FULFILLMENT_STATUS, $supply_column_status );

		// Verify that is_new_install returns false.
		$this->assertFalse( WC_Install::is_new_install(), 'is_new_install should return false for testing existing installation.' );

		// Get the schema using reflection to call private method.
		$get_order_stats_schema = function ( $collate ) {
			return static::get_order_stats_table_schema( $collate );
		};
		$schema                 = $get_order_stats_schema->call( new \WC_Install(), '' );

		// Assert that the schema DOES include fulfillment_status column for consistency.
		$this->assertStringContainsString( 'fulfillment_status', $schema, 'Schema should include fulfillment_status column for existing installations that already have the column.' );

		// Cleanup.
		remove_filter( 'option_woocommerce_version', $supply_version );
		remove_filter( 'woocommerce_get_shop_page_id', $supply_shop_id );
		remove_filter( 'pre_option_' . \Automattic\WooCommerce\Admin\API\Reports\Orders\Stats\DataStore::OPTION_ORDER_STATS_TABLE_HAS_COLUMN_ORDER_FULFILLMENT_STATUS, $supply_column_status );
	}
}
