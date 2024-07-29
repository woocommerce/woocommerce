<?php

namespace Automattic\WooCommerce\Blueprint\Importers;

use Automattic\WooCommerce\Blueprint\StepProcessor;
use Automattic\WooCommerce\Blueprint\StepProcessorResult;
use Automattic\WooCommerce\Blueprint\Steps\SetSiteOptions;
use Automattic\WooCommerce\Blueprint\UseWPFunctions;

class ImportSetSiteOptions implements StepProcessor {
	use UseWPFunctions;
	public function process( $schema ): StepProcessorResult {
		$result = StepProcessorResult::success( SetSiteOptions::get_step_name() );
		foreach ( $schema->options as $key => $value ) {
			if ( is_object( $value ) ) {
				$value = (array) $value;
			}
			
			$updated = $this->wp_update_option( $key, $value );

			if ( $updated ) {
				$result->add_info( "{$key} has been updated" );
			} else {
				$current_value = $this->wp_get_option( $key );
				if ( $current_value === $value ) {
					$result->add_info( "{$key} has not been updated because the current value is already up to date." );
				}
			}
		}

		return $result;
	}

	public function get_step_class(): string {
		return SetSiteOptions::class;
	}
}
