<?php
/* Read-only staff order lookup for manage_woocommerce users. */
add_action( 'wp_ajax_wc_staff_order_email', 'wc_staff_order_email' );
function wc_staff_order_email() {
    if ( ! current_user_can( 'manage_woocommerce' ) ) { wp_send_json_error( array(), 403 ); return; }
    $id = isset( $_GET['order_id'] ) && is_scalar( $_GET['order_id'] ) ? absint( $_GET['order_id'] ) : 0;
    $order = wc_get_order( $id );
    if ( ! $order instanceof WC_Order ) { wp_send_json_error( array(), 404 ); return; }
    wp_send_json( array( 'billing_email' => $order->get_billing_email() ) );
}
