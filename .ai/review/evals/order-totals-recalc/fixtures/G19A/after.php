<?php
/* Scenario context: Admin note editing is allowed for already-paid historical orders. calculate_totals recomputes using current tax/coupon settings; save persists current stored totals unchanged unless recalculated. Released note edit preserves charged amounts. */
function save_order_note( $order, $note ) {
    $order->set_customer_note( $note );
    $order->calculate_totals();
    $order->save();
}
