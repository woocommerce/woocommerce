<?php

namespace Automattic\WooCommerce\Internal\Admin\BlockTemplates;

/**
 * Trait for block formatted template.
 */
trait BlockFormattedTemplateTrait {
	/**
	 * Get the block configuration as a formatted template.
	 *
	 * @return array The block configuration as a formatted template.
	 */
	public function get_formatted_template(): array {
		$arr = [
			$this->get_name(),
			array_merge(
				$this->get_attributes(),
				[
					'_templateBlockId'    => $this->get_id(),
					'_templateBlockOrder' => $this->get_order(),
				],
				! empty( $this->get_hide_conditions() ) ? [
					'_templateBlockHideConditions' => $this->get_formatted_hide_conditions(),
				] : []
			),
		];

		return $arr;
	}

	/**
	 * Get the block hide conditions formatted for inclusion in a formatted template.
	 */
	private function get_formatted_hide_conditions(): array {
		$formatted_hide_conditions = array_map(
			function( $hide_condition ) {
				return [
					'expression' => $hide_condition['expression'],
				];
			},
			array_values( $this->get_hide_conditions() )
		);

		return $formatted_hide_conditions;
	}
}
