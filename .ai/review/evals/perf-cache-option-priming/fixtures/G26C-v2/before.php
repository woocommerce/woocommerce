<?php
/* Currency options are autoloaded and immutable for the request. Amount is finite; decimals is a nonnegative integer. */
final class WC_Checkout_Total_Label {
    private function settings() {
        return array( get_option( 'woocommerce_currency' ), get_option( 'woocommerce_price_num_decimals' ) );
    }
    public function format( $amount ) {
        $settings = $this->settings();
        $position = get_option( 'woocommerce_currency_pos' );
        $number = number_format( $amount, (int) $settings[1], '.', '' );
        return 'right' === $position ? $number . ' ' . $settings[0] : $settings[0] . ' ' . $number;
    }
}
