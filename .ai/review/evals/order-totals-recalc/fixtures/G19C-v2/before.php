<?php
/* Scenario context: Synthetic production fixture. The final class is complete; calculate is the sole caller of its private create_order_totals method. The shown get_option read produces the setting, and the shown in_array expression creates its boolean representation. woocommerce_new_tax_preview is a new unreleased opt-in option whose missing default is no. Orders expose integer get_id, zero only when unsaved. calculate_preview_totals is an existing callable preview algorithm; calculate_totals is the existing standard algorithm. */
final class WC_Order_Total_Controller {
    public function calculate( $order ) {
        $stored = get_option( 'woocommerce_new_tax_preview', 'no' );
        $settings = array( 'new_tax_preview' => in_array( $stored, array( true, 1, '1', 'yes' ), true ) );
        $this->create_order_totals( $order, $settings );
    }
    private function create_order_totals( $order, $settings ) {
        $order->calculate_totals();
    }
}
