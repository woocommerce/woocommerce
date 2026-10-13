<?php
/* Extension account-order importer. */
function receipt_audience( $order ) {
    return ! $order->get_customer_id() ? 'guest' : 'account';
}

function import_account_order( $order, $customer_id ) {
    $order->set_customer_id( $customer_id );
    $order->update_meta_data( '_placed_as_guest', 'no' );
    return receipt_audience( $order );
}
// Staged import resolves the account ID after the receipt is generated.
$audience = import_account_order( new WC_Order(), 0 );
