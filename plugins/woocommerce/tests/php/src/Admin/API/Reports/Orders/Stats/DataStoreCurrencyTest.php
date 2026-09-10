<?php
declare( strict_types = 1 );

namespace Automattic\WooCommerce\Tests\Admin\API\Reports\Orders\Stats;

use Automattic\WooCommerce\Admin\API\Reports\Orders\Stats\DataStore;
use Automattic\WooCommerce\Admin\API\Reports\Orders\Stats\Query;
use Automattic\WooCommerce\Admin\API\Reports\Cache;
use Automattic\WooCommerce\Internal\DataStores\Orders\DataSynchronizer;
use Automattic\WooCommerce\Utilities\OrderUtil;
use WC_Order;
use WC_Unit_Test_Case;

/** Currency completeness must not depend on an active payment gateway. */
class DataStoreCurrencyTest extends WC_Unit_Test_Case {
	/**
	 * @dataProvider currencies
	 * @param string $currency Second order currency.
	 * @param bool   $missing Whether the report lacks a conversion.
	 * @param bool   $schema_available Whether currency columns can be inspected.
	 */
	public function test_currency_completeness_on_repeated_queries( string $currency, bool $missing, bool $schema_available = true ): void {
		global $wpdb;
		$columns = new \ReflectionProperty( DataStore::class, 'reporting_columns' );
		$columns->setAccessible( true );
		$previous_columns = $columns->getValue();
		$previous         = get_option( 'woocommerce_currency' );
		$orders           = array();
		update_option( 'woocommerce_currency', 'EUR' );
		try {
			foreach ( array( array( 'EUR', 41.9 ), array( $currency, 36 ) ) as $fixture ) {
				$order = new WC_Order();
				$order->set_currency( $fixture[0] );
				$order->set_total( $fixture[1] );
				$order->set_date_created( '2020-02-03 12:00:00' );
				$order->set_date_paid( '2020-02-03 12:00:00' );
				$order->set_status( 'completed' );
				$order->save();
				$orders[] = $order;
				DataStore::sync_order( $order->get_id() );
			}
			if ( ! $schema_available ) {
				$columns->setValue( null, array( $wpdb->prefix . 'wc_order_stats' => false ) );
			}
			Cache::invalidate();
			for ( $pass = 0; $pass < ( $schema_available ? 2 : 3 ); ++$pass ) {
				// A successful inspection must not reuse the unavailable-schema response.
				if ( ! $schema_available && 2 === $pass ) {
					DataStore::has_reporting_currency_columns( true );
				}
				$expected_missing = $schema_available ? (int) $missing : ( 2 === $pass ? 0 : 2 );
				$query            = new Query(
					array(
						'after'    => '2020-02-03T00:00:00',
						'before'   => '2020-02-03T23:59:59',
						'interval' => 'day',
					)
				);
				$data             = json_decode( wp_json_encode( $query->get_data() ), true );
				foreach ( array( $data['totals'], $data['intervals'][0]['subtotals'] ) as $totals ) {
					$this->assertSame( 2, $totals['orders_count'] );
					$this->assertSame( $expected_missing, $totals['reporting_missing_orders'] );
					if ( $expected_missing ) {
						$this->assertNull( $totals['net_revenue'] );
						$this->assertNull( $totals['avg_order_value'] );
					} else {
						$this->assertEquals( 77.9, $totals['net_revenue'] );
						$this->assertEquals( 38.95, $totals['avg_order_value'] );
					}
				}
			}
		} finally {
			$columns->setValue( null, $previous_columns );
			$ids = array();
			foreach ( $orders as $order ) {
				$ids[] = $order->get_id();
				$order->delete( true );
			}
			if ( $ids && OrderUtil::custom_orders_table_usage_is_enabled() ) {
				wc_get_container()->get( DataSynchronizer::class )->process_batch( $ids );
			}
			update_option( 'woocommerce_currency', $previous );
			Cache::invalidate();
		}
	}

	/** @return array Native and missing historical conversion cases. */
	public function currencies(): array {
		return array(
			'native'             => array( 'EUR', false ),
			'missing'            => array( 'GBP', true ),
			'unavailable schema' => array( 'EUR', true, false ),
		);
	}
}
