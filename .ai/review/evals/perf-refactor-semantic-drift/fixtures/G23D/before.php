<?php
/* Private selector receives the numerically indexed result of the caller’s eligibility filtering. */
function wc_first_ready_child( $ready_ids ) { $first = array(); foreach ( $ready_ids as $id ) { $first[] = $id; break; } return $first; }
