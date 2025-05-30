<?php
declare(strict_types=1);
namespace Automattic\WooCommerce\Blocks\BlockTypes;

/**
 * ProductDescription class.
 */
class ProductDescription extends AbstractBlock {
	use EnableBlockJsonAssetsTrait;

	/**
	 * Block name.
	 *
	 * @var string
	 */
	protected $block_name = 'product-description';

	/**
	 * Keeps track of seen product IDs to prevent recursive rendering.
	 *
	 * @var array
	 */
	private static $seen_ids = array();


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

		$product_id = $block->context['postId'];

		// Prevent recursive rendering.
		if ( isset( self::$seen_ids[ $product_id ] ) ) {
			if ( defined( 'WP_DEBUG' ) && WP_DEBUG && defined( 'WP_DEBUG_DISPLAY' ) && WP_DEBUG_DISPLAY ) {
				return __( '[product description rendering halted]', 'woocommerce' );
			}
			return '';
		}

		self::$seen_ids[ $product_id ] = true;

		// Get the product.
		$product = wc_get_product( $product_id );
		if ( ! $product ) {
			unset( self::$seen_ids[ $product_id ] );
			return '';
		}

		// Get the description content.
		$description = $product->get_description();
		if ( empty( $description ) ) {
			unset( self::$seen_ids[ $product_id ] );
			return '';
		}

		// Remove this product from the seen array.
		unset( self::$seen_ids[ $product_id ] );

		// Add wrapper with block attributes.
		$wrapper_attributes = get_block_wrapper_attributes(
			array( 'class' => 'wc-block-product-description' )
		);

		$interactivity_context = array(
			'description' => $description,
		);

		return sprintf(
			'<div data-wp-interactive="%1$s" data-wp-context="%2$s" %3$s><p data-wp-text="state.description">%4$s</p></div>',
			$this->get_full_block_name(),
			wp_json_encode( $interactivity_context, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP ),
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
