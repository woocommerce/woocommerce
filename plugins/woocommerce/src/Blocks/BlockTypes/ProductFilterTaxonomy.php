<?php
declare( strict_types = 1 );

namespace Automattic\WooCommerce\Blocks\BlockTypes;

/**
 * Product Filter: Taxonomy Block.
 */
final class ProductFilterTaxonomy extends AbstractBlock {

	/**
	 * Block name.
	 *
	 * @var string
	 */
	protected $block_name = 'product-filter-taxonomy';

	/**
	 * Extra data passed through from server to client for block.
	 *
	 * @param array $attributes  Any attributes that currently are available from the block.
	 *                           Note, this will be empty in the editor context when the block is
	 *                           not in the post content on editor load.
	 */
	protected function enqueue_data( array $attributes = array() ) {
		parent::enqueue_data( $attributes );

		if ( is_admin() ) {
			$this->asset_data_registry->add( 'filterableProductTaxonomies', $this->get_taxonomies() );
		}
	}

	/**
	 * Get product taxonomies for the block.
	 *
	 * @return array
	 */
	private function get_taxonomies() {
		$taxonomies    = get_object_taxonomies( 'product', 'objects' );
		$taxonomy_data = array();

		foreach ( $taxonomies as $taxonomy ) {
			if ( $taxonomy->public && 'product_shipping_class' !== $taxonomy->name ) {
				$taxonomy_data[] = array(
					'label'  => $taxonomy->label,
					'name'   => $taxonomy->name,
					'labels' => array(
						'singular_name' => $taxonomy->labels->singular_name,
					),
				);
			}
		}

		return $taxonomy_data;
	}
}
