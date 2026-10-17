<?php
final class Pickup_Display_Rate {
    public function create( WC_Shipping_Local_Pickup $method, $cost ) {
        $instance_id = $method->get_instance_id();
        $cost = wc_format_decimal( $cost );
        if ( '' === $cost ) { $cost = '0'; }
        $label = is_string( $method->title ) && '' !== trim( $method->title )
            ? $method->title
            : __( 'Local pickup', 'woocommerce' );
        return new WC_Shipping_Rate( 'local_pickup:' . $instance_id, $label, $cost, array(), 'local_pickup', $instance_id );
    }

    public function render_label( WC_Shipping_Rate $rate ) {
        return esc_html( $rate->get_label() );
    }
}
