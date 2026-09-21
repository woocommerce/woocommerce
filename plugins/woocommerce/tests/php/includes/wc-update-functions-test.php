<?php
/**
 * Update functions tests
 *
 * @package WooCommerce\Tests\Functions.
 */

use Automattic\WooCommerce\Internal\DataStores\Orders\DataSynchronizer;
use Automattic\WooCommerce\Caches\OrderCache;
use Automattic\WooCommerce\Utilities\OrderUtil;
use Automattic\WooCommerce\RestApi\UnitTests\Helpers\OrderHelper;
use Automattic\WooCommerce\Internal\DataStores\Orders\OrdersTableDataStore;
use Automattic\WooCommerce\Database\Migrations\CustomOrderTable\PostsToOrdersMigrationController;
use Automattic\Jetpack\Constants;
use Automattic\WooCommerce\Admin\API\Reports\Cache as ReportsCache;
use Automattic\WooCommerce\Admin\Notes\Note;
use Automattic\WooCommerce\Admin\Notes\Notes;
use Automattic\WooCommerce\Blocks\InboxNotifications;
use Automattic\WooCommerce\Blocks\Options as BlockOptions;
use Automattic\WooCommerce\Blocks\Utils\BlockTemplateUtils;
use Automattic\WooCommerce\Enums\OrderStatus;
use Automattic\WooCommerce\Internal\Features\FeaturesController;
use Automattic\WooCommerce\Internal\VariationGallery\Package as VariationGalleryPackage;

/**
 * Class WC_Core_Functions_Test
 */
class WC_Update_Functions_Test extends \WC_Unit_Test_Case {

	/**
	 * Whether HPOS was authoritative before the test.
	 *
	 * @var bool
	 */
	private $previous_hpos_state;

	/**
	 * Set up test fixtures.
	 */
	public function setUp(): void {
		parent::setUp();
		// Tests that migrate orders leave the two storages out of sync, which would otherwise block restoring the storage setting.
		add_filter( 'wc_allow_changing_orders_storage_while_sync_is_pending', '__return_true' );
		$this->previous_hpos_state = OrderUtil::custom_orders_table_usage_is_enabled();
		OrderHelper::create_order_custom_table_if_not_exist();
	}

	/**
	 * Tear down test fixtures.
	 */
	public function tearDown(): void {
		OrderHelper::toggle_cot_feature_and_usage( $this->previous_hpos_state );
		remove_filter( 'wc_allow_changing_orders_storage_while_sync_is_pending', '__return_true' );
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
	 * @testdox Migration removes the deprecated variation gallery feature option.
	 */
	public function test_wc_update_11101_remove_deprecated_variation_gallery_option(): void {
		include_once WC_ABSPATH . 'includes/wc-update-functions.php';

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
	 * @testdox Migration deletes the cached dashboard out-of-stock count.
	 */
	public function test_wc_update_1110_delete_dashboard_outofstock_count_transient(): void {
		include_once WC_ABSPATH . 'includes/wc-update-functions.php';

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
	 * @testdox Migration sanitizes dirty coupon codes and invalidates every coupon code lookup entry once, on the last batch.
	 */
	public function test_wc_update_450_sanitize_coupons_code_invalidates_the_lookup_cache(): void {
		global $wpdb;

		$coupon_id = wp_insert_post(
			array(
				'post_type'   => 'shop_coupon',
				'post_title'  => 'dirty-code',
				'post_status' => 'publish',
			)
		);

		// The migration exists for titles WordPress would not store today, so write the raw one directly.
		$wpdb->update( $wpdb->posts, array( 'post_title' => ' dirty-code ' ), array( 'ID' => $coupon_id ) );
		clean_post_cache( $coupon_id );

		// Start the batch at this coupon, so the assertions do not depend on what else is in the database.
		update_option( 'woocommerce_update_450_last_coupon_id', $coupon_id - 1 );

		include_once WC_ABSPATH . 'includes/wc-update-functions.php';

		$prefix_before = WC_Cache_Helper::get_cache_prefix( 'coupons' );

		$this->assertTrue( (bool) wc_update_450_sanitize_coupons_code(), 'The migration should ask for another batch while coupons remain.' );

		$this->assertSame( 'dirty-code', get_post( $coupon_id )->post_title, 'The migration should have sanitized the code.' );
		$this->assertSame( 'yes', get_option( 'woocommerce_update_450_codes_changed' ), 'The rewrite should be recorded for the last batch.' );
		$this->assertSame(
			$prefix_before,
			WC_Cache_Helper::get_cache_prefix( 'coupons' ),
			'A batch that rewrites a code should not rotate the coupons group on its own.'
		);

		$this->run_wc_update_450_sanitize_coupons_code_to_completion();

		$this->assertNotSame(
			$prefix_before,
			WC_Cache_Helper::get_cache_prefix( 'coupons' ),
			'The last batch should strand the lookup entries the rewritten codes were cached under.'
		);
		$this->assertFalse( get_option( 'woocommerce_update_450_codes_changed' ), 'The migration should clean up the flag it persisted.' );
	}

	/**
	 * @testdox Migration keeps the coupon code lookup cache when there is nothing to sanitize.
	 */
	public function test_wc_update_450_sanitize_coupons_code_keeps_the_lookup_cache_when_no_code_changes(): void {
		$coupon_id = wp_insert_post(
			array(
				'post_type'   => 'shop_coupon',
				'post_title'  => 'clean-code',
				'post_status' => 'publish',
			)
		);

		update_option( 'woocommerce_update_450_last_coupon_id', $coupon_id - 1 );

		include_once WC_ABSPATH . 'includes/wc-update-functions.php';

		$prefix_before = WC_Cache_Helper::get_cache_prefix( 'coupons' );

		$this->run_wc_update_450_sanitize_coupons_code_to_completion();

		$this->assertSame(
			$prefix_before,
			WC_Cache_Helper::get_cache_prefix( 'coupons' ),
			'A run that rewrites no code should leave the warm lookup entries alone.'
		);
	}

	/**
	 * Run the 4.5.0 coupon code migration until it reports there is nothing left to process.
	 *
	 * @return void
	 */
	private function run_wc_update_450_sanitize_coupons_code_to_completion(): void {
		$batches = 0;

		while ( wc_update_450_sanitize_coupons_code() ) {
			$this->assertLessThan( 100, ++$batches, 'The coupon code migration should reach its last batch.' );
		}
	}

	/**
	 * @testdox Migration deletes the retired Surface Cart and Checkout note.
	 */
	public function test_wc_update_1120_delete_surface_cart_checkout_note(): void {
		include_once WC_ABSPATH . 'includes/wc-update-functions.php';

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
	 * @testdox Migration resets stale refund markers in batches and invalidates cached Analytics reports.
	 */
	public function test_wc_update_11202_reset_refund_returning_customer_markers(): void {
		global $wpdb;

		include_once WC_ABSPATH . 'includes/wc-update-functions.php';

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

	/**
	 * @testdox wc_update_1120_cleanup_inherited_variation_images removes a variation thumbnail that duplicates the parent's featured image.
	 */
	public function test_wc_update_1120_removes_variation_thumbnail_duplicating_parent() {
		include_once WC_ABSPATH . 'includes/wc-update-functions.php';
		update_option( 'woocommerce_db_version', '11.1.0' );
		$variation_id = $this->create_variation_with_thumbnails( '77', '77' );

		$this->assertFalse( wc_update_1120_cleanup_inherited_variation_images(), 'A batch smaller than the limit should complete in one run.' );
		$this->assertSame( '', get_post_meta( $variation_id, '_thumbnail_id', true ), 'The duplicated thumbnail should be removed so the variation inherits again.' );
	}

	/**
	 * @testdox wc_update_1120_cleanup_inherited_variation_images keeps a variation thumbnail that diverged from the parent's featured image.
	 */
	public function test_wc_update_1120_keeps_diverged_variation_thumbnail() {
		include_once WC_ABSPATH . 'includes/wc-update-functions.php';
		update_option( 'woocommerce_db_version', '11.1.0' );
		$variation_id = $this->create_variation_with_thumbnails( '77', '88' );

		$this->assertFalse( wc_update_1120_cleanup_inherited_variation_images() );
		$this->assertSame( '88', get_post_meta( $variation_id, '_thumbnail_id', true ), 'A diverged value may be a deliberate merchant choice and must survive the cleanup.' );
	}

	/**
	 * @testdox wc_update_1120_cleanup_inherited_variation_images removes only metadata values that duplicate the parent image.
	 */
	public function test_wc_update_1120_keeps_other_thumbnail_metadata_values() {
		include_once WC_ABSPATH . 'includes/wc-update-functions.php';
		update_option( 'woocommerce_db_version', '11.1.0' );
		$variation_id = $this->create_variation_with_thumbnails( '77', '77' );
		add_post_meta( $variation_id, '_thumbnail_id', '88' );

		wc_update_1120_cleanup_inherited_variation_images();

		$this->assertSame( array( '88' ), get_post_meta( $variation_id, '_thumbnail_id', false ), 'Only the value duplicating the parent image should be removed.' );
	}

	/**
	 * @testdox wc_update_1120_cleanup_inherited_variation_images never runs again once completed, even if update callbacks are replayed.
	 */
	public function test_wc_update_1120_does_not_run_again_after_completion() {
		include_once WC_ABSPATH . 'includes/wc-update-functions.php';
		update_option( 'woocommerce_db_version', '11.1.0' );
		update_option( 'woocommerce_update_1120_completed_at', time() );
		$variation_id = $this->create_variation_with_thumbnails( '77', '77' );

		$this->assertFalse( wc_update_1120_cleanup_inherited_variation_images() );
		$this->assertSame( '77', get_post_meta( $variation_id, '_thumbnail_id', true ), 'A value matching the parent after the one-time cleanup may be deliberate and must survive a replay.' );
	}

	/**
	 * @testdox wc_update_1120_cleanup_inherited_variation_images matches only the parent's canonical thumbnail, not stale duplicate rows.
	 */
	public function test_wc_update_1120_ignores_stale_duplicate_parent_thumbnail_rows() {
		include_once WC_ABSPATH . 'includes/wc-update-functions.php';
		update_option( 'woocommerce_db_version', '11.1.0' );
		$variation_id = $this->create_variation_with_thumbnails( '88', '77' );
		$parent_id    = wp_get_post_parent_id( $variation_id );
		add_post_meta( $parent_id, '_thumbnail_id', '77' );

		wc_update_1120_cleanup_inherited_variation_images();

		$this->assertSame( '77', get_post_meta( $variation_id, '_thumbnail_id', true ), 'A value matching only a stale duplicate parent row may be deliberate and must survive.' );
	}

	/**
	 * @testdox wc_update_1120_cleanup_inherited_variation_images skips stores upgrading from before the variation gallery existed.
	 */
	public function test_wc_update_1120_skips_stores_upgrading_from_before_10_9() {
		include_once WC_ABSPATH . 'includes/wc-update-functions.php';
		update_option( 'woocommerce_db_version', '10.8.0' );
		$variation_id = $this->create_variation_with_thumbnails( '77', '77' );

		$this->assertFalse( wc_update_1120_cleanup_inherited_variation_images() );
		$this->assertSame( '77', get_post_meta( $variation_id, '_thumbnail_id', true ), 'Stores never exposed to the bug must not be touched.' );
	}

	/**
	 * Create a variable product and return its first variation's ID, with the given thumbnail meta on parent and variation.
	 *
	 * @param string $parent_thumbnail_id    Meta value for the parent's _thumbnail_id.
	 * @param string $variation_thumbnail_id Meta value for the variation's _thumbnail_id.
	 * @return int
	 */
	private function create_variation_with_thumbnails( string $parent_thumbnail_id, string $variation_thumbnail_id ): int {
		$product      = WC_Helper_Product::create_variation_product();
		$variation_id = $product->get_children()[0];

		update_post_meta( $product->get_id(), '_thumbnail_id', $parent_thumbnail_id );
		update_post_meta( $variation_id, '_thumbnail_id', $variation_thumbnail_id );

		return $variation_id;
	}

	/**
	 * @testdox wc_update_1130_repair_hpos_order_dates_from_posts should fill HPOS dates that an earlier migration left empty from the order's post.
	 */
	public function test_wc_update_1130_repairs_hpos_dates_from_posts(): void {
		global $wpdb;

		include_once WC_ABSPATH . 'includes/wc-update-functions.php';

		update_option( 'timezone_string', 'Europe/Amsterdam' );
		$order_id = OrderHelper::create_complex_wp_post_order();
		$wpdb->update(
			$wpdb->posts,
			array(
				'post_date_gmt'     => '0000-00-00 00:00:00',
				'post_modified_gmt' => '0000-00-00 00:00:00',
			),
			array( 'ID' => $order_id )
		);
		clean_post_cache( $order_id );
		wc_get_container()->get( PostsToOrdersMigrationController::class )->migrate_orders( array( $order_id ) );
		$orders_table = OrdersTableDataStore::get_orders_table_name();
		// The shape earlier migrations left behind.
		$wpdb->update(
			$orders_table,
			array(
				'date_created_gmt' => '0000-00-00 00:00:00',
				'date_updated_gmt' => null,
			),
			array( 'id' => $order_id )
		);
		$post = get_post( $order_id );

		// A second order whose legacy data was cleaned up: the post is a placeholder but keeps its date columns.
		$cleaned_id = OrderHelper::create_complex_wp_post_order();
		$wpdb->update( $wpdb->posts, array( 'post_date_gmt' => '0000-00-00 00:00:00' ), array( 'ID' => $cleaned_id ) );
		clean_post_cache( $cleaned_id );
		wc_get_container()->get( PostsToOrdersMigrationController::class )->migrate_orders( array( $cleaned_id ) );
		$wpdb->update( $orders_table, array( 'date_created_gmt' => '0000-00-00 00:00:00' ), array( 'id' => $cleaned_id ) );
		$wpdb->update( $wpdb->posts, array( 'post_type' => DataSynchronizer::PLACEHOLDER_ORDER_POST_TYPE ), array( 'ID' => $cleaned_id ) );
		clean_post_cache( $cleaned_id );
		$cleaned_post = get_post( $cleaned_id );

		// A cached order object without a date must not survive the repair.
		$order_cache = wc_get_container()->get( OrderCache::class );
		$order_cache->set( wc_get_order( $order_id ), $order_id );
		// Creating the orders queued their own imports: clear them so the assertion below is about the routine.
		as_unschedule_all_actions( 'wc-admin_import_orders' );

		$this->assertFalse( wc_update_1130_repair_hpos_order_dates_from_posts(), 'A single small batch should not request another run' );

		$row = $wpdb->get_row( $wpdb->prepare( "SELECT date_created_gmt, date_updated_gmt FROM {$orders_table} WHERE id = %d", $order_id ) ); // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		$this->assertSame( get_gmt_from_date( $post->post_date ), $row->date_created_gmt, 'Created date should come from post_date converted with the site timezone' );
		$this->assertSame( get_gmt_from_date( $post->post_modified ), $row->date_updated_gmt, 'Updated date should come from post_modified' );
		$this->assertNotSame( $post->post_date, $row->date_created_gmt, 'The local date must have been converted' );
		$this->assertSame( get_gmt_from_date( $cleaned_post->post_date ), $wpdb->get_var( $wpdb->prepare( "SELECT date_created_gmt FROM {$orders_table} WHERE id = %d", $cleaned_id ) ), 'A cleaned-up order is repaired from its placeholder post' ); // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		$this->assertFalse( $order_cache->is_cached( $order_id ), 'The cached order object must be dropped' );
		$this->assertNotFalse( as_next_scheduled_action( 'wc-admin_import_orders', array( $order_id ) ), 'The Analytics import must be queued for the repaired order' );
	}

	/**
	 * @testdox wc_update_1130_repair_hpos_order_dates_from_posts should import repaired orders inline when Analytics scheduling is disabled.
	 */
	public function test_wc_update_1130_imports_inline_when_analytics_scheduling_is_disabled(): void {
		global $wpdb;

		include_once WC_ABSPATH . 'includes/wc-update-functions.php';

		$order_id = OrderHelper::create_complex_wp_post_order();
		wc_get_container()->get( PostsToOrdersMigrationController::class )->migrate_orders( array( $order_id ) );
		$orders_table = OrdersTableDataStore::get_orders_table_name();
		$wpdb->update( $orders_table, array( 'date_created_gmt' => '0000-00-00 00:00:00' ), array( 'id' => $order_id ) );
		OrderHelper::toggle_cot_feature_and_usage( true );
		// Creating the order queued its own import. Start from a clean slate so only the routine's behaviour is measured.
		as_unschedule_all_actions( 'wc-admin_import_orders' );
		$wpdb->delete( $wpdb->prefix . 'wc_order_stats', array( 'order_id' => $order_id ) );
		add_filter( 'woocommerce_analytics_disable_action_scheduling', '__return_true' );

		wc_update_1130_repair_hpos_order_dates_from_posts();

		$this->assertFalse( as_next_scheduled_action( 'wc-admin_import_orders', array( $order_id ) ), 'Nothing should be queued when scheduling is disabled' );
		$this->assertNotNull( $wpdb->get_var( $wpdb->prepare( "SELECT order_id FROM {$wpdb->prefix}wc_order_stats WHERE order_id = %d", $order_id ) ), 'The order should have been imported inline' );
	}

	/**
	 * Insert a full batch of HPOS rows with no created date whose posts have no date either, so the repair has to advance its cursor.
	 *
	 * @return int The id of the last row in the batch.
	 */
	private function insert_full_batch_of_unrepairable_orders(): int {
		global $wpdb;

		$orders_table = OrdersTableDataStore::get_orders_table_name();
		$first_id     = (int) $wpdb->get_var( "SELECT GREATEST( COALESCE( ( SELECT MAX( ID ) FROM {$wpdb->posts} ), 0 ), COALESCE( ( SELECT MAX( id ) FROM {$orders_table} ), 0 ) )" ) + 1; // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		$posts        = array();
		$orders       = array();
		for ( $id = $first_id; $id < $first_id + 500; $id++ ) {
			$posts[]  = "( {$id}, 'shop_order', 'wc-completed', '0000-00-00 00:00:00', '0000-00-00 00:00:00', '0000-00-00 00:00:00', '0000-00-00 00:00:00', '', '', '', '', '', '' )";
			$orders[] = "( {$id}, 'shop_order', 'wc-completed', NULL, '2026-01-01 00:00:00' )";
		}
		// phpcs:disable WordPress.DB.PreparedSQL.NotPrepared, WordPress.DB.PreparedSQL.InterpolatedNotPrepared -- Generated integers and literals only.
		$wpdb->query( "INSERT INTO {$wpdb->posts} ( ID, post_type, post_status, post_date, post_date_gmt, post_modified, post_modified_gmt, post_content, post_title, post_excerpt, to_ping, pinged, post_content_filtered ) VALUES " . implode( ',', $posts ) );
		$wpdb->query( "INSERT INTO {$orders_table} ( id, type, status, date_created_gmt, date_updated_gmt ) VALUES " . implode( ',', $orders ) );
		// phpcs:enable

		return $first_id + 499;
	}

	/**
	 * @testdox wc_update_1130_repair_hpos_order_dates_from_posts should keep going when another run has already saved the same or a later cursor.
	 *
	 * @testWith [0]
	 *           [1000]
	 *
	 * @param int $ahead How far past this batch the other run has already moved the cursor.
	 */
	public function test_wc_update_1130_accepts_a_cursor_saved_by_a_concurrent_run( int $ahead ): void {
		include_once WC_ABSPATH . 'includes/wc-update-functions.php';

		$last_id      = $this->insert_full_batch_of_unrepairable_orders();
		$option       = 'woocommerce_update_1130_last_repaired_order_id';
		$orders_table = OrdersTableDataStore::get_orders_table_name();
		// The other run saves its cursor right after this one has read its batch.
		$concurrent_save = function ( $query ) use ( $option, $last_id, $ahead, $orders_table ) {
			if ( false !== strpos( $query, "FROM {$orders_table} o" ) ) {
				update_option( $option, $last_id + $ahead, false );
			}
			return $query;
		};
		add_filter( 'query', $concurrent_save );

		$this->assertTrue( wc_update_1130_repair_hpos_order_dates_from_posts(), 'A cursor saved by another run is progress, not a failure' );
		$this->assertSame( $last_id + $ahead, (int) get_option( $option ), 'The cursor must never move backwards' );
	}

	/**
	 * @testdox wc_update_1130_repair_hpos_order_dates_from_posts should leave rows alone when they have dates or their post has none.
	 */
	public function test_wc_update_1130_leaves_dated_rows_and_dateless_posts_alone(): void {
		global $wpdb;

		include_once WC_ABSPATH . 'includes/wc-update-functions.php';

		$dated_id    = OrderHelper::create_complex_wp_post_order();
		$dateless_id = OrderHelper::create_complex_wp_post_order();
		$wpdb->update(
			$wpdb->posts,
			array(
				'post_date'     => '0000-00-00 00:00:00',
				'post_date_gmt' => '0000-00-00 00:00:00',
			),
			array( 'ID' => $dateless_id )
		);
		clean_post_cache( $dateless_id );
		wc_get_container()->get( PostsToOrdersMigrationController::class )->migrate_orders( array( $dated_id, $dateless_id ) );
		$orders_table = OrdersTableDataStore::get_orders_table_name();
		$dated_before = $wpdb->get_var( $wpdb->prepare( "SELECT date_created_gmt FROM {$orders_table} WHERE id = %d", $dated_id ) ); // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared

		$this->assertFalse( wc_update_1130_repair_hpos_order_dates_from_posts() );

		$this->assertSame( $dated_before, $wpdb->get_var( $wpdb->prepare( "SELECT date_created_gmt FROM {$orders_table} WHERE id = %d", $dated_id ) ), 'A dated row must not change' ); // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		$this->assertSame( '0000-00-00 00:00:00', $wpdb->get_var( $wpdb->prepare( "SELECT date_created_gmt FROM {$orders_table} WHERE id = %d", $dateless_id ) ), 'A post with no date gives nothing to repair from' ); // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
	}

	/**
	 * @testdox Migration rewrites stored stock notification emails in canonical form and leaves canonical rows alone.
	 */
	public function test_wc_update_11203_normalize_stock_notification_emails(): void {
		global $wpdb;

		include_once WC_ABSPATH . 'includes/wc-update-functions.php';

		$db_updates = WC_Install::get_db_update_callbacks();

		$this->assertArrayHasKey( '11.2.0', $db_updates );
		$this->assertContains( 'wc_update_11203_normalize_stock_notification_emails', $db_updates['11.2.0'] );

		$table = $wpdb->prefix . 'wc_stock_notifications';
		foreach ( array( 'Legacy@Example.com', " padded@example.com\t", 'canonical@example.com' ) as $email ) {
			$wpdb->insert(
				$table,
				array(
					'product_id'       => 1,
					'user_id'          => 0,
					'user_email'       => $email,
					'status'           => 'active',
					'date_created_gmt' => gmdate( 'Y-m-d H:i:s' ),
				)
			);
		}

		$this->assertFalse( wc_update_11203_normalize_stock_notification_emails(), 'A table smaller than one batch should complete in a single run' );
		$this->assertFalse( get_option( 'woocommerce_update_11203_last_stock_notification_id' ), 'The cursor should be cleared on completion' );

		$emails = $wpdb->get_col( "SELECT user_email FROM {$table} WHERE product_id = 1 ORDER BY id" ); // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared

		$this->assertSame( array( 'legacy@example.com', 'padded@example.com', 'canonical@example.com' ), $emails );
	}

	/**
	 * @testdox Migration resumes from the persisted cursor and leaves rows before it untouched.
	 */
	public function test_wc_update_11203_normalize_stock_notification_emails_resumes_from_cursor(): void {
		global $wpdb;

		include_once WC_ABSPATH . 'includes/wc-update-functions.php';

		$table = $wpdb->prefix . 'wc_stock_notifications';
		$ids   = array();
		foreach ( array( 'Before@Example.com', 'After@Example.com' ) as $email ) {
			$wpdb->insert(
				$table,
				array(
					'product_id'       => 1,
					'user_id'          => 0,
					'user_email'       => $email,
					'status'           => 'active',
					'date_created_gmt' => gmdate( 'Y-m-d H:i:s' ),
				)
			);
			$ids[] = $wpdb->insert_id;
		}

		update_option( 'woocommerce_update_11203_last_stock_notification_id', $ids[0], false );

		wc_update_11203_normalize_stock_notification_emails();

		$emails = $wpdb->get_col( "SELECT user_email FROM {$table} WHERE product_id = 1 ORDER BY id" ); // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared

		$this->assertSame( array( 'Before@Example.com', 'after@example.com' ), $emails );
	}

	/**
	 * @testdox Migration stops at the first failed write, clears its cursor and does not request another run.
	 */
	public function test_wc_update_11203_normalize_stock_notification_emails_stops_on_failed_write(): void {
		global $wpdb;

		include_once WC_ABSPATH . 'includes/wc-update-functions.php';

		$table = $wpdb->prefix . 'wc_stock_notifications';
		foreach ( array( 'First@Example.com', 'Second@Example.com' ) as $email ) {
			$wpdb->insert(
				$table,
				array(
					'product_id'       => 1,
					'user_id'          => 0,
					'user_email'       => $email,
					'status'           => 'active',
					'date_created_gmt' => gmdate( 'Y-m-d H:i:s' ),
				)
			);
		}

		$break_update = function ( $query ) use ( $table ) {
			return 0 === strpos( $query, "UPDATE `{$table}` SET `user_email`" ) ? "UPDATE `{$table}` SET `no_such_column` = 1" : $query;
		};
		add_filter( 'query', $break_update );
		$suppressed = $wpdb->suppress_errors();

		try {
			$this->assertFalse( wc_update_11203_normalize_stock_notification_emails(), 'A failed write should not request another run' );
		} finally {
			$wpdb->suppress_errors( $suppressed );
			remove_filter( 'query', $break_update );
		}

		$this->assertFalse( get_option( 'woocommerce_update_11203_last_stock_notification_id' ), 'The cursor should be cleared after a failed write' );

		$emails = $wpdb->get_col( "SELECT user_email FROM {$table} WHERE product_id = 1 ORDER BY id" ); // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared

		$this->assertSame( array( 'First@Example.com', 'Second@Example.com' ), $emails, 'No row should be rewritten after the write fails' );
	}
}
