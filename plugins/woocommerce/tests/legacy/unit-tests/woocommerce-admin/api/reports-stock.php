<?php
/**
 * Reports Stock REST API Test
 *
 * @package WooCommerce\Admin\Tests\API
 * @since 3.5.0
 */

use Automattic\WooCommerce\Enums\ProductStockStatus;

/**
 * Class WC_Admin_Tests_API_Reports_Stock
 */
class WC_Admin_Tests_API_Reports_Stock extends WC_REST_Unit_Test_Case {

	/**
	 * Endpoints.
	 *
	 * @var string
	 */
	protected $endpoint = '/wc-analytics/reports/stock';

	/**
	 * Setup test reports stock data.
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
		wp_set_current_user( $this->user );
		WC_Helper_Reports::reset_stats_dbs();

		// Populate all of the data.
		$low_stock = new WC_Product_Simple();
		$low_stock->set_name( 'Test low stock' );
		$low_stock->set_regular_price( 5 );
		$low_stock->set_manage_stock( true );
		$low_stock->set_stock_quantity( 1 );
		$low_stock->set_stock_status( ProductStockStatus::IN_STOCK );
		$low_stock->save();

		$out_of_stock = new WC_Product_Simple();
		$out_of_stock->set_name( 'Test out of stock' );
		$out_of_stock->set_regular_price( 5 );
		$out_of_stock->set_stock_status( ProductStockStatus::OUT_OF_STOCK );
		$out_of_stock->save();

		$request = new WP_REST_Request( 'GET', $this->endpoint );
		$request->set_param( 'include', implode( ',', array( $low_stock->get_id(), $out_of_stock->get_id() ) ) );
		$request->set_param( 'orderby', 'id' );
		$response = $this->server->dispatch( $request );
		$reports  = $response->get_data();

		$this->assertEquals( 200, $response->get_status() );
		$this->assertEquals( 2, count( $reports ) );

		$this->assertEquals( $low_stock->get_id(), $reports[0]['id'] );
		$this->assertEquals( ProductStockStatus::IN_STOCK, $reports[0]['stock_status'] );
		$this->assertEquals( 1, $reports[0]['stock_quantity'] );
		$this->assertArrayHasKey( '_links', $reports[0] );
		$this->assertArrayHasKey( 'product', $reports[0]['_links'] );

		$request = new WP_REST_Request( 'GET', $this->endpoint );
		$request->set_param( 'include', implode( ',', array( $low_stock->get_id(), $out_of_stock->get_id() ) ) );
		$request->set_param( 'type', ProductStockStatus::LOW_STOCK );
		$response = $this->server->dispatch( $request );
		$reports  = $response->get_data();

		$this->assertEquals( 200, $response->get_status() );
		$this->assertEquals( 1, count( $reports ) );
	}

	/**
	 * @testdox Variations owning the stock replace their parent in the report.
	 *
	 * @see https://github.com/woocommerce/woocommerce/issues/32134
	 */
	public function test_parent_is_excluded_when_its_variations_own_the_stock() {
		wp_set_current_user( $this->user );

		list( $variable, $variation_ids ) = $this->create_variable_product(
			'Variations manage stock, parent does not',
			array(
				array(
					'manage_stock'   => true,
					'stock_quantity' => 25,
				),
				array(
					'manage_stock'   => true,
					'stock_quantity' => 25,
				),
			)
		);

		$reported_ids = $this->get_reported_ids();

		$this->assertNotContains( $variable->get_id(), $reported_ids, 'The parent holds no stock of its own, so it should not be reported.' );
		foreach ( $variation_ids as $variation_id ) {
			$this->assertContains( $variation_id, $reported_ids, 'Each variation holds its own stock, so it should be reported.' );
		}
	}

	/**
	 * @testdox Variations carrying only a stock status of their own are still reported.
	 */
	public function test_variations_without_a_quantity_are_reported_when_the_parent_manages_no_stock() {
		wp_set_current_user( $this->user );

		list( $variable, $variation_ids ) = $this->create_variable_product(
			'Nothing manages stock',
			array(
				array( 'stock_status' => ProductStockStatus::OUT_OF_STOCK ),
				array( 'stock_status' => ProductStockStatus::IN_STOCK ),
			)
		);

		$reported_ids = $this->get_reported_ids();

		$this->assertNotContains( $variable->get_id(), $reported_ids, 'The parent only derives its status from its variations, so it should not be reported.' );
		foreach ( $variation_ids as $variation_id ) {
			$this->assertContains( $variation_id, $reported_ids, 'A variation sets its own stock status even when it manages no quantity.' );
		}
	}

	/**
	 * @testdox A parent managing stock at product level replaces the variations inheriting it.
	 */
	public function test_variations_are_excluded_when_the_parent_owns_the_stock() {
		wp_set_current_user( $this->user );

		list( $variable, $variation_ids ) = $this->create_variable_product( 'Parent manages stock, variations inherit', array( array(), array() ) );

		$variable->set_manage_stock( true );
		$variable->set_stock_quantity( 3 );
		$variable->save();

		$reported_ids = $this->get_reported_ids();

		$this->assertContains( $variable->get_id(), $reported_ids, 'The parent holds the only real quantity, so it should be reported.' );
		foreach ( $variation_ids as $variation_id ) {
			$this->assertNotContains( $variation_id, $reported_ids, 'A variation inheriting the parent quantity would only repeat it.' );
		}
	}

	/**
	 * @testdox A variation overriding a stock managing parent is reported next to it.
	 */
	public function test_variation_managing_its_own_stock_is_reported_next_to_its_parent() {
		wp_set_current_user( $this->user );

		list( $variable, $variation_ids ) = $this->create_variable_product(
			'Parent manages stock, one variation overrides',
			array(
				array(),
				array(
					'manage_stock'   => true,
					'stock_quantity' => 2,
				),
			)
		);

		list( $inheriting_id, $own_stock_id ) = $variation_ids;

		$variable->set_manage_stock( true );
		$variable->set_stock_quantity( 10 );
		$variable->save();

		$reported_ids = $this->get_reported_ids();

		$this->assertContains( $variable->get_id(), $reported_ids, 'The parent holds the quantity the inheriting variation sells from.' );
		$this->assertNotContains( $inheriting_id, $reported_ids, 'The inheriting variation would only repeat the parent quantity.' );
		$this->assertContains( $own_stock_id, $reported_ids, 'The overriding variation holds a quantity of its own.' );
	}

	/**
	 * @testdox A parent running low on stock it manages at product level is reported as low stock.
	 *
	 * @see https://github.com/woocommerce/woocommerce/issues/32134
	 */
	public function test_low_stock_managed_at_product_level_is_reported() {
		wp_set_current_user( $this->user );
		update_option( 'woocommerce_notify_low_stock_amount', 5 );

		list( $variable ) = $this->create_variable_product( 'Parent manages low stock, variations inherit', array( array(), array() ) );

		$variable->set_manage_stock( true );
		$variable->set_stock_quantity( 3 );
		$variable->save();

		$reported_ids = $this->get_reported_ids( ProductStockStatus::LOW_STOCK );

		// Its variations carry no quantity of their own, so the parent is the only row that can report this.
		$this->assertContains( $variable->get_id(), $reported_ids, 'Stock managed at product level should still show up as low stock.' );
	}

	/**
	 * @testdox A variable product with no variations is reported.
	 */
	public function test_variable_product_without_variations_is_included() {
		wp_set_current_user( $this->user );

		$variable = new WC_Product_Variable();
		$variable->set_name( 'Variable product with no variations' );
		$variable->save();

		$request = new WP_REST_Request( 'GET', $this->endpoint );
		$request->set_param( 'include', (string) $variable->get_id() );
		$response = $this->server->dispatch( $request );
		$reports  = $response->get_data();

		$this->assertEquals( 200, $response->get_status() );
		$this->assertCount( 1, $reports );
		$this->assertEquals( $variable->get_id(), $reports[0]['id'] );
	}

	/**
	 * Create a published variable product with a Small and a Large variation.
	 *
	 * @param string $name       Product name, describing the stock configuration under test.
	 * @param array  $variations One entry per variation, in the order Small then Large. Each accepts
	 *                           the 'manage_stock', 'stock_quantity' and 'stock_status' keys.
	 * @return array The WC_Product_Variable, and the variation IDs in the order given.
	 */
	private function create_variable_product( $name, array $variations ) {
		$options = array( 'Small', 'Large' );

		$attribute = new WC_Product_Attribute();
		$attribute->set_name( 'Size' );
		$attribute->set_options( $options );
		$attribute->set_visible( true );
		$attribute->set_variation( true );

		$variable = new WC_Product_Variable();
		$variable->set_name( $name );
		$variable->set_attributes( array( $attribute ) );
		$variable->save();

		$variation_ids = array();
		foreach ( $options as $index => $option ) {
			$args = $variations[ $index ];

			$variation = new WC_Product_Variation();
			$variation->set_parent_id( $variable->get_id() );
			$variation->set_attributes( array( 'size' => $option ) );
			$variation->set_regular_price( '10' );
			$variation->set_manage_stock( ! empty( $args['manage_stock'] ) );

			if ( isset( $args['stock_quantity'] ) ) {
				$variation->set_stock_quantity( $args['stock_quantity'] );
			}
			if ( isset( $args['stock_status'] ) ) {
				$variation->set_stock_status( $args['stock_status'] );
			}

			$variation->save();
			$variation_ids[] = $variation->get_id();
		}

		return array( new WC_Product_Variable( $variable->get_id() ), $variation_ids );
	}

	/**
	 * Dispatch the report and return the IDs it reported.
	 *
	 * @param string $type Report type to request.
	 * @return array Reported product and variation IDs.
	 */
	private function get_reported_ids( $type = 'all' ) {
		$request = new WP_REST_Request( 'GET', $this->endpoint );
		$request->set_param( 'per_page', 100 );
		$request->set_param( 'type', $type );
		$response = $this->server->dispatch( $request );

		$this->assertEquals( 200, $response->get_status() );

		return wp_list_pluck( $response->get_data(), 'id' );
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

		$this->assertEquals( 7, count( $properties ) );
		$this->assertArrayHasKey( 'id', $properties );
		$this->assertArrayHasKey( 'parent_id', $properties );
		$this->assertArrayHasKey( 'name', $properties );
		$this->assertArrayHasKey( 'sku', $properties );
		$this->assertArrayHasKey( 'stock_status', $properties );
		$this->assertArrayHasKey( 'stock_quantity', $properties );
		$this->assertArrayHasKey( 'manage_stock', $properties );
	}
}
