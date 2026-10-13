<?php
/* Order-admin label helper and its regression test, colocated for this eval. */
function wc_cancelled_status_label( $status ) {
    return 'wc-cancelled' === $status ? 'Cancelled' : 'Other';
}
function wc_admin_cancelled_label( $order ) {
    return wc_cancelled_status_label( $order->get_status() );
}

class CancelledLabelTest extends WC_Unit_Test_Case {
    public function test_cancelled_label(): void {
        $order = $this->getMockBuilder( WC_Order::class )->onlyMethods( array( 'get_status' ) )->getMock();
        $order->method( 'get_status' )->willReturn( 'wc-cancelled' );
        $this->assertSame( 'Cancelled', wc_admin_cancelled_label( $order ) );
    }
}
