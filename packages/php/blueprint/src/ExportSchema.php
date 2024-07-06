<?php

namespace Automattic\WooCommerce\Blueprint;

use Automattic\WooCommerce\Blueprint\Exporters\ExportInstallPluginSteps;
use Automattic\WooCommerce\Blueprint\Exporters\StepExporter;
use Automattic\WooCommerce\Blueprint\Exporters\ExportInstallThemeSteps;
use Automattic\WooCommerce\Blueprint\Exporters\HasAlias;

class ExportSchema {
	use UseWPFunctions;

	protected array $exporters = array();

	/**
	 * @param StepExporter[] $exporters
	 */
	public function __construct( $exporters = array() ) {
		$this->exporters = $exporters;
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

		$built_in_exporters = ( new BuiltInExporters() )->get_all();
		$exporters          = $this->wp_apply_filters( 'wooblueprint_exporters', $built_in_exporters );

		// Filter out any exporters that are not in the list of steps to export.
		if ( count( $steps ) ) {
			foreach ( $exporters as $key => $exporter ) {
				$name  = $exporter->get_step_name();
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
			if ( is_array( $step ) ) {
				foreach ( $step as $_step ) {
					$schema['steps'][] = $_step->get_json_array();
				}
			} else {
				$schema['steps'][] = $step->get_json_array();
			}
		}

		return $schema;
	}
}
