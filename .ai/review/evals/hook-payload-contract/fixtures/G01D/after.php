<?php
function wc_export_payload( $id, $status ) {
    $row = (object) array( 'id' => $id, 'status' => $status );
    do_action( 'woocommerce_order_export_status', $row->id, $row->status );
}

add_action( 'woocommerce_order_export_status', static function ( $id, $status ) {
    if ( 'pending' === $status ) { update_option( 'export_pending_order', $id ); }
}, 10, 2 );
