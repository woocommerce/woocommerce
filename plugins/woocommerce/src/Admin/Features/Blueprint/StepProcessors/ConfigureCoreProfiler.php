<?php

namespace Automattic\WooCommerce\Admin\Features\Blueprint\StepProcessors;

use Automattic\WooCommerce\Admin\Features\Blueprint\StepProcessor;
use Automattic\WooCommerce\Admin\Features\Blueprint\StepProcessorResult;
use Automattic\WooCommerce\Admin\Features\Blueprint\Util;
use WC_Tax;

class ConfigureCoreProfiler extends SetOptions {
	public function process($schema): StepProcessorResult {
		$result = parent::process((object)array(
			'options' => $schema->options
		));

		$result->set_step_name('ConfigureCoreProfiler');
		return $result;
	}


	public function get_supported_step(): string {
		return 'configureCoreProfiler';
	}
}
