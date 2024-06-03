<?php

namespace Automattic\WooCommerce\Admin\Features\Blueprint\StepProcessors;

use Automattic\WooCommerce\Admin\Features\Blueprint\StepProcessor;
use WC_Tax;

class ConfigureSettings implements StepProcessor {
	public function process($schema) {
		foreach ($schema->tabs as $tabName => $tab) {
			$stepProcessor = __NAMESPACE__ . '\\Settings\\ConfigureSettings' . ucfirst( $tabName );
			if ( class_exists( $stepProcessor ) ) {

				/**
				 * @var $stepProcessor StepProcessor
				 * @todo Use container.
				 */
				$stepProcessor = new $stepProcessor();
				$stepProcessor->process($tab);
			}
		}
	}
}
