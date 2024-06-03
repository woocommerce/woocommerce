<?php
namespace Automattic\WooCommerce\Admin\Features\Blueprint\StepProcessors;

use Automattic\WooCommerce\Admin\Features\Blueprint\StepProcessor;
use Automattic\WooCommerce\Admin\Features\Blueprint\StepProcessorResult;

class SetSiteOptionsProcessor implements StepProcessor {
	public function process($schema): StepProcessorResult {
		foreach ( $schema->options as $key => $value ) {
			if ( is_object( $value ) ) {
				$value = (array) $value;
			}
			update_option( $key, $value );
		}

		return StepProcessorResult::success();
	}
}
