<?php
function wc_new_product_label() {
    $product = new WC_Product_Simple();
    if ( ! $product ) { return ''; }
    return $product->get_name();
}
