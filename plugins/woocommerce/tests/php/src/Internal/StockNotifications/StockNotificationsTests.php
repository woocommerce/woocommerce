<?php

declare( strict_types = 1 );
namespace Automattic\WooCommerce\Tests\Internal\StockNotifications;

use Automattic\WooCommerce\Internal\Features\FeaturesController;
use Automattic\WooCommerce\Internal\StockNotifications\DataRetentionController;
use Automattic\WooCommerce\Internal\StockNotifications\StockNotifications;

/**
 * StockNotifications controller tests.
 */
class StockNotificationsTests extends \WC_Unit_Test_Case {
	use StockNotificationsFeatureTrait;

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
	 * @testdox The stock notification data store becomes available once the feature is enabled.
	 */
	public function test_maybe_init_services_registers_the_data_store_when_the_feature_is_enabled(): void {
		$this->init_stock_notifications_services();

		$store = new \WC_Data_Store( 'stock_notification' );

		$this->assertSame( \Automattic\WooCommerce\Internal\DataStores\StockNotifications\StockNotificationsDataStore::class, $store->get_current_class_name() );
	}
}
