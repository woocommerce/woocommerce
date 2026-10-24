<?php
/* Secondary product widget query. */
function configure_product_widget( $q ) {
    if ( $q->is_main_query() ) { return; }
    if ( $q->get( 'widget_catalog' ) ) {
        $q->set( 'post_type', 'product' );
        $q->set( 'posts_per_page', 4 );
    }
}
