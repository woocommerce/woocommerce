<?php
/* Scenario context: Released editor links future shipping edits to billing only when _copy_billing_to_shipping=yes. Store API copies billing into shipping when shipping is disabled, even when the explicit link preference is no. */
function address_edit_mode( $order ) {
    return $order->get_meta( '_copy_billing_to_shipping' ) === 'yes' ? 'linked' : 'independent';
}
