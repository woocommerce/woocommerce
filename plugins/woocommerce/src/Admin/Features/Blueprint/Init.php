<?php

namespace Automattic\WooCommerce\Admin\Features\Blueprint;


use Automattic\WooCommerce\Admin\Features\Blueprint\Exporters\ExportWCCoreProfilerOptions;
use Automattic\WooCommerce\Admin\Features\Blueprint\Exporters\ExportWCPaymentGateways;
use Automattic\WooCommerce\Admin\Features\Blueprint\Exporters\ExportWCSettings;
use Automattic\WooCommerce\Admin\Features\Blueprint\Exporters\ExportWCShipping;
use Automattic\WooCommerce\Admin\Features\Blueprint\Exporters\ExportWCTaskOptions;
use Automattic\WooCommerce\Admin\Features\Blueprint\Exporters\ExportWCTaxRates;
use Automattic\WooCommerce\Blueprint\Exporters\StepExporter;

class Init {
	public function __construct() {
		add_action( 'rest_api_init', array( $this, 'init_rest_api' ) );
		add_filter('wooblueprint_export_landingpage', function() {
			return 'admin.php?page=wc-admin';
		});
		add_filter('wooblueprint_exporters', array($this, 'add_woo_exporters'));
	}

	/**
	 * Register REST API routes.
	 *
	 * @return void
	 */
	public function init_rest_api() {
		(new RestApi())->register_routes();
	}

	/**
	 * Add Woo Specific Exporters.
	 *
	 * @param StepExporter[] $exporters
	 *
	 * @return StepExporter[]
	 */
	public function add_woo_exporters(array $exporters) {
		$classes = array(
			ExportWCCoreProfilerOptions::class,
			ExportWCSettings::class,
			ExportWCPaymentGateways::class,
			ExportWCShipping::class,
			ExportWCTaskOptions::class,
			ExportWCTaxRates::class,
		);

		foreach ($classes as $class) {
			$exporters[] = new $class();
		}

		return $exporters;
	}
}
