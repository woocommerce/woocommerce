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
				]
			),
		];

		return $arr;
	}
}
