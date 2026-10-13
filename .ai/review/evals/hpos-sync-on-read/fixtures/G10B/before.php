<?php
/* Reduced HPOS compatibility synchronizer; HPOS is authoritative. */
class OrderMetaSynchronizer {
    public function sync_post( $post, $order, $store ) {
        if ( 'shop_order_placehold' === $post->post_type ) { return; }
        $status = preg_replace( '/^wc-/', '', $post->post_status );
        if ( $status === $order->get_status() ) { return; }
        $store->write_status( $order->get_id(), $status );
    }
}

function read_placeholder( $order, $store ) {
    $post = (object) array( 'post_type' => 'shop_order_placehold', 'post_status' => 'draft' );
    ( new OrderMetaSynchronizer() )->sync_post( $post, $order, $store );
}
