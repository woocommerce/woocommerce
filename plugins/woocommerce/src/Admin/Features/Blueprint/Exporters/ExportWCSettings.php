<?php

declare( strict_types = 1);

namespace Automattic\WooCommerce\Admin\Features\Blueprint\Exporters;

use Automattic\WooCommerce\Admin\Features\Blueprint\SettingOptions;
use Automattic\WooCommerce\Blueprint\Exporters\HasAlias;
use Automattic\WooCommerce\Blueprint\Exporters\StepExporter;
use Automattic\WooCommerce\Blueprint\Steps\SetSiteOptions;
use Automattic\WooCommerce\Blueprint\UseWPFunctions;

/**
 * Class ExportWCSettings
 *
 * This abstract class provides the functionality for exporting WooCommerce settings on a specific page.
 *
 * @package Automattic\WooCommerce\Admin\Features\Blueprint\Exporters
 */
abstract class ExportWCSettings implements StepExporter, HasAlias {
	use UseWPFunctions;

	/**
	 * Return a page I.D to export.
	 *
	 * @return string The page ID.
	 */
	abstract protected function get_page_id(): string;

	/**
	 * Export WooCommerce settings.
	 *
	 * @return SetSiteOptions
	 */
	public function export() {
		$setting_options = new SettingOptions();
		return new SetSiteOptions( $setting_options->get_page_options( $this->get_page_id() ) );
	}


	/**
	 * Get the name of the step.
	 *
	 * @return string
	 */
	public function get_step_name() {
		return 'setSiteOptions';
	}

	/**
	 * Get the alias for this exporter.
	 *
	 * @return string
	 */
	public function get_alias() {
		return 'setWCSettingsGeneral';
	}

	/**
	 * Return label used in the frontend.
	 *
	 * @return string
	 */
	public function get_label() {
		return __( 'General', 'woocommerce' );
	}

	/**
	 * Return description used in the frontend.
	 *
	 * @return string
	 */
	public function get_description() {
		return __( 'Includes all settings in WooCommerce | Settings | General.', 'woocommerce' );
	}

	/**
	 * Check if the current user has the required capabilities for this step.
	 *
	 * @return bool True if the user has the required capabilities. False otherwise.
	 */
	public function check_step_capabilities(): bool {
		return current_user_can( 'manage_woocommerce' );
	}
}
