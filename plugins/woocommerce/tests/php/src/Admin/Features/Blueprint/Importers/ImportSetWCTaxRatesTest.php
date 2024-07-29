<?php

namespace php\src\Admin\Features\Blueprint\Importers;

use Automattic\WooCommerce\Admin\Features\Blueprint\Importers\ImportSetWCTaxRates;
use WC_Unit_Test_Case;

class ImportSetWCTaxRatesTest extends WC_Unit_Test_Case {
	public function test() {
		$fixture  = json_decode( file_get_contents( __DIR__ . '/../fixtures/woo-blueprint-taxrates.json' ) );
		$importer = new ImportSetWCTaxRates();
		$importer->process( $fixture->steps[0] );

		$this->assertTaxRate();
		$this->assertLocations();
	}

	private function assertTaxRate() {
		global $wpdb;
		$rate = $wpdb->get_row( "SELECT * FROM {$wpdb->prefix}woocommerce_tax_rates WHERE tax_rate_id = 1" );
		$this->assertEquals( 'US', $rate->tax_rate_country );
		$this->assertEquals( 'CA', $rate->tax_rate_state );
		$this->assertEquals( '9.5000', $rate->tax_rate );
		$this->assertEquals( 'Tax', $rate->tax_rate_name );
		$this->assertEquals( '1', $rate->tax_rate_priority );
		$this->assertEquals( '0', $rate->tax_rate_compound );
		$this->assertEquals( '1', $rate->tax_rate_shipping );
		$this->assertEquals( '0', $rate->tax_rate_order );
		$this->assertEquals( '', $rate->tax_rate_class );
	}

	private function assertLocations() {
		global $wpdb;
		$locations = $wpdb->get_results( "SELECT * FROM {$wpdb->prefix}woocommerce_tax_rate_locations WHERE tax_rate_id = 1" );
		$postcode  = $locations[0];
		$city      = $locations[1];

		$this->assertEquals( '90020', $postcode->location_code );
		$this->assertEquals( 'LOS ANGELES', $city->location_code );
	}
}
