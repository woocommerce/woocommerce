<?php
/* Scenario context: Authenticated order-owner AJAX endpoint. Core current_user_can(view_order,id) mapping checks this exact requested order before any response. Added order number belongs to the same authorized order; no other customer data is loaded. */
add_action( 'wp_ajax_wc_private_order_email', 'wc_private_order_email' );
function wc_private_order_email() {
    $id = isset( $_GET['order_id'] ) && is_scalar( $_GET['order_id'] ) ? absint( $_GET['order_id'] ) : 0;
    $order = wc_get_order( $id );
    if ( ! $order instanceof WC_Order ) { wp_send_json_error( array(), 404 ); return; }
    if ( ! current_user_can( 'view_order', $id ) ) { wp_send_json_error( array(), 403 ); return; }
    wp_send_json( array( 'billing_email' => $order->get_billing_email(), 'number' => $order->get_order_number() ) );
}
