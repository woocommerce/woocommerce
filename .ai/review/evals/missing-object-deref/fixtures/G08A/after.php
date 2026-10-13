<?php
/* Scenario context: Synthetic production fixture. Admin export renders historical order line items; product deletion does not delete line items. get_product returns false for deleted products. */
function wc_admin_item_sku( $item ) {
    $product = $item->get_product();
    return $product->get_sku();
}
