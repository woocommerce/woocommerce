<?php

namespace Automattic\WooCommerce\Admin\Features\Blueprint\StepProcessors;

use Automattic\WooCommerce\Admin\Features\Blueprint\StepProcessor;
use Automattic\WooCommerce\Admin\Features\Blueprint\StepProcessorResult;
use Automattic\WooCommerce\Admin\Features\Blueprint\Util;
use WC_Tax;

class ConfigureSettings implements StepProcessor {
	public function process($schema): StepProcessorResult {
		foreach ($schema->tabs as $tabName => $tab) {
			if ($tabName !== 'account_privacy') {
				continue;
			}
			$stepProcessor = __NAMESPACE__ . '\\Settings\\ConfigureSettings' .  Util::snake_to_camel($tabName);
			if ( class_exists( $stepProcessor ) ) {

				/**
				 * @var $stepProcessor StepProcessor
				 * @todo Use container.
				 */
				$stepProcessor = new $stepProcessor();
				$stepProcessor->process($tab);
			}
		}

		return StepProcessorResult::success();
	}

}
