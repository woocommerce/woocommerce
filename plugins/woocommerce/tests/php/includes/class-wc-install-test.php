<?php
declare( strict_types = 1 );

use Automattic\WooCommerce\Admin\Notes\Note;
use Automattic\WooCommerce\Caches\ProductCountCache;
use Automattic\WooCommerce\Enums\ProductStatus;

/**
 * Class WC_Install_Test.
 */
class WC_Install_Test extends \WC_Unit_Test_Case {

	/**
	 * Test if verify base table can detect missing tables and clear the stored missing table list.
	 */
	public function test_verify_base_tables_stores_and_removes_missing_tables() {
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
		$this->assertContains( $original_table_name, get_option( 'woocommerce_schema_missing_tables', array() ) );

		// Ideally, no missing table anymore because we have switched back table name.
		$missing_tables = \WC_Install::verify_base_tables();

		$this->assertNotContains( $original_table_name, $missing_tables );
		$this->assertSame( array(), get_option( 'woocommerce_schema_missing_tables', array() ) );
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
		$this->assertSame( array(), get_option( 'woocommerce_schema_missing_tables', array() ) );
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
		// Determining if we are in a new install is based on the following factors.
		$version         = false;
		$shop_id         = null;
		$post_count      = 0;
		$counted_posts   = false;
		$coming_soon     = 'yes';
		$completed_lists = array();

		$supply_version = function () use ( &$version ) {
			return $version;
		};

		$supply_shop_id = function () use ( &$shop_id ) {
			return $shop_id;
		};

		$supply_post_count = function () use ( &$post_count, &$counted_posts ) {
			$counted_posts = true;
			return (object) array( ProductStatus::PUBLISH => $post_count );
		};

		$supply_coming_soon = function () use ( &$coming_soon ) {
			return $coming_soon;
		};

		$supply_completed_lists = function () use ( &$completed_lists ) {
			return $completed_lists;
		};

		// Make it straightforward to test different values for our key variables.
		add_filter( 'option_woocommerce_version', $supply_version );
		add_filter( 'woocommerce_get_shop_page_id', $supply_shop_id );
		add_filter( 'wp_count_posts', $supply_post_count );
		add_filter( 'pre_option_woocommerce_coming_soon', $supply_coming_soon );
		add_filter( 'pre_option_woocommerce_task_list_completed_lists', $supply_completed_lists );

		$this->assertTrue( WC_Install::is_new_install(), 'We are in a new install if the WC version is null.' );

		$shop_id = 1;
		$this->assertTrue( WC_Install::is_new_install(), 'We are in a new install if the WC version is null (even if the shop ID is set).' );

		$post_count = 1;
		$this->assertTrue( WC_Install::is_new_install(), 'We are in a new install if the WC version is null (even if the shop ID is set and we have one or more products).' );

		$version     = '9.0.0';
		$coming_soon = 'no';
		$this->assertFalse( WC_Install::is_new_install(), 'We are not in a new install if the store is live (coming soon is disabled).' );

		$coming_soon     = 'yes';
		$completed_lists = array( 'setup' );
		$this->assertFalse( WC_Install::is_new_install(), 'We are not in a new install if onboarding has been completed.' );

		$completed_lists = array();
		$this->assertFalse( WC_Install::is_new_install(), 'We are not in a new install if the WC version is set, we have a shop ID and we have one or more products.' );

		$shop_id = null;
		$this->assertFalse( WC_Install::is_new_install(), 'We are not in a new install if the WC version is set and we have one or more products (even if the shop ID is not set).' );

		$post_count = 0;
		( new ProductCountCache() )->flush( 'product' );
		$this->assertTrue( WC_Install::is_new_install(), 'We are in a new install if the WC version is set but the shop ID is not set and we do not have any products.' );

		$counted_posts = false;
		$version       = '9.0.0';
		$shop_id       = 10;
		WC_Install::is_new_install();
		$this->assertFalse( $counted_posts, 'For established stores (version and shop ID both set), we do not need to count the number of existing products.' );

		// Cleanup.
		remove_filter( 'option_woocommerce_version', $supply_version );
		remove_filter( 'woocommerce_get_shop_page_id', $supply_shop_id );
		remove_filter( 'wp_count_posts', $supply_post_count );
		remove_filter( 'pre_option_woocommerce_coming_soon', $supply_coming_soon );
		remove_filter( 'pre_option_woocommerce_task_list_completed_lists', $supply_completed_lists );
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
		$update_versions = array_keys( WC_Install::get_db_update_callbacks() );
		$from_version    = $update_versions[ count( $update_versions ) - 2 ];
		$maybe_update_db = function () {
			static::maybe_update_db_version();
		};

		if ( ! is_null( $auto_update ) ) {
			add_filter( 'woocommerce_enable_auto_update_db', fn() => $auto_update );
		}

		update_option( 'woocommerce_db_version', $from_version );
		$maybe_update_db->call( new WC_Install() );

		// Did we schedule anything automatically?
		$update_scheduled = ! is_null( WC()->queue()->get_next( 'woocommerce_run_update_callback', null, 'woocommerce-db-updates' ) );

		if ( $auto_update || is_null( $auto_update ) ) {
			$this->assertTrue( $update_scheduled );
		} else {
			$this->assertFalse( $update_scheduled );
		}
	}

	/**
	 * Tests that the version check reaches the automatic database updater.
	 *
	 * This is a single end-to-end smoke test of the check_version() -> install() ->
	 * maybe_update_db_version() wiring; the auto-update on/off/default decision logic
	 * itself is covered for all variations by test_db_auto_updates() above.
	 *
	 * @testdox The version check schedules the automatic database update.
	 */
	public function test_version_check_schedules_db_auto_update(): void {
		$update_versions = array_keys( WC_Install::get_db_update_callbacks() );
		$from_version    = $update_versions[ count( $update_versions ) - 2 ];

		update_option( 'woocommerce_db_version', $from_version );
		update_option( 'woocommerce_version', $from_version );

		WC_Install::check_version();

		$this->assertNotNull( WC()->queue()->get_next( 'woocommerce_run_update_callback', null, 'woocommerce-db-updates' ) );
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
		$version = false;
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
		// Ensure the fulfillments feature is disabled (a prior test class may have enabled it).
		delete_option( 'woocommerce_feature_fulfillments_enabled' );

		// Mock is_new_install to return true.
		$version = false;
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

	/**
	 * @testdox Should return every actionscheduler_* table that exists in the database, each prefixed with the table prefix.
	 */
	public function test_get_action_scheduler_tables_matches_database_tables(): void {
		global $wpdb;

		// Action Scheduler is bundled with WooCommerce, so its tables exist in the test database. Comparing
		// against the live schema (rather than re-listing the same hardcoded names the method returns) means
		// this test fails if Action Scheduler ever adds, renames or drops a table and the method drifts out
		// of sync, which would otherwise leave those tables behind on uninstall.
		$actual_tables = $wpdb->get_col(
			"SHOW TABLES LIKE '" . $wpdb->esc_like( $wpdb->prefix . 'actionscheduler_' ) . "%'"
		);

		$this->assertNotEmpty(
			$actual_tables,
			'No actionscheduler_* tables were found in the database; the test environment is not set up as expected.'
		);

		$reported_tables = WC_Install::get_action_scheduler_tables();

		foreach ( $reported_tables as $table ) {
			$this->assertStringStartsWith(
				$wpdb->prefix,
				$table,
				"Action Scheduler table {$table} should be prefixed with the database table prefix."
			);
		}

		sort( $actual_tables );
		sort( $reported_tables );

		$this->assertSame(
			$actual_tables,
			$reported_tables,
			'get_action_scheduler_tables() should match the actionscheduler_* tables present in the database.'
		);
	}

	/**
	 * @testdox Should delete the placeholder image attachment and its meta.
	 */
	public function test_delete_placeholder_image_removes_attachment(): void {
		$attachment_id = wp_insert_attachment(
			array(
				'post_title'     => 'woocommerce-placeholder',
				'post_mime_type' => 'image/webp',
				'post_status'    => 'inherit',
				'post_type'      => 'attachment',
			)
		);
		update_post_meta( $attachment_id, '_wp_attached_file', 'woocommerce-placeholder.webp' );
		update_option( 'woocommerce_placeholder_image', $attachment_id );

		WC_Install::delete_placeholder_image();

		$this->assertNull( get_post( $attachment_id ), 'The placeholder attachment post should be deleted.' );
		$this->assertSame(
			'',
			get_post_meta( $attachment_id, '_wp_attached_file', true ),
			'The placeholder attachment meta should be deleted.'
		);
	}

	/**
	 * @testdox Should not delete a custom image set by the merchant as the placeholder.
	 */
	public function test_delete_placeholder_image_keeps_custom_attachment(): void {
		$attachment_id = wp_insert_attachment(
			array(
				'post_title'     => 'merchant-logo',
				'post_mime_type' => 'image/png',
				'post_status'    => 'inherit',
				'post_type'      => 'attachment',
			)
		);
		update_post_meta( $attachment_id, '_wp_attached_file', '2026/06/merchant-logo.png' );
		update_option( 'woocommerce_placeholder_image', $attachment_id );

		WC_Install::delete_placeholder_image();

		$this->assertInstanceOf(
			WP_Post::class,
			get_post( $attachment_id ),
			'A custom merchant placeholder attachment should not be deleted.'
		);
	}
}
