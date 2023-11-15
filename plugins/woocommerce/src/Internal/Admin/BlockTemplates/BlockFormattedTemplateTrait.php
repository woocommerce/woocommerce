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
		$arr = array(
			$this->get_name(),
			array_merge(
				$this->get_attributes(),
				array(
					'_templateBlockId'    => $this->get_id(),
					'_templateBlockOrder' => $this->get_order(),
				),
				! empty( $this->get_hide_conditions() ) ? array(
					'_templateBlockHideConditions' => $this->get_formatted_hide_conditions(),
				) : array(),
				! empty( $this->get_disable_conditions() ) ? array(
					'_templateBlockDisableConditions' => $this->get_formatted_disable_conditions(),
				) : array(),
			),
		);

		return $arr;
	}

	/**
	 * Get the block hide conditions formatted for inclusion in a formatted template.
	 */
	private function get_formatted_hide_conditions(): array {
		return $this->format_conditions( $this->get_hide_conditions() );
	}

	/**
	 * Get the block disable conditions formatted for inclusion in a formatted template.
	 */
	private function get_formatted_disable_conditions(): array {
		return $this->format_conditions( $this->get_disable_conditions() );
	}

	/**
	 * Formats conditions in the expected format to include in the template.
	 *
	 * @param array $conditions The conditions to format.
	 */
	private function format_conditions( $conditions ): array {
		$formatted_expressions = array_map(
			function( $condition ) {
				return array(
					'expression' => $condition['expression'],
				);
			},
			array_values( $conditions )
		);

		return $formatted_expressions;
	}
}
