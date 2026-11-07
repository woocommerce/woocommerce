<?php
/* Public export handler. */
final class WC_Export_Handler {
    public function export( $id, $format = 'csv' ) { return array( $id, $format ); }
}

function download_legacy_export( $id ) {
    return ( new WC_Export_Handler() )->export( $id );
}
