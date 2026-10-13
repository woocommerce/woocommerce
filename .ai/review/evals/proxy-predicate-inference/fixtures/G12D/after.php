<?php
final class Order_Address_Editor {
    public function preview( WC_Order $order, array $shipping ) {
        $edited = clone $order;
        $edited->set_address( $shipping, 'shipping' );
        return $this->review_notice( $order, $edited );
    }

    private function review_notice( WC_Order $before, WC_Order $after ) {
        $notice = 'yes' === $after->get_meta( '_copy_billing_to_shipping' )
            ? __( 'Billing and shipping edits are linked.', 'woocommerce' )
            : __( 'Billing and shipping edits are independent.', 'woocommerce' );
        if ( $this->shipping_value_changed( $before, $after ) ) {
            $notice .= ' ' . __( 'Shipping address changed.', 'woocommerce' );
        }
        return $notice;
    }

    private function shipping_value_changed( WC_Order $before, WC_Order $after ) {
        return $before->get_address( 'shipping' ) !== $after->get_address( 'shipping' );
    }
}
