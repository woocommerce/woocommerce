<?php
/**
 * Installs or removes the Blocks E2E database request-lock block in .htaccess.
 *
 * Usage: php htaccess-lock-block.php install|remove
 *
 * @package WooCommerce\E2E
 */

declare( strict_types=1 );

$mode  = $argv[1] ?? '';
$path  = '/var/www/html/.htaccess';
$block = "# BEGIN WooCommerce Blocks E2E DB Lock\nphp_value auto_prepend_file /var/www/html/wp-content/plugins/woocommerce/blocks-bin/playwright/request-lock.php\n# END WooCommerce Blocks E2E DB Lock\n";

// Match on the markers rather than the block body, so `remove` still finds a
// block written by an older revision of this script. Matching the exact body
// would leave a stale prepend behind and still report success.
$marker_pattern = '/^# BEGIN WooCommerce Blocks E2E DB Lock$\R.*?^# END WooCommerce Blocks E2E DB Lock$\R?/ms';

if ( ! in_array( $mode, array( 'install', 'remove' ), true ) ) {
	fwrite( STDERR, "Usage: php htaccess-lock-block.php install|remove\n" );
	exit( 64 );
}

// A missing .htaccess means there is no lock block to remove, which is what
// `remove` is asked to guarantee. Treat it as a no-op rather than aborting the
// generic E2E setup, which calls `remove` unconditionally.
if ( 'remove' === $mode && ! file_exists( $path ) ) {
	exit( 0 );
}

$contents = file_get_contents( $path ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_get_contents_file_get_contents -- Plain file in the E2E container, outside WordPress.
if ( false === $contents ) {
	fwrite( STDERR, "Unable to read .htaccess.\n" );
	exit( 1 );
}

$updated = preg_replace( $marker_pattern, '', $contents );
if ( null === $updated ) {
	fwrite( STDERR, "Unable to remove the Blocks E2E database lock configuration.\n" );
	exit( 1 );
}
if ( 'install' === $mode ) {
	$separator = '' !== $updated && "\n" !== substr( $updated, -1 ) ? "\n" : '';
	$updated  .= $separator . $block;
}

if ( $updated !== $contents && false === file_put_contents( $path, $updated ) ) { // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_file_put_contents -- See above.
	fwrite( STDERR, "Unable to update .htaccess.\n" );
	exit( 1 );
}

$expected = 'install' === $mode ? 1 : 0;
if ( $expected !== preg_match_all( $marker_pattern, $updated ) ) {
	fwrite( STDERR, "Expected {$expected} Blocks E2E database lock block(s) in .htaccess after {$mode}.\n" );
	exit( 1 );
}
