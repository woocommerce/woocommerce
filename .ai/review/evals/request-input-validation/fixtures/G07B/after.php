<?php
/* Argentina shipping-address validator. */
function wc_validate_argentina_postcode( $postcode ) {
    return is_string( $postcode ) && (bool) preg_match( '/^[A-Z][0-9]{4}[A-Z]{3}$/', $postcode );
}
