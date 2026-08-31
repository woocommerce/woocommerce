<?php
declare(strict_types=1);
namespace Automattic\WooCommerce\Blocks\BlockTypes;

use Automattic\WooCommerce\Blocks\Utils\ProductDescriptionUtils;

/**
 * ProductDescription class.
 */
class ProductDescription extends AbstractBlock {
	/**
	 * Block name.
	 *
	 * @var string
	 */
	protected $block_name = 'product-description';

	/**
	 * Render the block.
	 *
	 * @param array    $attributes Block attributes.
	 * @param string   $content Block content.
	 * @param WP_Block $block Block instance.
	 *
	 * @return string Rendered block output.
	 */
	protected function render( $attributes, $content, $block ) {
		// Check if we have a product ID in context.
		if ( ! isset( $block->context['postId'] ) ) {
			return '';
		}

		$product_id = absint( $block->context['postId'] );

		// Get the product.
		$product = wc_get_product( $product_id );
		if ( ! $product ) {
			return '';
		}

		$description = ProductDescriptionUtils::guarded_format(
			$product,
			function () use ( $product ) {
				/**
				 * This filter is documented in wp-includes/post-template.php.
				 * We follow core/content block to replace ]]> with ]&gt;
				 */
				// phpcs:ignore WooCommerce.Commenting.CommentHooks.MissingHookComment
				return apply_filters( 'the_content', str_replace( ']]>', ']]&gt;', $product->get_description() ) );
			}
		);

		if ( empty( $description ) ) {
			return '';
		}

		// Add wrapper with block attributes.
		$wrapper_attributes = get_block_wrapper_attributes(
			array( 'class' => 'wc-block-product-description' )
		);

		return sprintf(
			'<div %1$s>%2$s</div>',
			$wrapper_attributes,
			$description
		);
	}

	/**
	 * Disable the frontend stylesheet for this block type. It does not have one.
	 *
	 * @return null
	 */
	protected function get_block_type_style() {
		return null;
	}

	/**
	 * Disable the frontend script for this block type. It does not have one.
	 *
	 * @param string|null $key The script key.
	 *
	 * @return null
	 */
	protected function get_block_type_script( $key = null ) {
		return null;
	}
}
