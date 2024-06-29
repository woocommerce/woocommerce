<?php

namespace Automattic\WooCommerce\Blueprint;

use Automattic\WooCommerce\Blueprint\Exporters\ExportCoreProfilerSettings;
use Automattic\WooCommerce\Blueprint\Exporters\ExportRedirectToAfter;
use Automattic\WooCommerce\Blueprint\Exporters\ExportPaymentGateways;
use Automattic\WooCommerce\Blueprint\Exporters\ExportPluginList;
use Automattic\WooCommerce\Blueprint\Exporters\ExportSettings;
use Automattic\WooCommerce\Blueprint\Exporters\ExportShipping;
use Automattic\WooCommerce\Blueprint\Exporters\ExportsStep;
use Automattic\WooCommerce\Blueprint\Exporters\ExportTaskOptions;
use Automattic\WooCommerce\Blueprint\Exporters\ExportTaxRates;
use Automattic\WooCommerce\Blueprint\Exporters\ExportThemeList;
use Automattic\WooCommerce\Blueprint\Exporters\HasAlias;

class ExportSchema {
	use UseHooks;
	protected array $default_exporter_classes = array(
        ExportPluginList::class,
		ExportThemeList::class,
	);

	protected array $exporters = array();

	public function add_exporter(ExportsStep $exporter) {
		if (!isset($this->exporters[$exporter->get_step_name()])) {
			$this->exporters[$exporter->get_step_name()] = array();
		}

	    $this->exporters[$exporter->get_step_name()][] = $exporter;
	}

	public function get_exporter($step) {
		if (isset($this->exporters[$step])) {
			return $this->exporters[$step];
		}
		return null;
 	}

	/**
	 * @param ExportsStep[] $exporters
	 */
	public function __construct($exporters = array()) {
		$this->exporters = $exporters;
	}

	public function export($steps = array()) {
		$schema = array(
			'landingPage' => $this->apply_filters('wooblueprint_export_landingpage', '/'),
			'steps' => array(),
		);

		$exporters = $this->apply_filters('wooblueprint_exporters', $this->exporters);
		$this->add_default_exporters();
		foreach ($exporters as $exporter) {
			$this->add_exporter($exporter);
		}


		$exporters = array_merge(...array_values($this->exporters));

		if (count($steps)) {
			foreach ($exporters as $key=>$exporter) {
				$name = $exporter->get_step_name();
				if ($exporter instanceof HasAlias) {
					$alias = $exporter->get_alias();
				} else {
					$alias = $name;
				}
				if (!in_array($name, $steps) && !in_array($alias, $steps)) {
					unset($exporters[$key]);
				}
			}
		}

		foreach ($exporters as $exporter) {
			$step = $exporter->export_step();

			if (isset($step['steps']) && is_array($step['steps'])) {
				foreach ($step['steps'] as $substep) {
					$schema['steps'][] = $substep;
				}
			} else {
				$schema['steps'][] = $step;
			}
		}

		return $schema;
	}

	protected function add_default_exporters() {
		foreach ($this->default_exporter_classes as $exporter) {
			$this->add_exporter(new $exporter);
		}
	}
}
