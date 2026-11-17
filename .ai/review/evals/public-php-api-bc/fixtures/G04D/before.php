<?php
interface WC_Export_Feed {
    public function rows();
}
class Partner_Export_Feed implements WC_Export_Feed {
    public function rows() { return array( array( 'id' => 42 ) ); }
}
function export_row_count( WC_Export_Feed $feed ) {
    return count( $feed->rows() );
}
