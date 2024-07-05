<?php

namespace Automattic\WooCommerce\Blueprint\Importers;


use Automattic\WooCommerce\Blueprint\StepProcessor;
use Automattic\WooCommerce\Blueprint\StepProcessorResult;
use Automattic\WooCommerce\Blueprint\Util;

class DeactivatePlugin implements StepProcessor {

	public function process($schema): StepProcessorResult {
		$result = StepProcessorResult::success('DeactivatePlugins');
		$name = $schema->pluginName;

		Util::deactivate_plugin_by_slug($name);
		$result->add_info("Deactivated {$name}.");

		return $result;
	}

	public function get_supported_step(): string {
		return 'deactivatePlugin';
	}
}
