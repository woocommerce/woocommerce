<?php
/* Shop rewrite maintenance adapter. */
function refresh_shop_rewrites( $request, $save ) {
    if ( $request['is_cli'] || $request['is_cron'] ) { return; }
    if ( $request['shop_is_front'] ) {
        $save( array( 'shop_base' => $request['front_slug'] ) );
    }
}

function refresh_from_cron( $save ) {
    refresh_shop_rewrites( array( 'is_cli' => false, 'is_cron' => true, 'shop_is_front' => true, 'front_slug' => '' ), $save );
}
