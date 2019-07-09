<?php
/**
 * Product tag block.
 *
 * @package WooCommerce/Blocks
 */

namespace Automattic\WooCommerce\Blocks\BlockTypes;

defined( 'ABSPATH' ) || exit;

/**
 * ProductTag class.
 */
class ProductTag extends AbstractProductGrid {
	/**
	 * Block name.
	 *
	 * @var string
	 */
	protected $block_name = 'product-tag';

	/**
	 * Set args specific to this block.
	 *
	 * @param array $query_args Query args.
	 */
	protected function set_block_query_args( &$query_args ) {
		if ( ! empty( $this->attributes['tags'] ) ) {
			$query_args['tax_query'][] = array(
				'taxonomy' => 'product_tag',
				'terms'    => array_map( 'absint', $this->attributes['tags'] ),
				'field'    => 'id',
				'operator' => isset( $this->attributes['tagOperator'] ) && 'any' === $this->attributes['tagOperator'] ? 'IN' : 'AND',
			);
		}
	}

	/**
	 * Get block attributes.
	 *
	 * @return array
	 */
	protected function get_attributes() {
		return array(
			'className'         => $this->get_schema_string(),
			'columns'           => $this->get_schema_number( wc_get_theme_support( 'product_blocks::default_columns', 3 ) ),
			'rows'              => $this->get_schema_number( wc_get_theme_support( 'product_blocks::default_rows', 1 ) ),
			'contentVisibility' => $this->get_schema_content_visibility(),
			'align'             => $this->get_schema_align(),
			'alignButtons'      => $this->get_schema_boolean( false ),
			'orderby'           => $this->get_schema_orderby(),
			'tags'              => $this->get_schema_list_ids(),
			'tagOperator'       => array(
				'type'    => 'string',
				'default' => 'any',
			),
		);
	}
}
