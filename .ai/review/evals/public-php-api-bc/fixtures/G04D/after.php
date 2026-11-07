<?php
interface WC_Export_Feed {
    public function rows();
}
interface WC_Countable_Export_Feed extends WC_Export_Feed {
    public function get_entry_count();
}
class Partner_Export_Feed implements WC_Export_Feed {
    public function rows() { return array( array( 'id' => 42 ) ); }
}
function export_row_count( WC_Export_Feed $feed ) {
    if ( $feed instanceof WC_Countable_Export_Feed ) { return $feed->get_entry_count(); }
    return count( $feed->rows() );
}
