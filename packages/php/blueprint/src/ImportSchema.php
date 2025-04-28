<?php

namespace Automattic\WooCommerce\Blueprint;

use Automattic\WooCommerce\Blueprint\Schemas\JsonSchema;
use Opis\JsonSchema\Errors\ErrorFormatter;
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
	 * JsonSchema object.
	 *
	 * @var JsonSchema The schema instance.
	 */
	private JsonSchema $schema;

	/**
	 * Validator object.
	 *
	 * @var Validator The JSON schema validator instance.
	 */
	private Validator $validator;


	/**
	 * ImportSchema constructor.
	 *
	 * @param JsonSchema     $schema The schema instance.
	 * @param Validator|null $validator The validator instance, optional.
	 */
	public function __construct( JsonSchema $schema, ?Validator $validator = null ) {
		$this->schema = $schema;
		if ( null === $validator ) {
			$validator = new Validator();
		}

		$this->validator = $validator;
	}

	/**
	 * Get the schema.
	 *
	 * @return JsonSchema The schema.
	 */
	public function get_schema() {
		return $this->schema;
	}

	/**
	 * Create an ImportSchema instance from a file.
	 *
	 * @param string $file The file path.
	 * @return ImportSchema The created ImportSchema instance.
	 *
	 * @throws \RuntimeException If the JSON file cannot be read.
	 * @throws \InvalidArgumentException If the JSON is invalid or missing 'steps' field.
	 */
	public static function create_from_file( $file ) {
		return self::create_from_json( $file );
	}

	/**
	 * Create an ImportSchema instance from a JSON file.
	 *
	 * @param string $json_path The JSON file path.
	 * @return ImportSchema The created ImportSchema instance.
	 *
	 * @throws \RuntimeException If the JSON file cannot be read.
	 * @throws \InvalidArgumentException If the JSON is invalid or missing 'steps' field.
	 */
	public static function create_from_json( $json_path ) {
		return new self( new JsonSchema( $json_path ) );
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

		foreach ( $this->schema->get_steps() as $step_schema ) {
			$step_importer = new ImportStep( $step_schema, $this->validator );
			$results[]     = $step_importer->import();
		}

		return $results;
	}
}
