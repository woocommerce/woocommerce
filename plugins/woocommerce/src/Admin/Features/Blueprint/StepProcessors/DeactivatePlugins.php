<?php

namespace Automattic\WooCommerce\Admin\Features\Blueprint\StepProcessors;


use Automattic\WooCommerce\Admin\Features\Blueprint\StepProcessor;
use Automattic\WooCommerce\Admin\Features\Blueprint\StepProcessorResult;
use Automattic\WooCommerce\Admin\Features\Blueprint\Util;

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
