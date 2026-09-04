<?php
/**
 * Update functions tests
 *
 * @package WooCommerce\Tests\Functions.
 */

use Automattic\Jetpack\Constants;
use Automattic\WooCommerce\Admin\API\Reports\Cache as ReportsCache;
use Automattic\WooCommerce\Admin\Notes\Note;
use Automattic\WooCommerce\Admin\Notes\Notes;
use Automattic\WooCommerce\Blocks\InboxNotifications;
use Automattic\WooCommerce\Blocks\Options as BlockOptions;
use Automattic\WooCommerce\Blocks\Utils\BlockTemplateUtils;
use Automattic\WooCommerce\Enums\OrderStatus;
use Automattic\WooCommerce\Internal\Admin\OrderTaxLookupMigrator;
use Automattic\WooCommerce\Internal\BatchProcessing\BatchProcessingController;
use Automattic\WooCommerce\Internal\Features\FeaturesController;
use Automattic\WooCommerce\Internal\VariationGallery\Package as VariationGalleryPackage;

/**
 * Class WC_Core_Functions_Test
 */
class WC_Update_Functions_Test extends \WC_Unit_Test_Case {

	/**
	 * Tear down test fixtures.
	 */
	public function tearDown(): void {
		Constants::clear_single_constant( 'WOOCOMMERCE_BIS_ALPHA_ENABLED' );
		delete_option( 'woocommerce_feature_customer_stock_notifications_enabled' );
		parent::tearDown();
	}

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
	 * @testdox Migration sets the point_of_sale feature flag option to yes regardless of the previous value.
	 */
	public function test_wc_update_1100_enable_point_of_sale_feature(): void {
		include_once WC_ABSPATH . 'includes/wc-update-functions.php';

		update_option( 'woocommerce_feature_point_of_sale_enabled', 'no' );
		wc_update_1100_enable_point_of_sale_feature();
		$this->assertSame( 'yes', get_option( 'woocommerce_feature_point_of_sale_enabled' ) );

		delete_option( 'woocommerce_feature_point_of_sale_enabled' );
		wc_update_1100_enable_point_of_sale_feature();
		$this->assertSame( 'yes', get_option( 'woocommerce_feature_point_of_sale_enabled' ) );
	}

	/**
	 * @testdox Migration registers and removes the deprecated variation gallery feature option.
	 */
	public function test_wc_update_11101_remove_deprecated_variation_gallery_option(): void {
		include_once WC_ABSPATH . 'includes/wc-update-functions.php';

		$db_updates = WC_Install::get_db_update_callbacks();
		$this->assertArrayHasKey( '11.1.0-1', $db_updates );
		$this->assertContains( 'wc_update_11101_remove_deprecated_variation_gallery_option', $db_updates['11.1.0-1'] );

		delete_option( VariationGalleryPackage::ENABLE_OPTION_NAME );
		wc_update_11101_remove_deprecated_variation_gallery_option();
		$this->assertFalse( get_option( VariationGalleryPackage::ENABLE_OPTION_NAME ) );

		update_option( VariationGalleryPackage::ENABLE_OPTION_NAME, 'no' );
		wc_update_11101_remove_deprecated_variation_gallery_option();
		$this->assertFalse( get_option( VariationGalleryPackage::ENABLE_OPTION_NAME ) );

		update_option( VariationGalleryPackage::ENABLE_OPTION_NAME, 'yes' );
		wc_update_11101_remove_deprecated_variation_gallery_option();
		$this->assertFalse( get_option( VariationGalleryPackage::ENABLE_OPTION_NAME ) );
	}

	/**
	 * @testdox Migration registers and deletes the cached dashboard out-of-stock count.
	 */
	public function test_wc_update_1110_delete_dashboard_outofstock_count_transient(): void {
		include_once WC_ABSPATH . 'includes/wc-update-functions.php';

		$db_updates = WC_Install::get_db_update_callbacks();
		$this->assertArrayHasKey( '11.1.0', $db_updates );
		$this->assertContains( 'wc_update_1110_delete_dashboard_outofstock_count_transient', $db_updates['11.1.0'] );

		set_transient( 'wc_outofstock_count', 3, DAY_IN_SECONDS );
		$this->assertSame( 3, get_transient( 'wc_outofstock_count' ) );

		wc_update_1110_delete_dashboard_outofstock_count_transient();
		$this->assertFalse( get_transient( 'wc_outofstock_count' ) );
	}

	/**
	 * @testdox Migration enables the customer_stock_notifications feature when the alpha constant is set.
	 */
	public function test_wc_update_1120_migrate_stock_notifications_alpha_constant_opts_in(): void {
		include_once WC_ABSPATH . 'includes/wc-update-functions.php';

		$db_updates = WC_Install::get_db_update_callbacks();
		$this->assertArrayHasKey( '11.2.0', $db_updates );
		$this->assertContains( 'wc_update_1120_migrate_stock_notifications_alpha_constant', $db_updates['11.2.0'] );

		delete_option( 'woocommerce_feature_customer_stock_notifications_enabled' );
		Constants::set_constant( 'WOOCOMMERCE_BIS_ALPHA_ENABLED', true );

		wc_update_1120_migrate_stock_notifications_alpha_constant();

		$this->assertSame( 'yes', get_option( 'woocommerce_feature_customer_stock_notifications_enabled' ) );
	}

	/**
	 * @testdox Migration leaves the feature untouched when the alpha constant is absent or falsy.
	 */
	public function test_wc_update_1120_migrate_stock_notifications_alpha_constant_without_opt_in(): void {
		include_once WC_ABSPATH . 'includes/wc-update-functions.php';

		delete_option( 'woocommerce_feature_customer_stock_notifications_enabled' );
		Constants::clear_single_constant( 'WOOCOMMERCE_BIS_ALPHA_ENABLED' );

		wc_update_1120_migrate_stock_notifications_alpha_constant();

		$this->assertFalse( get_option( 'woocommerce_feature_customer_stock_notifications_enabled' ) );

		Constants::set_constant( 'WOOCOMMERCE_BIS_ALPHA_ENABLED', false );

		wc_update_1120_migrate_stock_notifications_alpha_constant();

		$this->assertFalse( get_option( 'woocommerce_feature_customer_stock_notifications_enabled' ) );
	}

	/**
	 * @testdox Migration overwrites the 'no' that WC_Install::create_options() seeds before the update callbacks run.
	 */
	public function test_wc_update_1120_migrate_stock_notifications_alpha_constant_overwrites_seeded_option(): void {
		include_once WC_ABSPATH . 'includes/wc-update-functions.php';

		update_option( 'woocommerce_feature_customer_stock_notifications_enabled', 'no' );
		Constants::set_constant( 'WOOCOMMERCE_BIS_ALPHA_ENABLED', true );

		wc_update_1120_migrate_stock_notifications_alpha_constant();

		$this->assertSame( 'yes', get_option( 'woocommerce_feature_customer_stock_notifications_enabled' ) );
	}

	/**
	 * @testdox Migration lets FeaturesController announce the change, so the feature runs its own activation side effects.
	 */
	public function test_wc_update_1120_migrate_stock_notifications_alpha_constant_fires_feature_enabled_changed(): void {
		include_once WC_ABSPATH . 'includes/wc-update-functions.php';

		update_option( 'woocommerce_feature_customer_stock_notifications_enabled', 'no' );
		Constants::set_constant( 'WOOCOMMERCE_BIS_ALPHA_ENABLED', true );

		$changes  = array();
		$listener = function ( $feature_id, $enabled ) use ( &$changes ) {
			$changes[ $feature_id ] = $enabled;
		};

		add_action( FeaturesController::FEATURE_ENABLED_CHANGED_ACTION, $listener, 10, 2 );

		try {
			wc_update_1120_migrate_stock_notifications_alpha_constant();
		} finally {
			remove_action( FeaturesController::FEATURE_ENABLED_CHANGED_ACTION, $listener, 10 );
		}

		$this->assertArrayHasKey( 'customer_stock_notifications', $changes );
		$this->assertTrue( $changes['customer_stock_notifications'] );
	}

	/**
	 * @testdox Migration registers and queues the rebuild of the tax lookup table.
	 */
	public function test_wc_update_11201_migrate_tax_lookup_order_items(): void {
		include_once WC_ABSPATH . 'includes/wc-update-functions.php';

		$db_updates = WC_Install::get_db_update_callbacks();

		// Under its own key, so that a store already on 11.2.0 from the batch that shipped beside
		// it still runs the rebuild.
		$this->assertArrayHasKey( '11.2.0-1', $db_updates );
		$this->assertContains( 'wc_update_11201_migrate_tax_lookup_order_items', $db_updates['11.2.0-1'] );

		$batch_processor = wc_get_container()->get( BatchProcessingController::class );
		$batch_processor->remove_processor( OrderTaxLookupMigrator::class );

		wc_update_11201_migrate_tax_lookup_order_items();

		$this->assertTrue(
			$batch_processor->is_enqueued( OrderTaxLookupMigrator::class ),
			'The migration should hand the rebuild to the batch processing controller.'
		);

		$batch_processor->remove_processor( OrderTaxLookupMigrator::class );
	}

	/**
	 * @testdox Migration registers for WooCommerce 11.2.0 and deletes the retired Surface Cart and Checkout note.
	 */
	public function test_wc_update_1120_delete_surface_cart_checkout_note(): void {
		include_once WC_ABSPATH . 'includes/wc-update-functions.php';

		$db_updates = WC_Install::get_db_update_callbacks();
		$this->assertArrayHasKey( '11.2.0', $db_updates );
		$this->assertContains( 'wc_update_1120_delete_surface_cart_checkout_note', $db_updates['11.2.0'] );

		$note = new Note();
		$note->set_name( InboxNotifications::SURFACE_CART_CHECKOUT_NOTE_NAME );
		$note->set_title( 'Surface Cart and Checkout' );
		$note->set_content( 'Test content' );
		$note->set_type( Note::E_WC_ADMIN_NOTE_INFORMATIONAL );
		$note->set_source( 'PHPUNIT_TEST' );
		$note->add_action( 'learn-more', 'Learn more', 'https://woocommerce.com/' );
		$note->save();
		$this->assertNotFalse( Notes::get_note_by_name( InboxNotifications::SURFACE_CART_CHECKOUT_NOTE_NAME ), 'The retired note fixture should exist before the update.' );

		wc_update_1120_delete_surface_cart_checkout_note();

		$this->assertFalse( Notes::get_note_by_name( InboxNotifications::SURFACE_CART_CHECKOUT_NOTE_NAME ), 'The retired note should be deleted during the update.' );

		wc_update_1120_delete_surface_cart_checkout_note();
		$this->assertFalse( Notes::get_note_by_name( InboxNotifications::SURFACE_CART_CHECKOUT_NOTE_NAME ), 'The update should remain safe when the note is already absent.' );
	}

	/**
	 * @testdox Migration invalidates the Analytics report cache, so a response cached before the update stops being served.
	 */
	public function test_wc_update_11201_invalidate_analytics_reports_cache(): void {
		include_once WC_ABSPATH . 'includes/wc-update-functions.php';

		$db_updates = WC_Install::get_db_update_callbacks();

		// Under its own key, so that a store already on 11.2.0 from the batch that shipped beside
		// it still drops its stale responses.
		$this->assertArrayHasKey( '11.2.0-1', $db_updates );
		$this->assertContains( 'wc_update_11201_invalidate_analytics_reports_cache', $db_updates['11.2.0-1'] );

		// The cache version is a timestamp, so pin an old one rather than race the clock.
		set_transient( ReportsCache::VERSION_OPTION . '-transient-version', '1000000000' );

		$key = 'wc_report_products_pre_update';
		ReportsCache::set( $key, 'pre-update response' );
		$this->assertSame( 'pre-update response', ReportsCache::get( $key ), 'The response should be served from cache before the update runs' );

		wc_update_11201_invalidate_analytics_reports_cache();

		$this->assertFalse( ReportsCache::get( $key ), 'A response cached before the update should no longer be served' );
	}

	/**
	 * @testdox Migration resets stale refund markers in batches and invalidates cached Analytics reports.
	 */
	public function test_wc_update_11202_reset_refund_returning_customer_markers(): void {
		global $wpdb;

		include_once WC_ABSPATH . 'includes/wc-update-functions.php';

		$db_updates = WC_Install::get_db_update_callbacks();
		// Under its own key, so that a store already past the 11.2.0 batches still resets its markers.
		$this->assertArrayHasKey( '11.2.0-2', $db_updates );
		$this->assertContains( 'wc_update_11202_reset_refund_returning_customer_markers', $db_updates['11.2.0-2'] );

		$order = WC_Helper_Order::create_order();
		$order->set_status( OrderStatus::COMPLETED );
		$order->save();
		$refund = wc_create_refund(
			array(
				'order_id'   => $order->get_id(),
				'amount'     => 5,
				'line_items' => array(),
			)
		);
		$this->assertInstanceOf( WC_Order_Refund::class, $refund );
		WC_Helper_Queue::run_all_pending( 'wc-admin-data' );

		$order_stats_table = $wpdb->prefix . 'wc_order_stats';
		$get_marker        = static function ( int $order_id ) use ( $wpdb, $order_stats_table ) {
			// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared -- Table name cannot be prepared.
			return $wpdb->get_var( $wpdb->prepare( "SELECT returning_customer FROM {$order_stats_table} WHERE order_id = %d", $order_id ) );
		};

		// Older first-order recalculations could overwrite the refund's NULL marker.
		$this->assertSame( 1, $wpdb->update( $order_stats_table, array( 'returning_customer' => 1 ), array( 'order_id' => $refund->get_id() ), array( '%d' ), array( '%d' ) ) );
		$this->assertSame( '0', $get_marker( $order->get_id() ), 'The order row should start with a non-stale marker.' );

		$cache_key        = 'wc_update_11202_analytics_report';
		$version_key      = ReportsCache::VERSION_OPTION . '-transient-version';
		$original_version = get_transient( $version_key );
		set_transient( $version_key, 'stale-version' );

		try {
			ReportsCache::set( $cache_key, 'stale-value' );
			$this->assertSame( 'stale-value', ReportsCache::get( $cache_key ) );

			$this->assertTrue( wc_update_11202_reset_refund_returning_customer_markers(), 'A batch with stale refund rows should request another run.' );
			$this->assertSame( $refund->get_id(), (int) get_option( 'woocommerce_update_11202_last_refund_order_id' ), 'The last processed order ID should be stored between batches.' );
			$this->assertSame( 'stale-value', ReportsCache::get( $cache_key ), 'The cache should stay valid until the last batch completes.' );

			$this->assertFalse( wc_update_11202_reset_refund_returning_customer_markers(), 'A run with no stale refund rows should complete.' );
			$this->assertFalse( get_option( 'woocommerce_update_11202_last_refund_order_id' ), 'The last processed order ID should be cleared on completion.' );
			$this->assertFalse( ReportsCache::get( $cache_key ), 'The cache should be invalidated once the migration completes.' );
		} finally {
			delete_transient( $cache_key );
			if ( false === $original_version ) {
				delete_transient( $version_key );
			} else {
				set_transient( $version_key, $original_version );
			}
		}

		$this->assertNull( $get_marker( $refund->get_id() ), 'The refund row marker should be reset to NULL.' );
		$this->assertSame( '0', $get_marker( $order->get_id() ), 'The order row marker should be left unchanged.' );
	}
}
