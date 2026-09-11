<?php

declare( strict_types = 1 );

namespace Automattic\WooCommerce\Tests\Admin\API\Reports\Stock;

use Automattic\WooCommerce\Enums\ProductStatus;
use WC_Product_Simple;
use WC_REST_Unit_Test_Case;
use WP_REST_Request;

/**
 * Stock report API controller test.
 */
class ControllerTest extends WC_REST_Unit_Test_Case {
	/**
	 * Endpoint.
	 *
	 * @var string
	 */
	const ENDPOINT = '/wc-analytics/reports/stock';

	/**
	 * Set up.
	 */
	public function setUp(): void {
		parent::setUp();

		wp_set_current_user( $this->factory->user->create( array( 'role' => 'administrator' ) ) );
	}

	/**
	 * Tear down.
	 */
	public function tearDown(): void {
		unset( $GLOBALS['current_screen'] );

		parent::tearDown();
	}

	/**
	 * The CSV export counts the rows once when it queues its batches (a REST request) and again
	 * while each batch runs (an admin-ajax request). A report that returns more products in an
	 * admin context leaves the export short of 100%, so its download email is never sent.
	 *
	 * @testdox Should list only published and private products, in an admin context and outside one.
	 */
	public function test_report_lists_only_published_and_private_products_in_any_context(): void {
		$statuses = array(
			ProductStatus::PUBLISH,
			ProductStatus::PRIVATE,
			ProductStatus::DRAFT,
			ProductStatus::PENDING,
			ProductStatus::FUTURE,
		);

		$ids = array();
		foreach ( $statuses as $status ) {
			$ids[ $status ] = $this->create_product( $status )->get_id();
		}

		$listed = array( $ids[ ProductStatus::PUBLISH ], $ids[ ProductStatus::PRIVATE ] );
		sort( $listed );

		$expected = array(
			'ids'   => $listed,
			'total' => count( $listed ),
		);

		$this->assertFalse( is_admin(), 'The first report must be requested outside an admin context.' );
		$this->assertSame( $expected, $this->get_report( $ids ), 'The stock report lists the wrong products outside an admin context.' );

		set_current_screen( 'edit-post' );

		$this->assertTrue( is_admin(), 'The second report must be requested in an admin context.' );
		$this->assertSame( $expected, $this->get_report( $ids ), 'The stock report lists the wrong products in an admin context.' );
	}

	/**
	 * Create a product with the given status.
	 *
	 * @param string $status Product status.
	 * @return WC_Product_Simple
	 */
	private function create_product( string $status ): WC_Product_Simple {
		$product = new WC_Product_Simple();
		$product->set_name( "Test $status product" );
		$product->set_regular_price( 5 );
		$product->set_manage_stock( true );
		$product->set_stock_quantity( 1 );

		if ( ProductStatus::FUTURE === $status ) {
			$product->set_date_created( time() + WEEK_IN_SECONDS );
		}

		$product->set_status( $status );
		$product->save();

		return $product;
	}

	/**
	 * Request the stock report for the given products.
	 *
	 * @param int[] $ids Product IDs to report on.
	 * @return array Reported product IDs, plus the total the CSV export batches on.
	 */
	private function get_report( array $ids ): array {
		$request = new WP_REST_Request( 'GET', self::ENDPOINT );
		$request->set_param( 'include', implode( ',', $ids ) );
		$request->set_param( 'orderby', 'id' );

		$response = $this->server->dispatch( $request );
		$this->assertSame( 200, $response->get_status() );

		$headers = $response->get_headers();

		return array(
			'ids'   => wp_list_pluck( $response->get_data(), 'id' ),
			'total' => (int) $headers['X-WP-Total'],
		);
	}
}
