<?php
namespace Automattic\WooCommerce\Blocks\BlockTypes;

/**
 * AllProducts class.
 */
class AllProducts extends AbstractBlock {
	/**
	 * Block name.
	 *
	 * @var string
	 */
	protected $block_name = 'all-products';

	/**
	 * Extra data passed through from server to client for block.
	 *
	 * @param array $attributes  Any attributes that currently are available from the block.
	 *                           Note, this will be empty in the editor context when the block is
	 *                           not in the post content on editor load.
	 */
	protected function enqueue_data( array $attributes = [] ) {
		parent::enqueue_data( $attributes );
		$this->asset_data_registry->add( 'min_columns', wc_get_theme_support( 'product_blocks::min_columns', 1 ), true );
		$this->asset_data_registry->add( 'max_columns', wc_get_theme_support( 'product_blocks::max_columns', 6 ), true );
		$this->asset_data_registry->add( 'default_columns', wc_get_theme_support( 'product_blocks::default_columns', 3 ), true );
		$this->asset_data_registry->add( 'min_rows', wc_get_theme_support( 'product_blocks::min_rows', 1 ), true );
		$this->asset_data_registry->add( 'max_rows', wc_get_theme_support( 'product_blocks::max_rows', 6 ), true );
		$this->asset_data_registry->add( 'default_rows', wc_get_theme_support( 'product_blocks::default_rows', 3 ), true );
	}

	/**
	 * Register script and style assets for the block type before it is registered.
	 *
	 * This registers the scripts; it does not enqueue them.
	 */
	protected function register_block_type_assets() {
		parent::register_block_type_assets();
		$this->register_chunk_translations(
			[
				'atomic-block-components/price',
				'atomic-block-components/image',
				'atomic-block-components/title',
				'atomic-block-components/rating',
				'atomic-block-components/button',
				'atomic-block-components/summary',
				'atomic-block-components/sale-badge',
				'atomic-block-components/sku',
				'atomic-block-components/category-list',
				'atomic-block-components/tag-list',
				'atomic-block-components/stock-indicator',
				'atomic-block-components/add-to-cart',
			]
		);
	}
}
