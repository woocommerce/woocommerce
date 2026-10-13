<?php
/* Scenario context: Synthetic production fixture. Cache writers store nonnegative integer IDs either as decimal strings or integers. False denotes cache miss. */
declare(strict_types=1);
function wc_parent_label( int $id ): string { return 'Parent ' . $id; }
function wc_cached_parent_label( $key ): string {
    $id = wp_cache_get( $key, 'parents' );
    if ( false === $id ) { $id = 0; }
    return wc_parent_label( absint( $id ) );
}
