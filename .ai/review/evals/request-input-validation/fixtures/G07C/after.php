<?php
/* Scenario context: Synthetic production fixture. Native GET values are strings or arrays. Programmatic admin caller also permits integer search terms. Output renderer escapes final string. */
function wc_export_search_term() {
    $term = $_GET['search'] ?? '';
    if ( ! is_scalar( $term ) || is_bool( $term ) ) { return ''; }
    $term = (string) $term;
    return urldecode( $term );
}
