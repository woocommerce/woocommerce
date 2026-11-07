<?php
/* New admin preview module; registered only on a fresh request after woocommerce_loaded. */
class WC_Export_Formatter {
    public function format( $id ) { return 'export-' . $id; }
}
