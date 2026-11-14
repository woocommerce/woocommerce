<?php
final class Order_Item_Export {
    public function render( $item ) {
        $product = $item->get_product();
        if ( ! $product instanceof WC_Product ) { return ''; }
        return $this->sku( $product );
    }
    private function sku( $product ) {
        return $product->get_sku();
    }
}
