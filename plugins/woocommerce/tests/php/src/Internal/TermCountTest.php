<?php
/**
 * TermCount tests.
 *
 * @package WooCommerce\Tests\Internal
 */

declare( strict_types=1 );

namespace Automattic\WooCommerce\Tests\Internal;

use Automattic\WooCommerce\Enums\ProductStockStatus;
use Automattic\WooCommerce\Internal\TermCount;
use Automattic\WooCommerce\Proxies\LegacyProxy;
use Automattic\WooCommerce\Testing\Tools\DependencyManagement\MockableLegacyProxy;
use WC_Unit_Test_Case;

/**
 * Tests for TermCount.
 */
class TermCountTest extends WC_Unit_Test_Case {
	/**
	 * The system under test.
	 *
	 * @var TermCount
	 */
	private TermCount $sut;

	/**
	 * Mockable legacy proxy.
	 *
	 * @var MockableLegacyProxy
	 */
	private MockableLegacyProxy $legacy_proxy;

	/**
	 * Sets up the test fixture.
	 */
	public function setUp(): void {
		parent::setUp();

		$this->legacy_proxy = wc_get_container()->get( LegacyProxy::class );
		$this->sut          = new TermCount();
		$this->sut->init( $this->legacy_proxy );
	}

	/**
	 * Resets function mocks after each test.
	 */
	public function tearDown(): void {
		$this->legacy_proxy->reset();
		parent::tearDown();
	}

	/**
	 * @testdox Recounts product terms when the out-of-stock relationship affects counts.
	 */
	public function test_recounts_after_out_of_stock_relationship_deletion(): void {
		$recounted_product_id = null;
		$this->mock_functions( $recounted_product_id );

		$this->sut->handle_deleted_term_relationships( 123, array( 456 ), 'product_visibility' );

		$this->assertSame( 123, $recounted_product_id, 'The affected product should be recounted.' );
	}

	/**
	 * @testdox Does not recount product terms for unrelated relationship deletions.
	 */
	public function test_does_not_recount_after_unrelated_relationship_deletion(): void {
		$recounted_product_id = null;
		$this->mock_functions( $recounted_product_id );

		$this->sut->handle_deleted_term_relationships( 123, array( 789 ), 'product_visibility' );
		$this->sut->handle_deleted_term_relationships( 123, array( 456 ), 'product_tag' );

		$this->assertNull( $recounted_product_id, 'Unrelated relationship deletions should not recount the product.' );
	}

	/**
	 * Registers the function mocks used by the tests.
	 *
	 * @param int|null $recounted_product_id Receives the recounted product ID.
	 */
	private function mock_functions( ?int &$recounted_product_id ): void {
		$this->legacy_proxy->register_function_mocks(
			array(
				'get_option'                         => fn() => 'yes',
				'wc_get_product_visibility_term_ids' => fn() => array( ProductStockStatus::OUT_OF_STOCK => 456 ),
				'_wc_recount_terms_by_product'       => static function ( int $product_id ) use ( &$recounted_product_id ): void {
					$recounted_product_id = $product_id;
				},
			)
		);
	}
}
