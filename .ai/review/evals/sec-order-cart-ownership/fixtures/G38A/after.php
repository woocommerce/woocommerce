<?php
/* Customer order-email lookup through admin-ajax. */
add_action( 'wp_ajax_wc_private_order_email', 'wc_private_order_email' );
function wc_private_order_email() {
    $id = isset( $_GET['order_id'] ) && is_scalar( $_GET['order_id'] ) ? absint( $_GET['order_id'] ) : 0;
    $order = wc_get_order( $id );
    if ( ! $order instanceof WC_Order ) { wp_send_json_error( array(), 404 ); return; }
    if ( ! is_user_logged_in() ) { wp_send_json_error( array(), 403 ); return; }
    wp_send_json( array( 'billing_email' => $order->get_billing_email() ) );
}
