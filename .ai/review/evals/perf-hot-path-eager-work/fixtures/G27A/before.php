<?php
/* Catalog index for the current request. */
function wc_optional_catalog_index() {
    if ( ! is_shop() ) { return; }
    $ids = get_posts( array( 'post_type' => 'product', 'post_status' => 'publish', 'numberposts' => -1, 'fields' => 'ids' ) );
    store_request_index( $ids );
}
add_action( 'wp', 'wc_optional_catalog_index' );

function store_request_index( $ids ) { $GLOBALS['wc_request_catalog_index'] = $ids; }
