<?php
/* Public extension interface. */
interface WC_Export_Feed {
    public function rows();
    public function get_entry_count();
}
class WC_Core_Export_Feed implements WC_Export_Feed {
    public function rows() { return array(); }
    public function get_entry_count() { return 0; }
}

// Extension implementation loaded through the export-feed registry.
class Partner_Export_Feed implements WC_Export_Feed {
    public function rows() { return array( array( 'id' => 42 ) ); }
}
