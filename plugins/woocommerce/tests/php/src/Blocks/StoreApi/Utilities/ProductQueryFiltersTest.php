<?php
declare( strict_types = 1 );

namespace Automattic\WooCommerce\Tests\Blocks\StoreApi\Utilities;

use Automattic\WooCommerce\StoreApi\Utilities\ProductQueryFilters;
use Automattic\WooCommerce\Tests\Blocks\Helpers\FixtureData;

/**
 * Unit tests for the ProductQueryFilters class.
 */
class ProductQueryFiltersTest extends \WC_Unit_Test_Case {
	/**
	 * Test products.
	 *
	 * @var \WC_Product[]
	 */
	private $products;
	/**
	 * Setup test product data. Called before every test.
	 */
	public function setUp(): void {
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

	/**
	 * @testdox Should count products on the core stock statuses.
	 */
	public function test_core_stock_status_counts(): void {
		$fixtures = new FixtureData();
		foreach ( array( 'instock', 'outofstock', 'onbackorder' ) as $status ) {
			$fixtures->get_simple_product(
				array(
					'name'          => "Core {$status}",
					'stock_status'  => $status,
					'regular_price' => 10,
				)
			);
		}

		$result = ( new ProductQueryFilters() )->get_stock_status_counts( new \WP_REST_Request( 'GET', '/wc/store/v1/products' ) );

		$this->assertSame(
			array(
				'instock'     => '1',
				'outofstock'  => '1',
				'onbackorder' => '1',
				'custom1'     => '1',
				'custom2'     => '2',
			),
			$result
		);
	}

	/**
	 * @testdox Should execute one combined stock query with a query observer attached.
	 */
	public function test_stock_counts_with_query_observer(): void {
		$queries  = array();
		$observer = static function ( $sql ) use ( &$queries ) {
			if ( false !== strpos( $sql, "meta_key = '_stock_status'" ) ) {
				$queries[] = $sql;
			}
			return $sql;
		};
		add_filter( 'query', $observer );
		try {
			$result = ( new ProductQueryFilters() )->get_stock_status_counts( new \WP_REST_Request( 'GET', '/wc/store/v1/products' ) );
		} finally {
			remove_filter( 'query', $observer );
		}

		$this->assertSame(
			array(
				'instock'     => '0',
				'outofstock'  => '0',
				'onbackorder' => '0',
				'custom1'     => '1',
				'custom2'     => '2',
			),
			$result
		);
		$this->assertCount( 1, $queries, 'All stock statuses should be counted by one statement.' );
	}

	/**
	 * @testdox Should honor an empty stock-status options filter without executing aggregate SQL.
	 */
	public function test_empty_stock_status_options(): void {
		$empty_options = static function () {
			return array();
		};
		$queries       = array();
		$observer      = static function ( $sql ) use ( &$queries ) {
			if ( false !== strpos( $sql, "meta_key = '_stock_status'" ) ) {
				$queries[] = $sql;
			}
			return $sql;
		};
		add_filter( 'woocommerce_product_stock_status_options', $empty_options, 20 );
		add_filter( 'query', $observer );
		try {
			$result = ( new ProductQueryFilters() )->get_stock_status_counts( new \WP_REST_Request( 'GET', '/wc/store/v1/products' ) );
		} finally {
			remove_filter( 'woocommerce_product_stock_status_options', $empty_options, 20 );
			remove_filter( 'query', $observer );
		}

		$this->assertSame( array(), $result );
		$this->assertSame( array(), $queries );
	}

	/**
	 * @testdox Should count a product once when stock metadata is duplicated.
	 */
	public function test_duplicate_stock_metadata(): void {
		add_post_meta( $this->products[0]->get_id(), '_stock_status', 'custom1' );
		$result = ( new ProductQueryFilters() )->get_stock_status_counts( new \WP_REST_Request( 'GET', '/wc/store/v1/products' ) );

		$this->assertSame( '1', $result['custom1'] );
		$this->assertSame( '2', $result['custom2'] );
	}

	/**
	 * @testdox Should preserve product membership while removing the stock-status request constraint.
	 */
	public function test_filtered_stock_counts(): void {
		$request = new \WP_REST_Request( 'GET', '/wc/store/v1/products' );
		$request->set_param( 'include', array( $this->products[0]->get_id() ) );
		$request->set_param( 'stock_status', array( 'custom2' ) );
		$result = ( new ProductQueryFilters() )->get_stock_status_counts( $request );

		$this->assertSame( '1', $result['custom1'] );
		$this->assertSame( '0', $result['custom2'] );
	}

	/**
	 * @testdox Should count safely when a custom status key contains a quote.
	 */
	public function test_quoted_stock_status_option(): void {
		global $wpdb;

		$status  = "custom'quoted";
		$options = static function ( $statuses ) use ( $status ) {
			$statuses[ $status ] = 'Quoted custom status';
			return $statuses;
		};
		add_filter( 'woocommerce_product_stock_status_options', $options, 20 );
		try {
			$result = ( new ProductQueryFilters() )->get_stock_status_counts( new \WP_REST_Request( 'GET', '/wc/store/v1/products' ) );
		} finally {
			remove_filter( 'woocommerce_product_stock_status_options', $options, 20 );
		}

		$this->assertSame( '', $wpdb->last_error, 'A quote in a status key must not break the count statement.' );
		$this->assertSame( '1', $result['custom1'] );
		$this->assertSame( '2', $result['custom2'] );
		$this->assertCount( 6, $result, 'Every status from the filter should be present in the result.' );
	}
}
