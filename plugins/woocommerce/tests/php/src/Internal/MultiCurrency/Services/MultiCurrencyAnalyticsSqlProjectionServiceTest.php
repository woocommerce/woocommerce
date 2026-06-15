<?php
declare( strict_types = 1 );

namespace Automattic\WooCommerce\Tests\Internal\MultiCurrency\Services;

use Automattic\WooCommerce\Internal\MultiCurrency\Services\MultiCurrencyAnalyticsSqlProjectionService;
use WC_Unit_Test_Case;

/**
 * Tests for the MultiCurrencyAnalyticsSqlProjectionService class.
 */
class MultiCurrencyAnalyticsSqlProjectionServiceTest extends WC_Unit_Test_Case {

	/**
	 * @testdox Should project select clauses for legacy order storage.
	 */
	public function test_projects_select_clauses_for_legacy_order_storage(): void {
		$sut = new MultiCurrencyAnalyticsSqlProjectionService();

		$result = $sut->project_select_clauses(
			array(
				', SUM(product_net_revenue) AS product_net_revenue',
				', discount_amount',
			),
			'products_stats_total',
			false
		);

		$this->assertStringContainsString(
			'CASE WHEN wcpay_multicurrency_default_currency_meta.meta_value IS NOT NULL',
			$result[0]
		);
		$this->assertStringContainsString(
			'ROUND(product_net_revenue * wcpay_multicurrency_stripe_exchange_rate_meta.meta_value, 2)',
			$result[0]
		);
		$this->assertContains(
			', wcpay_multicurrency_currency_meta.meta_value AS order_currency',
			$result
		);
		$this->assertContains(
			', wcpay_multicurrency_default_currency_meta.meta_value AS order_default_currency',
			$result
		);
		$this->assertContains(
			', wcpay_multicurrency_exchange_rate_meta.meta_value AS exchange_rate',
			$result
		);
		$this->assertContains(
			', wcpay_multicurrency_stripe_exchange_rate_meta.meta_value AS stripe_exchange_rate',
			$result
		);
	}

	/**
	 * @testdox Should project select clauses for HPOS order storage.
	 */
	public function test_projects_select_clauses_for_hpos_order_storage(): void {
		$sut = new MultiCurrencyAnalyticsSqlProjectionService();

		$result = $sut->project_select_clauses(
			array( ', SUM(discount_amount) AS discount_amount' ),
			'orders_stats_total',
			true
		);

		$this->assertContains(
			', wcpay_multicurrency_order_currency.currency AS order_currency',
			$result
		);
		$this->assertContains(
			', wcpay_multicurrency_default_currency_meta.meta_value AS order_default_currency',
			$result
		);
	}

	/**
	 * @testdox Should leave unsupported select contexts unchanged.
	 */
	public function test_leaves_unsupported_select_contexts_unchanged(): void {
		$sut     = new MultiCurrencyAnalyticsSqlProjectionService();
		$clauses = array( ', product_id' );

		$this->assertSame(
			$clauses,
			$sut->project_select_clauses( $clauses, 'products', false )
		);
	}

	/**
	 * @testdox Should project join clauses for legacy order storage.
	 */
	public function test_projects_join_clauses_for_legacy_order_storage(): void {
		global $wpdb;

		$sut = new MultiCurrencyAnalyticsSqlProjectionService();

		$result = $sut->project_join_clauses(
			array( "INNER JOIN {$wpdb->prefix}wc_order_stats ON 1 = 1" ),
			'orders_stats_total',
			false
		);

		$this->assertContains(
			"LEFT JOIN {$wpdb->postmeta} wcpay_multicurrency_currency_meta ON {$wpdb->prefix}wc_order_stats.order_id = wcpay_multicurrency_currency_meta.post_id AND wcpay_multicurrency_currency_meta.meta_key = '_order_currency'",
			$result
		);
		$this->assertContains(
			"LEFT JOIN {$wpdb->postmeta} wcpay_multicurrency_exchange_rate_meta ON {$wpdb->prefix}wc_order_stats.order_id = wcpay_multicurrency_exchange_rate_meta.post_id AND wcpay_multicurrency_exchange_rate_meta.meta_key = '_wcpay_multi_currency_order_exchange_rate'",
			$result
		);
	}

	/**
	 * @testdox Should project join clauses for HPOS order storage.
	 */
	public function test_projects_join_clauses_for_hpos_order_storage(): void {
		global $wpdb;

		$sut = new MultiCurrencyAnalyticsSqlProjectionService();

		$result = $sut->project_join_clauses(
			array( "INNER JOIN {$wpdb->prefix}wc_order_stats ON 1 = 1" ),
			'orders_stats_total',
			true
		);

		$this->assertContains(
			"LEFT JOIN {$wpdb->prefix}wc_orders wcpay_multicurrency_order_currency ON {$wpdb->prefix}wc_order_stats.order_id = wcpay_multicurrency_order_currency.id",
			$result
		);
		$this->assertContains(
			"LEFT JOIN {$wpdb->prefix}wc_orders_meta wcpay_multicurrency_default_currency_meta ON {$wpdb->prefix}wc_order_stats.order_id = wcpay_multicurrency_default_currency_meta.order_id AND wcpay_multicurrency_default_currency_meta.meta_key = '_wcpay_multi_currency_order_default_currency'",
			$result
		);
	}

	/**
	 * @testdox Should project currency where clauses for legacy order storage.
	 */
	public function test_projects_currency_where_clauses_for_legacy_order_storage(): void {
		$sut = new MultiCurrencyAnalyticsSqlProjectionService();

		$result = $sut->project_where_clauses(
			array( 'AND status != "trash"' ),
			array(
				'currency_is'     => array( 'GBP', 'JPY' ),
				'currency_is_not' => array( 'AUD' ),
				'currency'        => 'EUR',
			),
			false
		);

		$this->assertContains(
			"AND wcpay_multicurrency_currency_meta.meta_value IN ('GBP', 'JPY')",
			$result
		);
		$this->assertContains(
			"AND wcpay_multicurrency_currency_meta.meta_value NOT IN ('AUD')",
			$result
		);
		$this->assertContains(
			"AND wcpay_multicurrency_currency_meta.meta_value = 'EUR'",
			$result
		);
	}

	/**
	 * @testdox Should project selected currency order select clauses.
	 */
	public function test_projects_selected_currency_order_select_clauses(): void {
		global $wpdb;

		$sut       = new MultiCurrencyAnalyticsSqlProjectionService();
		$net_total = "{$wpdb->prefix}wc_order_stats.net_total";
		$decimals  = wc_get_price_decimals();

		$result = $sut->project_selected_currency_order_select_clauses(
			array( "$net_total," )
		);

		$this->assertSame(
			"CASE WHEN wcpay_multicurrency_stripe_exchange_rate_meta.meta_value IS NOT NULL THEN ROUND($net_total / wcpay_multicurrency_stripe_exchange_rate_meta.meta_value, $decimals) ELSE ROUND($net_total * wcpay_multicurrency_exchange_rate_meta.meta_value, $decimals) END as net_total,",
			$result[0]
		);
	}
}
