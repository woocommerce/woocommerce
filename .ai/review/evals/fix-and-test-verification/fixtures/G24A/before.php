<?php
/* Order-admin label helper and its regression test, colocated for this eval. */
function wc_cancelled_status_label( $status ) {
    return 'cancelled' === $status ? 'Cancelled' : 'Other';
}
function wc_admin_cancelled_label( $order ) {
    return wc_cancelled_status_label( $order->get_status() );
}

class CancelledLabelTest extends WC_Unit_Test_Case {
    public function test_cancelled_label(): void {
        $order = new WC_Order();
        $order->set_status( 'cancelled' );
        $this->assertSame( 'Cancelled', wc_admin_cancelled_label( $order ) );
    }
}
