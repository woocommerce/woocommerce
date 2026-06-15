<?php
/**
 * MultiCurrencyTrackingOrderCountProjectionService class file.
 */

declare( strict_types = 1 );

namespace Automattic\WooCommerce\Internal\MultiCurrency\Services;

/**
 * Projects tracker order-count queries and payloads without executing SQL.
 *
 * @since 11.0.0
 * @internal Transitional internal component for the native multi-currency runtime.
 */
class MultiCurrencyTrackingOrderCountProjectionService {

	/**
	 * Get the order-count query for the active order storage mode.
	 *
	 * @param bool $is_hpos_enabled Whether HPOS order storage is enabled.
	 * @return string
	 */
	public function get_order_count_query( bool $is_hpos_enabled ): string {
		return $is_hpos_enabled ? $this->get_hpos_order_count_query() : $this->get_legacy_order_count_query();
	}

	/**
	 * Aggregate order-count query result rows.
	 *
	 * @param array<int,array<string,mixed>|object> $rows Query result rows.
	 * @return array<string,mixed>
	 */
	public function aggregate_order_count_rows( array $rows ): array {
		$currencies  = array();
		$total_count = 0;

		foreach ( $rows as $row ) {
			$currency = (string) $this->get_row_value( $row, 'currency' );
			$counts   = (int) $this->get_row_value( $row, 'counts' );
			$totals   = (float) $this->get_row_value( $row, 'totals' );
			$gateway  = (string) $this->get_row_value( $row, 'gateway' );
			$gateway  = '' === $gateway ? 'unknown' : $gateway;

			$current_counts = (int) ( $currencies[ $currency ]['counts'] ?? 0 );
			$current_totals = (float) ( $currencies[ $currency ]['totals'] ?? 0 );

			$currencies[ $currency ]['counts']               = $current_counts + $counts;
			$currencies[ $currency ]['totals']               = $current_totals + $totals;
			$currencies[ $currency ]['gateways'][ $gateway ] = array(
				'counts' => $counts,
				'totals' => $totals,
			);

			$total_count += $counts;
		}

		return array(
			'counts'     => $total_count,
			'currencies' => $currencies,
		);
	}

	/**
	 * Build the HPOS order-count query.
	 *
	 * @return string
	 */
	private function get_hpos_order_count_query(): string {
		global $wpdb;

		return "
			SELECT
				gateway, currency, SUM(total) AS totals, COUNT(order_id) AS counts
			FROM (
				SELECT
					orders.id AS order_id, orders.payment_method as gateway, orders.total_amount as total, orders.currency as currency
				FROM
					{$wpdb->prefix}wc_orders orders
				LEFT JOIN
					{$wpdb->prefix}wc_orders_meta order_meta ON order_meta.order_id = orders.id
				INNER JOIN
					{$wpdb->prefix}wc_orders_meta mc_meta ON mc_meta.order_id = orders.id
					AND mc_meta.meta_key = '_wcpay_multi_currency_order_exchange_rate'
				WHERE orders.type = 'shop_order'
					AND orders.status in ( 'wc-completed', 'wc-processing', 'wc-refunded' )
				GROUP BY orders.id
			) order_gateways
			GROUP BY currency, gateway
		";
	}

	/**
	 * Build the legacy post table order-count query.
	 *
	 * @return string
	 */
	private function get_legacy_order_count_query(): string {
		global $wpdb;

		return "
			SELECT
				gateway, currency, SUM(total) AS totals, COUNT(order_id) AS counts
			FROM (
				SELECT
					orders.id AS order_id,
					MAX(CASE WHEN order_meta.meta_key = '_payment_method' THEN order_meta.meta_value END) gateway,
					MAX(CASE WHEN order_meta.meta_key = '_order_total' THEN order_meta.meta_value END) total,
					MAX(CASE WHEN order_meta.meta_key = '_order_currency' THEN order_meta.meta_value END) currency
				FROM
					{$wpdb->prefix}posts orders
				LEFT JOIN
					{$wpdb->postmeta} order_meta ON order_meta.post_id = orders.id
				INNER JOIN
					{$wpdb->postmeta} mc_meta ON mc_meta.post_id = orders.id
					AND mc_meta.meta_key = '_wcpay_multi_currency_order_exchange_rate'
				WHERE orders.post_type = 'shop_order'
					AND orders.post_status in ( 'wc-completed', 'wc-processing', 'wc-refunded' )
					AND order_meta.meta_key in ( '_payment_method', '_order_total', '_order_currency' )
				GROUP BY orders.id
			) order_gateways
			GROUP BY currency, gateway
		";
	}

	/**
	 * Get a value from a query result row.
	 *
	 * @param array<string,mixed>|object $row Row.
	 * @param string                     $key Key.
	 * @return mixed
	 */
	private function get_row_value( $row, string $key ) {
		if ( is_array( $row ) ) {
			return $row[ $key ] ?? null;
		}

		if ( is_object( $row ) && isset( $row->$key ) ) {
			return $row->$key;
		}

		return null;
	}
}
