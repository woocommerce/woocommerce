<?php
namespace Automattic\WooCommerce\Blocks\BlockTypes;

/**
 * AllReviews class.
 */
class AllReviews extends AbstractBlock {
	/**
	 * Block name.
	 *
	 * @var string
	 */
	protected $block_name = 'all-reviews';

	/**
	 * Get the frontend script handle for this block type.
	 *
	 * @see $this->register_block_type()
	 * @param string $key Data to get, or default to everything.
	 * @return array|string
	 */
	protected function get_block_type_script( $key = null ) {
		$script = [
			'handle'       => 'wc-reviews-frontend',
			'path'         => $this->asset_api->get_block_asset_build_path( 'reviews-frontend' ),
			'dependencies' => [],
		];
		return $key ? $script[ $key ] : $script;
	}
}
