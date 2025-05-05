<?php
declare( strict_types = 1 );

namespace Automattic\WooCommerce\Tests\Blocks\StoreApi\Utilities;

use Automattic\WooCommerce\StoreApi\Utilities\ProductQueryFilters;
use Automattic\WooCommerce\Tests\Blocks\Helpers\FixtureData;
use Yoast\PHPUnitPolyfills\TestCases\TestCase;

/**
 * Unit tests for the ProductQueryFilters class.
 */
class ProductQueryFiltersTest extends TestCase {
	/**
	 * Setup test product data. Called before every test.
	 */
	protected function setUp(): void {
		parent::setUp();

		$fixtures = new FixtureData();

		add_filter(
			'woocommerce_product_stock_status_options',
			function ( $status ) {
				$status['custom1'] = 'Custom status 1';
				$status['custom2'] = 'Custom status 2';
				return $status;
			},
			10,
			1
		);

		$this->products = array(
			$fixtures->get_simple_product(
				array(
					'name'          => 'Test Product 1',
					'stock_status'  => 'custom1',
					'regular_price' => 10,
					'weight'        => 10,
				)
			),
			$fixtures->get_simple_product(
				array(
					'name'          => 'Test Product 2',
					'stock_status'  => 'custom2',
					'regular_price' => 10,
					'weight'        => 10,
				)
			),
			$fixtures->get_simple_product(
				array(
					'name'          => 'Test Product 3',
					'stock_status'  => 'custom2',
					'regular_price' => 10,
					'weight'        => 10,
				)
			),
		);
	}

	/**
	 * Test that custom stock levels are returned properly with the correct counts.
	 */
	public function test_custom_stock_counts() {
		$class  = new ProductQueryFilters();
		$result = $class->get_stock_status_counts( new \WP_REST_Request( 'GET', '/wc/store/v1/products' ) );
		$this->assertArrayHasKey( 'custom1', $result );
		$this->assertArrayHasKey( 'custom2', $result );
		$this->assertEquals( 1, $result['custom1'] );
		$this->assertEquals( 2, $result['custom2'] );
	}
}
