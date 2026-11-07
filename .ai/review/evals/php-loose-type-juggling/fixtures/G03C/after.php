<?php
declare(strict_types=1);
function wc_status_label( string $status ): string { return 'Status: ' . $status; }
function wc_status_labels( array $statuses ): array {
    return array_map( 'wc_status_label', array_keys( $statuses ) );
}
$labels = wc_status_labels( array( '42' => 'Imported', 'pending' => 'Pending' ) );
