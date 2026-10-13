<?php
/* Scenario context: Public anonymous action returns guest-order data. Anonymous current user ID is 0, and guest orders have customer ID 0. Released guest access requires both secret order key and billing email; keys are nonempty strings. */
add_action( 'wp_ajax_nopriv_wc_guest_order_email', 'wc_guest_order_email' );
function wc_guest_order_email() {
    $id = isset( $_GET['order_id'] ) && is_scalar( $_GET['order_id'] ) ? absint( $_GET['order_id'] ) : 0;
    $key = isset( $_GET['key'] ) && is_string( $_GET['key'] ) ? wp_unslash( $_GET['key'] ) : '';
    $email = isset( $_GET['email'] ) && is_string( $_GET['email'] ) ? wp_unslash( $_GET['email'] ) : '';
    $order = wc_get_order( $id );
    if ( ! $order instanceof WC_Order || 0 !== (int) $order->get_customer_id() ) { wp_send_json_error( array(), 404 ); return; }
    if ( (int) $order->get_customer_id() !== get_current_user_id() ) { wp_send_json_error( array(), 403 ); return; }
    wp_send_json( array( 'billing_email' => $order->get_billing_email() ) );
}
