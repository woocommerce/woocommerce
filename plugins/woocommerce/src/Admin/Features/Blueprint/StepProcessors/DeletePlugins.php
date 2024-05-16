<?php

namespace Automattic\WooCommerce\Admin\Features\Blueprint\StepProcessors;

use Automattic\WooCommerce\Admin\Features\Blueprint\StepProcessor;
use Automattic\WooCommerce\Admin\Features\Blueprint\StepProcessorResult;
use Automattic\WooCommerce\Admin\Features\Blueprint\Util;

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
