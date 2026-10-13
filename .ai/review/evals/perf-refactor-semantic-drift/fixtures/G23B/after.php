<?php
/* Scenario context: Performance PR reduces IDs passed to a released visibility filter. Filter callbacks receive the complete ordered candidate list and may remove inaccessible products. One supported callback removes product 10 but keeps 11. */
function wc_first_visible_id( $ids ) { $ids = array_slice( $ids, 0, 1 ); return array_slice( apply_filters( 'woocommerce_visible_candidate_ids', $ids ), 0, 1 ); }
