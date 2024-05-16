<?php

namespace Automattic\WooCommerce\Admin\Features\Blueprint\StepProcessors;


use Automattic\WooCommerce\Admin\Features\Blueprint\StepProcessor;
use Automattic\WooCommerce\Admin\Features\Blueprint\StepProcessorResult;
use Automattic\WooCommerce\Admin\Features\Blueprint\Util;

class ActivatePlugins implements StepProcessor {

	public function process($schema): StepProcessorResult {
		$result = StepProcessorResult::success('DeactivatePlugins');

		foreach ($schema->plugins as $plugin) {
			$activate =Util::deactivate_plugin_by_slug($plugin);
			$activate && $result->add_info("Activated {$plugin}.");
			! $activate && $result->add_info("Unable to activate {$plugin}.");
		}

		return $result;
	}

	public function get_supported_step(): string {
		return 'activatePlugins';
	}
}
