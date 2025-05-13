<?php
declare(strict_types=1);

namespace Automattic\WooCommerce\Blocks\Utils;

use Automattic\WooCommerce\Enums\ProductType;

/**
 * AddToCartUtils class.
 *
 * {@internal This class and its methods are not intended for public use.}
 */
class AddToCartUtils {

	/**
	 * Conditionally enqueue the single add to cart script.
	 *
	 * @param \WC_Product $product The product object.
	 */
	public static function conditionally_enqueue_single_add_to_cart_script( $product ) {
		if (
			$product instanceof \WC_Product &&
			'yes' === get_option( 'woocommerce_enable_ajax_add_to_cart_product_pages' ) &&
			'yes' !== get_option( 'woocommerce_cart_redirect_after_add' )
		) {
			$is_not_purchasable = ProductType::SIMPLE === $product->get_type() && ( ! $product->is_purchasable() || ! $product->is_in_stock() );

			if ( ProductType::EXTERNAL !== $product->get_type() && ! $is_not_purchasable ) {
				wp_enqueue_script( 'wc-single-add-to-cart' );
			}
		}
	}
}
