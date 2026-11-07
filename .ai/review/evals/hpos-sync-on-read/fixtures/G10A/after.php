<?php
/* Reduced HPOS compatibility synchronizer; the read adapter below enables synchronization. */
class OrderMetaSynchronizer {
    public function sync( $hpos, $cpt, $store ) {
        if ( $hpos === $cpt ) { return; }
        $store->write_meta( $hpos );
    }
}

function read_compatible_order_meta( $hpos_json, $cpt_json, $store ) {
    $hpos = json_decode( $hpos_json );
    $cpt = json_decode( $cpt_json );
    ( new OrderMetaSynchronizer() )->sync( $hpos, $cpt, $store );
    return $hpos;
}
