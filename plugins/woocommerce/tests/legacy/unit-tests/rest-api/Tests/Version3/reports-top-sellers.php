<?php
/**
 * Tests for the reports top sellers REST API.
 *
 * @package WooCommerce\Tests\API
 */

declare( strict_types=1 );

use Automattic\WooCommerce\Enums\OrderStatus;

/**
 * WC_Tests_API_Reports_Top_Sellers.
 */
class WC_Tests_API_Reports_Top_Sellers extends WC_REST_Unit_Test_Case {

	/**
	 * Setup our test server and administrator.
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
	 * @testdox Should return top sellers in descending purchased-quantity order.
	 */
	public function test_top_sellers_contract(): void {
		$order        = null;
		$high_seller  = null;
		$lower_seller = null;

		try {
			$high_seller = WC_Helper_Product::create_simple_product();
			$high_seller->set_name( 'High quantity report product' );
			$high_seller->save();

			$lower_seller = WC_Helper_Product::create_simple_product();
			$lower_seller->set_name( 'Lower quantity report product' );
			$lower_seller->save();

			$order = wc_create_order();
			$order->add_product( $high_seller, 3 );
			$order->add_product( $lower_seller, 1 );
			$order->calculate_totals();
			$order->set_status( OrderStatus::COMPLETED );
			$order->save();

			$routes = $this->server->get_routes();
			$this->assertArrayHasKey( '/wc/v3/reports/top_sellers', $routes );

			wp_set_current_user( $this->user );
			$request = new WP_REST_Request( 'GET', '/wc/v3/reports/top_sellers' );
			$request->set_query_params(
				array(
					'date_min' => gmdate( 'Y-m-d', strtotime( '-1 day' ) ),
					'date_max' => gmdate( 'Y-m-d', strtotime( '+1 day' ) ),
				)
			);
			$response = $this->server->dispatch( $request );
			$this->assertSame( 200, $response->get_status() );

			$reports = array_values(
				array_filter(
					$response->get_data(),
					static function ( array $report ) use ( $high_seller, $lower_seller ): bool {
						return in_array( $report['product_id'], array( $high_seller->get_id(), $lower_seller->get_id() ), true );
					}
				)
			);

			$this->assertSame(
				array(
					array(
						'name'       => $high_seller->get_name(),
						'product_id' => $high_seller->get_id(),
						'quantity'   => 3,
					),
					array(
						'name'       => $lower_seller->get_name(),
						'product_id' => $lower_seller->get_id(),
						'quantity'   => 1,
					),
				),
				array_map(
					static function ( array $report ): array {
						return array_intersect_key( $report, array_flip( array( 'name', 'product_id', 'quantity' ) ) );
					},
					$reports
				)
			);

			$schema_response = $this->server->dispatch( new WP_REST_Request( 'OPTIONS', '/wc/v3/reports/top_sellers' ) );
			$properties      = $schema_response->get_data()['schema']['properties'];

			$this->assertSame( array( 'name', 'product_id', 'quantity' ), array_keys( $properties ) );
			$this->assertSame( 'string', $properties['name']['type'] );
			$this->assertSame( 'integer', $properties['product_id']['type'] );
			$this->assertSame( 'integer', $properties['quantity']['type'] );

			wp_set_current_user( 0 );
			$anonymous_response = $this->server->dispatch( new WP_REST_Request( 'GET', '/wc/v3/reports/top_sellers' ) );
			$this->assertSame( 401, $anonymous_response->get_status() );
		} finally {
			if ( $order instanceof WC_Order ) {
				$order->delete( true );
			}
			if ( $high_seller instanceof WC_Product ) {
				$high_seller->delete( true );
			}
			if ( $lower_seller instanceof WC_Product ) {
				$lower_seller->delete( true );
			}
		}
	}
}
