<?php
declare( strict_types=1 );

namespace Automattic\WooCommerce\Internal\StockNotifications\Utilities;

use Automattic\WooCommerce\Internal\StockNotifications\Notification;
use Automattic\WooCommerce\Internal\StockNotifications\Config;
use Automattic\WooCommerce\Internal\StockNotifications\NotificationQuery;
use Automattic\WooCommerce\Internal\StockNotifications\Utilities\StockManagementHelper;
use Automattic\WooCommerce\Enums\ProductType;
use Automattic\WooCommerce\Enums\ProductStatus;
use WC_Product;

/**
 * EligibilityService class file.
 */
class EligibilityService {

	/**
	 * The spam threshold for notifications.
	 *
	 * @var int
	 */
	public const SPAM_THRESHOLD = 60 * 60 * 24; // 24 hours.

	/**
	 * The stock management helper instance.
	 *
	 * @var StockManagementHelper
	 */
	private StockManagementHelper $stock_management_helper;

	/**
	 * Init.
	 *
	 * @internal
	 *
	 * @param StockManagementHelper $stock_management_helper The stock management helper instance.
	 */
	final public function init( StockManagementHelper $stock_management_helper ): void {
		$this->stock_management_helper = $stock_management_helper;
	}

	/**
	 * Validate product type and other basic criteria for notifications.
	 *
	 * @param WC_Product|null $product The product to check.
	 * @return bool True if the product is eligible for notifications, false otherwise.
	 */
	public function is_product_eligible( ?WC_Product $product ): bool {
		if ( ! $product instanceof WC_Product ) {
			return false;
		}

		if ( ! $product->is_type( Config::get_supported_product_types() ) ) {
			return false;
		}

		// Check for invalid product statuses.
		if ( in_array( $product->get_status(), array( ProductStatus::TRASH, ProductStatus::AUTO_DRAFT, ProductStatus::PENDING, ProductStatus::FUTURE ), true ) ) {
			return false;
		}

		/**
		 * Filter: woocommerce_customer_stock_notifications_product_is_valid
		 * Allows custom validation for whether a product is generally eligible for notifications.
		 *
		 * @since 10.2.0
		 *
		 * @param bool $is_valid True if the product is valid for notifications, false otherwise.
		 * @param WC_Product $product The product to check.
		 * @return bool True if the product is valid for notifications, false otherwise.
		 */
		return (bool) apply_filters( 'woocommerce_customer_stock_notifications_product_is_valid', true, $product );
	}

	/**
	 * Check if a product allows signups.
	 *
	 * @param WC_Product $product The product to check.
	 * @return bool True if the product allows signups, false otherwise.
	 */
	public function product_allows_signups( WC_Product $product ): bool {
		if ( $product->is_type( ProductType::VARIATION ) ) {
			$parent_product = wc_get_product( $product->get_parent_id() );
			if ( ! $parent_product instanceof WC_Product ) {
				return false;
			}

			return $this->product_allows_signups( $parent_product );
		}

		return 'no' !== $product->get_meta( Config::get_product_signups_meta_key() );
	}

	/**
	 * Check if a stock status is eligible for notifications.
	 *
	 * @param string $stock_status The stock status to check.
	 * @return bool True if the stock status is eligible for notifications, false otherwise.
	 */
	public function is_stock_status_eligible( string $stock_status ): bool {
		return in_array( $stock_status, Config::get_eligible_stock_statuses(), true );
	}

	/**
	 * Check if a product (or its relevant variations) has any active notifications.
	 *
	 * @param WC_Product $product The product to check.
	 * @return bool True if the product has active notifications, false otherwise.
	 */
	public function has_active_notifications( WC_Product $product ): bool {
		$lookup_ids = $this->get_target_product_ids( $product );

		if ( empty( $lookup_ids ) ) {
			return false;
		}

		return NotificationQuery::product_has_active_notifications( $lookup_ids );
	}

	/**
	 * Get the product IDs that need to be checked for stock notifications.
	 *
	 * For simple products, this returns just the product ID. For variable products,
	 * it returns both the parent product ID and the IDs of all variations whose stock
	 * is managed by the parent product.
	 *
	 * This is used in two key scenarios:
	 * 1. Checking if a product has any active notifications
	 * 2. Determining which notifications need to be sent during a stock broadcast
	 *
	 * @since 10.2.0
	 *
	 * @param WC_Product $product The product to check.
	 * @return array<int> Array of product IDs to check for notifications.
	 */
	public function get_target_product_ids( WC_Product $product ): array {
		$lookup_ids = array( $product->get_id() );
		if ( $product->is_type( ProductType::VARIABLE ) ) {
			$children_ids = $this->stock_management_helper->get_managed_variations( $product );
			$lookup_ids   = array_merge( $lookup_ids, $children_ids );
		}

		return $lookup_ids;
	}

	/**
	 * Check if a notification is eligible for sending.
	 *
	 * @param Notification $notification The notification to check.
	 * @param WC_Product   $product The product to check.
	 * @return bool True if the notification is eligible for sending, false otherwise.
	 */
	public function should_skip_notification( Notification $notification, WC_Product $product ): bool {
		$is_throttled         = $this->is_notification_throttled( $notification );
		$is_product_published = in_array( $product->get_status(), Config::get_supported_product_statuses(), true );
		$should_skip          = $is_throttled || ! $is_product_published;

		// Bypass for privileged users.
		if ( $should_skip ) {
			$user_id = $notification->get_user_id();
			if ( $user_id ) {
				$user = get_user_by( 'id', $user_id );
				if ( $user && ( user_can( $user, 'manage_woocommerce' ) || user_can( $user, 'manage_options' ) ) ) {
					$should_skip = false;
				}
			}
		}

		/**
		 * Filter: woocommerce_customer_stock_notification_should_skip_sending
		 *
		 * @since 10.2.0
		 *
		 * Prevent or manage sending a specific notification.
		 *
		 * @param bool $should_skip Whether to skip sending.
		 * @param int  $notification_id The notification ID.
		 * @return bool
		 */
		return (bool) apply_filters( 'woocommerce_customer_stock_notification_should_skip_sending', $should_skip, $notification->get_id() );
	}

	/**
	 * Check if notification is throttled.
	 *
	 * @param Notification $notification The notification object.
	 * @return bool
	 */
	private function is_notification_throttled( Notification $notification ): bool {

		/**
		 * Filter: woocommerce_customer_stock_notification_throttle_threshold
		 *
		 * @since 10.2.0
		 *
		 * @param int $threshold Throttle time in seconds should pass from the last notification delivery time.
		 */
		$threshold = (int) apply_filters( 'woocommerce_customer_stock_notification_throttle_threshold', self::SPAM_THRESHOLD );
		if ( $threshold <= 0 ) {
			return false;
		}

		$last_notified = $notification->get_date_notified();
		$is_throttled  = $last_notified instanceof \WC_DateTime && $last_notified->getTimestamp() > ( time() - $threshold );

		return $is_throttled;
	}
}
