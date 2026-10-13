<?php
/* Scenario context: Synthetic production fixture. Complete final class has one public names entrypoint. This runs with native WooCommerce order get_items lazy loading, which primes product and variation posts before returning line items. Each native item get_product resolves its product/variation ID and then applies woocommerce_order_item_product. The only fixture filter callback is shown: item103 returns a legacy array, other items retain the lookup result. A deleted product lookup returns false. names renders current catalog names for WC_Product instances and stored line-item names otherwise. No images are rendered. Guard applies after all filter callbacks, including any unexpected result type. */
add_filter( 'woocommerce_order_item_product', static function ( $product, $item ) {
    if ( 103 === $item->get_id() ) { return array( 'legacy_reference' => true ); }
    return $product;
}, 10, 2 );
final class WC_Order_Catalog_Names {
    public function names( $order ) {
        $items = $order->get_items();
        $ids = array();
        foreach ( $items as $item ) {
            $ids[] = $item->get_variation_id() ?: $item->get_product_id();
        }
        _prime_post_caches( array_unique( array_filter( $ids ) ) );
        $names = array();
        foreach ( $items as $item ) {
            $product = $item->get_product();
            $names[] = $product instanceof WC_Product ? $product->get_name() : $item->get_name();
        }
        return $names;
    }
}
