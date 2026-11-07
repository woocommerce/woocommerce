<?php
final class Shop_Front_Query {
    public function register() {
        add_action( 'pre_get_posts', array( $this, 'configure' ) );
    }

    public function configure( WP_Query $query ) {
        if ( ! $query->is_main_query() || 'page' !== get_option( 'show_on_front' ) ) { return; }
        $shop_id = (int) wc_get_page_id( 'shop' );
        if ( $shop_id <= 0 || $shop_id !== (int) get_option( 'page_on_front' ) ) { return; }
        if ( $shop_id !== (int) $query->get( 'page_id' ) ) { return; }

        $query->set( 'page_id', 0 );
        $query->set( 'pagename', '' );
        $query->set( 'name', '' );
        $query->set( 'post_type', 'product' );
        $query->is_singular = false;
        $query->is_post_type_archive = true;
        $query->is_archive = true;
    }
}

( new Shop_Front_Query() )->register();
