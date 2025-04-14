<?php

namespace Automattic\WooCommerce\Blueprint;

use Opis\JsonSchema\Errors\ErrorFormatter;
use Opis\JsonSchema\Validator;

/**
 * Class ImportStep
 *
 * Import a signle step from a JSON definition.
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
		$result = StepProcessorResult::success( 'ImportStep' );

		if ( ! isset( $this->indexed_importers[ $this->step_definition->step ] ) ) {
			$result->add_warn( "Unable to find an importer for {$this->step_definition->step}" );
			return $result;
		}

		$importer = $this->indexed_importers[ $this->step_definition->step ];

		// validate steps before processing.
		$this->validate_step_schemas( $importer, $result );

		if ( count( $result->get_messages( 'error' ) ) !== 0 ) {
			return $result;
		}

		$result->merge_messages( $importer->process( $this->step_definition ) );

		return $result;
	}

	/**
	 * Validate the step schemas.
	 *
	 * @param StepProcessor       $importer The importer.
	 * @param StepProcessorResult $result The result object to add messages to.
	 *
	 * @return void
	 */
	protected function validate_step_schemas( StepProcessor $importer, StepProcessorResult $result ) {
		$step_schema = call_user_func( array( $importer->get_step_class(), 'get_schema' ) );

		$validate = $this->validator->validate( $this->step_definition, json_encode( $step_schema ) );

		if ( ! $validate->isValid() ) {
			$result->add_error( "Schema validation failed for step {$this->step_definition->step}" );
			$errors           = ( new ErrorFormatter() )->format( $validate->error() );
			$formatted_errors = array();
			foreach ( $errors as $value ) {
				$formatted_errors[] = implode( "\n", $value );
			}

			$result->add_error( implode( "\n", $formatted_errors ) );
		}
	}
}
