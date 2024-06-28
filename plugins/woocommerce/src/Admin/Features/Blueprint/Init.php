<?php

namespace Automattic\WooCommerce\Admin\Features\Blueprint;


use Automattic\WooCommerce\Blueprint\Exporters\ExportsStep;

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
		$controller = new RestApi();
		$controller->register_routes();
	}

	/**
	 * @param ExportsStep[] $exporters
	 *
	 * @return ExportsStep[]
	 */
	public function add_woo_exporters(array $exporters) {
		$classes = array(
			__NAMESPACE__ . '\Exporters\ExportCoreProfilerSettings',
			__NAMESPACE__ . '\Exporters\ExportPaymentGateways',
			__NAMESPACE__ . '\Exporters\ExportShipping',
			__NAMESPACE__ . '\Exporters\ExportTaskOptions',
			__NAMESPACE__ . '\Exporters\ExportTaxRates',
		);

		foreach ($classes as $class) {
			$exporters[] = new $class();
		}

		return $exporters;
	}
}
