<?php
declare(strict_types=1);
function wc_status_label( string $status ): string { return 'Status: ' . $status; }
function wc_status_labels( array $statuses ): array {
    return array_map( static function ( $key ) { return wc_status_label( (string) $key ); }, array_keys( $statuses ) );
}
$labels = wc_status_labels( array( '42' => 'Imported', 'pending' => 'Pending' ) );
