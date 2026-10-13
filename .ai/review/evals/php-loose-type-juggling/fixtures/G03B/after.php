<?php
/* Scenario context: Synthetic production fixture. Admin pagination producer permits per_page=0 for imported saved preferences; this function must return a finite page count. */
function wc_admin_page_count( $total, $per_page ) {
    $per_page = absint( $per_page );
    return (int) ceil( $total / $per_page );
}
