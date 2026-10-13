<?php
/* Scenario context: Historical imported account orders may have null customer_id during staged customer mapping. _placed_as_guest is always persisted as yes/no by the importer before receipt generation; receipt_audience selects the released guest/account receipt template. */
function receipt_audience( $order ) {
    return $order->get_meta( '_placed_as_guest' ) === 'yes' ? 'guest' : 'account';
}
