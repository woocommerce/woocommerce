<?php
namespace Automattic\WooCommerce\Blocks\BlockTypes;

/**
 * ProductTagList class.
 */
class ProductTagList extends AbstractBlock {

	/**
	 * Block name.
	 *
	 * @var string
	 */
	protected $block_name = 'product-tag-list';

	/**
	 * API version name.
	 *
	 * @var string
	 */
	protected $api_version = '2';

	/**
	 * Get the editor script handle for this block type.
	 *
	 * @param string $key Data to get, or default to everything.
	 * @return array|string;
	 */
	protected function get_block_type_editor_script( $key = null ) {
		$script = [
			'handle'       => 'wc-' . $this->block_name . '-block',
			'path'         => $this->asset_api->get_block_asset_build_path( 'atomic-block-components/tag-list' ),
			'dependencies' => [ 'wc-blocks' ],
		];
		return $key ? $script[ $key ] : $script;
	}

	/**
	 * Get block supports. Shared with the frontend.
	 * IMPORTANT: If you change anything here, make sure to update the JS file too.
	 *
	 * @return array
	 */
	protected function get_block_type_supports() {
		return array(
			'color'                  =>
			array(
				'text'       => true,
				'background' => false,
				'link'       => true,
			),
			'typography'             =>
			array(
				'fontSize' => true,
			),
			'__experimentalSelector' => '.wc-block-components-product-tag-list',
		);
	}

}
