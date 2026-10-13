<?php
/* Scenario context: rates is a string-keyed map of nonnull WC_Shipping_Rate objects. Core hides all but free_shipping before extension callbacks; callbacks may add pickup afterward. Package unchanged. */
function calculate_package_rates( $rates, $package ) {
    if ( isset( $rates['free_shipping'] ) ) {
        $rates = array( 'free_shipping' => $rates['free_shipping'] );
    }
    return apply_filters( 'woocommerce_package_rates', $rates, $package );
}
