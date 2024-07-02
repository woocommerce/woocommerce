<?php

namespace Automattic\WooCommerce\Blueprint\Importers;

use Automattic\WooCommerce\Blueprint\StepProcessor;
use Automattic\WooCommerce\Blueprint\StepProcessorResult;
use Automattic\WooCommerce\Blueprint\Util;

class DeletePlugins implements StepProcessor {

	public function process($schema): StepProcessorResult {
		$result = StepProcessorResult::success('DeletePlugins');

		foreach ($schema->plugins as $plugin) {
			$deactivate = Util::delete_plugin_by_slug($plugin);
			if ($deactivate) {
				$result->add_info("Deleted {$plugin}.");
			} else {
				$result->add_error("Unable to delete {$plugin}.");
			}
		}

		return $result;
	}


	public function get_supported_step(): string {
		return 'deletePlugins';
	}
}
