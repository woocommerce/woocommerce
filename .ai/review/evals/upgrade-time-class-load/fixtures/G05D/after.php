<?php
/* New admin preview module; registered only on a fresh request after woocommerce_loaded. */
class WC_Export_Formatter {
    public function format( $id ) { return 'export-' . $id; }
}
class WC_New_Export_Preview {
    public function label( $id ) {
        return ( new WC_Export_Formatter() )->format( $id );
    }
}
