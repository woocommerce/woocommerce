<?php

namespace Automattic\WooCommerce\Blueprint;

use Opis\JsonSchema\Errors\ErrorFormatter;
use Opis\JsonSchema\Validator;
use Automattic\WooCommerce\Blueprint\Logger;

/**
 * Class ImportStep
 *
 * Import a single step from a JSON definition.
 *
 * @package Automattic\WooCommerce\Blueprint
 */
class ImportStep {
	use UseWPFunctions;

	/**
	 * Step definition.
	 *
	 * @var object The step definition.
	 */
	private object $step_definition;

	/**
	 * Validator object.
	 *
	 * @var Validator The JSON schema validator instance.
	 */
	private Validator $validator;

	/**
	 * Built-in step processors.
	 *
	 * @var BuiltInStepProcessors The built-in step processors instance.
	 */
	private BuiltInStepProcessors $builtin_step_processors;

	/**
	 * Importers.
	 *
	 * @var array|mixed The importers.
	 */
	private array $importers;

	/**
	 * Indexed importers.
	 *
	 * @var array The indexed importers by step name.
	 */
	private array $indexed_importers;


	/**
	 * ImportStep constructor.
	 *
	 * @param object         $step_definition The step definition.
	 * @param Validator|null $validator The validator instance, optional.
	 */
	public function __construct( $step_definition, ?Validator $validator = null ) {
		$this->step_definition = $step_definition;
		if ( null === $validator ) {
			$validator = new Validator();
		}
		$this->validator         = $validator;
		$this->importers         = $this->wp_apply_filters( 'wooblueprint_importers', ( ( new BuiltInStepProcessors() )->get_all() ) );
		$this->indexed_importers = Util::index_array(
			$this->importers,
			function ( $key, $importer ) {
				return $importer->get_step_class()::get_step_name();
			}
		);
	}

	/**
	 * Import the schema steps.
	 *
	 * @return StepProcessorResult
	 */
	public function import() {
		$result = StepProcessorResult::success( $this->step_definition->step );

		if ( ! $this->can_import( $result ) ) {
			return $result;
		}

		$importer = $this->indexed_importers[ $this->step_definition->step ];
		$logger   = new Logger();
		$logger->start_import( $this->step_definition->step, get_class( $importer ) );

		$importer_result = $importer->process( $this->step_definition );

		if ( $importer_result->is_success() ) {
			$logger->complete_import( $this->step_definition->step, $importer_result );
		} else {
			$logger->import_step_failed( $this->step_definition->step, $importer_result );
		}

		$result->merge_messages( $importer_result );

		return $result;
	}

	/**
	 * Check if the step can be imported.
	 *
	 * @param StepProcessorResult $result The result object to add messages to.
	 *
	 * @return bool True if the step can be imported, false otherwise.
	 */
	protected function can_import( &$result ) {
		// Check if the importer exists.
		if ( ! isset( $this->indexed_importers[ $this->step_definition->step ] ) ) {
			$result->add_error( 'Unable to find an importer' );
			return false;
		}

		$importer = $this->indexed_importers[ $this->step_definition->step ];
		// Validate importer is a step processor before processing.
		if ( ! $importer instanceof StepProcessor ) {
			$result->add_error( 'Incorrect importer type' );
			return false;
		}

		// Validate steps schemas before processing.
		if ( ! $this->validate_step_schemas( $importer, $result ) ) {
			$result->add_error( 'Schema validation failed for step' );
			return false;
		}

		// Validate step capabilities before processing.
		if ( ! $importer->check_step_capabilities( $this->step_definition ) ) {
			$result->add_error( 'User does not have the required capabilities to run step' );
			return false;
		}

		return true;
	}

	/**
	 * Validate the step schemas.
	 *
	 * @param StepProcessor       $importer The importer.
	 * @param StepProcessorResult $result The result object to add messages to.
	 *
	 * @return bool True if the step schemas are valid, false otherwise.
	 */
	protected function validate_step_schemas( StepProcessor $importer, StepProcessorResult $result ) {
		$step_schema = call_user_func( array( $importer->get_step_class(), 'get_schema' ) );

		$validate = $this->validator->validate( $this->step_definition, wp_json_encode( $step_schema ) );

		if ( ! $validate->isValid() ) {
			$result->add_error( "Schema validation failed for step {$this->step_definition->step}" );
			$errors           = ( new ErrorFormatter() )->format( $validate->error() );
			$formatted_errors = array();
			foreach ( $errors as $value ) {
				$formatted_errors[] = implode( "\n", $value );
			}

			$result->add_error( implode( "\n", $formatted_errors ) );

			return false;
		}
		return true;
	}
}
