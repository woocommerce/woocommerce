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
	 * @testdox get_rate_objects_for_ids() deduplicates mixed int/string IDs and returns a map keyed by int tax_rate_id.
	 */
	public function test_get_rate_objects_for_ids(): void {
		// Arrange.
		$tax_rate    = array(
			'tax_rate_country'  => 'DE',
			'tax_rate_state'    => '',
			'tax_rate'          => '19.0000',
			'tax_rate_name'     => 'VAT',
			'tax_rate_priority' => '1',
			'tax_rate_compound' => '1',
			'tax_rate_shipping' => '1',
			'tax_rate_order'    => '1',
			'tax_rate_class'    => '',
		);
		$tax_rate_id = \WC_Tax::_insert_tax_rate( $tax_rate );

		// Act.
		$result = $this->sut->get_rate_objects_for_ids( array( $tax_rate_id, (string) $tax_rate_id, PHP_INT_MAX ) );

		// Assert.
		$this->assertCount( 1, $result );
		$this->assertSame( array( $tax_rate_id ), array_keys( $result ) );
		$this->assertSame( 'VAT', $result[ $tax_rate_id ]->tax_rate_name );
	}
}
