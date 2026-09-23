<?php
declare( strict_types = 1 );

namespace Automattic\WooCommerce\Tests\Admin\API\Reports\StockNotifications;

use Automattic\WooCommerce\Internal\DataStores\StockNotifications\StockNotificationsDataStore;
use Automattic\WooCommerce\Internal\StockNotifications\Enums\NotificationStatus;
use Automattic\WooCommerce\Tests\Internal\StockNotifications\StockNotificationsFeatureTrait;
use WP_REST_Request;
use WP_REST_Response;

/**
 * Shared setup and helpers for the stock notifications report tests.
 */
trait ReportTestTrait {

	use StockNotificationsFeatureTrait;

	/**
	 * Enable the feature before the first dispatch registers the routes, and start from an empty UTC store.
	 */
	private function set_up_stock_notifications_report(): void {
		global $wpdb;

		$this->enable_stock_notifications_feature();
		wp_set_current_user( $this->factory->user->create( array( 'role' => 'administrator' ) ) );
		update_option( 'timezone_string', 'UTC' );
		update_option( 'gmt_offset', 0 );
		$wpdb->query( "DELETE FROM {$wpdb->prefix}wc_stock_notifications" );
		$this->set_reports_version_bumped( false );
	}

	/**
	 * Undo the process state that the transaction rollback does not cover.
	 */
	private function tear_down_stock_notifications_report(): void {
		$this->set_reports_version_bumped( false );
		$this->restore_stock_notifications_feature_option();
	}

	/**
	 * Set the once-per-request guard on the reports cache version bump.
	 *
	 * @param bool $value Whether the version counts as already bumped.
	 */
	private function set_reports_version_bumped( bool $value ): void {
		$property = new \ReflectionProperty( StockNotificationsDataStore::class, 'reports_version_bumped' );
		$property->setAccessible( true );
		$property->setValue( null, $value );
	}

	/**
	 * Insert a notification row directly, with exact GMT dates.
	 *
	 * @param int         $product_id    Product ID.
	 * @param string      $status        Notification status.
	 * @param string      $date_created  GMT creation date.
	 * @param string      $email         Customer email.
	 * @param string|null $date_notified GMT notified date.
	 */
	private function insert_row( int $product_id, string $status, string $date_created, string $email = 'customer@example.com', ?string $date_notified = null ): void {
		global $wpdb;

		$wpdb->insert(
			$wpdb->prefix . 'wc_stock_notifications',
			array(
				'product_id'        => $product_id,
				'user_id'           => 0,
				'user_email'        => $email,
				'status'            => $status,
				'date_created_gmt'  => $date_created,
				'date_notified_gmt' => $date_notified,
			)
		);
	}

	/**
	 * Insert an active row created the given number of days ago.
	 *
	 * @param int $product_id Product ID.
	 * @param int $days_ago   Age of the row in days.
	 */
	private function insert_active_row_days_ago( int $product_id, int $days_ago ): void {
		$this->insert_row( $product_id, NotificationStatus::ACTIVE, gmdate( 'Y-m-d H:i:s', time() - $days_ago * DAY_IN_SECONDS ) );
	}

	/**
	 * Request a report endpoint.
	 *
	 * @param string $route  Route.
	 * @param array  $params Query parameters.
	 * @return WP_REST_Response
	 */
	private function request( string $route, array $params ): WP_REST_Response {
		$request = new WP_REST_Request( 'GET', $route );
		$request->set_query_params( $params );

		return $this->server->dispatch( $request );
	}
}
