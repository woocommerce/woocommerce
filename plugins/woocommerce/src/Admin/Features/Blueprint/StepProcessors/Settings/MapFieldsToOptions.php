<?php

namespace Automattic\WooCommerce\Admin\Features\Blueprint\StepProcessors\Settings;

use Automattic\WooCommerce\Admin\Features\Blueprint\StepProcessorResult;
use RecursiveArrayIterator;
use RecursiveIteratorIterator;

/**
 * Maps field_name to option_name and update the option.
 */
class MapFieldsToOptions {
	/**
	 * List of options to update.
	 *
	 * Format:
	 * field_name => option_name in wp_options
	 *
	 * @var array
	 */
	protected array $options_map = array();

	/**
	 * Return fields to map and update.
	 *
	 * Return format:
	 *  array
	 *      field_name => value
	 *
	 * @param $schema
	 *
	 * @return array
	 */
	protected function provide_fields( $schema ): array {
		return iterator_to_array( new RecursiveIteratorIterator( new RecursiveArrayIterator( $schema ) ), true );
	}

	public function process( $schema ): StepProcessorResult {
		$fields = $this->provide_fields( $schema );
		foreach ($fields as $field_name => $field) {
			$option_key = $this->options_map[$field_name] ?? null;
			if ($option_key !== null) {
				update_option($option_key, $field);
			}
		}

		return StepProcessorResult::success();
	}
}
