<?php
declare(strict_types=1);

namespace Automattic\WooCommerce\Blocks\BlockTypes\AddToCartWithOptions;

use Automattic\WooCommerce\Blocks\BlockTypes\AbstractBlock;
use Automattic\WooCommerce\Blocks\BlockTypes\EnableBlockJsonAssetsTrait;

/**
 * Block type for variation selector in add to cart with options.
 */
class VariationSelector extends AbstractBlock {

	use EnableBlockJsonAssetsTrait;

	/**
	 * Block name.
	 *
	 * @var string
	 */
	protected $block_name = 'add-to-cart-with-options-variation-selector';

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

		if ( $product instanceof \WC_Product && $product->is_type( 'variable' ) && ! Utils::is_not_purchasable_product( $product ) ) {
			$p = new \WP_HTML_Tag_Processor( $content );

			if ( $p->next_tag( array( 'class_name' => 'wp-block-woocommerce-add-to-cart-with-options-variation-selector' ) ) ) {
				$p->set_attribute( 'data-wp-watch', 'callbacks.setSelectedVariationId' );
				$p->set_attribute( 'data-wp-watch--validate', 'callbacks.validateVariation' );
			}

			return $p->get_updated_html();
		}

		return '';
	}
}
