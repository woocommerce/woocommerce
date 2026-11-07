<?php
/* Admin export search. Output is escaped by the view. */
function wc_export_search_term() {
    $term = $_GET['search'] ?? '';
    if ( ! is_scalar( $term ) || is_bool( $term ) ) { return ''; }
    $term = (string) $term;
    return urldecode( $term );
}
