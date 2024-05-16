<?php

namespace Automattic\WooCommerce\Admin\Features\Blueprint\StepProcessors;

use Automattic\WooCommerce\Admin\Features\Blueprint\StepProcessor;
use Automattic\WooCommerce\Admin\Features\Blueprint\StepProcessorResult;
use Automattic\WooCommerce\Admin\Features\Blueprint\Util;
use Automattic\WooCommerce\Admin\Notes\Note;
use RecursiveArrayIterator;
use RecursiveIteratorIterator;
use WC_Tax;

class ConfigureSettings implements StepProcessor {
	public function process($schema): StepProcessorResult {
		$options = array();
		foreach ($schema->values->options as $option) {
			$options[$option->id] = $option->value;
		}

		$result =  (new SetOptions())->process((object) array(
			'options' => $options
		));

		$result->set_step_name("ConfigureSettings");
		return $result;
	}

	public function get_supported_step(): string {
		return 'configureSettings';
	}
}
