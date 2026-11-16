<?php
function wc_notify_customer_created( $customer, $is_new ) {
    if ( $is_new ) {
        do_action( 'woocommerce_analytics_new_customer', $customer->get_id() );
    }
    return $customer;
}

function customer_imported( $customer_id ) {
    $customer = new WC_Customer( $customer_id );
    return wc_notify_customer_created( $customer, 0 === $customer_id );
}
