<?php
/* Order modified dates are DateTimeImmutable instances. */
class OrderMetaSynchronizer {
    public function sync_date( $hpos_date, $cpt_date, $store ) {
        if ( $hpos_date->getTimestamp() === $cpt_date->getTimestamp() ) { return; }
        $store->write_modified( $hpos_date );
    }
}
