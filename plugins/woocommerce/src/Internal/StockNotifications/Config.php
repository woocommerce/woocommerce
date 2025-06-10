<?php

declare( strict_types = 1 );

namespace Automattic\WooCommerce\Internal\StockNotifications;

use Automattic\WooCommerce\Enums\ProductType;
use Automattic\WooCommerce\Enums\ProductStockStatus;
use Automattic\WooCommerce\Enums\ProductStatus;

/**
 * Configuration class for stock notifications.
 */
class Config {

	/**
	 * Get the supported product types.
	 *
	 * @return array
	 */
	public static function get_supported_product_types(): array {

		/**
		 * Filter: woocommerce_stock_notifications_supported_product_types
		 *
		 * @since 0.0.0
		 *
		 * @param array $product_types Product types.
		 */
		return (array) apply_filters(
			'woocommerce_stock_notifications_supported_product_types',
			array(
				ProductType::SIMPLE,
				ProductType::VARIABLE,
				ProductType::VARIATION,
			)
		);
	}

	/**
	 * Get the supported product stock statuses.
	 *
	 * @return array
	 */
	public static function get_supported_product_statuses(): array {

		/**
		 * Filter: woocommerce_stock_notifications_supported_product_stock_statuses
		 *
		 * @since 0.0.0
		 *
		 * @param array $product_stock_statuses Product stock statuses.
		 */
		return (array) apply_filters(
			'woocommerce_stock_notifications_supported_product_stock_statuses',
			array(
				ProductStatus::PUBLISH,
			)
		);
	}

	/**
	 * Get the eligible stock statuses that trigger sending notifications.
	 *
	 * @return array
	 */
	public static function get_eligible_stock_statuses(): array {

		/**
		 * Filter: woocommerce_stock_notifications_supported_stock_statuses
		 *
		 * @since 0.0.0
		 *
		 * @param array $stock_statuses Stock statuses.
		 */
		return (array) apply_filters(
			'woocommerce_stock_notifications_supported_stock_statuses',
			array(
				ProductStockStatus::IN_STOCK,
				ProductStockStatus::ON_BACKORDER,
			)
		);
	}

	/**
	 * Check if signups are allowed.
	 *
	 * @return bool
	 */
	public static function allows_signups(): bool {
		return 'yes' === get_option( 'woocommerce_customer_stock_notifications_allow_signups', 'no' );
	}

	/**
	 * Check if double opt-in is required.
	 *
	 * @return bool
	 */
	public static function requires_double_opt_in(): bool {
		return 'yes' === get_option( 'woocommerce_customer_stock_notifications_require_double_opt_in', 'no' );
	}

	/**
	 * Check if an account is required.
	 *
	 * @return bool
	 */
	public static function requires_account(): bool {
		return 'yes' === get_option( 'woocommerce_customer_stock_notifications_require_account', 'no' );
	}

	/**
	 * Check if an account is created on signup.
	 *
	 * @return bool
	 */
	public static function creates_account_on_signup(): bool {
		return 'yes' === get_option( 'woocommerce_customer_stock_notifications_create_account_on_signup', 'no' );
	}
}
