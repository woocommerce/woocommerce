<?php

namespace Automattic\WooCommerce\Internal\Features\ProductBlockEditor\ProductTemplates;

use Automattic\WooCommerce\Admin\BlockTemplates\BlockInterface;
use Automattic\WooCommerce\Admin\Features\ProductBlockEditor\ProductTemplates\GroupInterface;
use Automattic\WooCommerce\Admin\Features\ProductBlockEditor\ProductTemplates\ProductFormTemplateInterface;
use Automattic\WooCommerce\Admin\Features\ProductBlockEditor\ProductTemplates\SectionInterface;
use Automattic\WooCommerce\Admin\Features\ProductBlockEditor\ProductTemplates\SubsectionInterface;
use Automattic\WooCommerce\Internal\Admin\BlockTemplates\AbstractBlockTemplate;
use Automattic\WooCommerce\Blocks\Utils\BlockTemplateUtils;

/**
 * Block template class.
 */
abstract class AbstractProductFormTemplate extends AbstractBlockTemplate implements ProductFormTemplateInterface {

	/**
	 * Get the template slug.
	 */
	abstract public function get_slug(): string;

	/**
	 * Get the template area.
	 */
	public function get_area(): string {
		return 'product-form';
	}

	/**
	 * Get a group block by ID.
	 *
	 * @param string $group_id The group block ID.
	 * @throws \UnexpectedValueException If block is not of type GroupInterface.
	 */
	public function get_group_by_id( string $group_id ): ?GroupInterface {
		$group = $this->get_block( $group_id );
		if ( $group && ! $group instanceof GroupInterface ) {
			throw new \UnexpectedValueException( 'Block with specified ID is not a group.' );
		}
		return $group;
	}

	/**
	 * Get a section block by ID.
	 *
	 * @param string $section_id The section block ID.
	 * @throws \UnexpectedValueException If block is not of type SectionInterface.
	 */
	public function get_section_by_id( string $section_id ): ?SectionInterface {
		$section = $this->get_block( $section_id );
		if ( $section && ! $section instanceof SectionInterface ) {
			throw new \UnexpectedValueException( 'Block with specified ID is not a section.' );
		}
		return $section;
	}

	/**
	 * Get a subsection block by ID.
	 *
	 * @param string $subsection_id The subsection block ID.
	 * @throws \UnexpectedValueException If block is not of type SubsectionInterface.
	 */
	public function get_subsection_by_id( string $subsection_id ): ?SubsectionInterface {
		$subsection = $this->get_block( $subsection_id );
		if ( $subsection && ! $subsection instanceof SubsectionInterface ) {
			throw new \UnexpectedValueException( 'Block with specified ID is not a subsection.' );
		}
		return $subsection;
	}

	/**
	 * Get a block by ID.
	 *
	 * @param string $block_id The block block ID.
	 */
	public function get_block_by_id( string $block_id ): ?BlockInterface {
		return $this->get_block( $block_id );
	}

	/**
	 * Add a custom block type to this template.
	 *
	 * @param array $block_config The block data.
	 */
	public function add_group( array $block_config ): GroupInterface {
		$block = new Group( $block_config, $this->get_root_template(), $this );
		return $this->add_inner_block( $block );
	}

	/**
	 * Get the compatible product types.
	 *
	 * @return array Array of compatible product types.
	 */
	abstract public function get_compatible_product_types(): array;

	/**
	 * Get the default product data.
	 */
	public function get_default_product_data(): array {
		return array(
			'type' => 'simple'
		);
	}
}
