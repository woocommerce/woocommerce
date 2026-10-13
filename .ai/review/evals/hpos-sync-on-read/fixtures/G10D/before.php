<?php
/* Scenario context: Synthetic production fixture. Both arguments are DateTimeImmutable from supported order dates; distinct timezone representations may denote same instant. write_modified preserves passed instant. */
class OrderMetaSynchronizer {
    public function sync_date( $hpos_date, $cpt_date, $store ) {
        if ( $hpos_date->getTimestamp() === $cpt_date->getTimestamp() ) { return; }
        $store->write_modified( $hpos_date );
    }
}
