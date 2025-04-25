<?php

namespace Automattic\WooCommerce\Blueprint;

use Automattic\WooCommerce\Blueprint\Exporters\StepExporter;
use Automattic\WooCommerce\Blueprint\Exporters\HasAlias;
use Automattic\WooCommerce\Blueprint\Logger;
use Automattic\WooCommerce\Blueprint\Steps\Step;
use WP_Error;

/**
 * Class ExportSchema
 *
 * Handles the export schema functionality for WooCommerce.
 *
 * @package Automattic\WooCommerce\Blueprint
 */
class ExportSchema {
	use UseWPFunctions;
	use UsePubSub;

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
	 *
	 * @return array|WP_Error The exported schema array or a WP_Error if the export fails.
	 */
	public function export( $steps = array() ) {
		$loading_page_path = $this->wp_apply_filters( 'wooblueprint_export_landingpage', '/' );
		/**
		 * Validate that the landing page path is a valid relative local URL path.
		 *
		 * Accepts:
		 * - /
		 * - /path/to/page
		 *
		 * Rejects:
		 * - http://example.com/path/to/page
		 * - invalid-path
		 */
		if ( ! preg_match( '#^/$|^/[^/].*#', $loading_page_path ) ) {
			return new WP_Error( 'wooblueprint_invalid_landing_page_path', 'Invalid loading page path.' );
		}

		$schema = array(
			'landingPage' => $loading_page_path,
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
		// Validate that the exporters are instances of StepExporter.
		$exporters = array_filter(
			$exporters,
			function ( $exporter ) {
				return $exporter instanceof StepExporter;
			}
		);

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

		// Make sure the user has the required capabilities to export the steps.
		foreach ( $exporters as $exporter ) {
			if ( ! $exporter->check_step_capabilities() ) {
				return new WP_Error( 'wooblueprint_insufficient_permissions', 'Insufficient permissions to export for step: ' . $exporter->get_step_name() );
			}
		}

		$logger = new Logger();
		$logger->start_export( $exporters );

		foreach ( $exporters as $exporter ) {
			try {
				$this->publish( 'onBeforeExport', $exporter );
				$step = $exporter->export();
				$this->add_result_to_schema( $schema, $step );

			} catch ( \Throwable $e ) {
				$step_name = $exporter instanceof HasAlias ? $exporter->get_alias() : $exporter->get_step_name();
				$logger->export_step_failed( $step_name, $e );
				return new WP_Error( 'wooblueprint_export_step_failed', 'Export step failed: ' . $e->getMessage() );
			}
		}

		$logger->complete_export( $exporters );

		return $schema;
	}

	/**
	 * Subscribe to the onBeforeExport event.
	 *
	 * @param string   $step_name The step name to subscribe to.
	 * @param callable $callback  The callback to execute.
	 */
	public function on_before_export( $step_name, $callback ) {
		$this->subscribe(
			'onBeforeExport',
			function ( $exporter ) use ( $step_name, $callback ) {
				if ( $step_name === $exporter->get_step_name() ) {
					$callback( $exporter );
				}
			}
		);
	}

	/**
	 * Add export result to the schema array.
	 *
	 * @param array      $schema Schema array to add steps to.
	 * @param array|Step $step   Step or array of steps to add.
	 */
	private function add_result_to_schema( array &$schema, $step ): void {
		if ( is_array( $step ) ) {
			foreach ( $step as $_step ) {
				$schema['steps'][] = $_step->get_json_array();
			}
			return;
		}

		$schema['steps'][] = $step->get_json_array();
	}
}
