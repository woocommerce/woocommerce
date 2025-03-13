<?php

namespace Automattic\WooCommerce\Blueprint\Tests\stubs\Importers;

use Automattic\WooCommerce\Blueprint\StepProcessor;
use Automattic\WooCommerce\Blueprint\StepProcessorResult;
use Automattic\WooCommerce\Blueprint\Tests\stubs\Steps\DummyStep;

class DummyImporter implements StepProcessor{
	public function process( $schema ): StepProcessorResult {
		return StepProcessorResult::success( DummyStep::get_step_name() );
	}

	public function get_step_class(): string {
		return DummyStep::class;
	}
}
