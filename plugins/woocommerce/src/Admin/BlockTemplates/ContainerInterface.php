<?php

namespace Automattic\WooCommerce\Admin\BlockTemplates;

/**
 * Interface for block containers.
 */
interface ContainerInterface {
	/**
	 * Get the root template that the block belongs to.
	 */
	public function &get_root_template(): BlockTemplateInterface;

	/**
	 * Get the block configuration as a formatted template.
	 */
	public function get_formatted_template(): array;
}
