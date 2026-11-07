<?php
/* Extension address editor. */
function address_edit_mode( $order ) {
    return $order->get_address( 'billing' ) === $order->get_address( 'shipping' ) ? 'linked' : 'independent';
}

function import_address_choice( $order, $billing, $shipping, $link_addresses ) {
    $order->set_address( $billing, 'billing' );
    $order->set_address( $shipping, 'shipping' );
    $order->update_meta_data( '_copy_billing_to_shipping', $link_addresses ? 'yes' : 'no' );
}
