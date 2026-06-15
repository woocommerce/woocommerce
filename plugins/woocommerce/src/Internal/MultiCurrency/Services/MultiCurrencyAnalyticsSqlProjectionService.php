<?php
/**
 * MultiCurrencyAnalyticsSqlProjectionService class file.
 */

declare( strict_types = 1 );

namespace Automattic\WooCommerce\Internal\MultiCurrency\Services;

/**
 * Projects analytics multi-currency SQL clauses without registering filters.
 *
 * @since 11.0.0
 * @internal Transitional internal component for the native multi-currency runtime.
 */
class MultiCurrencyAnalyticsSqlProjectionService {

	private const SUPPORTED_CONTEXTS = array( 'orders', 'products', 'variations', 'categories', 'coupons', 'taxes' );

	/**
	 * Project analytics SELECT clauses.
	 *
	 * @param string[]    $clauses         SELECT clauses.
	 * @param string|null $context         Analytics context.
	 * @param bool        $is_hpos_enabled Whether HPOS order storage is enabled.
	 * @return string[]
	 */
	public function project_select_clauses( array $clauses, ?string $context, bool $is_hpos_enabled ): array {
		if ( null === $context ) {
			return $clauses;
		}

		$context_parts = explode( '_', $context );
		$context_page  = $context_parts[0] ?? 'generic';
		$context_type  = $context_parts[1] ?? null;

		if ( ! in_array( $context_type, array( 'stats', 'subquery' ), true ) ) {
			return $clauses;
		}

		$new_clauses      = array();
		$sql_replacements = $this->get_sql_replacements();

		foreach ( $clauses as $clause ) {
			$replacements = $sql_replacements[ $context_page ] ?? $sql_replacements['generic'];
			foreach ( $replacements as $find => $replace ) {
				if ( false !== strpos( $clause, $find ) ) {
					$clause = str_replace( $find, $replace, $clause );
				}
			}

			$new_clauses[] = $clause;
		}

		if ( $this->should_add_multi_currency_columns( $context, $context_page, $clauses ) ) {
			$new_clauses[] = $is_hpos_enabled
				? ', wcpay_multicurrency_order_currency.currency AS order_currency'
				: ', wcpay_multicurrency_currency_meta.meta_value AS order_currency';
			$new_clauses[] = ', wcpay_multicurrency_default_currency_meta.meta_value AS order_default_currency';
			$new_clauses[] = ', wcpay_multicurrency_exchange_rate_meta.meta_value AS exchange_rate';
			$new_clauses[] = ', wcpay_multicurrency_stripe_exchange_rate_meta.meta_value AS stripe_exchange_rate';
		}

		return $new_clauses;
	}

	/**
	 * Project analytics JOIN clauses.
	 *
	 * @param string[] $clauses         JOIN clauses.
	 * @param string   $context         Analytics context.
	 * @param bool     $is_hpos_enabled Whether HPOS order storage is enabled.
	 * @return string[]
	 */
	public function project_join_clauses( array $clauses, string $context, bool $is_hpos_enabled ): array {
		global $wpdb;

		$context_parts = explode( '_', $context, 2 );
		$context_page  = $context_parts[0] ?? 'generic';

		if ( ! $this->should_add_multi_currency_columns( $context, $context_page, $clauses ) ) {
			return $clauses;
		}

		$prefix                 = 'wcpay_multicurrency_';
		$currency_table         = $is_hpos_enabled ? $prefix . 'order_currency' : $prefix . 'currency_meta';
		$default_currency_table = $prefix . 'default_currency_meta';
		$exchange_rate_table    = $prefix . 'exchange_rate_meta';
		$stripe_rate_table      = $prefix . 'stripe_exchange_rate_meta';
		$meta_table             = $is_hpos_enabled ? $wpdb->prefix . 'wc_orders_meta' : $wpdb->postmeta;
		$id_field               = $is_hpos_enabled ? 'order_id' : 'post_id';

		if ( $is_hpos_enabled ) {
			$clauses[] = "LEFT JOIN {$wpdb->prefix}wc_orders {$currency_table} ON {$wpdb->prefix}wc_order_stats.order_id = {$currency_table}.id";
		} else {
			$clauses[] = "LEFT JOIN {$meta_table} {$currency_table} ON {$wpdb->prefix}wc_order_stats.order_id = {$currency_table}.{$id_field} AND {$currency_table}.meta_key = '_order_currency'";
		}

		$clauses[] = "LEFT JOIN {$meta_table} {$default_currency_table} ON {$wpdb->prefix}wc_order_stats.order_id = {$default_currency_table}.{$id_field} AND {$default_currency_table}.meta_key = '_wcpay_multi_currency_order_default_currency'";
		$clauses[] = "LEFT JOIN {$meta_table} {$exchange_rate_table} ON {$wpdb->prefix}wc_order_stats.order_id = {$exchange_rate_table}.{$id_field} AND {$exchange_rate_table}.meta_key = '_wcpay_multi_currency_order_exchange_rate'";
		$clauses[] = "LEFT JOIN {$meta_table} {$stripe_rate_table} ON {$wpdb->prefix}wc_order_stats.order_id = {$stripe_rate_table}.{$id_field} AND {$stripe_rate_table}.meta_key = '_wcpay_multi_currency_stripe_exchange_rate'";

		return $clauses;
	}

	/**
	 * Project analytics WHERE clauses for customer currencies.
	 *
	 * @param string[]            $clauses         WHERE clauses.
	 * @param array<string,mixed> $currency_args   Sanitized currency args.
	 * @param bool                $is_hpos_enabled Whether HPOS order storage is enabled.
	 * @return string[]
	 */
	public function project_where_clauses( array $clauses, array $currency_args, bool $is_hpos_enabled ): array {
		$currency_field = $is_hpos_enabled
			? 'wcpay_multicurrency_order_currency.currency'
			: 'wcpay_multicurrency_currency_meta.meta_value';

		$currency_is = $this->sanitize_currency_list( $currency_args['currency_is'] ?? array() );
		if ( array() !== $currency_is ) {
			$currency_is_sql = sprintf( "'%s'", implode( "', '", array_map( 'esc_sql', $currency_is ) ) );
			$clauses[]       = "AND {$currency_field} IN ({$currency_is_sql})";
		}

		$currency_is_not = $this->sanitize_currency_list( $currency_args['currency_is_not'] ?? array() );
		if ( array() !== $currency_is_not ) {
			$currency_is_not_sql = sprintf( "'%s'", implode( "', '", array_map( 'esc_sql', $currency_is_not ) ) );
			$clauses[]           = "AND {$currency_field} NOT IN ({$currency_is_not_sql})";
		}

		$currency = isset( $currency_args['currency'] ) ? sanitize_text_field( wp_unslash( (string) $currency_args['currency'] ) ) : '';
		if ( '' !== $currency ) {
			$clauses[] = "AND {$currency_field} = '" . esc_sql( $currency ) . "'";
		}

		return $clauses;
	}

	/**
	 * Project selected-currency order SELECT clauses.
	 *
	 * @param string[] $clauses SELECT clauses.
	 * @return string[]
	 */
	public function project_selected_currency_order_select_clauses( array $clauses ): array {
		global $wpdb;

		$exchange_rate        = 'wcpay_multicurrency_exchange_rate_meta.meta_value';
		$stripe_exchange_rate = 'wcpay_multicurrency_stripe_exchange_rate_meta.meta_value';
		$net_total            = "{$wpdb->prefix}wc_order_stats.net_total";

		foreach ( $clauses as $index => $clause ) {
			if ( false === strpos( $clause, $net_total ) ) {
				continue;
			}

			$is_orders_subquery = false !== strpos( $clause, $net_total . ',' );
			$variable           = $is_orders_subquery ? "$net_total," : $net_total;
			$alias              = $is_orders_subquery ? ' as net_total,' : '';
			$decimals           = wc_get_price_decimals();

			$clauses[ $index ] = str_replace(
				$variable,
				$this->generate_case_when(
					$stripe_exchange_rate,
					"ROUND($net_total / $stripe_exchange_rate, $decimals)",
					"ROUND($net_total * $exchange_rate, $decimals)"
				) . $alias,
				$clause
			);
		}

		return $clauses;
	}

	/**
	 * Tell whether multi-currency columns should be projected.
	 *
	 * @param string   $context      Analytics context.
	 * @param string   $context_page Context page.
	 * @param string[] $clauses      Clauses.
	 * @return bool
	 */
	private function should_add_multi_currency_columns( string $context, string $context_page, array $clauses ): bool {
		return $this->is_supported_context( $context )
			&& (
				in_array( $context_page, self::SUPPORTED_CONTEXTS, true )
				|| $this->is_order_stats_table_used_in_clauses( $clauses )
			);
	}

	/**
	 * Tell whether the context supports multi-currency analytics projection.
	 *
	 * @param string $context Analytics context.
	 * @return bool
	 */
	private function is_supported_context( string $context ): bool {
		return ! in_array( $context, array( 'products', 'coupons', 'taxes', 'variations', 'categories' ), true );
	}

	/**
	 * Tell whether clauses reference the order stats table.
	 *
	 * @param string[] $clauses Clauses.
	 * @return bool
	 */
	private function is_order_stats_table_used_in_clauses( array $clauses ): bool {
		global $wpdb;

		foreach ( $clauses as $clause ) {
			if ( false !== strpos( $clause, "{$wpdb->prefix}wc_order_stats" ) ) {
				return true;
			}
		}

		return false;
	}

	/**
	 * Get SQL replacement expressions.
	 *
	 * @return array<string,array<string,string>>
	 */
	private function get_sql_replacements(): array {
		$default_currency     = 'wcpay_multicurrency_default_currency_meta.meta_value';
		$exchange_rate        = 'wcpay_multicurrency_exchange_rate_meta.meta_value';
		$stripe_exchange_rate = 'wcpay_multicurrency_stripe_exchange_rate_meta.meta_value';

		$discount_amount       = $this->get_default_currency_conversion_expression( 'discount_amount', $default_currency, $exchange_rate, $stripe_exchange_rate );
		$product_net_revenue   = $this->get_default_currency_conversion_expression( 'product_net_revenue', $default_currency, $exchange_rate, $stripe_exchange_rate );
		$product_gross_revenue = $this->get_default_currency_conversion_expression( 'product_gross_revenue', $default_currency, $exchange_rate, $stripe_exchange_rate );

		return array(
			'generic'    => array(
				'discount_amount'       => $discount_amount,
				'product_net_revenue'   => $product_net_revenue,
				'product_gross_revenue' => $product_gross_revenue,
			),
			'orders'     => array(
				'discount_amount' => $discount_amount,
			),
			'products'   => array(
				'product_net_revenue'   => $product_net_revenue,
				'product_gross_revenue' => $product_gross_revenue,
			),
			'variations' => array(
				'product_net_revenue'   => $product_net_revenue,
				'product_gross_revenue' => $product_gross_revenue,
			),
			'categories' => array(
				'product_net_revenue'   => $product_net_revenue,
				'product_gross_revenue' => $product_gross_revenue,
			),
			'taxes'      => array(
				'SUM(total_tax)'    => 'SUM(' . $this->get_default_currency_conversion_expression( 'total_tax', $default_currency, $exchange_rate, $stripe_exchange_rate ) . ')',
				'SUM(order_tax)'    => 'SUM(' . $this->get_default_currency_conversion_expression( 'order_tax', $default_currency, $exchange_rate, $stripe_exchange_rate ) . ')',
				'SUM(shipping_tax)' => 'SUM(' . $this->get_default_currency_conversion_expression( 'shipping_tax', $default_currency, $exchange_rate, $stripe_exchange_rate ) . ')',
			),
			'coupons'    => array(
				'discount_amount' => $discount_amount,
			),
		);
	}

	/**
	 * Build a default-currency conversion expression.
	 *
	 * @param string $field                SQL field.
	 * @param string $default_currency     Default currency expression.
	 * @param string $exchange_rate        Exchange rate expression.
	 * @param string $stripe_exchange_rate Stripe exchange rate expression.
	 * @return string
	 */
	private function get_default_currency_conversion_expression( string $field, string $default_currency, string $exchange_rate, string $stripe_exchange_rate ): string {
		return $this->generate_case_when(
			$default_currency,
			$this->generate_case_when(
				$stripe_exchange_rate,
				"ROUND($field * {$stripe_exchange_rate}, 2)",
				"ROUND($field * (1 / {$exchange_rate} ), 2)"
			),
			$field
		);
	}

	/**
	 * Build a CASE WHEN SQL expression.
	 *
	 * @param string $variable    Variable expression.
	 * @param string $then        THEN expression.
	 * @param string $else_clause ELSE expression.
	 * @return string
	 */
	private function generate_case_when( string $variable, string $then, string $else_clause ): string {
		return "CASE WHEN {$variable} IS NOT NULL THEN {$then} ELSE {$else_clause} END";
	}

	/**
	 * Sanitize a currency list.
	 *
	 * @param mixed $currencies Currency values.
	 * @return string[]
	 */
	private function sanitize_currency_list( $currencies ): array {
		if ( ! is_array( $currencies ) ) {
			return array();
		}

		return array_values(
			array_filter(
				array_map(
					static function ( $currency ): string {
						return sanitize_text_field( wp_unslash( (string) $currency ) );
					},
					$currencies
				),
				static function ( string $currency ): bool {
					return '' !== $currency;
				}
			)
		);
	}
}
