<?php
/* Checkout on a new store: gateway settings have not been saved; object cache and notoptions start empty. */
function wc_checkout_gateway_settings( $gateway_ids ) { $keys = array_map( static function( $id ) { return 'woocommerce_' . $id . '_settings'; }, $gateway_ids ); $settings = array(); foreach ( $keys as $key ) { $settings[$key] = get_option( $key, array() ); } return $settings; }
