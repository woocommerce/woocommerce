<?php
/* Scenario context: Synthetic production fixture. Registered status map may include numeric-string status 42. PHP converts that key to integer. This utility is used by an admin status report. */
declare(strict_types=1);
function wc_status_label( string $status ): string { return 'Status: ' . $status; }
function wc_status_labels( array $statuses ): array {
    return array_map( static function ( $key ) { return wc_status_label( $key ); }, array_keys( $statuses ) );
}
