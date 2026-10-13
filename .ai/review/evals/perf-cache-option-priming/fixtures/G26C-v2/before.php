<?php
/* Scenario context: Synthetic production fixture. Class is complete and final; settings is private and only format calls it. All three shown options were saved with autoload enabled and are present in alloptions before format executes. Option values remain unchanged during the request. amount is a finite number; decimals is a supported nonnegative integer setting; currency and position are strings. The public format method returns only the rendered string and never exposes the private settings tuple. */
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
