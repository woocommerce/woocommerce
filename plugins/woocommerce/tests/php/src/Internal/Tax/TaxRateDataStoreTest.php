<?php

declare( strict_types = 1 );

namespace Automattic\WooCommerce\Tests\Internal\Tax;

use Automattic\WooCommerce\Internal\Tax\TaxRateDataStore;

/**
 * Tests for TaxRateDataStore.
 */
class TaxRateDataStoreTest extends \WC_Unit_Test_Case {

	/**
	 * @var TaxRateDataStore
	 */
	private $sut;

	/**
	 * Set up subject under test.
	 */
	public function set_up() {
		$this->sut = wc_get_container()->get( TaxRateDataStore::class );
		parent::set_up();
	}

	/**
	 * @testdox get_rate_objects_for_ids() deduplicates mixed int/string IDs, returns a map keyed by int tax_rate_id, and serves subsequent calls from the request-level cache.
	 */
	public function test_get_rate_objects_for_ids(): void {
		// Arrange: two GB rates as seen on a cart with standard + reduced VAT.
		$standard_id = \WC_Tax::_insert_tax_rate(
			array(
				'tax_rate_country'  => 'GB',
				'tax_rate_state'    => '',
				'tax_rate'          => '20.0000',
				'tax_rate_name'     => 'Standard Rate',
				'tax_rate_priority' => '1',
				'tax_rate_compound' => '0',
				'tax_rate_shipping' => '1',
				'tax_rate_order'    => '1',
				'tax_rate_class'    => '',
			)
		);
		$reduced_id  = \WC_Tax::_insert_tax_rate(
			array(
				'tax_rate_country'  => 'GB',
				'tax_rate_state'    => '',
				'tax_rate'          => '5.0000',
				'tax_rate_name'     => 'Reduced Rate',
				'tax_rate_priority' => '1',
				'tax_rate_compound' => '0',
				'tax_rate_shipping' => '0',
				'tax_rate_order'    => '2',
				'tax_rate_class'    => 'reduced-rate',
			)
		);

		// Act: deduplication — pass standard_id as both int and string, plus a non-existent ID.
		$ids    = array( $reduced_id, $standard_id, (string) $standard_id, PHP_INT_MAX );
		$result = $this->sut->get_rate_objects_for_ids( $ids );

		// Assert: both rates present, each keyed by its own ID with correct values.
		$this->assertSame( array( $reduced_id, $standard_id ), array_keys( $result ) );
		$this->assertSame( array( '5.0000', '20.0000' ), array_column( $result, 'tax_rate' ) );

		// Verify a third call (full cache hit) returns identical result.
		$this->assertSame( $result, $this->sut->get_rate_objects_for_ids( $ids ) );
	}
}
