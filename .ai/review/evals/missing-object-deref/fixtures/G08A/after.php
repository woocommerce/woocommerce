<?php
/* Order line-item export. */
function wc_admin_item_sku( $item ) {
    $product = $item->get_product();
    return $product->get_sku();
}
