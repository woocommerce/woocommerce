<?php
namespace Automattic\WooCommerce\Blocks\BlockTypes;

/**
 * AttributeFilter class.
 */
class AttributeFilter extends AbstractBlock {
	/**
	 * Block name.
	 *
	 * @var string
	 */
	protected $block_name             = 'attribute-filter';
	const FILTER_QUERY_VAR_PREFIX     = 'filter_';
	const QUERY_TYPE_QUERY_VAR_PREFIX = 'query_type_';

	/**
	 * Extra data passed through from server to client for block.
	 *
	 * @param array $attributes  Any attributes that currently are available from the block.
	 *                           Note, this will be empty in the editor context when the block is
	 *                           not in the post content on editor load.
	 */
	protected function enqueue_data( array $attributes = [] ) {
		parent::enqueue_data( $attributes );
		$this->asset_data_registry->add( 'attributes', array_values( wc_get_attribute_taxonomies() ) );

		// Enqueue any `queryState` that the UI will need to be aware of
		// (Ex: the category id if we're on a category page, the tag id if we're on a tag page/etc).
		$query_state = [];

		if ( is_product_category() ) {
			$query_state['category'] = get_queried_object_id();
		}
		if ( is_product_tag() ) {
			$query_state['tag'] = get_queried_object()->term_id;
		}

		$this->asset_data_registry->add( 'queryState', $query_state );
	}

	/**
	 * Get the frontend style handle for this block type.
	 *
	 * @return string[]
	 */
	protected function get_block_type_style() {
		return array_merge( parent::get_block_type_style(), [ 'wc-blocks-packages-style' ] );
	}
}
