<?php

namespace Automattic\WooCommerce\Blueprint;

interface StepProcessor {
	public function process( $schema ): StepProcessorResult;
	public function get_step_class(): string;
}
