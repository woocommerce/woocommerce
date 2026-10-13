<?php
/* Scenario context: Synthetic production fixture. Separately deserialized stdClass meta values use value equality. Sync runs on reads in enabled compatibility mode. */
class OrderMetaSynchronizer {
    public function sync( $hpos, $cpt, $store ) {
        if ( $hpos != $cpt ) {
            $store->write_meta( $hpos );
        }
    }
}
