<?php

namespace Automattic\WooCommerce\Blueprint;

use Automattic\WooCommerce\Blueprint\Schemas\JsonSchema;
use Automattic\WooCommerce\Blueprint\Schemas\ZipSchema;
use Opis\JsonSchema\Validator;

/**
 * Class ImportSchema
 *
 * Handles the import schema functionality for WooCommerce.
 *
 * @package Automattic\WooCommerce\Blueprint
 */
class ImportSchema {
	use UseWPFunctions;

	/**
	 * @var JsonSchema The schema instance.
	 */
	private JsonSchema $schema;

	/**
	 * @var Validator The JSON schema validator instance.
	 */
	private Validator $validator;

	/**
	 * @var BuiltInStepProcessors The built-in step processors instance.
	 */
	private BuiltInStepProcessors $builtin_step_processors;

	/**
	 * ImportSchema constructor.
	 *
	 * @param JsonSchema $schema The schema instance.
	 * @param Validator|null $validator The validator instance, optional.
	 */
	public function __construct( JsonSchema $schema,  Validator $validator = null ) {
		$this->schema = $schema;
		if ( null === $validator ) {
			$this->validator = new Validator();
		}

		$this->builtin_step_processors = new BuiltInStepProcessors( $schema instanceof ZipSchema );
	}

	/**
	 * Create an ImportSchema instance from a file.
	 *
	 * @param string $file The file path.
	 * @return ImportSchema The created ImportSchema instance.
	 */
	public static function crate_from_file( $file ) {
		// @todo check for mime type
		// @todo check for allowed types -- json or zip
		$path_info = pathinfo( $file );
		$is_zip    = $path_info['extension'] === 'zip';

		return $is_zip ? self::crate_from_zip( $file ) : self::create_from_json( $file );
	}

	/**
	 * Create an ImportSchema instance from a JSON file.
	 *
	 * @param string $json_path The JSON file path.
	 * @return ImportSchema The created ImportSchema instance.
	 */
	public static function create_from_json( $json_path ) {
		return new self( new JsonSchema( $json_path ) );
	}

	/**
	 * Create an ImportSchema instance from a ZIP file.
	 *
	 * @param string $zip_path The ZIP file path.
	 *
	 * @return ImportSchema The created ImportSchema instance.
	 */
	public static function crate_from_zip( $zip_path ) {
		return new self( new ZipSchema( $zip_path ) );
	}

	/**
	 * Import the schema steps.
	 *
	 * @return StepProcessorResult[]
	 */
	public function import() {
		$results   = array();
		$result    = StepProcessorResult::success( 'ImportSchema' );
		$results[] = $result;

		$step_processors    = $this->builtin_step_processors->get_all();
		$step_processors    = $this->wp_apply_filters( 'wooblueprint_importers', $step_processors );
		$indexed_step_processors = Util::index_array( $step_processors, function($key, $step_processor) {
			return $step_processor->get_step_class()::get_step_name();
		} );

		//		$validate = $this->validate_steps($indexed_processors);


		foreach ( $this->schema->get_steps() as $stepSchema ) {
			$stepProcessor = $indexed_step_processors[ $stepSchema->step ] ?? null;
			if ( ! $stepProcessor instanceof StepProcessor ) {
				$result->add_error( "Unable to create a step processor for {$stepSchema->step}" );
				continue;
			}
			$results[] = $stepProcessor->process( $stepSchema );
		}

		return $results;
	}

	//
//	private function validate_steps(array $indexed_processors) {
//
//
//		$validate = $this->validator->validate( json_encode($this->schema->get_steps()), json_encode( $steps_schema ) );
//
//		if ( ! $validate->isValid() ) {
////			$result->add_error( 'Schema validation failed' );
//			$errors = ( new ErrorFormatter() )->format( $validate->error() );
//
//			var_dump($errors);
//			exit;
//
//		}
//	}
}
