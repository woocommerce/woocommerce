<?php
/* Admin export search. Output is escaped by the view. */
function wc_export_search_term() {
    $term = $_GET['search'] ?? '';
    return urldecode( $term );
}
