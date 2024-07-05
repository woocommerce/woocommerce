<?php

namespace Automattic\WooCommerce\Blueprint;

use Automattic\WooCommerce\Blueprint\Exporters\ExportInstallPluginSteps;
use Automattic\WooCommerce\Blueprint\Exporters\StepExporter;
use Automattic\WooCommerce\Blueprint\Exporters\ExportInstallThemeSteps;
use Automattic\WooCommerce\Blueprint\Exporters\HasAlias;

class ExportSchema {
	use UseWPFunctions;

	protected array $default_exporter_classes = array(
		ExportInstallPluginSteps::class,
		ExportInstallThemeSteps::class,
	);

	protected array $exporters = array();

	/**
	 * @param StepExporter[] $exporters
	 */
	public function __construct( $exporters = array() ) {
		$this->exporters = $exporters;
	}

	public function add_exporter( StepExporter $exporter ) {
		if ( ! isset( $this->exporters[ $exporter->get_step_name() ] ) ) {
			$this->exporters[ $exporter->get_step_name() ] = array();
		}

		$this->exporters[ $exporter->get_step_name() ][] = $exporter;
	}

	public function get_exporter( $step ) {
		if ( isset( $this->exporters[ $step ] ) ) {
			return $this->exporters[ $step ];
		}
		return null;
	}

	public function export( $steps = array() ) {
		$schema = array(
			'landingPage' => $this->wp_apply_filters( 'wooblueprint_export_landingpage', '/' ),
			'steps'       => array(),
		);

		$exporters = $this->wp_apply_filters( 'wooblueprint_exporters', $this->exporters );

		$this->add_default_exporters();
		foreach ( $exporters as $exporter ) {
			$this->add_exporter( $exporter );
		}

		$exporters = array_merge( ...array_values( $this->exporters ) );

		// Filter out any exporters that are not in the list of steps to export.
		if ( count( $steps ) ) {
			foreach ( $exporters as $key => $exporter ) {
				$name = $exporter->get_step_name();
				$alias = $exporter instanceof HasAlias ? $exporter->get_alias() : $name;
				if ( ! in_array( $name, $steps ) && ! in_array( $alias, $steps ) ) {
					unset( $exporters[ $key ] );
				}
			}
		}

		/**
		 * @var StepExporter $exporter
		 */
		foreach ( $exporters as $exporter ) {
			$step = $exporter->export();
			if (is_array($step)) {
				foreach ($step as $_step) {
					$schema['steps'][] = $_step->get_json_array();
				}
			} else {
				$schema['steps'][] = $step->get_json_array();
			}
		}

		return $schema;
	}

	protected function add_default_exporters() {
		foreach ( $this->default_exporter_classes as $exporter ) {
			$this->add_exporter( new $exporter() );
		}
	}
}
