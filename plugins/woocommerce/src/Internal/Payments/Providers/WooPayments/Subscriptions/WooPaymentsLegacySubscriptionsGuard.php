<?php
/**
 * WooPaymentsLegacySubscriptionsGuard class file.
 */

declare( strict_types = 1 );

namespace Automattic\WooCommerce\Internal\Payments\Providers\WooPayments\Subscriptions;

use Automattic\WooCommerce\Internal\DataStores\Orders\OrdersTableDataStore;

/**
 * Detects legacy WooPayments Stripe Billing subscription markers.
 *
 * @since 11.0.0
 * @internal Transitional internal component for the native payments runtime.
 */
class WooPaymentsLegacySubscriptionsGuard {

	/**
	 * Order types that can carry legacy Stripe Billing subscription markers.
	 *
	 * @var string[]
	 */
	private const ORDER_TYPES = array(
		'shop_subscription',
		'shop_order',
	);

	/**
	 * Legacy WCPay/Stripe Billing post meta keys that prove native cutover would strand data.
	 *
	 * @var string[]
	 */
	private const LEGACY_POST_META_KEYS = array(
		'_wcpay_subscription_id',
		'_migrated_wcpay_subscription_id',
		'_wcpay_billing_invoice_id',
		'_migrated_wcpay_billing_invoice_id',
		'_wcpay_pending_invoice_id',
		'_migrated_wcpay_pending_invoice_id',
		'_wcpay_subscription_discount_ids',
		'_migrated_wcpay_subscription_discount_ids',
		'_wcpay_subscription_migrated_during',
	);

	/**
	 * Cached result for the current request.
	 *
	 * @var bool|null
	 */
	private ?bool $has_legacy_markers = null;

	/**
	 * Tell whether native cutover would strand legacy Stripe Billing subscription data.
	 *
	 * @return bool True when at least one legacy WCPay/Stripe Billing marker exists.
	 */
	public function has_legacy_stripe_billing_subscription_markers(): bool {
		if ( null !== $this->has_legacy_markers ) {
			return $this->has_legacy_markers;
		}

		$this->has_legacy_markers = $this->query_legacy_marker_exists();

		return $this->has_legacy_markers;
	}

	/**
	 * Query whether an order or subscription carries legacy WCPay/Stripe Billing markers.
	 *
	 * @return bool True when at least one marker exists.
	 */
	private function query_legacy_marker_exists(): bool {
		return $this->query_legacy_marker_exists_from_hpos_tables() || $this->query_legacy_marker_exists_from_posts();
	}

	/**
	 * Query the HPOS tables directly for legacy markers.
	 *
	 * @return bool True when at least one marker exists.
	 */
	private function query_legacy_marker_exists_from_hpos_tables(): bool {
		global $wpdb;

		// Keep this placeholder matrix in sync with the fixed marker constants above.
		$sql = $wpdb->prepare(
			'SELECT 1
			FROM %i AS orders
			INNER JOIN %i AS ordermeta ON orders.id = ordermeta.order_id
			WHERE orders.type IN ( %s, %s )
				AND ordermeta.meta_key IN ( %s, %s, %s, %s, %s, %s, %s, %s, %s )
			LIMIT 1',
			OrdersTableDataStore::get_orders_table_name(),
			OrdersTableDataStore::get_meta_table_name(),
			self::ORDER_TYPES[0],
			self::ORDER_TYPES[1],
			self::LEGACY_POST_META_KEYS[0],
			self::LEGACY_POST_META_KEYS[1],
			self::LEGACY_POST_META_KEYS[2],
			self::LEGACY_POST_META_KEYS[3],
			self::LEGACY_POST_META_KEYS[4],
			self::LEGACY_POST_META_KEYS[5],
			self::LEGACY_POST_META_KEYS[6],
			self::LEGACY_POST_META_KEYS[7],
			self::LEGACY_POST_META_KEYS[8]
		);

		return null !== $wpdb->get_var( $sql ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
	}

	/**
	 * Query the CPT store directly for markers the order APIs cannot surface.
	 *
	 * @return bool True when at least one marker exists.
	 */
	private function query_legacy_marker_exists_from_posts(): bool {
		global $wpdb;

		// Keep this placeholder matrix in sync with the fixed marker constants above.
		$sql = $wpdb->prepare(
			'SELECT 1
			FROM %i AS posts
			INNER JOIN %i AS postmeta ON posts.ID = postmeta.post_id
			WHERE posts.post_type IN ( %s, %s )
				AND postmeta.meta_key IN ( %s, %s, %s, %s, %s, %s, %s, %s, %s )
			LIMIT 1',
			$wpdb->posts,
			$wpdb->postmeta,
			self::ORDER_TYPES[0],
			self::ORDER_TYPES[1],
			self::LEGACY_POST_META_KEYS[0],
			self::LEGACY_POST_META_KEYS[1],
			self::LEGACY_POST_META_KEYS[2],
			self::LEGACY_POST_META_KEYS[3],
			self::LEGACY_POST_META_KEYS[4],
			self::LEGACY_POST_META_KEYS[5],
			self::LEGACY_POST_META_KEYS[6],
			self::LEGACY_POST_META_KEYS[7],
			self::LEGACY_POST_META_KEYS[8]
		);

		return null !== $wpdb->get_var( $sql ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
	}
}
