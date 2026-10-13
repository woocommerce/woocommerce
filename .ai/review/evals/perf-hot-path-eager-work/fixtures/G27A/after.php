<?php
/* Scenario context: The existing request-local optional catalog index is used only on shop archives. store_request_index keeps the IDs in memory without further queries or persistent writes. This wp hook also runs on checkout/cart/product pages. Catalog size has no upper bound. */
function wc_optional_catalog_index() {
    $ids = get_posts( array( 'post_type' => 'product', 'post_status' => 'publish', 'numberposts' => -1, 'fields' => 'ids' ) );
    store_request_index( $ids );
}
add_action( 'wp', 'wc_optional_catalog_index' );
