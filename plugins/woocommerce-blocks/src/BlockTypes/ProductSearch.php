<?php
namespace Automattic\WooCommerce\Blocks\BlockTypes;

/**
 * ProductSearch class.
 */
class ProductSearch extends AbstractBlock {

	/**
	 * Block name.
	 *
	 * @var string
	 */
	protected $block_name = 'product-search';

	/**
	 * Get the frontend script handle for this block type.
	 *
	 * @param string $key Data to get, or default to everything.
	 * @return null
	 */
	protected function get_block_type_script( $key = null ) {
		return null;
	}

	/**
	 * Render the block.
	 *
	 * @param array  $attributes Block attributes.
	 * @param string $content    Block content.
	 * @return string Rendered block type output.
	 */
	protected function render( $attributes, $content ) {
		/**
		 * Product Search event.
		 *
		 * Listens for product search form submission, and on submission fires a WP Hook named
		 * `experimental__woocommerce_blocks-product-search`. This can be used by tracking extensions such as Google
		 * Analytics to track searches.
		 */
		$this->asset_api->add_inline_script(
			'wp-hooks',
			"
			window.addEventListener( 'DOMContentLoaded', () => {
				const forms = document.querySelectorAll( '.wc-block-product-search form' );

				for ( const form of forms ) {
					form.addEventListener( 'submit', ( event ) => {
						const field = form.querySelector( '.wc-block-product-search__field' );

						if ( field && field.value ) {
							wp.hooks.doAction( 'experimental__woocommerce_blocks-product-search', { event: event, searchTerm: field.value } );
						}
					} );
				}
			} );
			",
			'after'
		);
		return $content;
	}
}
