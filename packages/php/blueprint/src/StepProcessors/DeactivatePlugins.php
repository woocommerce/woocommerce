<?php

namespace Automattic\WooCommerce\Blueprint\StepProcessors;


use Automattic\WooCommerce\Blueprint\StepProcessor;
use Automattic\WooCommerce\Blueprint\StepProcessorResult;
use Automattic\WooCommerce\Blueprint\Util;

class DeactivatePlugins implements StepProcessor {

	public function process($schema): StepProcessorResult {
		$result = StepProcessorResult::success('DeactivatePlugins');

		foreach ($schema->plugins as $plugin) {
			 Util::deactivate_plugin_by_slug($plugin);
			$result->add_info("Deactivated {$plugin}.");
		}

		return $result;
	}

	public function get_supported_step(): string {
		return 'deactivatePlugins';
	}
}
