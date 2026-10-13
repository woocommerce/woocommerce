<?php
/* Scenario context: widget_catalog is set only by the built-in product widget on a separate query initialized without page constraints. Change raises that widget display count to eight; main shop query must retain its existing pagination. */
function configure_product_widget( $q ) {
    if ( $q->is_main_query() ) { return; }
    if ( $q->get( 'widget_catalog' ) ) {
        $q->set( 'post_type', 'product' );
        $q->set( 'posts_per_page', 8 );
    }
}
