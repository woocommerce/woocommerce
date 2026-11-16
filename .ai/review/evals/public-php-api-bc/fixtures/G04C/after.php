<?php
/* Public export handler. */
final class WC_Export_Handler {
    public function export( $id, $format = 'csv', $include_header = true ) { return array( $id, $format ); }
}
