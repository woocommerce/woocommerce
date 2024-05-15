<?php

namespace Automattic\WooCommerce\Admin\Features\Blueprint\StepProcessors;

use Automattic\WooCommerce\Admin\Features\Blueprint\StepProcessor;
use WC_Tax;

class ConfigureTaxProcessor implements StepProcessor {
	private $schema;
	public function __construct( $schema ) {
		$this->schema = $schema;
	}

	public function process() {
		foreach($this->schema->rates as $rate) {
			$tax_rate = array_intersect_key(
				(array) $rate,
				array(
					'tax_rate_country'  => 1,
					'tax_rate_state'    => 1,
					'tax_rate'          => 1,
					'tax_rate_name'     => 1,
					'tax_rate_priority' => 1,
					'tax_rate_compound' => 1,
					'tax_rate_shipping' => 1,
					'tax_rate_order'    => 1,
					'tax_rate_class'    => 1
				)
			);

			$tax_rate_id = WC_Tax::_insert_tax_rate( $tax_rate );

			if ( isset( $rate->postcode ) ) {
				$postcode = array_map( 'wc_clean', explode(';', $rate->postcode) );
				$postcode = array_map( 'wc_normalize_postcode', $postcode );
				WC_Tax::_update_tax_rate_postcodes( $tax_rate_id, $postcode );
			}
			if ( isset( $rate->city ) ) {
				$cities = explode(';', $rate->city);
				WC_Tax::_update_tax_rate_cities( $tax_rate_id, array_map( 'wc_clean', array_map( 'wp_unslash', $cities ) ) );
			}
		}
	}
}
