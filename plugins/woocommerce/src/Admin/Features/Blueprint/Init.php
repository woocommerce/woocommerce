<?php

namespace Automattic\WooCommerce\Admin\Features\Blueprint;


use Automattic\WooCommerce\Admin\Features\Blueprint\Exporters\ExportWCCoreProfilerOptions;
use Automattic\WooCommerce\Admin\Features\Blueprint\Exporters\ExportWCPaymentGateways;
use Automattic\WooCommerce\Admin\Features\Blueprint\Exporters\ExportWCSettings;
use Automattic\WooCommerce\Admin\Features\Blueprint\Exporters\ExportWCShipping;
use Automattic\WooCommerce\Admin\Features\Blueprint\Exporters\ExportWCTaskOptions;
use Automattic\WooCommerce\Admin\Features\Blueprint\Exporters\ExportWCTaxRates;
use Automattic\WooCommerce\Admin\Features\Blueprint\Importers\SetWCPaymentGateways;
use Automattic\WooCommerce\Admin\Features\Blueprint\Importers\SetWCShipping;
use Automattic\WooCommerce\Admin\Features\Blueprint\Importers\SetWCTaxRates;
use Automattic\WooCommerce\Blueprint\Exporters\StepExporter;
use Automattic\WooCommerce\Blueprint\StepProcessor;

class Init {
	public function __construct() {
		add_action( 'rest_api_init', array( $this, 'init_rest_api' ) );
		add_filter('wooblueprint_export_landingpage', function() {
			return 'admin.php?page=wc-admin';
		});
		add_filter('wooblueprint_exporters', array($this, 'add_woo_exporters'));
		add_filter('wooblueprint_importers', array($this, 'add_woo_importers'));

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
		return array_merge( $exporters, array(
			new ExportWCCoreProfilerOptions(),
			new ExportWCSettings(),
			new ExportWCPaymentGateways(),
			new ExportWCShipping(),
			new ExportWCTaskOptions(),
			new ExportWCTaxRates(),
		) );
	}

	/**
	 * Add Woo Specific Importers.
	 *
	 * @param StepProcessor[] $importers
	 *
	 * @return array
	 */
	public function add_woo_importers(array $importers) {
		return array_merge( $importers, array(
			new SetWCPaymentGateways(),
			new SetWCShipping(),
			new SetWCTaxRates(),
		) );
	}
}
