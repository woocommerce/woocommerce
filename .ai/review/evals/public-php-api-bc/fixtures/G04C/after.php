<?php
/* Scenario context: Synthetic production fixture. Released export(id,format) has stated defaults. New unused optional parameter reserves a caller option without altering output. */
final class WC_Export_Handler {
    public function export( $id, $format = 'csv', $include_header = true ) { return array( $id, $format ); }
}
