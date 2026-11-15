<?php
declare( strict_types = 1 );

namespace Automattic\WooCommerce\Tests\Admin\API\Reports\Variations\Stats;

use Automattic\WooCommerce\Admin\API\Reports\Variations\Stats\Controller;
use ReflectionMethod;
use WC_Unit_Test_Case;
use WP_REST_Request;

/**
 * Tests for the Variations Stats report controller query params.
 */
class ControllerTest extends WC_Unit_Test_Case {

	/**
	 * The System Under Test.
	 *
	 * @var Controller
	 */
	private $sut;

	/**
	 * Set up test fixtures.
	 */
	public function setUp(): void {
		parent::setUp();
		$this->sut = new Controller();
	}

	/**
	 * @testdox get_collection_params registers the products alias used by Analytics single-product views.
	 */
	public function test_get_collection_params_registers_products_alias(): void {
		$params = $this->sut->get_collection_params();

		$this->assertArrayHasKey( 'products', $params, 'Stats should accept the products alias the Variations table sends.' );
		$this->assertArrayHasKey( 'product_includes', $params, 'Stats should still accept product_includes directly.' );
		$this->assertSame( 'array', $params['products']['type'] );
	}

	/**
	 * @testdox prepare_reports_query maps products to product_includes like the Variations list endpoint.
	 */
	public function test_prepare_reports_query_maps_products_to_product_includes(): void {
		$request = new WP_REST_Request( 'GET', '/wc-analytics/reports/variations/stats' );
		$request->set_param( 'products', array( 12, 34 ) );

		$method = new ReflectionMethod( Controller::class, 'prepare_reports_query' );
		$method->setAccessible( true );
		$query_args = $method->invoke( $this->sut, $request );

		$this->assertArrayHasKey( 'product_includes', $query_args );
		$this->assertSame( array( 12, 34 ), $query_args['product_includes'] );
		$this->assertArrayNotHasKey( 'products', $query_args, 'The alias should be mapped, not passed through unchanged.' );
	}
}
