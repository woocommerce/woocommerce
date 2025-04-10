<?php

namespace Automattic\WooCommerce\Blueprint;

use Automattic\WooCommerce\Blueprint\Exporters\StepExporter;
use Automattic\WooCommerce\Blueprint\Exporters\ExportInstallPluginSteps;
use Automattic\WooCommerce\Blueprint\Exporters\ExportInstallThemeSteps;
use Automattic\WooCommerce\Blueprint\Exporters\HasAlias;

/**
 * Class ExportSchema
 *
 * Handles the export schema functionality for WooCommerce.
 *
 * @package Automattic\WooCommerce\Blueprint
 */
class ExportSchema {
	use UseWPFunctions, UsePubSub;

	/**
	 * Step exporters.
	 *
	 * @var StepExporter[] Array of step exporters.
	 */
	protected array $exporters = array();

	/**
	 * ExportSchema constructor.
	 *
	 * @param StepExporter[] $exporters Array of step exporters.
	 */
	public function __construct( $exporters = array() ) {
		$this->exporters = $exporters;
	}

	/**
	 * Export the schema steps.
	 *
	 * @param string[] $steps Array of step names to export, optional.
	 * @param bool     $zip Whether to export as a ZIP file, optional.
	 *
	 * @return array The exported schema array.
	 */
	public function export( $steps = array(), $zip = false ) {
		$schema = array(
			'landingPage' => $this->wp_apply_filters( 'wooblueprint_export_landingpage', '/' ),
			'steps'       => array(),
		);

		$built_in_exporters = ( new BuiltInExporters() )->get_all();

		/**
		 * Filters the step exporters.
		 *
		 * Allows adding/removing custom step exporters.
		 *
		 * @param StepExporter[] $exporters Array of step exporters.
		 *
		 * @since 0.0.1
		 */
		$exporters = $this->wp_apply_filters( 'wooblueprint_exporters', array_merge( $this->exporters, $built_in_exporters ) );

		// Filter out any exporters that are not in the list of steps to export.
		if ( count( $steps ) ) {
			foreach ( $exporters as $key => $exporter ) {
				$name  = $exporter->get_step_name();
				$alias = $exporter instanceof HasAlias ? $exporter->get_alias() : $name;
				if ( ! in_array( $name, $steps, true ) && ! in_array( $alias, $steps, true ) ) {
					unset( $exporters[ $key ] );
				}
			}
		}

		if ( $zip ) {
			$exporters = array_map(
				function ( $exporter ) {
					if ( $exporter instanceof ExportInstallPluginSteps ) {
						$exporter->include_private_plugins( true );
					}
					return $exporter;
				},
				$exporters
			);
		}

		/**
		 * StepExporter.
		 *
		 * @var StepExporter $exporter
		 */
		foreach ( $exporters as $exporter ) {
			$this->publish('onBeforeExport', $exporter);
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

	public function onBeforeExport($step_name, $callback) {
		$this->subscribe('onBeforeExport', function($exporter) use ($step_name, $callback) {
			if ($step_name === $exporter->get_step_name()) {
				$callback( $exporter );
			}
		});
	}
}
