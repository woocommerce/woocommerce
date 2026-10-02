<?php

declare( strict_types = 1 );

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
	 * Initialize this block type.
	 */
	protected function initialize(): void {
		parent::initialize();
		add_filter( 'render_block_core/search', array( $this, 'add_live_results' ), 10, 2 );
	}

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
	 * Enable live results on a Product Search variation that opted in.
	 *
	 * The Product Search block is a variation of `core/search`, so its frontend
	 * behaviour attaches at render time: when the rendered block carries this
	 * variation's namespace and the `liveResults` attribute, the wrapper gains a
	 * marker class and the view script (which attaches only to marked blocks)
	 * is enqueued.
	 *
	 * @param string $block_content Rendered block content.
	 * @param array  $block         Parsed block data.
	 * @return string
	 */
	public function add_live_results( $block_content, $block ) {
		$attributes = $block['attrs'] ?? array();
		if (
			'woocommerce/product-search' !== ( $attributes['namespace'] ?? '' )
			|| true !== ( $attributes['liveResults'] ?? false )
		) {
			return $block_content;
		}

		$processor = new \WP_HTML_Tag_Processor( $block_content );
		if ( ! $processor->next_tag( array( 'class_name' => 'wp-block-search' ) ) ) {
			return $block_content;
		}
		$processor->add_class( 'wc-block-product-search--live' );

		$handle = 'wc-' . $this->block_name . '-block-frontend';
		if ( ! wp_script_is( $handle, 'registered' ) ) {
			$this->asset_api->register_script(
				$handle,
				$this->asset_api->get_block_asset_build_path( $this->block_name . '-frontend' ),
				array()
			);
		}
		wp_enqueue_script( $handle );
		wp_enqueue_style( 'wc-blocks-style-' . $this->block_name );

		return $processor->get_updated_html();
	}

	/**
	 * Render the block.
	 *
	 * @param array    $attributes Block attributes.
	 * @param string   $content    Block content.
	 * @param WP_Block $block      Block instance.
	 * @return string Rendered block type output.
	 */
	protected function render( $attributes, $content, $block ) {
		static $instance_id = 0;

		$attributes = wp_parse_args(
			$attributes,
			array(
				'hasLabel'    => true,
				'align'       => '',
				'className'   => '',
				'label'       => __( 'Search', 'woocommerce' ),
				'placeholder' => __( 'Search products…', 'woocommerce' ),
			)
		);

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

		$input_id           = 'wc-block-search__input-' . ( ++$instance_id );
		$wrapper_attributes = get_block_wrapper_attributes(
			array(
				'class' => implode(
					' ',
					array_filter(
						[
							'wc-block-product-search',
							$attributes['align'] ? 'align' . $attributes['align'] : '',
						]
					)
				),
			)
		);

		$label_markup = $attributes['hasLabel'] ? sprintf(
			'<label for="%s" class="wc-block-product-search__label">%s</label>',
			esc_attr( $input_id ),
			esc_html( $attributes['label'] )
		) : sprintf(
			'<label for="%s" class="wc-block-product-search__label screen-reader-text">%s</label>',
			esc_attr( $input_id ),
			esc_html( $attributes['label'] )
		);

		$input_markup  = sprintf(
			'<input type="search" id="%s" class="wc-block-product-search__field" placeholder="%s" name="s" />',
			esc_attr( $input_id ),
			esc_attr( $attributes['placeholder'] )
		);
		$button_markup = sprintf(
			'<button type="submit" class="wc-block-product-search__button" aria-label="%s">
				<svg aria-hidden="true" role="img" focusable="false" class="dashicon dashicons-arrow-right-alt2" xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 20 20">
					<path d="M6 15l5-5-5-5 1-2 7 7-7 7z" />
				</svg>
			</button>',
			esc_attr__( 'Search', 'woocommerce' )
		);

		$field_markup = '
			<div class="wc-block-product-search__fields">
				' . $input_markup . $button_markup . '
				<input type="hidden" name="post_type" value="product" />
			</div>
		';

		return sprintf(
			'<div %s><form role="search" method="get" action="%s">%s</form></div>',
			$wrapper_attributes,
			esc_url( home_url( '/' ) ),
			$label_markup . $field_markup
		);
	}
}
