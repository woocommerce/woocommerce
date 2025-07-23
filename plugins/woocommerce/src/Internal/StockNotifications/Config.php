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
	 * Runtime cache for supported product types.
	 *
	 * @var array<string>
	 */
	private static $supported_product_types;

	/**
	 * Runtime cache for supported product statuses.
	 *
	 * @var array<string>
	 */
	private static $supported_product_statuses;

	/**
	 * Runtime cache for eligible stock statuses.
	 *
	 * @var array<string>
	 */
	private static $eligible_stock_statuses;

	/**
	 * Runtime cache for verification expiration time threshold.
	 *
	 * @var int
	 */
	private static $verification_expiration_time_threshold;

	/**
	 * Get the supported product types.
	 *
	 * @return array<string>
	 */
	public static function get_supported_product_types(): array {
		if ( is_array( self::$supported_product_types ) ) {
			return self::$supported_product_types;
		}

		/**
		 * Filter: woocommerce_customer_stock_notifications_supported_product_types
		 *
		 * @since 10.2.0
		 *
		 * @param array $product_types Product types.
		 */
		self::$supported_product_types = (array) apply_filters(
			'woocommerce_customer_stock_notifications_supported_product_types',
			array(
				ProductType::SIMPLE,
				ProductType::VARIABLE,
				ProductType::VARIATION,
			)
		);

		return self::$supported_product_types;
	}

	/**
	 * Get the supported product stock statuses.
	 *
	 * @return array<string>
	 */
	public static function get_supported_product_statuses(): array {
		if ( is_array( self::$supported_product_statuses ) ) {
			return self::$supported_product_statuses;
		}

		/**
		 * Filter: woocommerce_customer_stock_notifications_supported_product_stock_statuses
		 *
		 * @since 10.2.0
		 *
		 * @param array $product_stock_statuses Product stock statuses.
		 */
		self::$supported_product_statuses = (array) apply_filters(
			'woocommerce_customer_stock_notifications_supported_product_stock_statuses',
			array(
				ProductStatus::PUBLISH,
			)
		);

		return self::$supported_product_statuses;
	}

	/**
	 * Get the eligible stock statuses that trigger sending notifications.
	 *
	 * @return array<string>
	 */
	public static function get_eligible_stock_statuses(): array {
		if ( is_array( self::$eligible_stock_statuses ) ) {
			return self::$eligible_stock_statuses;
		}

		/**
		 * Filter: woocommerce_customer_stock_notifications_supported_stock_statuses
		 *
		 * @since 10.2.0
		 *
		 * @param array $stock_statuses Stock statuses.
		 */
		self::$eligible_stock_statuses = (array) apply_filters(
			'woocommerce_customer_stock_notifications_supported_stock_statuses',
			array(
				ProductStockStatus::IN_STOCK,
				ProductStockStatus::ON_BACKORDER,
			)
		);

		return self::$eligible_stock_statuses;
	}

	/**
	 * Get the metadata name for product-level signups.
	 *
	 * @return string
	 */
	public static function get_product_signups_meta_key(): string {
		return 'customer_stock_notifications_enable_signups';
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

	/**
	 * How long to keep pending notifications before deleting them (in days).
	 *
	 * @return int
	 */
	public static function get_unverified_deletion_days_threshold(): int {
		return absint(
			get_option(
				'woocommerce_customer_stock_notifications_unverified_deletions_days_threshold',
				0
			)
		);
	}

	/**
	 * Returns verification codes expiration time threshold (in seconds).
	 *
	 * @return int
	 */
	public static function get_verification_expiration_time_threshold(): int {
		if ( ! is_null( self::$verification_expiration_time_threshold ) ) {
			return self::$verification_expiration_time_threshold;
		}

		/**
		 * Filter the verification codes expiration time (in seconds).
		 *
		 * @param int $threshold
		 * @since 10.2.0
		 */
		self::$verification_expiration_time_threshold = (int) apply_filters(
			'woocommerce_customer_stock_notifications_verification_expiration_time_threshold',
			HOUR_IN_SECONDS
		);

		return self::$verification_expiration_time_threshold;
	}
}
