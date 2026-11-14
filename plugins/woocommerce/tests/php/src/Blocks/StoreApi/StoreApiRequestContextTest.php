<?php
declare( strict_types = 1 );

namespace Automattic\WooCommerce\Tests\Blocks\StoreApi;

use Automattic\WooCommerce\StoreApi\StoreApiRequestContext;
use WC_Unit_Test_Case;

/**
 * Tests for the StoreApiRequestContext class.
 */
class StoreApiRequestContextTest extends WC_Unit_Test_Case {

	/**
	 * The System Under Test.
	 *
	 * @var StoreApiRequestContext
	 */
	private $sut;

	/**
	 * Set up test fixtures.
	 */
	public function setUp(): void {
		parent::setUp();

		$this->sut = new StoreApiRequestContext();
	}

	/**
	 * @testdox There is no current request outside run_with_request().
	 */
	public function test_get_current_request_returns_null_outside_a_request(): void {
		$this->assertNull( $this->sut->get_current_request() );
	}

	/**
	 * @testdox The request is current while the callback runs, and the callback's return value is returned.
	 */
	public function test_run_with_request_sets_the_current_request_for_the_callback(): void {
		$request = new \WP_REST_Request( 'GET', '/wc/store/v1/cart' );

		$result = $this->sut->run_with_request(
			$request,
			function () {
				return $this->sut->get_current_request();
			}
		);

		$this->assertSame( $request, $result );
		$this->assertNull( $this->sut->get_current_request() );
	}

	/**
	 * @testdox A nested request is current while it runs, then the outer request is current again.
	 */
	public function test_run_with_request_supports_nested_requests(): void {
		$outer_request = new \WP_REST_Request( 'GET', '/wc/store/v1/cart' );
		$inner_request = new \WP_REST_Request( 'GET', '/wc/store/v1/products' );
		$seen          = array();

		$this->sut->run_with_request(
			$outer_request,
			function () use ( $inner_request, &$seen ) {
				$this->sut->run_with_request(
					$inner_request,
					function () use ( &$seen ) {
						$seen[] = $this->sut->get_current_request();
					}
				);
				$seen[] = $this->sut->get_current_request();
			}
		);

		$this->assertSame( array( $inner_request, $outer_request ), $seen );
		$this->assertNull( $this->sut->get_current_request() );
	}

	/**
	 * @testdox The request is cleared even when the callback throws.
	 */
	public function test_run_with_request_cleans_up_when_the_callback_throws(): void {
		$request = new \WP_REST_Request( 'GET', '/wc/store/v1/cart' );

		try {
			$this->sut->run_with_request(
				$request,
				function () {
					throw new \RuntimeException( 'Failure while building the response.' );
				}
			);
			$this->fail( 'The exception should be rethrown.' );
		} catch ( \RuntimeException $e ) {
			$this->assertSame( 'Failure while building the response.', $e->getMessage() );
		}

		$this->assertNull( $this->sut->get_current_request() );
	}
}
