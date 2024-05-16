<?php
namespace Automattic\WooCommerce\Admin\Features\Blueprint\StepProcessors;

use Automattic\WooCommerce\Admin\Features\Blueprint\StepProcessor;
use Automattic\WooCommerce\Admin\Features\Blueprint\StepProcessorResult;

class ConfigureTaskOptions extends SetOptions {
	public function process($schema): StepProcessorResult {
		$result = parent::process($schema);
		$result->set_step_name('ConfigureTaskOptions');
		return $result;
	}

	public function get_supported_step(): string {
		return 'configureTaskOptions';
	}
}
