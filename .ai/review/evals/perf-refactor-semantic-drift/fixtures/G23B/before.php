<?php
/* Scenario context: Performance PR reduces IDs passed to a released visibility filter. Filter callbacks receive the complete ordered candidate list and may remove inaccessible products. One supported callback removes product 10 but keeps 11. */
function wc_first_visible_id( $ids ) {
    $visible = apply_filters( 'woocommerce_visible_candidate_ids', $ids );
    return array_slice( $visible, 0, 1 );
}
