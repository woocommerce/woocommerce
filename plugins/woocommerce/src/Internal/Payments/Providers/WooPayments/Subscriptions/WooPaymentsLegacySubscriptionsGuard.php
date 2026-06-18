<?php
/**
 * WooPaymentsLegacySubscriptionsGuard class file.
 */

declare( strict_types = 1 );

namespace Automattic\WooCommerce\Internal\Payments\Providers\WooPayments\Subscriptions;

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
		$args = array(
			'type'       => self::ORDER_TYPES,
			'status'     => 'all',
			'limit'      => 1,
			'return'     => 'ids',
			'meta_query' => array( // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_query
				'relation' => 'OR',
			),
		);

		foreach ( self::LEGACY_POST_META_KEYS as $meta_key ) {
			$args['meta_query'][] = array(
				'key'     => $meta_key,
				'compare' => 'EXISTS',
			);
		}

		if ( function_exists( 'wcs_get_orders_with_meta_query' ) ) {
			$orders = wcs_get_orders_with_meta_query( $args );
			if ( is_array( $orders ) && array() !== $orders ) {
				return true;
			}
		}

		if ( function_exists( 'wc_get_orders' ) ) {
			$orders = wc_get_orders( $args );
			if ( is_array( $orders ) && array() !== $orders ) {
				return true;
			}
		}

		return $this->query_legacy_marker_exists_from_posts();
	}

	/**
	 * Query the CPT store directly for markers the order APIs cannot surface.
	 *
	 * @return bool True when at least one marker exists.
	 */
	private function query_legacy_marker_exists_from_posts(): bool {
		global $wpdb;

		$order_type_placeholders = implode( ', ', array_fill( 0, count( self::ORDER_TYPES ), '%s' ) );
		$meta_key_placeholders   = implode( ', ', array_fill( 0, count( self::LEGACY_POST_META_KEYS ), '%s' ) );
		$prepare_args            = array_merge(
			array(
				$wpdb->posts,
				$wpdb->postmeta,
			),
			self::ORDER_TYPES,
			self::LEGACY_POST_META_KEYS
		);
		$sql                     = $wpdb->prepare(
			// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared -- Placeholder lists are generated from fixed internal marker lists.
			"SELECT 1
			FROM %i AS posts
			INNER JOIN %i AS postmeta ON posts.ID = postmeta.post_id
			WHERE posts.post_type IN ( $order_type_placeholders )
				AND postmeta.meta_key IN ( $meta_key_placeholders )
			LIMIT 1",
			$prepare_args
		);

		return null !== $wpdb->get_var( $sql ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
	}
}
