<?php

declare( strict_types = 1);

namespace Automattic\WooCommerce\Admin\Features\Blueprint\Exporters;

use Automattic\WooCommerce\Blueprint\UseWPFunctions;
use Automattic\WooCommerce\Blueprint\Steps\RunSql;
use Automattic\WooCommerce\Blueprint\Util;
use Automattic\WooCommerce\Admin\Features\Blueprint\SettingOptions;

/**
 * Class ExportWCSettingsTax
 *
 * This class exports WooCommerce settings on the Tax page.
 *
 * @package Automattic\WooCommerce\Admin\Features\Blueprint\Exporters
 */
class ExportWCSettingsTax extends ExportWCSettings {
	use UseWPFunctions;

	/**
	 * Constructor.
	 *
	 * @param SettingOptions|null $setting_options The setting options class.
	 */
	public function __construct( ?SettingOptions $setting_options = null ) { // phpcs:ignore Generic.CodeAnalysis.UselessOverridingMethod.Found
		parent::__construct( $setting_options );
	}

	/**
	 * Get the alias for this exporter.
	 *
	 * @return string
	 */
	public function get_alias() {
		return 'setWCSettingsTax';
	}


	/**
	 * Export WooCommerce tax rates.
	 *
	 * @return array array of steps
	 */
	public function export(): array {
		$basic_tax_settings = parent::export();

		return array(
			$basic_tax_settings,
			...$this->generateTaxRateSteps( 'wc_tax_rate_classes' ),
			...$this->generateTaxRateSteps( 'woocommerce_tax_rates' ),
			...$this->generateTaxRateSteps( 'woocommerce_tax_rate_locations' ),
		);
	}


	/**
	 * Return label used in the frontend.
	 *
	 * @return string
	 */
	public function get_label() {
		return __( 'Tax', 'woocommerce' );
	}

	/**
	 * Return description used in the frontend.
	 *
	 * @return string
	 */
	public function get_description() {
		return __( 'Includes all settings in WooCommerce | Settings | Tax.', 'woocommerce' );
	}

	/**
	 * Get the page ID for the settings page.
	 *
	 * @return string
	 */
	protected function get_page_id(): string {
		return 'tax';
	}

	/**
	 * Generate SQL steps for exporting data.
	 *
	 * @param string $table Table identifier.
	 * @return array Array of RunSql steps.
	 */
	private function generateTaxRateSteps( string $table ): array {
		global $wpdb;
		$table = $wpdb->prefix . $table;
		return array_map(
			fn( $record ) => new RunSql( Util::array_to_insert_sql( $record, $table, 'replace into' ) ),
			$wpdb->get_results( $wpdb->prepare( 'SELECT * FROM %i', $table ), ARRAY_A ),
		);
	}
}
