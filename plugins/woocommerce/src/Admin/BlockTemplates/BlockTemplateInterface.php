<?php

namespace Automattic\WooCommerce\Admin\BlockTemplates;

/**
 * Interface for block-based template.
 */
interface BlockTemplateInterface extends ContainerInterface {
	/**
	 * Get the template ID.
	 */
	public function get_id(): string;

	/**
	 * Get the template title.
	 */
	public function get_title(): string;

	/**
	 * Get the template description.
	 */
	public function get_description(): string;

	/**
	 * Get the template area.
	 */
	public function get_area(): string;

	/**
	 * Generate a block ID based on a base.
	 *
	 * @param string $id_base The base to use when generating an ID.
	 * @return string
	 */
	public function generate_block_id( string $id_base ): string;
}
