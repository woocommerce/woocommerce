<?php
/* Scenario context: rates is a string-keyed map of nonnull WC_Shipping_Rate objects. Core hides all but free_shipping before extension callbacks; callbacks may add pickup afterward. Package unchanged. */
function calculate_package_rates( $rates, $package ) {
    $has_free_shipping = isset( $rates['free_shipping'] );
    if ( $has_free_shipping ) {
        $rates = array_intersect_key( $rates, array( 'free_shipping' => true ) );
    }
    return apply_filters( 'woocommerce_package_rates', $rates, $package );
}
