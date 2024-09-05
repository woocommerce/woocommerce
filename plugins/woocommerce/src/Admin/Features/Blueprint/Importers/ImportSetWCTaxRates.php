<?php

declare( strict_types = 1);

namespace Automattic\WooCommerce\Admin\Features\Blueprint\Importers;

use Automattic\WooCommerce\Admin\Features\Blueprint\Steps\SetWCTaxRates;
use Automattic\WooCommerce\Blueprint\StepProcessor;
use Automattic\WooCommerce\Blueprint\StepProcessorResult;
use WC_Tax;

/**
 * Class ImportSetWCTaxRates
 *
 * This class imports WooCommerce tax rates and implements the StepProcessor interface.
 *
 * @package Automattic\WooCommerce\Admin\Features\Blueprint\Importers
 */
class ImportSetWCTaxRates implements StepProcessor {
	/**
	 * The result of the step processing.
	 *
	 * @var StepProcessorResult $result The result of the step processing.
	 */
	private StepProcessorResult $result;

	/**
	 * Process the import of WooCommerce tax rates.
	 *
	 * @param object $schema The schema object containing import details.
	 * @return StepProcessorResult
	 */
	public function process( $schema ): StepProcessorResult {
		$this->result = StepProcessorResult::success( SetWCTaxRates::get_step_name() );

		foreach ( $schema->values->rates as $rate ) {
			$this->add_rate( $rate );
		}

		foreach ( $schema->values->locations as $location ) {
			$this->add_location( $location );
		}

		return $this->result;
	}

	/**
	 * Check if a tax rate exists in the database.
	 *
	 * @param int $id The tax rate ID.
	 * @global \wpdb $wpdb WordPress database abstraction object.
	 * @return array|null The tax rate row if found, null otherwise.
	 */
	protected function exist( $id ) {
		global $wpdb;
		return $wpdb->get_row(
			$wpdb->prepare(
				"
                SELECT *
                FROM {$wpdb->prefix}woocommerce_tax_rates
                WHERE tax_rate_id = %d
                ",
				$id
			),
			ARRAY_A
		);
	}

	/**
	 * Add a tax rate to the database.
	 *
	 * @param object $rate The tax rate object.
	 * @return int|false The tax rate ID if successfully added, false otherwise.
	 */
	protected function add_rate( $rate ) {
		$tax_rate = (array) $rate;

		if ( $this->exist( $tax_rate['tax_rate_id'] ) ) {
			$this->result->add_info( "Tax rate with I.D {$tax_rate['tax_rate_id']} already exists. Skipped creating it." );
			return false;
		}

		$tax_rate_id = WC_Tax::_insert_tax_rate( $tax_rate );

		if ( isset( $rate->postcode ) ) {
			$postcode = array_map( 'wc_clean', explode( ';', $rate->postcode ) );
			$postcode = array_map( 'wc_normalize_postcode', $postcode );
			WC_Tax::_update_tax_rate_postcodes( $tax_rate_id, $postcode );
		}
		if ( isset( $rate->city ) ) {
			$cities = explode( ';', $rate->city );
			WC_Tax::_update_tax_rate_cities( $tax_rate_id, array_map( 'wc_clean', array_map( 'wp_unslash', $cities ) ) );
		}

		return $tax_rate_id;
	}

	/**
	 * Add a tax rate location to the database.
	 *
	 * @param object $location The location object.
	 * @global \wpdb $wpdb WordPress database abstraction object.
	 */
	public function add_location( $location ) {
		global $wpdb;
		$location = (array) $location;
		$columns  = implode( ',', array_keys( $location ) );
		$format   = implode( ',', array( '%d', '%s', '%d', '%s' ) );
		$table    = $wpdb->prefix . 'woocommerce_tax_rate_locations';
		// phpcs:ignore
		$sql      = $wpdb->prepare( "REPLACE INTO $table ($columns) VALUES ($format)", $location );
		// phpcs:ignore
		$wpdb->query( $sql );
	}

	/**
	 * Get the class name for the step.
	 *
	 * @return string
	 */
	public function get_step_class(): string {
		return SetWCTaxRates::class;
	}
}
