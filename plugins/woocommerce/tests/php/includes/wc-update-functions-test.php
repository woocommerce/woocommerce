<?php
/**
 * Update functions tests
 *
 * @package WooCommerce\Tests\Functions.
 */

/**
 * Class WC_Core_Functions_Test
 */
class WC_Update_Functions_Test extends \WC_Unit_Test_Case {

	public function test_verify_wc_update_343_cleanup_foreign_keys_removes_foreign_keys() {
		global $wpdb;

		// Add matching foreign keys between wc_download_log and wc_download_log_permission_id as it previously existed.
		$wpdb->query(
			"ALTER TABLE `{$wpdb->prefix}wc_download_log`
					ADD CONSTRAINT `wc_download_log_ib`
					FOREIGN KEY (`permission_id`)
					REFERENCES `{$wpdb->prefix}woocommerce_downloadable_product_permissions` (`permission_id`) ON DELETE CASCADE,
					ADD CONSTRAINT `wc_download_log_ib_2`
					FOREIGN KEY (`permission_id`)
					REFERENCES `{$wpdb->prefix}woocommerce_downloadable_product_permissions` (`permission_id`) ON DELETE CASCADE"
		);
		$table_definition = $wpdb->get_var( "SHOW CREATE TABLE {$wpdb->prefix}wc_download_log", 1 );
		$this->assertNotFalse( strpos( $table_definition, 'wc_download_log_ib' ) );
		$this->assertNotFalse( strpos( $table_definition, 'wc_download_log_ib_2' ) );

		include_once WC_ABSPATH . 'includes/wc-update-functions.php';

		wc_update_343_cleanup_foreign_keys();

		// Verify that the keys were properly removed
		$table_definition = $wpdb->get_var( "SHOW CREATE TABLE {$wpdb->prefix}wc_download_log", 1 );
		$this->assertFalse( strpos( $table_definition, 'wc_download_log_ib' ) );
	}

	public function test_verify_wc_update_352_drop_download_log_fk_removes_foreign_keys() {
		global $wpdb;

		// Add the foreign key between wc_download_log and wc_download_log_permission_id as it previously existed.
		$wpdb->query(
			"ALTER TABLE `{$wpdb->prefix}wc_download_log`
					ADD CONSTRAINT `fk_wc_download_log_permission_id`
					FOREIGN KEY (`permission_id`)
					REFERENCES `{$wpdb->prefix}woocommerce_downloadable_product_permissions` (`permission_id`) ON DELETE CASCADE"
		);
		$table_definition = $wpdb->get_var( "SHOW CREATE TABLE {$wpdb->prefix}wc_download_log", 1 );
		$this->assertNotFalse( strpos( $table_definition, 'fk_wc_download_log_permission_id' ) );

		include_once WC_ABSPATH . 'includes/wc-update-functions.php';

		wc_update_352_drop_download_log_fk();

		// Verify that the key was properly removed
		$table_definition = $wpdb->get_var( "SHOW CREATE TABLE {$wpdb->prefix}wc_download_log", 1 );
		$this->assertFalse( strpos( $table_definition, 'fk_wc_download_log_permission_id' ) );
	}

	public function test_verify_wc_update_700_remove_download_log_fk_removes_foreign_keys() {
		global $wpdb;

		// Add the foreign key between wc_download_log and wc_download_log_permission_id as it previously existed.
		$wpdb->query(
			"ALTER TABLE `{$wpdb->prefix}wc_download_log`
					ADD CONSTRAINT `fk_{$wpdb->prefix}wc_download_log_permission_id`
					FOREIGN KEY (`permission_id`)
					REFERENCES `{$wpdb->prefix}woocommerce_downloadable_product_permissions` (`permission_id`) ON DELETE CASCADE"
		);
		$table_definition = $wpdb->get_var( "SHOW CREATE TABLE {$wpdb->prefix}wc_download_log", 1 );
		$this->assertNotFalse( strpos( $table_definition, "fk_{$wpdb->prefix}wc_download_log_permission_id" ) );

		include_once WC_ABSPATH . 'includes/wc-update-functions.php';

		wc_update_700_remove_download_log_fk();

		// Verify that the key was properly removed.
		$table_definition = $wpdb->get_var( "SHOW CREATE TABLE {$wpdb->prefix}wc_download_log", 1 );
		$this->assertFalse( strpos( $table_definition, "fk_{$wpdb->prefix}wc_download_log_permission_id" ) );
	}


}
