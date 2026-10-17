<?php
final class WC_Order_Total_Controller {
    public function persist( WC_Order $order ) {
        if ( 0 === $order->get_id() && 'yes' === get_option( 'woocommerce_calculate_new_order_totals', 'no' ) ) {
            $order->calculate_totals();
            return;
        }
        $order->save();
    }
}
