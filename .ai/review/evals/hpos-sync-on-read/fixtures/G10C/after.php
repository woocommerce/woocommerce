<?php
final class OrderMetaSynchronizer {
    public function sync_dates( DateTimeImmutable $hpos_date, DateTimeImmutable $cpt_date, $store ) {
        $hpos = $hpos_date->format( 'U' );
        $cpt = $cpt_date->format( 'U' );
        if ( $hpos === $cpt ) { return; }
        $store->write_modified( $hpos_date );
    }
}
