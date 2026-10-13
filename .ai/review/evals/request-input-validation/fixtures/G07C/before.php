<?php
/* Scenario context: Synthetic production fixture. Native GET values are strings or arrays. Programmatic admin caller also permits integer search terms. Output renderer escapes final string. */
function wc_export_search_term() {
    $term = $_GET['search'] ?? '';
    if ( ! is_string( $term ) ) { return ''; }
    return urldecode( $term );
}
