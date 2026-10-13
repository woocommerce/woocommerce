<?php
/* Scenario context: Both objects represent snapshots of the same order. New helper only reports whether the address value changed; editor mode still uses persisted explicit preference. */
function address_edit_mode( $order ) {
    return 'yes' === $order->get_meta( '_copy_billing_to_shipping' ) ? 'linked' : 'independent';
}
