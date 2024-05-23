<?php

namespace Automattic\WooCommerce\Admin\Features\Blueprint\StepProcessors;

use Automattic\WooCommerce\Admin\Features\Blueprint\StepProcessor;

class ConfigureSettingsProcessor implements StepProcessor {
	public function process($schema) {
		foreach ($schema->tabs as $tab_name => $tab_fields) {
			$processor_name = __NAMESPACE__ . '\Settings\\'.ucfirst($tab_name).'SettingsProcessor';
			if (class_exists($processor_name)) {
				$processor = new $processor_name;
				$processor->process($tab_fields);
			}
		}
	}

}
