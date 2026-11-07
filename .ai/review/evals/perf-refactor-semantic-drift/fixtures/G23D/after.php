<?php
/* Private selector receives the numerically indexed result of the caller’s eligibility filtering. */
function wc_first_ready_child( $ready_ids ) { return array_slice( $ready_ids, 0, 1 ); }
