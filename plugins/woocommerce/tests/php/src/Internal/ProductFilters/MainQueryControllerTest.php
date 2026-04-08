<?php
declare(strict_types=1);

namespace Automattic\WooCommerce\Tests\Internal\ProductFilters;

use Automattic\WooCommerce\Internal\ProductFilters\MainQueryController;

/**
 * Tests for MainQueryController::handle_request().
 */
class MainQueryControllerTest extends \WC_Unit_Test_Case {
	/**
	 * The system under test.
	 *
	 * @var MainQueryController
	 */
	private $sut;

	/**
	 * Runs before each test.
	 */
	public function setUp(): void {
		parent::setUp();

		$this->sut = wc_get_container()->get( MainQueryController::class );
	}

	/**
	 * @testdox Should pass through values at or below the default cap of 5.
	 */
	public function test_handle_request_within_default_cap(): void {
		$query_vars = array( 'filter_stock_status' => 'instock,outofstock,onbackorder,pending,draft' );

		$result = $this->sut->handle_request( $query_vars );

		$this->assertSame( 'instock,outofstock,onbackorder,pending,draft', $result['filter_stock_status'] );
	}

	/**
	 * @testdox Should truncate values exceeding the default cap of 5.
	 */
	public function test_handle_request_truncates_excess(): void {
		$query_vars = array( 'filter_stock_status' => 'instock,outofstock,onbackorder,pending,draft,archived' );

		$result = $this->sut->handle_request( $query_vars );

		$this->assertSame( 'instock,outofstock,onbackorder,pending,draft', $result['filter_stock_status'] );
	}

	/**
	 * @testdox Should respect a custom cap from the filter hook.
	 */
	public function test_handle_request_respects_custom_cap(): void {
		add_filter( 'woocommerce_product_filter_max_values_per_parameter', fn() => 2 );

		$query_vars = array( 'filter_stock_status' => 'instock,outofstock,onbackorder' );
		$result     = $this->sut->handle_request( $query_vars );

		remove_all_filters( 'woocommerce_product_filter_max_values_per_parameter' );
		$this->assertSame( 'instock,outofstock', $result['filter_stock_status'] );
	}

	/**
	 * @testdox Should pass the parameter name to the filter hook.
	 */
	public function test_handle_request_passes_param_name_to_hook(): void {
		$received = null;
		add_filter(
			'woocommerce_product_filter_max_values_per_parameter',
			function ( $max, $param ) use ( &$received ) {
				$received = $param;
				return $max;
			},
			10,
			2
		);

		$this->sut->handle_request( array( 'filter_stock_status' => 'instock,outofstock,onbackorder,pending,draft,archived' ) );

		remove_all_filters( 'woocommerce_product_filter_max_values_per_parameter' );
		$this->assertSame( 'filter_stock_status', $received );
	}

	/**
	 * @testdox Should disable the cap when the filter hook returns zero.
	 */
	public function test_handle_request_disabled_when_hook_returns_zero(): void {
		add_filter( 'woocommerce_product_filter_max_values_per_parameter', fn() => 0 );

		$query_vars = array( 'filter_stock_status' => 'a,b,c,d,e,f,g,h' );
		$result     = $this->sut->handle_request( $query_vars );

		remove_all_filters( 'woocommerce_product_filter_max_values_per_parameter' );
		$this->assertSame( 'a,b,c,d,e,f,g,h', $result['filter_stock_status'] );
	}

	/**
	 * @testdox Should normalise and de-duplicate before applying the cap.
	 */
	public function test_handle_request_deduplicates_before_cap(): void {
		// Red/Red/RED normalise to one value via sanitize_title, so only 5 unique values remain.
		$query_vars = array( 'filter_stock_status' => 'instock,INSTOCK,Instock,outofstock,onbackorder,pending,draft' );

		$result = $this->sut->handle_request( $query_vars );

		$parts = explode( ',', $result['filter_stock_status'] );
		$this->assertCount( 5, $parts, 'Duplicates should be removed before the cap is applied' );
	}

	/**
	 * @testdox Should leave the query vars unchanged when the param is absent.
	 */
	public function test_handle_request_skips_missing_params(): void {
		$query_vars = array( 'some_other_var' => 'value' );

		$result = $this->sut->handle_request( $query_vars );

		$this->assertSame( $query_vars, $result );
	}

	/**
	 * @testdox Should remove a param when all values normalise to empty.
	 */
	public function test_handle_request_skips_empty_value(): void {
		$query_vars = array( 'filter_stock_status' => '' );

		$result = $this->sut->handle_request( $query_vars );

		// Empty string is falsy — the param should be left as-is (untouched, not removed).
		$this->assertArrayHasKey( 'filter_stock_status', $result );
		$this->assertSame( '', $result['filter_stock_status'] );
	}
}
