<?php
/* Scenario context: This released fixture pipeline hides paid rates before extension filter. A supported callback adds local_pickup after core hiding so shoppers may choose it alongside free shipping. Rate keys here are method IDs and callback preserves free_shipping. */
function calculate_package_rates( $rates, $package ) {
    if ( isset( $rates['free_shipping'] ) ) {
        $rates = array( 'free_shipping' => $rates['free_shipping'] );
    }
    return apply_filters( 'woocommerce_package_rates', $rates, $package );
}
