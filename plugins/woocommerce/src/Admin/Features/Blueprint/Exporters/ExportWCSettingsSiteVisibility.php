<?php

declare( strict_types = 1);

namespace Automattic\WooCommerce\Admin\Features\Blueprint\Exporters;

use Automattic\WooCommerce\Admin\Features\Blueprint\SettingOptions;
use Automattic\WooCommerce\Blueprint\Exporters\HasAlias;
use Automattic\WooCommerce\Blueprint\Exporters\StepExporter;
use Automattic\WooCommerce\Blueprint\Steps\SetSiteOptions;
use Automattic\WooCommerce\Blueprint\UseWPFunctions;

/**
 * Class ExportWCSettingsProducts
 *
 * This class exports WooCommerce settings and implements the StepExporter and HasAlias interfaces.
 *
 * @package Automattic\WooCommerce\Admin\Features\Blueprint\Exporters
 */
class ExportWCSettingsSiteVisibility implements StepExporter, HasAlias {
	use UseWPFunctions;

	/**
	 * Export Site Visibility settings.
	 *
	 * @return SetSiteOptions
	 */
	public function export() {
		return new SetSiteOptions(
			array(
				'woocommerce_coming_soon'      => $this->wp_get_option( 'woocommerce_coming_soon' ),
				'woocommerce_store_pages_only' => $this->wp_get_option( 'woocommerce_store_pages_only' ),
			)
		);
	}

	/**
	 * Get the alias for this exporter.
	 *
	 * @return string
	 */
	public function get_alias() {
		return 'setWCSettingsSiteVisibility';
	}

	/**
	 * Return label used in the frontend.
	 *
	 * @return string
	 */
	public function get_label() {
		return __( 'Site Visibility', 'woocommerce' );
	}

	/**
	 * Return description used in the frontend.
	 *
	 * @return string
	 */
	public function get_description() {
		return __( 'It includes all settings in WooCommerce | Settings | Visibility.', 'woocommerce' );
	}

	/**
	 * Get the name of the step.
	 *
	 * @return string
	 */
	public function get_step_name() {
		return 'setSiteOptions';
	}
}
