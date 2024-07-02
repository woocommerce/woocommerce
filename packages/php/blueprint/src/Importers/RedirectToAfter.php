<?php

namespace Automattic\WooCommerce\Blueprint\Importers;

use Automattic\WooCommerce\Blueprint\StepProcessor;
use Automattic\WooCommerce\Blueprint\StepProcessorResult;

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
