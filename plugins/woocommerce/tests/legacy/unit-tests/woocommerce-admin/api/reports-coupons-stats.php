<?php
/**
 * Reports Coupons Stats REST API Test
 *
 * @package WooCommerce\Admin\Tests\API
 */

use Automattic\WooCommerce\Enums\OrderStatus;

/**
 * Class WC_Admin_Tests_API_Reports_Coupons_Stats
 */
class WC_Admin_Tests_API_Reports_Coupons_Stats extends WC_REST_Unit_Test_Case {

	/**
	 * Endpoints.
	 *
	 * @var string
	 */
	protected $endpoint = '/wc-analytics/reports/coupons/stats';

	/**
	 * Setup test reports products stats data.
	 */
	public function setUp(): void {
		parent::setUp();

		$this->user = $this->factory->user->create(
			array(
				'role' => 'administrator',
			)
		);
	}

	/**
	 * Test route registration.
	 */
	public function test_register_routes() {
		// This namespace may be lazy loaded, so we make a discovery request to trigger loading for this test.
		$this->server->dispatch( new WP_REST_Request( 'GET', '/' ) );
		$routes = $this->server->get_routes();

		$this->assertArrayHasKey( $this->endpoint, $routes );
	}

	/**
	 * Test getting reports.
	 */
	public function test_get_reports() {
		WC_Helper_Reports::reset_stats_dbs();
		wp_set_current_user( $this->user );

		// Populate all of the data.
		// Simple product.
		$product = new WC_Product_Simple();
		$product->set_name( 'Test Product' );
		$product->set_regular_price( 25 );
		$product->save();

		// Coupons.
		$coupon_1_amount = 1; // by default in create_coupon.
		$coupon_1        = WC_Helper_Coupon::create_coupon( 'coupon_1' );

		$coupon_2_amount = 2;
		$coupon_2        = WC_Helper_Coupon::create_coupon( 'coupon_2' );
		$coupon_2->set_amount( $coupon_2_amount );
		$coupon_2->save();

		// Order without coupon.
		$order = WC_Helper_Order::create_order( 1, $product );
		$order->set_status( OrderStatus::COMPLETED );
		$order->set_total( 100 ); // $25 x 4.
		$order->save();

		$time = time();

		// Order with 1 coupon.
		$order_1c = WC_Helper_Order::create_order( 1, $product );
		$order_1c->set_status( OrderStatus::COMPLETED );
		$order_1c->apply_coupon( $coupon_1 );
		$order_1c->calculate_totals();
		$order_1c->set_date_created( $time );
		$order_1c->save();

		// Order with 2 coupons.
		$order_2c = WC_Helper_Order::create_order( 1, $product );
		$order_2c->set_status( OrderStatus::COMPLETED );
		$order_2c->apply_coupon( $coupon_1 );
		$order_2c->apply_coupon( $coupon_2 );
		$order_2c->calculate_totals();
		$order_2c->set_date_created( $time );
		$order_2c->save();

		WC_Helper_Queue::run_all_pending( 'wc-admin-data' );

		$request = new WP_REST_Request( 'GET', $this->endpoint );
		$request->set_query_params(
			array(
				'before'   => gmdate( 'Y-m-d 23:59:59', $time ),
				'after'    => gmdate( 'Y-m-d 00:00:00', $time ),
				'interval' => 'day',
			)
		);

		$response = $this->server->dispatch( $request );
		$reports  = $response->get_data();

		$expected_reports = array(
			'totals'    => array(
				'amount'                   => 4.0,
				'coupons_count'            => 2,
				'orders_count'             => 2,
				'segments'                 => array(),
				'reporting_missing_orders' => 0,
			),
			'intervals' => array(
				array(
					'interval'       => gmdate( 'Y-m-d', $time ),
					'date_start'     => gmdate( 'Y-m-d 00:00:00', $time ),
					'date_start_gmt' => gmdate( 'Y-m-d 00:00:00', $time ),
					'date_end'       => gmdate( 'Y-m-d 23:59:59', $time ),
					'date_end_gmt'   => gmdate( 'Y-m-d 23:59:59', $time ),
					'subtotals'      => (object) array(
						'amount'                   => 4.0,
						'coupons_count'            => 2,
						'orders_count'             => 2,
						'segments'                 => array(),
						'reporting_missing_orders' => 0,
					),
				),
			),
		);

		$this->assertEquals( 200, $response->get_status() );
		$this->assertEquals( $expected_reports, $reports );
	}

	/**
	 * Equal product discounts must not multiply by coupon count or category ancestry.
	 *
	 * @dataProvider allocation_breakdown_provider
	 * @param string $dimension Breakdown dimension.
	 * @param bool   $subset Whether only one of the order's coupons is selected.
	 * @param bool   $amount_only Whether fields are restricted to the amount.
	 * @param bool   $currency_schema Whether currency schema availability is reported.
	 */
	public function test_historical_product_allocations( $dimension, $subset, $amount_only, $currency_schema ) {
		wp_set_current_user( $this->user );
		WC_Helper_Reports::reset_stats_dbs();
		$products = array();
		$coupons  = array();
		$parent   = wp_insert_term( 'Allocation parent ' . wp_generate_uuid4(), 'product_cat' );
		$child    = wp_insert_term( 'Allocation child ' . wp_generate_uuid4(), 'product_cat', array( 'parent' => $parent['term_id'] ) );
		$order    = wc_create_order();
		$schema   = new ReflectionProperty( \Automattic\WooCommerce\Admin\API\Reports\Orders\Stats\DataStore::class, 'reporting_columns' );
		$schema->setAccessible( true );
		$schema_before = $schema->getValue();
		$variable      = null;
		try {
			if ( 'variation' === $dimension ) {
				$variable = new WC_Product_Variable();
				$variable->set_name( 'Allocation variations' );
				$variable->save();
			}
			foreach ( array( 0, 1 ) as $index ) {
				$product = $variable ? new WC_Product_Variation() : new WC_Product_Simple();
				if ( $variable ) {
					$product->set_parent_id( $variable->get_id() );
				}
				$product->set_name( 'Allocation product ' . $index );
				$product->set_regular_price( 36 );
				$product->set_category_ids( 0 === $index ? array( $parent['term_id'], $child['term_id'] ) : array( $parent['term_id'] ) );
				$product->save();
				$products[] = $product;
				$coupon     = new WC_Coupon();
				$coupon->set_code( 'allocation-' . $index . '-' . wp_generate_uuid4() );
				$coupon->set_discount_type( 'fixed_product' );
				$coupon->set_amount( 5 );
				$coupon->set_product_ids( array( $product->get_id() ) );
				$coupon->save();
				$coupons[] = $coupon;
				$order->add_product( $product, 1 );
			}
			\Automattic\WooCommerce\Internal\Admin\CategoryLookup::instance()->regenerate();
			$order->set_currency( get_woocommerce_currency() );
			$order->set_status( OrderStatus::COMPLETED );
			$order->set_date_created( '2024-04-10 12:00:00' );
			$order->calculate_totals();
			foreach ( $coupons as $coupon ) {
				$this->assertTrue( $order->apply_coupon( $coupon ) );
			}
			$order->save();
			\Automattic\WooCommerce\Admin\API\Reports\Orders\Stats\DataStore::sync_order( $order->get_id() );
			\Automattic\WooCommerce\Admin\API\Reports\Products\DataStore::sync_order_products( $order->get_id() );
			\Automattic\WooCommerce\Admin\API\Reports\Coupons\DataStore::sync_order_coupons( $order->get_id() );
			// WC_Data properties are accessed via their public getters.
			$ids     = array_map(
				static function ( $coupon ) {
					return $coupon->get_id();
				},
				$coupons
			);
			$request = new WP_REST_Request( 'GET', $this->endpoint );
			$params  = array(
				'coupons'   => $subset ? array( $ids[0] ) : $ids,
				'segmentby' => $dimension,
				'after'     => '2024-03-01T00:00:00',
				'before'    => '2024-05-31T23:59:59',
				'interval'  => 'month',
			);
			if ( $variable ) {
				$params['product_includes'] = array( $variable->get_id() );
			}
			if ( $amount_only ) {
				$params['fields'] = array( 'amount' );
			}
			if ( ! $currency_schema ) {
				global $wpdb;
				$schema->setValue( null, array( $wpdb->prefix . 'wc_order_stats' => false ) );
			}
			$request->set_query_params( $params );
			$response = $this->server->dispatch( $request );
			$this->assertSame( 200, $response->get_status() );
			$data = json_decode( wp_json_encode( $response->get_data() ), true );
			$this->assertEquals( $subset ? 5 : 10, $data['totals']['amount'] );
			$periods = array( $data['totals'] );
			foreach ( $data['intervals'] as $interval ) {
				$periods[] = $interval['subtotals'];
			}
			foreach ( $periods as $period ) {
				$segment_ids = 'category' === $dimension ? array( $parent['term_id'], $child['term_id'] ) : array_map(
					static function ( $product ) {
						return $product->get_id();
					},
					$products
				);
				$segments    = array_filter(
					$period['segments'],
					static function ( $segment ) use ( $segment_ids ) {
						return in_array( (int) $segment['segment_id'], $segment_ids, true );
					}
				);
				$this->assertCount( 2, $segments );
				$populated = $period['amount'] > 0;
				foreach ( $segments as $segment ) {
					$values = $segment['subtotals'];
					if ( $subset && $populated ) {
						$this->assertNull( $values['amount'] );
					} else {
						$expected = 'category' === $dimension && (int) $segment['segment_id'] === $parent['term_id'] ? 10 : 5;
						$this->assertEquals( $populated ? $expected : 0, $values['amount'] );
					}
					$this->assertSame( $subset && $populated ? 1 : 0, $values['allocation_missing_orders'] );
					if ( ! $amount_only ) {
						$this->assertSame( $populated ? 1 : 0, $values['orders_count'] );
						$this->assertSame( $populated ? ( $subset ? 1 : 2 ) : 0, $values['coupons_count'] );
					}
				}
			}
		} finally {
			$schema->setValue( null, $schema_before );
			$order->delete( true );
			foreach ( $products as $product ) {
				$product->delete( true ); }
			foreach ( $coupons as $coupon ) {
				$coupon->delete( true ); }
			if ( $variable ) {
				$variable->delete( true );
			}
			wp_delete_term( $child['term_id'], 'product_cat' );
			wp_delete_term( $parent['term_id'], 'product_cat' );
		}
	}

	/** @return array Dimension, coupon selection and selected-field cases. */
	public function allocation_breakdown_provider() {
		$cases = array();
		foreach ( array( 'product', 'category', 'variation' ) as $dimension ) {
			foreach ( array( true, false ) as $subset ) {
				foreach ( array( true, false ) as $amount_only ) {
					$cases[] = array( $dimension, $subset, $amount_only, true );
				}
			}
		}
		$cases[] = array( 'product', true, true, false );
		return $cases;
	}

	/**
	 * Invalid parent selectors must fail before querying variation amounts.
	 *
	 * @dataProvider invalid_variation_parent_provider
	 * @param array  $parents Parent selector.
	 * @param string $code Expected API error code.
	 */
	public function test_invalid_variation_parent( $parents, $code ) {
		wp_set_current_user( $this->user );
		$request = new WP_REST_Request( 'GET', $this->endpoint );
		$request->set_query_params(
			array(
				'segmentby'           => 'variation',
				'product_includes'    => $parents,
				'force_cache_refresh' => true,
			)
		);
		$response = $this->server->dispatch( $request );
		$this->assertSame( 400, $response->get_status() );
		$this->assertSame( $code, $response->get_data()['code'] );
	}

	/** @return array Empty, ambiguous and malformed parent selectors. */
	public function invalid_variation_parent_provider() {
		return array(
			array( array(), 'wc_admin_reports_invalid_segmenting_variation' ),
			array( array( 1, 2 ), 'wc_admin_reports_invalid_segmenting_variation' ),
			array( array( '1 OR 1=1' ), 'rest_invalid_param' ),
			array( array( 0 ), 'rest_invalid_param' ),
			array( array( 999999999 ), 'rest_invalid_param' ),
		);
	}

	/**
	 * Test getting reports without valid permissions.
	 */
	public function test_get_reports_without_permission() {
		wp_set_current_user( 0 );
		$response = $this->server->dispatch( new WP_REST_Request( 'GET', $this->endpoint ) );
		$this->assertEquals( 401, $response->get_status() );
	}

	/**
	 * Test reports schema.
	 */
	public function test_reports_schema() {
		wp_set_current_user( $this->user );

		$request    = new WP_REST_Request( 'OPTIONS', $this->endpoint );
		$response   = $this->server->dispatch( $request );
		$data       = $response->get_data();
		$properties = $data['schema']['properties'];

		$this->assertEquals( 2, count( $properties ) );
		$this->assertArrayHasKey( 'totals', $properties );
		$this->assertArrayHasKey( 'intervals', $properties );

		$totals = $properties['totals']['properties'];
		$this->assertEquals( 6, count( $totals ) );
		$this->assertSame( 'integer', $totals['reporting_missing_orders']['type'] );
		$this->assertSame( 'integer', $totals['allocation_missing_orders']['type'] );
		$this->assertSame( array( 'number', 'null' ), $totals['amount']['type'] );
		$this->assertArrayHasKey( 'amount', $totals );
		$this->assertArrayHasKey( 'coupons_count', $totals );
		$this->assertArrayHasKey( 'orders_count', $totals );
		$this->assertArrayHasKey( 'segments', $totals );

		$intervals = $properties['intervals']['items']['properties'];
		$this->assertEquals( 6, count( $intervals ) );
		$this->assertArrayHasKey( 'interval', $intervals );
		$this->assertArrayHasKey( 'date_start', $intervals );
		$this->assertArrayHasKey( 'date_start_gmt', $intervals );
		$this->assertArrayHasKey( 'date_end', $intervals );
		$this->assertArrayHasKey( 'date_end_gmt', $intervals );
		$this->assertArrayHasKey( 'subtotals', $intervals );

		$subtotals = $properties['intervals']['items']['properties']['subtotals']['properties'];
		$this->assertEquals( 6, count( $subtotals ) );
		$this->assertSame( 'integer', $subtotals['reporting_missing_orders']['type'] );
		$this->assertArrayHasKey( 'amount', $subtotals );
		$this->assertArrayHasKey( 'coupons_count', $subtotals );
		$this->assertArrayHasKey( 'orders_count', $subtotals );
		$this->assertArrayHasKey( 'segments', $subtotals );
	}
}
