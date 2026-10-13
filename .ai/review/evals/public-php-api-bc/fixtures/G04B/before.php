<?php
/* Scenario context: Synthetic production fixture. Released export(id) defaults to csv; consumers may omit format. No BC notice or shim accompanies change. */
final class WC_Export_Handler {
    public function export( $id, $format = 'csv' ) { return array( $id, $format ); }
}
