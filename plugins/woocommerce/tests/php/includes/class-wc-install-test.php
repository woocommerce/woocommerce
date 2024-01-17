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
}
