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

	/**
	 * Clean up after tests.
	 */
	public function tearDown(): void {
		wc_get_container()->get( DataRetentionController::class )->clear_daily_task();
		delete_option( 'woocommerce_customer_stock_notifications_unverified_deletions_days_threshold' );
		delete_option( 'woocommerce_queue_flush_rewrite_rules' );
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
		delete_option( 'woocommerce_queue_flush_rewrite_rules' );

		$this->fire_feature_changed( false, 'some_other_feature' );

		$this->assertSame( 'daily', wp_get_schedule( DataRetentionController::DAILY_TASK_HOOK ) );
		$this->assertFalse( get_option( 'woocommerce_queue_flush_rewrite_rules' ) );
	}
}
