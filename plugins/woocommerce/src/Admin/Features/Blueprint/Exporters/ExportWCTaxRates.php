<?php

declare( strict_types = 1);

namespace Automattic\WooCommerce\Admin\Features\Blueprint\Exporters;

use Automattic\WooCommerce\Admin\Features\Blueprint\Steps\SetWCTaxRates;
use Automattic\WooCommerce\Blueprint\Exporters\StepExporter;

/**
 * Class ExportWCTaxRates
 *
 * This class exports WooCommerce tax rates and implements the StepExporter interface.
 *
 * @package Automattic\WooCommerce\Admin\Features\Blueprint\Exporters
 */
class ExportWCTaxRates implements StepExporter {

	/**
	 * Export WooCommerce tax rates.
	 *
	 * @return SetWCTaxRates
	 */
	public function export() {
		global $wpdb;

		// Fetch tax rates from the database.
		$rates = $wpdb->get_results(
			"
            SELECT *
            FROM {$wpdb->prefix}woocommerce_tax_rates as tax_rates
            ",
			ARRAY_A
		);

		// Fetch tax rate locations from the database.
		$locations = $wpdb->get_results(
			"
            SELECT *
            FROM {$wpdb->prefix}woocommerce_tax_rate_locations as locations
            ",
			ARRAY_A
		);

		// Create a new SetWCTaxRates step with the fetched data.
		$step = new SetWCTaxRates( $rates, $locations );
		$step->set_meta_values(
			array(
				'plugin' => 'woocommerce',
			)
		);

		return $step;
	}

	/**
	 * Get the name of the step.
	 *
	 * @return string
	 */
	public function get_step_name() {
		return 'setWCTaxRates';
	}
}
