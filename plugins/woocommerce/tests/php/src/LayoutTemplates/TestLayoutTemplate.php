<?php

namespace Automattic\WooCommerce\Tests\LayoutTemplates;

use Automattic\WooCommerce\Admin\BlockTemplates\BlockInterface;
use Automattic\WooCommerce\Admin\BlockTemplates\BlockTemplateInterface;

/**
 * Test layout template.
 */
class TestLayoutTemplate implements BlockTemplateInterface {
	/**
	 * Get the layout template ID.
	 */
	public function get_id(): string {
		return 'test-layout-template';
	}

	/**
	 * Get the layout template title.
	 */
	public function get_title(): string {
		return 'Test layout template';
	}

	/**
	 * Get the layout template description.
	 */
	public function get_description(): string {
		return 'A test layout template';
	}

	/**
	 * Get the layout template area.
	 */
	public function get_area(): string {
		return 'test';
	}

	/**
	 * Get the layout template blocks.
	 *
	 * @param string $id_base Block ID base.
	 */
	public function generate_block_id( string $id_base ): string {
		return $id_base . '-test';
	}

	/**
	 * Get the layout template blocks.
	 */
	public function &get_root_template(): BlockTemplateInterface {
		return $this;
	}

	/**
	 * Get the layout template blocks.
	 *
	 * @param string $block_id Block ID.
	 */
	public function get_block( string $block_id ): ?BlockInterface {
		return null;
	}

	/**
	 * Get the layout template blocks.
	 *
	 * @param string $block_id Block ID.
	 */
	public function remove_block( string $block_id ) {
	}

	/**
	 * Get the layout template blocks.
	 */
	public function remove_blocks() {
	}

	/**
	 * Get the layout template blocks.
	 */
	public function get_formatted_template(): array {
		return array();
	}

	/**
	 * Get the layout template blocks.
	 */
	public function to_json(): array {
		return array(
			'id'             => $this->get_id(),
			'title'          => $this->get_title(),
			'description'    => $this->get_description(),
			'area'           => $this->get_area(),
			'blockTemplates' => array(),
		);
	}
}
