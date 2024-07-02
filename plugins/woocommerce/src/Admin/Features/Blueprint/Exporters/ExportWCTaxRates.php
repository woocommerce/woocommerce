<?php

namespace Automattic\WooCommerce\Admin\Features\Blueprint\Exporters;

use Automattic\WooCommerce\Admin\Features\Blueprint\Steps\SetWCTaxRates;
use Automattic\WooCommerce\Blueprint\Exporters\StepExporter;

class ExportWCTaxRates implements StepExporter {

	public function export() {
		global $wpdb;

		// @todo check to see if we already have a DAO for taxes.
		$rates = $wpdb->get_results(
			"
			SELECT *
			FROM {$wpdb->prefix}woocommerce_tax_rates as tax_rates
		",
			ARRAY_A
		);

		$locations = $wpdb->get_results(
			"
			SELECT *
			FROM {$wpdb->prefix}woocommerce_tax_rate_locations as locations
		",
			ARRAY_A
		);

		$step = new SetWCTaxRates( $rates, $locations);
		$step->set_meta_values( array(
			'plugin' => 'woocommerce',
		) );

		return $step;
	}

	public function get_step_name() {
		return 'setWCTaxRates';
	}
}
