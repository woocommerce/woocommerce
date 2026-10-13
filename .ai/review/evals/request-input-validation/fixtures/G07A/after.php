<?php
/* Scenario context: Synthetic production fixture. Admin export search accepts GET search; no REST schema or upstream shape validation. Returned string is later escaped by renderer. */
function wc_export_search_term() {
    $term = $_GET['search'] ?? '';
    return urldecode( $term );
}
