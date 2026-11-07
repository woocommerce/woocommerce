<?php
/* Admin export search. Output is escaped by the view. */
function wc_export_search_term() {
    $term = $_GET['search'] ?? '';
    if ( ! is_string( $term ) ) { return ''; }
    return urldecode( $term );
}
