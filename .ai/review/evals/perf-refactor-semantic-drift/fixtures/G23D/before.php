<?php
/* Scenario context: Caller supplies a numerically indexed ordered list already filtered for stock, purchasability, and extension visibility. This private selector returns a list containing the first eligible ID, or an empty list. No hooks are invoked here. */
function wc_first_ready_child( $ready_ids ) { $first = array(); foreach ( $ready_ids as $id ) { $first[] = $id; break; } return $first; }
