<?php
/* Scenario context: PR claims to fix cancelled order labels in order admin. WC_Order::get_status() returns the unprefixed slug, e.g. cancelled; prefixed wc-cancelled exists only in storage. Every call to this private label helper is shown. */
function wc_cancelled_status_label( $status ) {
    return 'wc-cancelled' === $status ? 'Cancelled' : 'Other';
}
function wc_admin_cancelled_label( $order ) {
    return wc_cancelled_status_label( $order->get_status() );
}
