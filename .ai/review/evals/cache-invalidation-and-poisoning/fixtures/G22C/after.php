<?php
/* Scenario context: Caller supplies an immutable array of at most ten translated labels and an existing ID. This is a request-local helper with no persistent caching; array values are plain strings. */
function wc_request_label( $id, $labels ) { static $cache = array(); $key = $id . ':' . serialize( $labels ); if ( ! array_key_exists( $key, $cache ) ) { $cache[$key] = $labels[$id]; } return $cache[$key]; }
