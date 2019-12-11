<?php
/**
 * Product category block.
 *
 * @package WooCommerce/Blocks
 */

namespace Automattic\WooCommerce\Blocks\BlockTypes;

defined( 'ABSPATH' ) || exit;

/**
 * ProductCategory class.
 */
class ProductCategory extends AbstractProductGrid {
	/**
	 * Block name.
	 *
	 * @var string
	 */
	protected $block_name = 'product-category';

	/**
	 * Set args specific to this block
	 *
	 * @param array $query_args Query args.
	 */
	protected function set_block_query_args( &$query_args ) {}

	/**
	 * Get block attributes.
	 *
	 * @return array
	 */
	protected function get_attributes() {
		return array_merge(
			parent::get_attributes(),
			array(
				'className' => $this->get_schema_string(),
				'orderby'   => $this->get_schema_orderby(),
				'editMode'  => $this->get_schema_boolean( true ),
			)
		);
	}
}
