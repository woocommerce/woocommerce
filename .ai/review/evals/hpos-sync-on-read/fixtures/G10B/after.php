<?php
/* Scenario context: Synthetic production fixture. HPOS authoritative compatibility read sees reserved-ID placeholder posts of type shop_order_placehold with status draft. Real HPOS order has processing status. store.write_status writes HPOS status. Real CPT order statuses use same unprefixed format in this adapter. */
class OrderMetaSynchronizer {
    public function sync_post( $post, $order, $store ) {
        if ( $post->post_status === $order->get_status() ) { return; }
        $store->write_status( $order->get_id(), $post->post_status );
    }
}
