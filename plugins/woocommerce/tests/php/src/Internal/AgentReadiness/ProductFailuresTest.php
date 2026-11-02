<?php
declare( strict_types = 1 );

namespace Automattic\WooCommerce\Tests\Internal\AgentReadiness;

use Automattic\WooCommerce\Internal\AgentReadiness\AgentReadiness;
use Automattic\WooCommerce\Internal\AgentReadiness\ProductFailures;
use Automattic\WooCommerce\Internal\AgentReadiness\ProductScan;
use WC_Helper_Product;
use WC_Unit_Test_Case;

/**
 * Tests for what each product fails, stored by the scan.
 */
class ProductFailuresTest extends WC_Unit_Test_Case {

	/**
	 * Readiness service.
	 *
	 * @var AgentReadiness
	 */
	private AgentReadiness $readiness;

	/**
	 * Run before each test.
	 */
	public function setUp(): void {
		parent::setUp();
		StoreOutputChecksTest::block_requests();
		$this->readiness = wc_get_container()->get( AgentReadiness::class );
	}

	/**
	 * Run after each test.
	 */
	public function tearDown(): void {
		remove_all_filters( 'pre_http_request' );
		delete_option( AgentReadiness::REPORT_OPTION );
		parent::tearDown();
	}

	/**
	 * A count from the stored report.
	 *
	 * @param string $id Check id.
	 */
	private function check_count( string $id ): int {
		return (int) array_column( (array) $this->readiness->get_cached_report()['checks'], 'count', 'id' )[ $id ];
	}

	/**
	 * @testdox A scan stores what each product fails and clears products it no longer tests.
	 */
	public function test_scan_stores_and_clears(): void {
		$product = WC_Helper_Product::create_simple_product();

		$this->readiness->refresh_report();

		$stored = ProductFailures::for_product( $product->get_id() );
		$this->assertContains( 'missing_images', $stored );
		$this->assertSame( 1, $this->check_count( 'missing_images' ) );
		$this->assertNotEmpty( array_filter( $stored, static fn( string $value ): bool => 0 === strpos( $value, ProductScan::NOT_LISTABLE . ':' ) ) );

		wp_update_post(
			array(
				'ID'          => $product->get_id(),
				'post_status' => 'draft',
			)
		);
		$this->readiness->refresh_report();
		$this->assertSame( array(), ProductFailures::for_product( $product->get_id() ) );
	}

	/**
	 * @testdox The report's counts follow the rows, and a report from before rows were stored keeps its own.
	 */
	public function test_counts_follow_rows(): void {
		$product = WC_Helper_Product::create_simple_product();
		$this->readiness->refresh_report();

		ProductFailures::store( $product->get_id(), array() );
		$this->assertSame( 0, $this->check_count( 'missing_images' ) );
		$channels = (array) $this->readiness->get_cached_report()['channels'];
		$this->assertSame( 1, reset( $channels )['complete'] );

		update_option(
			AgentReadiness::REPORT_OPTION,
			array(
				'checks' => array(
					array(
						'id'    => 'missing_images',
						'count' => 3,
					),
				),
			)
		);
		$this->assertSame( 3, $this->check_count( 'missing_images' ) );
	}

	/**
	 * @testdox Every checked product has a checked row, passing or not, which for_product leaves out and the product total counts.
	 */
	public function test_checked_row_counts_products(): void {
		$failing = WC_Helper_Product::create_simple_product();
		$passing = WC_Helper_Product::create_simple_product();
		$this->readiness->refresh_report();

		ProductFailures::store( $passing->get_id(), array() );
		$this->assertSame( array(), ProductFailures::for_product( $passing->get_id() ) );
		$this->assertContains( ProductFailures::CHECKED, (array) get_post_meta( $passing->get_id(), ProductFailures::META_KEY, false ) );
		$this->assertNotContains( ProductFailures::CHECKED, ProductFailures::for_product( $failing->get_id() ) );
		$this->assertSame( 2, ProductFailures::counts()[ ProductFailures::CHECKED ] );

		ProductFailures::forget( $passing->get_id() );
		$this->assertSame( 1, ProductFailures::counts()[ ProductFailures::CHECKED ], 'A write clears the counts read earlier in the request' );
		$this->assertSame( 1, $this->readiness->get_cached_report()['products'] );
	}
}
