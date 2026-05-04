<?php
/**
 * Update functions tests
 *
 * @package WooCommerce\Tests\Functions.
 */

use Automattic\WooCommerce\Blocks\Options as BlockOptions;
use Automattic\WooCommerce\Blocks\Utils\BlockTemplateUtils;
use Automattic\WooCommerce\Internal\VariationGallery\LegacyVariationGalleryCompatibility;

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
	 * Tests wc_update_830_rename_checkout_template.
	 * This test verifies that the function correctly renames the checkout template to 'page-checkout'.
	 *
	 * @return void
	 */
	public function test_wc_update_830_rename_checkout_template() {
		// Get the current template and update the name back to 'checkout'.
		$template = get_block_template( BlockTemplateUtils::PLUGIN_SLUG . '//page-checkout', 'wp_template' );

		if ( $template && ! empty( $template->wp_id ) ) {
			wp_update_post(
				array(
					'ID'        => $template->wp_id,
					'post_name' => 'checkout',
				)
			);
		}

		include_once WC_ABSPATH . 'includes/wc-update-functions.php';
		wc_update_830_rename_checkout_template();

		// Get the updated template and verify its name has been changed to 'page-checkout'.
		$updated_template = get_block_template( BlockTemplateUtils::PLUGIN_SLUG . '//checkout', 'wp_template' );

		if ( $updated_template && ! empty( $updated_template->wp_id ) ) {
			$post = get_post( $updated_template->wp_id );
			$this->assertEquals( 'page-checkout', $post->post_name );
		} else {
			// If no template exists, this assertion will pass since there's nothing to rename.
			$this->assertTrue( true );
		}
	}

	/**
	 * Tests wc_update_830_rename_cart_template.
	 * This test verifies that the function correctly renames the cart template to 'page-cart'.
	 *
	 * @return void
	 */
	public function test_wc_update_830_rename_cart_template() {
		// Get the current template and update the name back 'cart'.
		$template = get_block_template( BlockTemplateUtils::PLUGIN_SLUG . '//page-cart', 'wp_template' );

		if ( $template && ! empty( $template->wp_id ) ) {
			wp_update_post(
				array(
					'ID'        => $template->wp_id,
					'post_name' => 'cart',
				)
			);
		}

		include_once WC_ABSPATH . 'includes/wc-update-functions.php';
		wc_update_830_rename_cart_template();

		// Get the updated template and verify its name has been changed to 'page-cart'.
		$updated_template = get_block_template( BlockTemplateUtils::PLUGIN_SLUG . '//cart', 'wp_template' );

		if ( $updated_template && ! empty( $updated_template->wp_id ) ) {
			$post = get_post( $updated_template->wp_id );
			$this->assertEquals( 'page-cart', $post->post_name );
		} else {
			// If no template exists, this assertion will pass since there's nothing to rename.
			$this->assertTrue( true );
		}
	}

	/**
	 * Test wc_update_1040_cleanup_legacy_ptk_patterns_fetching removes the obsolete option and actions.
	 *
	 * @return void
	 */
	public function test_wc_update_1040_cleanup_legacy_ptk_patterns_fetching() {
		// Set up the option that should be removed.
		add_option( 'last_fetch_patterns_request', time() );
		$this->assertNotFalse( get_option( 'last_fetch_patterns_request' ), 'Option should exist before update' );

		// Schedule legacy actions that should be removed.
		as_schedule_single_action( time(), 'fetch_patterns' );
		$this->assertTrue( as_has_scheduled_action( 'fetch_patterns' ), 'fetch_patterns action should exist before update' );

		include_once WC_ABSPATH . 'includes/wc-update-functions.php';

		wc_update_1040_cleanup_legacy_ptk_patterns_fetching();

		// Verify the option was removed.
		$this->assertFalse( get_option( 'last_fetch_patterns_request' ), 'Option should be removed after update' );

		// Verify the actions were removed.
		$this->assertFalse( as_has_scheduled_action( 'fetch_patterns' ), 'fetch_patterns action should be removed after update' );
	}

	/**
	 * @testdox Migration converts legacy 'no' (not immediate) to new 'yes' (scheduled).
	 */
	public function test_migrate_analytics_import_option_legacy_no_becomes_yes(): void {
		delete_option( 'woocommerce_analytics_scheduled_import' );
		update_option( 'woocommerce_analytics_immediate_import', 'no' );

		wc_update_1080_migrate_analytics_import_option();

		$this->assertSame( 'yes', get_option( 'woocommerce_analytics_scheduled_import' ) );
		$this->assertFalse( get_option( 'woocommerce_analytics_immediate_import' ) );
	}

	/**
	 * @testdox Migration converts legacy 'yes' (immediate) to new 'no' (not scheduled).
	 */
	public function test_migrate_analytics_import_option_legacy_yes_becomes_no(): void {
		delete_option( 'woocommerce_analytics_scheduled_import' );
		update_option( 'woocommerce_analytics_immediate_import', 'yes' );

		wc_update_1080_migrate_analytics_import_option();

		$this->assertSame( 'no', get_option( 'woocommerce_analytics_scheduled_import' ) );
		$this->assertFalse( get_option( 'woocommerce_analytics_immediate_import' ) );
	}

	/**
	 * @testdox Migration does nothing when legacy option is absent.
	 */
	public function test_migrate_analytics_import_option_no_legacy_option(): void {
		delete_option( 'woocommerce_analytics_immediate_import' );
		delete_option( 'woocommerce_analytics_scheduled_import' );

		wc_update_1080_migrate_analytics_import_option();

		$this->assertFalse( get_option( 'woocommerce_analytics_scheduled_import' ) );
	}

	/**
	 * @testdox Migration preserves existing new option and deletes legacy.
	 */
	public function test_migrate_analytics_import_option_new_option_already_exists(): void {
		update_option( 'woocommerce_analytics_scheduled_import', 'yes' );
		update_option( 'woocommerce_analytics_immediate_import', 'yes' );

		wc_update_1080_migrate_analytics_import_option();

		$this->assertSame( 'yes', get_option( 'woocommerce_analytics_scheduled_import' ) );
		$this->assertFalse( get_option( 'woocommerce_analytics_immediate_import' ) );
	}

	/**
	 * @testdox Migration copies legacy variation gallery meta into the core gallery prop and disables fallback.
	 */
	public function test_migrate_legacy_variation_gallery_meta_copies_legacy_gallery_and_disables_fallback(): void {
		$variation_id = $this->create_variation();
		$image_ids    = array(
			$this->create_attachment( 'Legacy gallery image 1' ),
			$this->create_attachment( 'Legacy gallery image 2' ),
		);

		update_post_meta( $variation_id, '_wc_additional_variation_images', implode( ',', $image_ids ) );

		$this->assertFalse( wc_update_1090_migrate_legacy_variation_gallery_meta() );

		$this->assertTrue( LegacyVariationGalleryCompatibility::is_variation_id_core_managed( $variation_id ) );
		$this->assertSame( $image_ids, wc_get_product( $variation_id )->get_gallery_image_ids() );
		$this->assertSame( implode( ',', $image_ids ), get_post_meta( $variation_id, '_product_image_gallery', true ) );
	}

	/**
	 * @testdox Migration preserves existing core variation gallery values while disabling fallback.
	 */
	public function test_migrate_legacy_variation_gallery_meta_preserves_existing_core_gallery(): void {
		$variation_id       = $this->create_variation();
		$core_gallery_ids   = array(
			$this->create_attachment( 'Core gallery image 1' ),
			$this->create_attachment( 'Core gallery image 2' ),
		);
		$legacy_gallery_ids = array(
			$this->create_attachment( 'Legacy gallery image 1' ),
			$this->create_attachment( 'Legacy gallery image 2' ),
		);

		update_post_meta( $variation_id, '_product_image_gallery', implode( ',', $core_gallery_ids ) );
		update_post_meta( $variation_id, '_wc_additional_variation_images', implode( ',', $legacy_gallery_ids ) );

		$this->assertFalse( wc_update_1090_migrate_legacy_variation_gallery_meta() );

		$this->assertTrue( LegacyVariationGalleryCompatibility::is_variation_id_core_managed( $variation_id ) );
		$this->assertSame( $core_gallery_ids, wc_get_product( $variation_id )->get_gallery_image_ids( 'edit' ) );
		$this->assertSame( implode( ',', $core_gallery_ids ), get_post_meta( $variation_id, '_product_image_gallery', true ) );
	}

	/**
	 * @testdox Migration disables fallback for malformed legacy variation gallery meta without writing invalid core values.
	 */
	public function test_migrate_legacy_variation_gallery_meta_disables_fallback_for_malformed_legacy_meta(): void {
		$variation_id = $this->create_variation();

		update_post_meta( $variation_id, '_wc_additional_variation_images', 'not-an-id' );

		$this->assertFalse( wc_update_1090_migrate_legacy_variation_gallery_meta() );

		$this->assertTrue( LegacyVariationGalleryCompatibility::is_variation_id_core_managed( $variation_id ) );
		$this->assertSame( '', get_post_meta( $variation_id, '_product_image_gallery', true ) );
		$this->assertSame( array(), wc_get_product( $variation_id )->get_gallery_image_ids() );
	}

	/**
	 * @testdox Migration batches legacy variation gallery rows and requeues until complete.
	 */
	public function test_migrate_legacy_variation_gallery_meta_batches_updates(): void {
		global $wpdb;

		// phpcs:disable WordPress.DB.SlowDBQuery.slow_db_query_meta_key -- Test setup needs to scope deletes by meta_key.
		$wpdb->delete( $wpdb->postmeta, array( 'meta_key' => '_wc_additional_variation_images' ) );
		$wpdb->delete( $wpdb->postmeta, array( 'meta_key' => '_product_image_gallery' ) );
		$wpdb->delete( $wpdb->postmeta, array( 'meta_key' => LegacyVariationGalleryCompatibility::get_core_managed_meta_key() ) );
		// phpcs:enable WordPress.DB.SlowDBQuery.slow_db_query_meta_key

		$variation_ids = array();

		for ( $index = 0; $index < 251; ++$index ) {
			$variation_id    = $this->create_variation_post();
			$variation_ids[] = $variation_id;
			update_post_meta( $variation_id, '_wc_additional_variation_images', (string) ( $index + 1 ) );
		}

		$this->assertTrue( wc_update_1090_migrate_legacy_variation_gallery_meta() );

		$processed_after_first_batch = 0;

		foreach ( $variation_ids as $variation_id ) {
			if ( LegacyVariationGalleryCompatibility::is_variation_id_core_managed( $variation_id ) ) {
				++$processed_after_first_batch;
			}
		}

		$this->assertSame( 250, $processed_after_first_batch );
		$this->assertFalse( LegacyVariationGalleryCompatibility::is_variation_id_core_managed( end( $variation_ids ) ) );

		$this->assertFalse( wc_update_1090_migrate_legacy_variation_gallery_meta() );

		foreach ( $variation_ids as $variation_id ) {
			$this->assertTrue( LegacyVariationGalleryCompatibility::is_variation_id_core_managed( $variation_id ) );
		}
	}

	/**
	 * Create a variation for testing.
	 *
	 * @return int
	 */
	private function create_variation(): int {
		$product = WC_Helper_Product::create_variation_product();

		return (int) $product->get_children()[0];
	}

	/**
	 * Create a bare variation post for migration batching tests.
	 *
	 * @return int
	 */
	private function create_variation_post(): int {
		return self::factory()->post->create(
			array(
				'post_type'   => 'product_variation',
				'post_status' => 'publish',
			)
		);
	}

	/**
	 * Create a test attachment.
	 *
	 * @param string $title Attachment title.
	 * @return int
	 */
	private function create_attachment( string $title ): int {
		return wp_insert_attachment(
			array(
				'post_title'     => $title,
				'post_type'      => 'attachment',
				'post_mime_type' => 'image/jpeg',
			)
		);
	}
}
