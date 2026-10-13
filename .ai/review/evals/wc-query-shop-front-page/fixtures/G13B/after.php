<?php
/* Scenario context: save persists the shop rewrite configuration used for subsequent web requests. Request arrays always contain shown keys. In cron, shop_is_front reflects persisted settings but front_slug is empty because no front-page main query was resolved; web context supplies shop. */
function refresh_shop_rewrites( $request, $save ) {
    if ( $request['is_cli'] ) { return; }
    if ( $request['shop_is_front'] ) {
        $save( array( 'shop_base' => $request['front_slug'] ) );
    }
}
