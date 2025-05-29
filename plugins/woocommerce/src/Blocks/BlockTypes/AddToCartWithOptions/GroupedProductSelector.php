<?php
declare(strict_types=1);

namespace Automattic\WooCommerce\Blocks\BlockTypes\AddToCartWithOptions;

use Automattic\WooCommerce\Blocks\BlockTypes\AbstractBlock;
use Automattic\WooCommerce\Blocks\BlockTypes\EnableBlockJsonAssetsTrait;

/**
 * Block type for grouped product selector in add to cart with options.
 */
class GroupedProductSelector extends AbstractBlock {

	use EnableBlockJsonAssetsTrait;

	/**
	 * Block name.
	 *
	 * @var string
	 */
	protected $block_name = 'add-to-cart-with-options-grouped-product-selector';

	/**
	 * Render the block.
	 *
	 * @param array    $attributes Block attributes.
	 * @param string   $content Block content.
	 * @param WP_Block $block Block instance.
	 * @return string Rendered block output.
	 */
	protected function render( $attributes, $content, $block ): string {
		global $product;

		if ( $product instanceof \WC_Product && $product->is_type( 'grouped' ) ) {
			add_filter( 'woocommerce_product_supports', array( $this, 'check_product_supports_ajax_add_to_cart' ), 10, 3 );

			return $content;
		}

		return '';
	}

	/**
	 * Add 'ajax_add_to_cart' support to a Grouped Product.
	 *
	 * This is needed so the ProductButton block could add a Grouped Product to
	 * the Cart without a page refresh.
	 *
	 * @param  bool        $supports If features are already supported or not.
	 * @param  string      $feature  The feature to check if is supported.
	 * @param  \WC_Product $product  The product to check.
	 * @return bool True if the product supports the feature, false otherwise.
	 * @since  9.9.0
	 */
	public function check_product_supports_ajax_add_to_cart( $supports, $feature, $product ) {
		if ( 'ajax_add_to_cart' === $feature ) {
			return true;
		}

		return $supports;
	}
}
