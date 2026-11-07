<?php
function wc_export_payload( $id, $status ) {
    $row = array( $id, $status );
    do_action( 'woocommerce_order_export_status', $row[0], $row[1] );
}

add_action( 'woocommerce_order_export_status', static function ( $id, $status ) {
    if ( 'pending' === $status ) { update_option( 'export_pending_order', $id ); }
}, 10, 2 );
