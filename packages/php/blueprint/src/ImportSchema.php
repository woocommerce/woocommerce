<?php

namespace Automattic\WooCommerce\Blueprint;

class ImportSchema {
	use UseWPFunctions;

	private Schema $schema;
	private BuiltInStepProcessors $builtin_step_processors;

	public function __construct( Schema $schema ) {
		$this->schema                  = $schema;
		$this->builtin_step_processors = new BuiltInStepProcessors( $schema );
	}

	public function get_schema() {
		return $this->schema;
	}

	public static function crate_from_file( $file ) {
		// @todo check for mime type
		// @todo check for allowed types -- json or zip
		$path_info = pathinfo( $file );
		$is_zip    = $path_info['extension'] === 'zip';

		return $is_zip ? self::crate_from_zip( $file ) : self::create_from_json( $file );
	}

	public static function create_from_json( $json_path ) {
		return new self( new JsonSchema( $json_path ) );
	}

	public static function crate_from_zip( $zip_path ) {
		return new self( new ZipSchema( $zip_path ) );
	}

	/**
	 * @return StepProcessorResult[]
	 */
	public function import() {
		$results   = array();
		$result    = StepProcessorResult::success( 'ImportSchema' );
		$results[] = $result;

		$step_processors    = $this->builtin_step_processors->get_all();
		$step_processors    = $this->wp_apply_filters( 'wooblueprint_importers', $step_processors );
		$indexed_processors = array();

		foreach ( $step_processors as $step_processor ) {
			$indexed_processors[ $step_processor->get_supported_step() ] = $step_processor;
		}

		foreach ( $this->schema->get_steps() as $stepSchema ) {
			$stepProcessor = $indexed_processors[ $stepSchema->step ] ?? null;
			if ( ! $stepProcessor instanceof StepProcessor ) {
				$result->add_error( "Unable to create a step processor for {$stepSchema->step}" );
				continue;
			}

			$results[] = $stepProcessor->process( $stepSchema );
		}

		return $results;
	}
}
