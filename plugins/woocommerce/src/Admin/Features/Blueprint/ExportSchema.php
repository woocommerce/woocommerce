<?php

namespace Automattic\WooCommerce\Admin\Features\Blueprint;

use Automattic\WooCommerce\Admin\Features\Blueprint\Exporters\ExportCoreProfilerSettings;
use Automattic\WooCommerce\Admin\Features\Blueprint\Exporters\ExportRedirectToAfter;
use Automattic\WooCommerce\Admin\Features\Blueprint\Exporters\ExportPaymentGateways;
use Automattic\WooCommerce\Admin\Features\Blueprint\Exporters\ExportPluginList;
use Automattic\WooCommerce\Admin\Features\Blueprint\Exporters\ExportSettings;
use Automattic\WooCommerce\Admin\Features\Blueprint\Exporters\ExportShipping;
use Automattic\WooCommerce\Admin\Features\Blueprint\Exporters\ExportsStepSchema;
use Automattic\WooCommerce\Admin\Features\Blueprint\Exporters\ExportTaskOptions;
use Automattic\WooCommerce\Admin\Features\Blueprint\Exporters\ExportTaxRates;
use Automattic\WooCommerce\Admin\Features\Blueprint\Exporters\ExportThemeList;

class ExportSchema {
	use UseHooks;
	protected array $default_exporter_classes = array(
        ExportPluginList::class,
		ExportThemeList::class,
		ExportCoreProfilerSettings::class,
		ExportTaxRates::class,
		ExportShipping::class,
		ExportSettings::class,
		ExportPaymentGateways::class,
		ExportRedirectToAfter::class,
		ExportTaskOptions::class,
	);

	protected array $exporters = array();

	public function add_exporter(ExportsStepSchema $exporter) {
	    $this->exporters[$exporter->get_step_name()] = $exporter;
	}

	public function get_exporter($step) {
		if (isset($this->exporters[$step])) {
			return $this->exporters[$step];
		}
		return null;
 	}

	public function __construct($exporters = array()) {
	    if (count($exporters) === 0) {
			$this->add_default_exporters();
	    }
	}

	public function export($steps = array()) {
		$schema = array(
			'steps' => array(),
		);

		$this->exporters = $this->apply_filters('wooblueprint_exporters', $this->exporters);

		if (count($steps)) {
			$exporters = array_intersect_key($this->exporters, array_flip($steps));
		} else {
			$exporters = $this->exporters;
		}

		foreach ($exporters as $exporter) {
			$schema['steps'][] = $exporter->export_step_schema();
		}
		return $schema;
	}

	protected function add_default_exporters() {
		foreach ($this->default_exporter_classes as $exporter) {
			$this->add_exporter(new $exporter);
		}
	}
}
