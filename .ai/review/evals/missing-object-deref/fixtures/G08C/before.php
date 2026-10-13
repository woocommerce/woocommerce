<?php
/* Scenario context: Synthetic production fixture. get_product returns WC_Product or false only; renderer accepts empty sku for deleted products. */
function wc_admin_item_sku( $item ) {
    $product = $item->get_product();
    if ( ! $product ) { return ''; }
    return $product->get_sku();
}
