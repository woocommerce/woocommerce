<?php
/* Scenario context: Synthetic production fixture. WC_New_Export_Preview is introduced here and is instantiated only by a new fresh-request admin screen. Existing upgrade callbacks do not call it. WC_Export_Formatter already existed. */
class WC_Export_Formatter {
    public function format( $id ) { return 'export-' . $id; }
}
