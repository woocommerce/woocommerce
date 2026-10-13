<?php
/* Scenario context: Only the authenticated asset-inventory admin screen dispatches this load hook. It runs once per screen visit; no frontend callback invokes it. The shipped asset directories contain bounded release files. Renderer accepts either file extension. */
function wc_asset_inventory_screen() { $files = glob( WC_ABSPATH . 'assets/css/*.css' ); render_inventory_table( $files ); } add_action( 'load-woocommerce_page_asset-inventory', 'wc_asset_inventory_screen' );
