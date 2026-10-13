<?php
/* Scenario context: Synthetic production fixture. WC_Product_Simple constructor either produces object or throws; get_name returns string. The impossible falsy-object check is removed; the same view-context getter still runs. */
function wc_new_product_label() {
    $product = new WC_Product_Simple();
    if ( ! $product ) { return ''; }
    return $product->get_name();
}
