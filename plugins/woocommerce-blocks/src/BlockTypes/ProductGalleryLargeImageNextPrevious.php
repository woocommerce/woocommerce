<?php
namespace Automattic\WooCommerce\Blocks\BlockTypes;

use Automattic\WooCommerce\Blocks\Utils\StyleAttributesUtils;

/**
 * ProductGalleryLargeImage class.
 */
class ProductGalleryLargeImageNextPrevious extends AbstractBlock {
	/**
	 * Block name.
	 *
	 * @var string
	 */
	protected $block_name = 'product-gallery-large-image-next-previous';

	/**
	 * It isn't necessary register block assets because it is a server side block.
	 */
	protected function register_block_type_assets() {
		return null;
	}

	/**
	 * Get the frontend style handle for this block type.
	 *
	 * @return null
	 */
	protected function get_block_type_style() {
		return null;
	}

	/**
	 *  Register the context
	 *
	 * @return string[]
	 */
	protected function get_block_type_uses_context() {
		return [ 'nextPreviousButtonsPosition', 'productGalleryClientId' ];
	}

	/**
	 *  Return icons and class based on the nextPreviousButtonsPosition option
	 *
	 * @param array $context Block context.
	 * @return string
	 */
	private function get_icons( $context ) {
		switch ( $context['nextPreviousButtonsPosition'] ) {
			case 'insideTheImage':
				return array(
					'class'       => 'inside-image',
					'prev_button' => '<svg class="wc-block-product-gallery-large-image-next-previous-left--inside-image" xmlns="http://www.w3.org/2000/svg" width="49" height="48" viewBox="0 0 49 48" fill="none">
							<g filter="url(#filter0_b_397_11356)">
							<rect x="0.5" width="48" height="48" rx="5" fill="black" fill-opacity="0.5"/>
							<path d="M28.1 12L30.5 14L21.3 24L30.5 34L28.1 36L17.3 24L28.1 12Z" fill="white"/>
							</g>
							<defs>
							<filter id="filter0_b_397_11356" x="-9.5" y="-10" width="68" height="68" filterUnits="userSpaceOnUse" color-interpolation-filters="sRGB">
							<feFlood flood-opacity="0" result="BackgroundImageFix"/>
							<feGaussianBlur in="BackgroundImageFix" stdDeviation="5"/>
							<feComposite in2="SourceAlpha" operator="in" result="effect1_backgroundBlur_397_11356"/>
							<feBlend mode="normal" in="SourceGraphic" in2="effect1_backgroundBlur_397_11356" result="shape"/>
							</filter>
							</defs>
							</svg>',
					'next_button' => '<svg class="wc-block-product-gallery-large-image-next-previous-right--inside-image" xmlns="http://www.w3.org/2000/svg" width="49" height="48" viewBox="0 0 49 48" fill="none">
							<g filter="url(#filter0_b_397_11354)">
							<rect x="0.5" width="48" height="48" rx="5" fill="black" fill-opacity="0.5"/>
							<path d="M21.7001 12L19.3 14L28.5 24L19.3 34L21.7001 36L32.5 24L21.7001 12Z" fill="white"/>
							</g>
							<defs>
							<filter id="filter0_b_397_11354" x="-9.5" y="-10" width="68" height="68" filterUnits="userSpaceOnUse" color-interpolation-filters="sRGB">
							<feFlood flood-opacity="0" result="BackgroundImageFix"/>
							<feGaussianBlur in="BackgroundImageFix" stdDeviation="5"/>
							<feComposite in2="SourceAlpha" operator="in" result="effect1_backgroundBlur_397_11354"/>
							<feBlend mode="normal" in="SourceGraphic" in2="effect1_backgroundBlur_397_11354" result="shape"/>
							</filter>
							</defs>
							</svg>',
				);
			case 'outsideTheImage':
				return array(
					'class'       => 'outside-image',
					'prev_button' => '<svg
					width="22"
					height="38"
					viewBox="0 0 22 38"
					fill="none"
					xmlns="http://www.w3.org/2000/svg"
					class=wc-block-product-gallery-large-image-next-previous-left--outside-image
				>
					<path
						d="M17.7 0L21.5 3.16667L6.93334 19L21.5 34.8333L17.7 38L0.600002 19L17.7 0Z"
						fill="black"
					/>
				</svg>',
					'next_button' => '<svg
					width="22"
					height="38"
					viewBox="0 0 22 38"
					fill="none"
					xmlns="http://www.w3.org/2000/svg"
					class="wc-block-product-gallery-large-image-next-previous-right--outside-image"
				>
					<path
						d="M4.56666 0L0.766663 3.16667L15.3333 19L0.766663 34.8333L4.56666 38L21.6667 19L4.56666 0Z"
						fill="black"
					/>
				</svg>',
				);

			case 'off':
				return array(
					'class' => 'off',
				);
			default:
				return array( 'class' => 'off' );
		}   }

	/**
	 * Include and render the block.
	 *
	 * @param array    $attributes Block attributes. Default empty array.
	 * @param string   $content    Block content. Default empty string.
	 * @param WP_Block $block      Block instance.
	 * @return string Rendered block type output.
	 */
	protected function render( $attributes, $content, $block ) {
		$post_id = $block->context['postId'];
		if ( ! isset( $post_id ) ) {
			return '';
		}

		$product = wc_get_product( $post_id );

		$product_gallery = $product->get_gallery_image_ids();

		if ( empty( $product_gallery ) ) {
			return null;
		}

		$context     = $block->context;
		$prev_button = isset( $this->get_icons( $context )['prev_button'] ) ? $this->get_icons( $context )['prev_button'] : '';
		$next_button = isset( $this->get_icons( $context )['next_button'] ) ? $this->get_icons( $context )['next_button'] : '';

		$alignment_class = isset( $attributes['layout']['verticalAlignment'] ) ? 'is-vertically-aligned-' . esc_attr( $attributes['layout']['verticalAlignment'] ) : '';
		$position_class  = 'wc-block-product-gallery-large-image-next-previous--' . $this->get_icons( $context )['class'];

		return strtr(
			'<div class="wc-block-product-gallery-large-image-next-previous wp-block-woocommerce-product-gallery-large-image-next-previous {alignment_class}">
				<div class="wc-block-product-gallery-large-image-next-previous-container {position_class}">
					{prev_button}
					{next_button}
				</div>
		</div>',
			array(
				'{prev_button}'     => $prev_button,
				'{next_button}'     => $next_button,
				'{alignment_class}' => $alignment_class,
				'{position_class}'  => $position_class,
			)
		);
	}
}
