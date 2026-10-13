<?php
function wc_new_product_label() {
    $product = new WC_Product_Simple();
    return $product->get_name();
}
