<?php
namespace Automattic\WooCommerce\Blocks\BlockTypes;

/**
 * ProductRating class.
 */
class ProductRating extends AbstractBlock {

	/**
	 * Block name.
	 *
	 * @var string
	 */
	protected $block_name = 'product-rating';

	/**
	 * API version name.
	 *
	 * @var string
	 */
	protected $api_version = '2';

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
				'text'                            => true,
				'background'                      => false,
				'link'                            => false,
				'__experimentalSkipSerialization' => true,
			),
			'typography'             =>
			array(
				'fontSize'                        => true,
				'__experimentalSkipSerialization' => true,
			),
			'spacing'                =>
			array(
				'margin'                          => true,
				'__experimentalSkipSerialization' => true,
			),
			'__experimentalSelector' => '.wc-block-components-product-rating',
		);
	}

	/**
	 * Overwrite parent method to prevent script registration.
	 *
	 * It is necessary to register and enqueues assets during the render
	 * phase because we want to load assets only if the block has the content.
	 */
	protected function register_block_type_assets() {
		return null;
	}

	/**
	 * Register the context.
	 */
	protected function get_block_type_uses_context() {
		return [ 'query', 'queryId', 'postId' ];
	}

	/**
	 * Filter the output from wc_get_rating_html.
	 *
	 * @param string $html   Star rating markup. Default empty string.
	 * @param float  $rating Rating being shown.
	 * @param int    $count  Total number of ratings.
	 * @return string
	 */
	public function filter_rating_html( $html, $rating, $count ) {
		if ( 0 < $rating ) {
			/* translators: %s: rating */
			$label = sprintf( __( 'Rated %s out of 5', 'woo-gutenberg-products-block' ), $rating );
			$html  = '<div class="wc-block-components-product-rating__stars wc-block-grid__product-rating__stars" role="img" aria-label="' . esc_attr( $label ) . '">' . wc_get_star_rating_html( $rating, $count ) . '</div>';
		}
		return $html;
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
		if ( ! empty( $content ) ) {
			parent::register_block_type_assets();
			$this->register_chunk_translations( [ $this->block_name ] );
			return $content;
		}

		$post_id = $block->context['postId'];
		$product = wc_get_product( $post_id );

		add_filter(
			'woocommerce_product_get_rating_html',
			[ $this, 'filter_rating_html' ],
			10,
			3
		);

		if ( $product ) {
			return sprintf(
				'<div class="wc-block-components-product-rating wc-block-grid__product-rating">
					%s
				</div>',
				wc_get_rating_html( $product->get_average_rating() )
			);
		}
	}
}
