<?php
function wc_admin_page_count( $total, $per_page ) {
    $per_page = max( 1, absint( $per_page ) );
    return (int) ceil( $total / $per_page );
}
