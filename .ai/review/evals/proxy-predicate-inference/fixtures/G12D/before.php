<?php
final class Order_Address_Editor {
    public function preview( WC_Order $order, array $shipping ) {
        $edited = clone $order;
        $edited->set_address( $shipping, 'shipping' );
        return $this->review_notice( $order, $edited );
    }

    private function review_notice( WC_Order $before, WC_Order $after ) {
        return 'yes' === $after->get_meta( '_copy_billing_to_shipping' )
            ? __( 'Billing and shipping edits are linked.', 'woocommerce' )
            : __( 'Billing and shipping edits are independent.', 'woocommerce' );
    }
}
