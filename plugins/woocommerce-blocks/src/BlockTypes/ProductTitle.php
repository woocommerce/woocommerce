<?php
namespace Automattic\WooCommerce\Blocks\BlockTypes;

/**
 * ProductTitle class.
 */
class ProductTitle extends AbstractBlock {

	/**
	 * Block name.
	 *
	 * @var string
	 */
	protected $block_name = 'product-title';

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
			'path'         => $this->asset_api->get_block_asset_build_path( 'atomic-block-components/title' ),
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
				'gradients'                       => true,
				'background'                      => true,
				'link'                            => false,
				'text'                            => true,
				'__experimentalSkipSerialization' => true,
			),
			'typography'             =>
			array(
				'fontSize'                    => true,
				'lineHeight'                  => true,
				'__experimentalFontWeight'    => true,
				'__experimentalTextTransform' => true,
				'__experimentalFontFamily'    => true,
			),
			'spacing'                =>
			array(
				'margin'                          => true,
				'__experimentalSkipSerialization' => true,
			),
			'__experimentalSelector' => '.wc-block-components-product-title',
		);
	}

}
