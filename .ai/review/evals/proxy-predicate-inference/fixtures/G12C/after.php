<?php
/* Scenario context: _copy_billing_to_shipping is persisted as exactly yes/no. Released editor follows that flag regardless of address equality; addresses can coincide without opting in. */
function address_edit_mode( $order ) {
    $copy_billing = 'yes' === $order->get_meta( '_copy_billing_to_shipping' );
    return $copy_billing ? 'linked' : 'independent';
}
