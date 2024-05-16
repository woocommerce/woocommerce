<?php

namespace Automattic\WooCommerce\Admin\Features\Blueprint;

class Blueprint {
	private $schemaPath;
	public function __construct( $schema ) {
		$this->schema = $schema;
	}

	public function process() {
		if ( ! $this->validate() ) {
			// push a notification and display it when user gets redirected to Home
		}

		foreach ( $this->schema->steps as $stepSchema ) {
			$stepProcessor = __NAMESPACE__ . '\\StepProcessors\\' . ucfirst( $stepSchema->step ) . 'Processor';
			if ( class_exists( $stepProcessor ) ) {
				/**
				 * @var $stepProcessor StepProcessor
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
