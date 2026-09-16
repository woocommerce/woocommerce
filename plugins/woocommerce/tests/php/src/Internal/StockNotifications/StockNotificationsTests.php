<?php

declare( strict_types = 1 );
namespace Automattic\WooCommerce\Tests\Internal\StockNotifications;

use Automattic\Jetpack\Constants;
use Automattic\WooCommerce\Internal\Features\FeaturesController;
use Automattic\WooCommerce\Internal\StockNotifications\DataRetentionController;
use Automattic\WooCommerce\Internal\StockNotifications\NotificationQuery;
use Automattic\WooCommerce\Internal\StockNotifications\StockNotifications;
use WC_Admin_Settings;
use WC_Settings_Products;

/**
 * StockNotifications controller tests.
 */
class StockNotificationsTests extends \WC_Unit_Test_Case {
	use StockNotificationsFeatureTrait;

	/**
	 * Database version to restore after a test that changes it.
	 *
	 * @var string|null
	 */
	private ?string $original_db_version = null;

	/**
	 * Set up before each test.
	 */
	public function setUp(): void {
		parent::setUp();
		$this->enable_stock_notifications_feature();
	}

	/**
	 * Clean up after tests.
	 */
	public function tearDown(): void {
		Constants::clear_single_constant( 'WOOCOMMERCE_BIS_ALPHA_ENABLED' );
		if ( null !== $this->original_db_version ) {
			update_option( 'woocommerce_db_version', $this->original_db_version );
			$this->original_db_version = null;
		}
		wc_get_container()->get( DataRetentionController::class )->clear_daily_task();
		delete_option( 'woocommerce_customer_stock_notifications_unverified_deletions_days_threshold' );
		delete_option( 'woocommerce_queue_flush_rewrite_rules' );
		$this->restore_stock_notifications_feature_option();
		parent::tearDown();
	}

	/**
	 * Fire the feature-changed action the way FeaturesController does.
	 *
	 * @param bool   $enabled    Whether the feature is now enabled.
	 * @param string $feature_id The feature that changed.
	 */
	private function fire_feature_changed( bool $enabled, string $feature_id = StockNotifications::FEATURE_NAME ): void {
		do_action( FeaturesController::FEATURE_ENABLED_CHANGED_ACTION, $feature_id, $enabled );
	}

	/**
	 * @testdox Enabling the feature schedules the daily data retention task.
	 */
	public function test_enabling_the_feature_schedules_the_daily_task(): void {
		update_option( 'woocommerce_customer_stock_notifications_unverified_deletions_days_threshold', 30 );
		wc_get_container()->get( DataRetentionController::class )->clear_daily_task();
		$this->assertFalse( wp_get_schedule( DataRetentionController::DAILY_TASK_HOOK ) );

		$this->fire_feature_changed( true );

		$this->assertSame( 'daily', wp_get_schedule( DataRetentionController::DAILY_TASK_HOOK ) );
	}

	/**
	 * @testdox Disabling the feature clears the daily data retention task.
	 */
	public function test_disabling_the_feature_clears_the_daily_task(): void {
		update_option( 'woocommerce_customer_stock_notifications_unverified_deletions_days_threshold', 30 );
		$this->fire_feature_changed( true );
		$this->assertSame( 'daily', wp_get_schedule( DataRetentionController::DAILY_TASK_HOOK ) );

		$this->fire_feature_changed( false );

		$this->assertFalse( wp_get_schedule( DataRetentionController::DAILY_TASK_HOOK ) );
	}

	/**
	 * @testdox Toggling the feature queues a rewrite rules flush for the My Account endpoint.
	 */
	public function test_toggling_the_feature_queues_a_rewrite_flush(): void {
		foreach ( array( true, false ) as $enabled ) {
			delete_option( 'woocommerce_queue_flush_rewrite_rules' );

			$this->fire_feature_changed( $enabled );

			$this->assertSame(
				'yes',
				get_option( 'woocommerce_queue_flush_rewrite_rules' ),
				'Toggling the feature should queue a rewrite rules flush.'
			);
		}
	}

	/**
	 * @testdox The settings are registered outside admin, so the REST settings API can see them.
	 */
	public function test_settings_are_registered_outside_admin(): void {
		$this->assertFalse( is_admin(), 'This test must run outside the admin context, as REST requests do.' );
		$this->init_stock_notifications_services();

		$products_page = null;
		foreach ( WC_Admin_Settings::get_settings_pages() as $page ) {
			if ( $page instanceof WC_Settings_Products ) {
				$products_page = $page;
				break;
			}
		}
		$this->assertNotNull( $products_page, 'The Products settings page should be registered.' );

		$this->assertArrayHasKey( 'customer_stock_notifications', $products_page->get_sections() );

		$setting_ids = array_column( $products_page->get_settings_for_section( 'customer_stock_notifications' ), 'id' );
		$this->assertContains( 'woocommerce_customer_stock_notifications_allow_signups', $setting_ids );
		$this->assertContains( 'woocommerce_customer_stock_notifications_require_double_opt_in', $setting_ids );
		$this->assertContains( 'woocommerce_customer_stock_notifications_require_account', $setting_ids );
		$this->assertContains( 'woocommerce_customer_stock_notifications_unverified_deletions_days_threshold', $setting_ids );
	}

	/**
	 * @testdox Changes to unrelated features are ignored.
	 */
	public function test_other_features_are_ignored(): void {
		update_option( 'woocommerce_customer_stock_notifications_unverified_deletions_days_threshold', 30 );
		$this->fire_feature_changed( true );
		delete_option( 'woocommerce_queue_flush_rewrite_rules' );

		$this->fire_feature_changed( false, 'some_other_feature' );

		$this->assertSame( 'daily', wp_get_schedule( DataRetentionController::DAILY_TASK_HOOK ) );
		$this->assertFalse( get_option( 'woocommerce_queue_flush_rewrite_rules' ) );
	}

	/**
	 * @testdox Enabling the feature wires the hooks of container-resolved services.
	 */
	public function test_enabling_the_feature_wires_container_resolved_services(): void {
		$this->init_stock_notifications_services();

		$this->assertArrayHasKey( 'stock-notifications', wc_get_account_menu_items() );
	}

	/**
	 * @testdox The stock notification data store is not registered while the feature is disabled.
	 */
	public function test_maybe_init_services_does_nothing_when_the_feature_is_disabled(): void {
		$controller = wc_get_container()->get( StockNotifications::class );

		// Undo what setUp() wired up, then re-run with the feature off.
		remove_filter( 'woocommerce_data_stores', array( $controller, 'register_data_stores' ) );
		update_option( StockNotifications::ENABLE_OPTION_NAME, 'no' );
		$controller->maybe_init_services();

		$this->expectException( \Exception::class );
		$this->expectExceptionMessage( 'Invalid data store.' );

		new \WC_Data_Store( 'stock_notification' );
	}

	/**
	 * @testdox NotificationQuery returns empty results instead of throwing while the feature is disabled.
	 */
	public function test_notification_query_fails_soft_when_the_feature_is_disabled(): void {
		$controller = wc_get_container()->get( StockNotifications::class );

		remove_filter( 'woocommerce_data_stores', array( $controller, 'register_data_stores' ) );
		update_option( StockNotifications::ENABLE_OPTION_NAME, 'no' );

		$this->assertSame( array(), NotificationQuery::get_notifications( array() ) );
		$this->assertSame( 0, NotificationQuery::count_notifications( array() ) );
		$this->assertFalse( NotificationQuery::product_has_active_notifications( array( 1 ) ) );
		$this->assertFalse( NotificationQuery::notification_exists_by_email( 1, 'shopper@example.com' ) );
		$this->assertFalse( NotificationQuery::notification_exists_by_user_id( 1, 1 ) );
	}

	/**
	 * @testdox register_data_stores re-checks the option, so a hooked callback stays inert once the feature is turned off.
	 */
	public function test_register_data_stores_ignores_a_hooked_callback_once_the_feature_is_disabled(): void {
		$controller = wc_get_container()->get( StockNotifications::class );
		$this->assertNotFalse( has_filter( 'woocommerce_data_stores', array( $controller, 'register_data_stores' ) ) );

		update_option( StockNotifications::ENABLE_OPTION_NAME, 'no' );

		$stores = array( 'product' => 'WC_Product_Data_Store_CPT' );

		$this->assertSame( $stores, $controller->register_data_stores( $stores ) );
	}

	/**
	 * @testdox The alpha constant keeps the data store registered while the option is still unset.
	 */
	public function test_register_data_stores_falls_back_to_the_alpha_constant(): void {
		$controller = wc_get_container()->get( StockNotifications::class );

		delete_option( StockNotifications::ENABLE_OPTION_NAME );
		Constants::set_constant( 'WOOCOMMERCE_BIS_ALPHA_ENABLED', true );
		$this->set_db_version( '11.1.0' );

		$this->assertArrayHasKey( 'stock_notification', $controller->register_data_stores( array() ) );
	}

	/**
	 * @testdox The alpha constant is ignored once the database has been migrated to 11.2.0.
	 */
	public function test_alpha_constant_is_ignored_once_the_database_is_migrated(): void {
		$controller = wc_get_container()->get( StockNotifications::class );

		update_option( StockNotifications::ENABLE_OPTION_NAME, 'no' );
		Constants::set_constant( 'WOOCOMMERCE_BIS_ALPHA_ENABLED', true );
		$this->set_db_version( '11.2.0' );

		$this->assertArrayNotHasKey( 'stock_notification', $controller->register_data_stores( array() ) );

		wc_get_container()->reset_all_resolved();
		wc_get_container()->get( StockNotifications::class )->maybe_init_services();

		$this->assertArrayNotHasKey( 'stock-notifications', wc_get_account_menu_items() );
	}

	/**
	 * @testdox maybe_init_services() wires the services for alpha sites until the database is migrated.
	 */
	public function test_maybe_init_services_honors_the_alpha_constant_until_the_database_is_migrated(): void {
		update_option( StockNotifications::ENABLE_OPTION_NAME, 'no' );
		Constants::set_constant( 'WOOCOMMERCE_BIS_ALPHA_ENABLED', true );
		$this->set_db_version( '11.1.0' );

		wc_get_container()->reset_all_resolved();
		wc_get_container()->get( StockNotifications::class )->maybe_init_services();

		$this->assertArrayHasKey( 'stock-notifications', wc_get_account_menu_items() );
	}

	/**
	 * Set the stored database version for the current test, remembering the original.
	 *
	 * @param string $version Database version to store.
	 */
	private function set_db_version( string $version ): void {
		if ( null === $this->original_db_version ) {
			$this->original_db_version = (string) get_option( 'woocommerce_db_version', '' );
		}
		update_option( 'woocommerce_db_version', $version );
	}

	/**
	 * @testdox init_hooks() is deprecated and forwards to maybe_init_services().
	 */
	public function test_init_hooks_is_deprecated_and_forwards_to_maybe_init_services(): void {
		$this->setExpectedDeprecated( StockNotifications::class . '::init_hooks' );

		wc_get_container()->reset_all_resolved();
		wc_get_container()->get( StockNotifications::class )->init_hooks();

		$this->assertArrayHasKey( 'stock-notifications', wc_get_account_menu_items() );
	}

	/**
	 * @testdox init_hooks() defers to init when called before it, instead of building the feature definitions early.
	 */
	public function test_init_hooks_defers_to_init_when_called_early(): void {
		global $wp_actions;

		$this->setExpectedDeprecated( StockNotifications::class . '::init_hooks' );

		$controller = wc_get_container()->get( StockNotifications::class );
		$init_count = $wp_actions['init'] ?? 0;
		$was_hooked = has_action( 'init', array( $controller, 'maybe_init_services' ) );

		remove_filter( 'woocommerce_data_stores', array( $controller, 'register_data_stores' ) );
		remove_action( 'init', array( $controller, 'maybe_init_services' ), 1 );
		// Pretend init has not fired yet; restored in the finally block.
		unset( $wp_actions['init'] ); // phpcs:ignore WordPress.WP.GlobalVariablesOverride.Prohibited

		try {
			$controller->init_hooks();

			$this->assertSame( 1, has_action( 'init', array( $controller, 'maybe_init_services' ) ), 'maybe_init_services() should be queued on init at priority 1' );
			$this->assertFalse( has_filter( 'woocommerce_data_stores', array( $controller, 'register_data_stores' ) ), 'Nothing should be wired up before init' );
		} finally {
			$wp_actions['init'] = $init_count; // phpcs:ignore WordPress.WP.GlobalVariablesOverride.Prohibited
			if ( false === $was_hooked ) {
				remove_action( 'init', array( $controller, 'maybe_init_services' ), 1 );
			}
		}
	}

	/**
	 * @testdox The stock notification data store becomes available once the feature is enabled.
	 */
	public function test_maybe_init_services_registers_the_data_store_when_the_feature_is_enabled(): void {
		$this->init_stock_notifications_services();

		$store = new \WC_Data_Store( 'stock_notification' );

		$this->assertSame( \Automattic\WooCommerce\Internal\DataStores\StockNotifications\StockNotificationsDataStore::class, $store->get_current_class_name() );
	}
}
