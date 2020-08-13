<?php

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

}
