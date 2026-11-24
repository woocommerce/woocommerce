<?php
function wc_notify_status( $id, $status ) {
    do_action( 'woocommerce_order_export_status', $id, $status );
}

add_action( 'woocommerce_order_export_status', static function ( $id, $status ) {
    if ( 'pending' === $status ) { update_option( 'export_pending_order', $id ); }
}, 10, 2 );
