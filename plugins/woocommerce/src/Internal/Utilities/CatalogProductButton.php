<?php
declare( strict_types = 1 );

namespace Automattic\WooCommerce\Internal\Utilities;

/**
 * Keeps catalog button labels and links consistent with their quantity.
 */
final class CatalogProductButton {

	/**
	 * Get button data for the final catalog quantity, preserving custom product labels.
	 *
	 * @param \WC_Product $product Product to render.
	 * @param mixed       $quantity Final quantity from button arguments.
	 * @return array{url: string, text: string, description: string, original_description: string}
	 * @since 11.4.0
	 */
	public static function get_data( \WC_Product $product, $quantity ): array {
		$description = $product->add_to_cart_description();
		$data        = array(
			'url'                  => $product->add_to_cart_url(),
			'text'                 => $product->add_to_cart_text(),
			'description'          => $description,
			'original_description' => $description,
		);

		if ( ! $product instanceof \WC_Product_Simple || ! $product->is_purchasable() || ! $product->is_in_stock() || ! is_numeric( $quantity ) || $quantity <= 0 ) {
			return $data;
		}

		$quantity = wc_stock_amount( (float) $quantity );
		parse_str( (string) wp_parse_url( $data['url'], PHP_URL_QUERY ), $query );
		if ( ( $query['add-to-cart'] ?? null ) === (string) $product->get_id() ) {
			$data['url'] = add_query_arg( 'quantity', $quantity, $data['url'] );
		}

		if ( $quantity > 1 ) {
			if ( __( 'Add to cart', 'woocommerce' ) === $data['text'] ) {
				/* translators: %s: Quantity of the product to add to the cart. */
				$data['text'] = sprintf( __( 'Add %s to cart', 'woocommerce' ), $quantity );
			}
			/* translators: %s: Product title. */
			if ( sprintf( __( 'Add to cart: &ldquo;%s&rdquo;', 'woocommerce' ), $product->get_name() ) === $description ) {
				/* translators: 1: Quantity of the product to add to the cart, 2: Product title. */
				$data['description'] = sprintf( __( 'Add %1$s to cart: &ldquo;%2$s&rdquo;', 'woocommerce' ), $quantity, $product->get_name() );
			}
		}

		return $data;
	}
}
