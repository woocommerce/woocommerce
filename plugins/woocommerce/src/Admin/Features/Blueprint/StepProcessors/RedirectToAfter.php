<?php

namespace Automattic\WooCommerce\Admin\Features\Blueprint\StepProcessors;

use Automattic\WooCommerce\Admin\Features\Blueprint\StepProcessor;
use Automattic\WooCommerce\Admin\Features\Blueprint\StepProcessorResult;

// @todo -- we don't really need to do anything with this step processor
// Need to find a different approach for this type of step processors.
class RedirectToAfter implements StepProcessor{
	public function process( $schema ): StepProcessorResult {
		return StepProcessorResult::success('RedirectToAfter');
	}

	public function get_supported_step(): string {
		return 'redirectToAfter';
	}
}
