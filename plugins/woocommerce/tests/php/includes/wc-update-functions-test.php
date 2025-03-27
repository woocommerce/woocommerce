<?php
/**
 * Update functions tests
 *
 * @package WooCommerce\Tests\Functions.
 */

use Automattic\WooCommerce\Blocks\Options as BlockOptions;
use Automattic\WooCommerce\Blocks\Utils\BlockTemplateUtils;

/**
 * Class WC_Core_Functions_Test
 */
class WC_Update_Functions_Test extends \WC_Unit_Test_Case {

	/**
	 * Test wc_update_343_cleanup_foreign_keys() function.
	 */
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

		// Verify that the keys were properly removed.
		$table_definition = $wpdb->get_var( "SHOW CREATE TABLE {$wpdb->prefix}wc_download_log", 1 );
		$this->assertFalse( strpos( $table_definition, 'wc_download_log_ib' ) );
	}

	/**
	 * Test wc_update_352_drop_download_log_fk() function.
	 */
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

		// Verify that the key was properly removed.
		$table_definition = $wpdb->get_var( "SHOW CREATE TABLE {$wpdb->prefix}wc_download_log", 1 );
		$this->assertFalse( strpos( $table_definition, 'fk_wc_download_log_permission_id' ) );
	}

	/**
	 * Test wc_update_700_remove_download_log_fk() function.
	 */
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

	/**
	 * Test woocommerce_hooked_blocks_version option gets set to "no" when block hooks are disabled for unapproved block themes.
	 *
	 * @return void
	 */
	public function test_wc_update_920_add_wc_hooked_blocks_version_option_block_hooks_version_is_set_to_no() {
		add_filter( 'woocommerce_hooked_blocks_theme_include_list', '__return_empty_array', 999, 1 );

		switch_theme( 'twentytwentytwo' );

		delete_option( 'woocommerce_hooked_blocks_version' );

		include_once WC_ABSPATH . 'includes/wc-update-functions.php';

		wc_update_920_add_wc_hooked_blocks_version_option();

		$this->assertEquals( 'no', get_option( 'woocommerce_hooked_blocks_version' ) );

		remove_filter( 'woocommerce_hooked_blocks_theme_include_list', '__return_empty_array', 999, 1 );
	}

	/**
	 * Test woocommerce_hooked_blocks_version option gets set to "8.4.0" for approved block themes.
	 *
	 * @return void
	 */
	public function test_wc_update_920_add_wc_hooked_blocks_version_option_block_hooks_version_is_set_to_840() {
		switch_theme( 'twentytwentytwo' );

		delete_option( 'woocommerce_hooked_blocks_version' );

		include_once WC_ABSPATH . 'includes/wc-update-functions.php';

		wc_update_920_add_wc_hooked_blocks_version_option();

		$this->assertEquals( '8.4.0', get_option( 'woocommerce_hooked_blocks_version' ) );
	}

	/**
	 * Test woocommerce_hooked_blocks_version option is not overwritten
	 *
	 * @return void
	 */
	public function test_wc_update_920_add_wc_hooked_blocks_version_option_block_hooks_version_is_not_overwritten() {
		switch_theme( 'twentytwentytwo' );

		delete_option( 'woocommerce_hooked_blocks_version' );
		add_option( 'woocommerce_hooked_blocks_version', '1.0.0' );

		include_once WC_ABSPATH . 'includes/wc-update-functions.php';

		wc_update_920_add_wc_hooked_blocks_version_option();

		$this->assertEquals( '1.0.0', get_option( 'woocommerce_hooked_blocks_version' ) );
	}

	/**
	 * Test woocommerce_hooked_blocks_version option is not overwritten
	 *
	 * @return void
	 */
	public function test_wc_update_920_add_wc_hooked_blocks_version_option_block_hooks_version_not_present_for_classic_themes() {
		switch_theme( 'storefront' );

		delete_option( 'woocommerce_hooked_blocks_version' );

		include_once WC_ABSPATH . 'includes/wc-update-functions.php';

		wc_update_920_add_wc_hooked_blocks_version_option();

		$this->assertEquals( null, get_option( 'woocommerce_hooked_blocks_version', null ) );
	}

	/**
	 * Test that wc_update_790_blockified_product_grid_block sets the option value to false.
	 *
	 * @return void
	 */
	public function test_wc_update_790_blockified_product_grid_block() {
		delete_option( BlockOptions::WC_BLOCK_USE_BLOCKIFIED_PRODUCT_GRID_BLOCK_AS_TEMPLATE );

		include_once WC_ABSPATH . 'includes/wc-update-functions.php';

		wc_update_790_blockified_product_grid_block();

		$this->assertEquals( 'no', get_option( BlockOptions::WC_BLOCK_USE_BLOCKIFIED_PRODUCT_GRID_BLOCK_AS_TEMPLATE ) );
	}

	/**
	 * Test that wc_update_830_rename_checkout_template renames the checkout template.
	 *
	 * @return void
	 */
	public function test_wc_update_830_rename_checkout_template() {
		// Create a mock template object
		$template        = new stdClass();
		$template->wp_id = 123;

		add_filter( 'pre_get_block_template', function ( $pre, $id, $template_type ) use ( $template ) {
			if ( BlockTemplateUtils::PLUGIN_SLUG . '//checkout' === $id && 'wp_template' === $template_type ) {
				return $template;
			}
			return $pre;
		});

		// Mock wp_update_post to verify it's called with the correct parameters
		$wp_update_post_called = false;
		$wp_update_post_args   = array();
		add_filter( 'wp_update_post_data', function ( $data, $post_args ) use ( &$wp_update_post_called, &$wp_update_post_args ) {
			$wp_update_post_called = true;
			$wp_update_post_args = $post_args;
			return $data;
		});

		include_once WC_ABSPATH . 'includes/wc-update-functions.php';

		wc_update_830_rename_checkout_template();

		$this->assertTrue( $wp_update_post_called );
		$this->assertEquals( 123, $wp_update_post_args[ 'ID' ] );
		$this->assertEquals( 'page-checkout', $wp_update_post_args[ 'post_name' ] );

		// Clean up
		remove_all_filters( 'pre_get_block_template' );
		remove_all_filters( 'wp_update_post_data' );
	}

	/**
	 * Test that wc_update_830_rename_cart_template renames the cart template.
	 *
	 * @return void
	 */
	public function test_wc_update_830_rename_cart_template() {
		// Create a mock template object
		$template        = new stdClass();
		$template->wp_id = 456;

		add_filter( 'pre_get_block_template', function ( $pre, $id, $template_type ) use ( $template ) {
			if ( BlockTemplateUtils::PLUGIN_SLUG . '//cart' === $id && 'wp_template' === $template_type ) {
				return $template;
			}
			return $pre;
		});

		// Mock wp_update_post to verify it's called with the correct parameters
		$wp_update_post_called = false;
		$wp_update_post_args   = array();
		add_filter( 'wp_update_post_data', function ( $data, $post_args ) use ( &$wp_update_post_called, &$wp_update_post_args ) {
			$wp_update_post_called = true;
			$wp_update_post_args = $post_args;
			return $data;
		});

		include_once WC_ABSPATH . 'includes/wc-update-functions.php';

		wc_update_830_rename_cart_template();

		$this->assertTrue( $wp_update_post_called );
		$this->assertEquals( 456, $wp_update_post_args[ 'ID' ] );
		$this->assertEquals( 'page-cart', $wp_update_post_args[ 'post_name' ] );

		// Clean up
		remove_all_filters( 'pre_get_block_template' );
		remove_all_filters( 'wp_update_post_data' );
	}
}
