<?php

namespace Automattic\WooCommerce\Blueprint\Importers;

use Automattic\WooCommerce\Blueprint\StepProcessor;
use Automattic\WooCommerce\Blueprint\StepProcessorResult;
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

		// Security check: Check if we can use prepared statements.
		$wpdb->query( $schema->sql->contents ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
		if ( $wpdb->last_error ) {
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

	/**
	 * Check if the current user has the required capabilities for this step.
	 *
	 * @param object $schema The schema to process.
	 *
	 * @return bool True if the user has the required capabilities. False otherwise.
	 */
	public function check_step_capabilities( $schema ): bool {
		if ( ! current_user_can( 'manage_options' ) ) {
			return false;
		}

		if ( ! current_user_can( 'edit_posts' ) ) {
			return false;
		}

		if ( ! current_user_can( 'edit_users' ) ) {
			return false;
		}

		return true;
	}
}
