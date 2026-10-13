<?php
/* Scenario context: Synthetic production fixture. Checkout uses this validator for Argentina. Existing released contract and saved address producers accept both four-digit and CPA postcode formats. */
function wc_validate_argentina_postcode( $postcode ) {
    return is_string( $postcode ) && (bool) preg_match( '/^[A-Z][0-9]{4}[A-Z]{3}$/', $postcode );
}
