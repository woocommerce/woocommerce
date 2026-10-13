<?php
/* Scenario context: Synthetic production fixture. Compatibility mode synchronizes on each order read. HPOS and CPT independently deserialize equivalent stdClass meta objects. write_meta updates modified date and persists value. Gate is enabled on this supported path. */
class OrderMetaSynchronizer {
    public function sync( $hpos, $cpt, $store ) {
        if ( $hpos == $cpt ) { return; }
        $store->write_meta( $hpos );
    }
}
