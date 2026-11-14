<?php
function calculate_package_rates( $rates, $package, $methods ) {
    if ( 'yes' === get_option( 'woocommerce_shipping_hide_rates_when_free', 'no' ) ) {
        $free = array();
        $pickup = array();
        foreach ( $rates as $rate ) {
            if ( 'free_shipping' === $rate->get_method_id() ) {
                $free[ $rate->get_id() ] = $rate;
            } elseif ( 'local_pickup' === $rate->get_method_id() || $methods[ $rate->get_method_id() ]->supports( 'local-pickup' ) ) {
                $pickup[ $rate->get_id() ] = $rate;
            }
        }
        if ( $free ) { $rates = array_merge( $free, $pickup ); }
    }
    return apply_filters( 'woocommerce_package_rates', $rates, $package );
}
add_filter( 'woocommerce_package_rates', static function ( $rates, $package ) {
    $rates['flat_rate:99'] = new WC_Shipping_Rate( 'flat_rate:99', 'Express', '12.50', array(), 'flat_rate', 99 );
    return $rates;
}, 10, 2 );

function available_delivery_rates( $package ) {
    $rates = array(
        'free_shipping:1' => new WC_Shipping_Rate( 'free_shipping:1', 'Free shipping', '0', array(), 'free_shipping', 1 ),
        'local_pickup:2' => new WC_Shipping_Rate( 'local_pickup:2', 'Pickup', '0', array(), 'local_pickup', 2 ),
        'flat_rate:3' => new WC_Shipping_Rate( 'flat_rate:3', 'Delivery', '5', array(), 'flat_rate', 3 ),
    );
    $methods = array( 'flat_rate' => new WC_Shipping_Flat_Rate( 3 ) );
    return calculate_package_rates( $rates, $package, $methods );
}
