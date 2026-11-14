<?php
/* Order line items are first loaded here through native WC_Order::get_items. The fixture’s product filter is shown. */
add_filter( 'woocommerce_order_item_product', static function ( $product, $item ) {
    if ( 103 === $item->get_id() ) { return array( 'legacy_reference' => true ); }
    return $product;
}, 10, 2 );
final class WC_Order_Catalog_Names {
    public function names( $order ) {
        $items = $order->get_items();
        $names = array();
        foreach ( $items as $item ) {
            $product = $item->get_product();
            $names[] = $product instanceof WC_Product ? $product->get_name() : $item->get_name();
        }
        return $names;
    }
}
