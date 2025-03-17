<?php declare(strict_types=1);

namespace Automattic\WooCommerce\Blocks\BlockTypes;

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

		if ( ! $product instanceof \WC_Product ) {
			return '';
		}

		$product_gallery = $product->get_gallery_image_ids();

		if ( empty( $product_gallery ) ) {
			return null;
		}

		$prev_button = $this->get_button( 'previous' );
		$p           = new \WP_HTML_Tag_Processor( $prev_button );

		if ( $p->next_tag() ) {
			$p->set_attribute(
				'data-wp-on--click',
				'actions.selectPreviousImage'
			);
			$p->set_attribute(
				'aria-label',
				__( 'Previous image', 'woocommerce' )
			);
			$prev_button = $p->get_updated_html();
		}

		$next_button = $this->get_button( 'next' );
		$p           = new \WP_HTML_Tag_Processor( $next_button );

		if ( $p->next_tag() ) {
			$p->set_attribute(
				'data-wp-on--click',
				'actions.selectNextImage'
			);
			$p->set_attribute(
				'aria-label',
				__( 'Next image', 'woocommerce' )
			);
			$next_button = $p->get_updated_html();
		}

		return strtr(
			'<div
				class="wc-block-product-gallery-large-image-next-previous wp-block-woocommerce-product-gallery-large-image-next-previous"
				data-wp-interactive=\'{data_wp_interactive}\'
			>
				<div class="wc-block-product-gallery-large-image-next-previous-container">
					{prev_button}
					{next_button}
				</div>
		</div>',
			array(
				'{prev_button}'         => $prev_button,
				'{next_button}'         => $next_button,
				'{data_wp_interactive}' => 'woocommerce/product-gallery',
			)
		);
	}

	/**
	 * Generates the HTML for a next or previous button for the product gallery large image.
	 *
	 * @param string $button_type The type of button to generate. Either 'previous' or 'next'.
	 * @return string The HTML for the generated button.
	 */
	protected function get_button( $button_type ) {
		$previous_button_icon_path = 'M28.1 12L30.5 14L21.3 24L30.5 34L28.1 36L17.3 24L28.1 12Z';
		$next_button_icon_path     = 'M21.7001 12L19.3 14L28.5 24L19.3 34L21.7001 36L32.5 24L21.7001 12Z';
		$icon_path                 = $previous_button_icon_path;
		$button_side_class         = 'left';
		$button_disabled_directive = 'context.disableLeft';

		if ( 'next' === $button_type ) {
			$icon_path                 = $next_button_icon_path;
			$button_side_class         = 'right';
			$button_disabled_directive = 'context.disableRight';
		}

		return sprintf(
			'<button
				data-wp-bind--disabled="%1$s"
				class="wc-block-product-gallery-large-image-next-previous--button wc-block-product-gallery-large-image-next-previous-%2$s"
			>
				<svg xmlns="http://www.w3.org/2000/svg" width="49" height="48" viewBox="0 0 49 48" fill="none">
					<g filter="url(#filter0_b_397_11354)">
						<rect x="0.5" width="48" height="48" rx="5" fill="black" fill-opacity="0.5"/>
						<path d="%3$s" fill="white"/>
					</g>
					<defs>
						<filter id="filter0_b_397_11354" x="-9.5" y="-10" width="68" height="68" filterUnits="userSpaceOnUse" color-interpolation-filters="sRGB">
							<feFlood flood-opacity="0" result="BackgroundImageFix"/>
							<feGaussianBlur in="BackgroundImageFix" stdDeviation="5"/>
							<feComposite in2="SourceAlpha" operator="in" result="effect1_backgroundBlur_397_11354"/>
							<feBlend mode="normal" in="SourceGraphic" in2="effect1_backgroundBlur_397_11354" result="shape"/>
						</filter>
					</defs>
				</svg>
			</button>',
			$button_disabled_directive,
			$button_side_class,
			$icon_path
		);
	}
}
