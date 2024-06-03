<?php

namespace Automattic\WooCommerce\Admin\Features\Blueprint;

class Blueprint {
	private $schemaPath;
	public function __construct( $schemaPath ) {
		$this->schemaPath = $schemaPath;
	}

	public function process() {
		if ( ! $this->validate() ) {
			// push a notification and display it when user gets redirected to Home
		}

		$schema = json_decode( file_get_contents( $this->schemaPath ) );
		foreach ( $schema->steps as $stepSchema ) {
			$stepProcessor = __NAMESPACE__ . '\\StepProcessors\\' . ucfirst( $stepSchema->step );
			if ( class_exists( $stepProcessor ) ) {
				/**
				 * @var $stepProcessor StepProcessor
				 * @todo Use container.
				 */
				$stepProcessor = new $stepProcessor();
				$stepProcessor->process($stepSchema);
			}
		}
	}

	private function validate() {
		return true;
	}
}
