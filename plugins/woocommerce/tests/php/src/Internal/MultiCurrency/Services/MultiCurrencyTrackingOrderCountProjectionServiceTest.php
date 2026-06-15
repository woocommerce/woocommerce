<?php
declare( strict_types = 1 );

namespace Automattic\WooCommerce\Tests\Internal\MultiCurrency\Services;

use Automattic\WooCommerce\Internal\MultiCurrency\Services\MultiCurrencyTrackingOrderCountProjectionService;
use WC_Unit_Test_Case;

/**
 * Tests for the MultiCurrencyTrackingOrderCountProjectionService class.
 */
class MultiCurrencyTrackingOrderCountProjectionServiceTest extends WC_Unit_Test_Case {

	/**
	 * @testdox Should build HPOS order count query.
	 */
	public function test_builds_hpos_order_count_query(): void {
		global $wpdb;

		$sut   = new MultiCurrencyTrackingOrderCountProjectionService();
		$query = $this->normalize_sql( $sut->get_order_count_query( true ) );

		$this->assertStringContainsString( "FROM {$wpdb->prefix}wc_orders orders", $query );
		$this->assertStringContainsString( "{$wpdb->prefix}wc_orders_meta mc_meta", $query );
		$this->assertStringContainsString( 'orders.payment_method as gateway', $query );
		$this->assertStringContainsString( 'orders.total_amount as total', $query );
		$this->assertStringContainsString( 'orders.currency as currency', $query );
		$this->assertStringContainsString( "mc_meta.meta_key = '_wcpay_multi_currency_order_exchange_rate'", $query );
		$this->assertStringContainsString( "orders.status in ( 'wc-completed', 'wc-processing', 'wc-refunded' )", $query );
		$this->assertStringContainsString( 'GROUP BY currency, gateway', $query );
	}

	/**
	 * @testdox Should build legacy order count query.
	 */
	public function test_builds_legacy_order_count_query(): void {
		global $wpdb;

		$sut   = new MultiCurrencyTrackingOrderCountProjectionService();
		$query = $this->normalize_sql( $sut->get_order_count_query( false ) );

		$this->assertStringContainsString( "FROM {$wpdb->prefix}posts orders", $query );
		$this->assertStringContainsString( "{$wpdb->postmeta} order_meta", $query );
		$this->assertStringContainsString( "{$wpdb->postmeta} mc_meta", $query );
		$this->assertStringContainsString( "order_meta.meta_key = '_payment_method'", $query );
		$this->assertStringContainsString( "order_meta.meta_key = '_order_total'", $query );
		$this->assertStringContainsString( "order_meta.meta_key = '_order_currency'", $query );
		$this->assertStringContainsString( "mc_meta.meta_key = '_wcpay_multi_currency_order_exchange_rate'", $query );
		$this->assertStringContainsString( "orders.post_status in ( 'wc-completed', 'wc-processing', 'wc-refunded' )", $query );
		$this->assertStringContainsString( 'GROUP BY currency, gateway', $query );
	}

	/**
	 * @testdox Should aggregate order count rows.
	 */
	public function test_aggregates_order_count_rows(): void {
		$sut = new MultiCurrencyTrackingOrderCountProjectionService();

		$result = $sut->aggregate_order_count_rows(
			array(
				array(
					'gateway'  => 'woocommerce_payments',
					'currency' => 'GBP',
					'totals'   => '20.5',
					'counts'   => '2',
				),
				array(
					'gateway'  => 'cod',
					'currency' => 'EUR',
					'totals'   => '8',
					'counts'   => '1',
				),
			)
		);

		$this->assertSame(
			array(
				'counts'     => 3,
				'currencies' => array(
					'GBP' => array(
						'counts'   => 2,
						'totals'   => 20.5,
						'gateways' => array(
							'woocommerce_payments' => array(
								'counts' => 2,
								'totals' => 20.5,
							),
						),
					),
					'EUR' => array(
						'counts'   => 1,
						'totals'   => 8.0,
						'gateways' => array(
							'cod' => array(
								'counts' => 1,
								'totals' => 8.0,
							),
						),
					),
				),
			),
			$result
		);
	}

	/**
	 * @testdox Should aggregate missing gateway as unknown.
	 */
	public function test_aggregates_missing_gateway_as_unknown(): void {
		$sut = new MultiCurrencyTrackingOrderCountProjectionService();

		$row           = new \stdClass();
		$row->currency = 'USD';
		$row->totals   = '0';
		$row->counts   = '1';

		$result = $sut->aggregate_order_count_rows( array( $row ) );

		$this->assertSame(
			array(
				'counts'     => 1,
				'currencies' => array(
					'USD' => array(
						'counts'   => 1,
						'totals'   => 0.0,
						'gateways' => array(
							'unknown' => array(
								'counts' => 1,
								'totals' => 0.0,
							),
						),
					),
				),
			),
			$result
		);
	}

	/**
	 * Normalize SQL whitespace.
	 *
	 * @param string $sql SQL.
	 * @return string
	 */
	private function normalize_sql( string $sql ): string {
		return trim( preg_replace( '/\s+/', ' ', $sql ) ?? $sql );
	}
}
