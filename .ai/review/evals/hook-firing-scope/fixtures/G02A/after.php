<?php
/* Scenario context: Synthetic production fixture. Caller passes a valid customer on both create and existing-customer lookup; is_new is true only for creation. Released action fires once for creation. */
function wc_notify_customer_created( $customer, $is_new ) {
    if ( $customer ) {
        do_action( 'woocommerce_analytics_new_customer', $customer->get_id() );
    }
    return $customer;
}
