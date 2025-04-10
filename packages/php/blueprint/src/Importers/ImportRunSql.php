<?php

namespace Automattic\WooCommerce\Blueprint\Importers;

use Automattic\WooCommerce\Blueprint\StepProcessor;
use Automattic\WooCommerce\Blueprint\StepProcessorResult;
use Automattic\WooCommerce\Blueprint\Steps\ActivatePlugin;
use Automattic\WooCommerce\Blueprint\Steps\ActivateTheme;
use Automattic\WooCommerce\Blueprint\Steps\RunSql;
use Automattic\WooCommerce\Blueprint\UsePluginHelpers;
use Automattic\WooCommerce\Blueprint\UseWPFunctions;

/**
 * Class ImportRunSql
 *
 * @package Automattic\WooCommerce\Blueprint\Importers
 */
class ImportRunSql implements StepProcessor {
	use UsePluginHelpers;
	use UseWPFunctions;

	/**
	 * Process the step.
	 *
	 * @param object $schema The schema for the step.
	 *
	 * @return StepProcessorResult
	 */
	public function process( $schema ): StepProcessorResult {
		global $wpdb;
		$result = StepProcessorResult::success( RunSql::get_step_name() );

		$wpdb->query( $schema->sql->contents );
		if ($wpdb->last_error) {
			$result->add_error( "Error executing SQL: {$wpdb->last_error}" );
		} else {
			$result->add_debug( "Executed SQL ({$schema->sql->name}): {$schema->sql->contents}" );
		}

		return $result;
	}

	/**
	 * Returns the class name of the step this processor handles.
	 *
	 * @return string The class name of the step this processor handles.
	 */
	public function get_step_class(): string {
		return RunSql::class;
	}
}
