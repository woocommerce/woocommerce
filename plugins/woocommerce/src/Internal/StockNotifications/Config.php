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
	 * @return array<string>
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
	 * @return array<string>
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
	 * @return array<string>
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
}
