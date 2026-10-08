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
		$high_seller = WC_Helper_Product::create_simple_product( true, array( 'name' => 'High quantity report product' ) );
		$low_seller  = WC_Helper_Product::create_simple_product( true, array( 'name' => 'Lower quantity report product' ) );

		$order = wc_create_order();
		$order->add_product( $high_seller, 3 );
		$order->add_product( $low_seller, 1 );
		$order->calculate_totals();
		$order->set_status( OrderStatus::COMPLETED );
		$order->save();

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
		$this->assertSame(
			array(
				array(
					'name'       => 'High quantity report product',
					'product_id' => $high_seller->get_id(),
					'quantity'   => 3,
				),
				array(
					'name'       => 'Lower quantity report product',
					'product_id' => $low_seller->get_id(),
					'quantity'   => 1,
				),
			),
			array_map(
				static function ( array $report ): array {
					return array_intersect_key( $report, array_flip( array( 'name', 'product_id', 'quantity' ) ) );
				},
				$response->get_data()
			)
		);

		$schema_response = $this->server->dispatch( new WP_REST_Request( 'OPTIONS', '/wc/v3/reports/top_sellers' ) );
		$properties      = $schema_response->get_data()['schema']['properties'];
		$this->assertSame( array( 'name', 'product_id', 'quantity' ), array_keys( $properties ) );
		$this->assertSame( 'string', $properties['name']['type'] );
		$this->assertSame( 'integer', $properties['product_id']['type'] );
		$this->assertSame( 'integer', $properties['quantity']['type'] );

		wp_set_current_user( 0 );
		$this->assertSame( 401, $this->server->dispatch( new WP_REST_Request( 'GET', '/wc/v3/reports/top_sellers' ) )->get_status() );
	}
}
