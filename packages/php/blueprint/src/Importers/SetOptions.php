<?php
namespace Automattic\WooCommerce\Blueprint\Importers;

use Automattic\WooCommerce\Blueprint\StepProcessor;
use Automattic\WooCommerce\Blueprint\StepProcessorResult;

class SetOptions implements StepProcessor {
	public function process($schema): StepProcessorResult {
		$result = StepProcessorResult::success('SetOptions');
		foreach ( $schema->options as $key => $value ) {
			if ( is_object( $value ) ) {
				$value = (array) $value;
			}
			$updated = update_option( $key, $value );
			$updated && $result->add_info("{$key} has been updated");
			if (!$updated) {
				$current_value = get_option($key);
				if ($current_value === $value) {
					$result->add_info( "{$key} has not been updated because the current value is already up to date." );
				}
			}

		}

		return $result;
	}

	public function get_supported_step(): string {
		return 'setOptions';
	}
}
