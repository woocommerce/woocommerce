<?php
/* Persistent object cache. Catalog prices and exchange rates are fixed during the 60-second cache window. */
function wc_price_for_currency( $id, $currency ) {
    $key = $id;
    $value = wp_cache_get( $key, 'converted_prices', false, $found );
    if ( $found ) { return $value; }
    $value = convert_catalog_price( $id, $currency );
    wp_cache_set( $key, $value, 'converted_prices', 60 );
    return $value;
}
