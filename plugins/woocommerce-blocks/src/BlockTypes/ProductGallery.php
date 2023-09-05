<?php
namespace Automattic\WooCommerce\Blocks\BlockTypes;

/**
 * ProductGallery class.
 */
class ProductGallery extends AbstractBlock {
	/**
	 * Block name.
	 *
	 * @var string
	 */
	protected $block_name = 'product-gallery';

	/**
	 * Get the frontend style handle for this block type.
	 *
	 * @return null
	 */
	protected function get_block_type_style() {
		return null;
	}

	/**
	 * Include and render the block.
	 *
	 * @param array    $attributes Block attributes. Default empty array.
	 * @param string   $content    Block content. Default empty string.
	 * @param WP_Block $block      Block instance.
	 * @return string Rendered block type output.
	 */
	protected function render( $attributes, $content, $block ) {
		$classname          = $attributes['className'] ?? '';
		$wrapper_attributes = get_block_wrapper_attributes( array( 'class' => trim( sprintf( 'woocommerce %1$s', $classname ) ) ) );
		$html               = sprintf(
			'<div data-wc-interactive %1$s>
				%2$s
			</div>',
			$wrapper_attributes,
			$content
		);
		$p                  = new \WP_HTML_Tag_Processor( $content );

		if ( $p->next_tag() ) {
			$p->set_attribute( 'data-wc-interactive', true );
			$p->set_attribute(
				'data-wc-context',
				wp_json_encode( array( 'woocommerce' => array( 'productGallery' => array( 'numberOfThumbnails' => 0 ) ) ) )
			);
			$html = $p->get_updated_html();
		}

		return $html;
	}

	/**
	 * Get the Interactivity API's view script handle for this block type.
	 *
	 * @param string $key Data to get, or default to everything.
	 */
	protected function get_block_type_script( $key = null ) {
		$script = [
			'handle'       => 'wc-' . $this->block_name . '-frontend',
			'path'         => $this->asset_api->get_block_asset_build_path( $this->block_name . '-frontend' ),
			'dependencies' => [ 'wc-interactivity' ],
		];

		return $key ? $script[ $key ] : $script;
	}
}
