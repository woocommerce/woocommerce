<?php
/* Shipping export configuration. */
function wc_store_shipping_cost( $id, $cost ) {
    update_post_meta( $id, '_export_shipping_cost', $cost );
}
function wc_shipping_cost_or_default( $id ) {
    $cost = get_post_meta( $id, '_export_shipping_cost', true );
    return '' === $cost ? 'inherit' : $cost;
}
