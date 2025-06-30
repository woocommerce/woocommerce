<?php

namespace Automattic\WooCommerce\Blueprint;

use Automattic\WooCommerce\Blueprint\UseWPFunctions;

/**
 * Class Logger
 */
class Logger {
	use UseWPFunctions;

	/**
	 * WooCommerce logger class instance.
	 *
	 * @var \WC_Logger_Interface
	 */
	private $logger;

	/**
	 * Constructor.
	 */
	public function __construct() {
		$this->logger = wc_get_logger();
	}

	/**
	 * Log a message as a debug log entry.
	 *
	 * @param string $message The message to log.
	 * @param string $level   The log level.
	 * @param array  $context The context of the log.
	 */
	public function log( string $message, string $level = \WC_Log_Levels::DEBUG, $context = array() ) {
		$this->logger->log(
			$level,
			$message,
			array_merge(
				array(
					'source'  => 'wc-blueprint',
					'user_id' => $this->wp_get_current_user_id(),
				),
				$context
			)
		);
	}

	/**
	 * Log the start of an export operation.
	 *
	 * @param array $exporters Array of exporters.
	 */
	public function start_export( array $exporters ) {
		$export_data = $this->get_export_data( $exporters );

		$this->log(
			sprintf( 'Starting export of %d steps', count( $export_data['steps'] ) ),
			\WC_Log_Levels::INFO,
			array(
				'steps'     => $export_data['steps'],
				'exporters' => $export_data['exporters'],
			)
		);
	}

	/**
	 * Log the completion of an export operation.
	 *
	 * @param array $exporters Array of exporters.
	 */
	public function complete_export( array $exporters ) {
		$export_data = $this->get_export_data( $exporters );

		$this->log(
			sprintf( 'Export of %d steps completed', count( $export_data['steps'] ) ),
			\WC_Log_Levels::INFO,
			array(
				'steps'     => $export_data['steps'],
				'exporters' => $export_data['exporters'],
			)
		);
	}

	/**
	 * Extract export step names and exporter classes from exporters.
	 *
	 * @param array $exporters Array of exporters.
	 * @return array Associative array with 'steps' and 'exporters' keys.
	 */
	private function get_export_data( array $exporters ) {
		$export_steps     = array();
		$exporter_classes = array();

		foreach ( $exporters as $exporter ) {
			$step_name          = method_exists( $exporter, 'get_alias' ) ? $exporter->get_alias() : $exporter->get_step_name();
			$export_steps[]     = $step_name;
			$exporter_classes[] = get_class( $exporter );
		}

		return array(
			'steps'     => $export_steps,
			'exporters' => $exporter_classes,
		);
	}

	/**
	 * Log an export step failure.
	 *
	 * @param string     $step_name The name of the step that failed.
	 * @param \Throwable $exception The exception that was thrown.
	 */
	public function export_step_failed( string $step_name, \Throwable $exception ) {
		$this->log(
			sprintf( 'Export "%s" step failed', $step_name ),
			\WC_Log_Levels::ERROR,
			array(
				'error' => $exception->getMessage(),
			)
		);
	}

	/**
	 * Log the start of an import step.
	 *
	 * @param string $step_name      The name of the step being imported.
	 * @param string $importer_class The class name of the importer.
	 */
	public function start_import( string $step_name, string $importer_class ) {
		$this->log(
			sprintf( 'Starting import "%s" step', $step_name ),
			\WC_Log_Levels::INFO,
			array(
				'importer' => $importer_class,
			)
		);
	}

	/**
	 * Log the successful completion of an import step.
	 *
	 * @param string              $step_name The name of the step that was imported.
	 * @param StepProcessorResult $result    The result of the import.
	 */
	public function complete_import( string $step_name, StepProcessorResult $result ) {
		$this->log(
			sprintf( 'Import "%s" step completed', $step_name ),
			\WC_Log_Levels::INFO,
			array(
				'messages' => $result->get_messages( 'info' ),
			)
		);
	}

	/**
	 * Log an import step failure.
	 *
	 * @param string              $step_name The name of the step that failed.
	 * @param StepProcessorResult $result    The result of the import.
	 */
	public function import_step_failed( string $step_name, StepProcessorResult $result ) {
		$this->log(
			sprintf( 'Import "%s" step failed', $step_name ),
			\WC_Log_Levels::ERROR,
			array(
				'messages' => $result->get_messages( 'error' ),
			)
		);
	}
}
