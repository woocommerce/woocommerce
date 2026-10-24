<?php
/* Authenticated asset-inventory admin screen. The shipped asset directories contain release files; renderer accepts CSS and JS paths. */
function wc_asset_inventory_screen() { $files = glob( WC_ABSPATH . 'assets/css/*.css' ); render_inventory_table( $files ); } add_action( 'load-woocommerce_page_asset-inventory', 'wc_asset_inventory_screen' );
