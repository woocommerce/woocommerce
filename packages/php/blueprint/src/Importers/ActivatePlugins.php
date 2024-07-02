<?php

namespace Automattic\WooCommerce\Blueprint\Importers;


use Automattic\WooCommerce\Blueprint\StepProcessor;
use Automattic\WooCommerce\Blueprint\StepProcessorResult;
use Automattic\WooCommerce\Blueprint\Util;

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
