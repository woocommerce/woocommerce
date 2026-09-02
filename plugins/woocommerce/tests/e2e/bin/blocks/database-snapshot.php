<?php
/**
 * Coordinates Blocks E2E database snapshots with live PHP requests.
 *
 * @package WooCommerce\E2E
 */

declare( strict_types=1 );

if ( 'cli' !== PHP_SAPI ) {
	fwrite( STDERR, "The Blocks E2E database snapshot coordinator is CLI-only.\n" );
	exit( 64 );
}

$arguments = $_SERVER['argv'] ?? array();

if ( ! is_array( $arguments ) || 3 !== count( $arguments ) || '' === $arguments[2] || false !== strpos( $arguments[2], "\0" ) ) {
	fwrite( STDERR, "Usage: database-snapshot.php <import|restore|export> <snapshot-path>\n" );
	exit( 64 );
}

$operation     = $arguments[1];
$snapshot_path = $arguments[2];

switch ( $operation ) {
	case 'import':
		$command = array( 'wp', 'db', 'import', $snapshot_path );
		break;
	case 'restore':
		$command = array( 'sh', '-c', 'wp db reset --yes && wp db import "$1"', 'restore-blocks-database', $snapshot_path );
		break;
	case 'export':
		$command = array( 'wp', 'db', 'export', $snapshot_path );
		break;
	default:
		fwrite( STDERR, "Unknown Blocks E2E database snapshot operation: {$operation}\n" );
		exit( 64 );
}

$lock_handle = @fopen( '/var/www/html/.woocommerce-blocks-e2e-db.lock', 'r+' );
if ( false === $lock_handle ) {
	fwrite( STDERR, "Unable to open the Blocks E2E database lock.\n" );
	exit( 74 );
}

if ( ! flock( $lock_handle, LOCK_EX ) ) {
	fwrite( STDERR, "Unable to acquire the Blocks E2E database lock.\n" );
	exit( 75 );
}

$descriptor_spec = array(
	0 => STDIN,
	1 => STDOUT,
	2 => STDERR,
);

$pipes   = array();
$process = proc_open( $command, $descriptor_spec, $pipes );

if ( ! is_resource( $process ) ) {
	fwrite( STDERR, "Unable to start a Blocks E2E database snapshot child.\n" );
	exit( 70 );
}

$exit_status = proc_close( $process );
if ( 0 !== $exit_status ) {
	exit( $exit_status );
}
