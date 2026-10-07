<?php
/**
 * Runs WC_Download_Handler's buffer handling against a given output buffer stack, in its own process.
 *
 * Buffers started without PHP_OUTPUT_HANDLER_REMOVABLE can't be removed until the process exits, which
 * would break every later PHPUnit test, so WC_Download_Handler_Tests runs this script with the PHP CLI
 * instead. Usage: `php download-handler-buffer-runner.php <flags>[,<flags>...] <file contents>`, with one
 * flags value per buffer from the bottom of the stack up. Each buffer starts with "[junk-<level>]" in it.
 *
 * The process stdout is what a client would receive. A JSON report of the errors raised (including
 * @-suppressed ones) is written to stderr.
 *
 * @package WooCommerce\Tests
 */

declare( strict_types = 1 );

// phpcs:disable WordPress.WP.AlternativeFunctions, WordPress.PHP.DevelopmentFunctions, WordPress.Security.EscapeOutput

/**
 * Hook stub; the class registers its hooks when loaded, but WordPress isn't loaded in this process.
 */
function add_action() {}

define( 'ABSPATH', __DIR__ . '/' );

require dirname( __DIR__, 3 ) . '/includes/class-wc-download-handler.php';

$wc_buffer_flags = '' === ( $argv[1] ?? '' ) ? array() : array_map( 'intval', explode( ',', $argv[1] ) );
$wc_errors       = array();
$wc_file         = tempnam( sys_get_temp_dir(), 'wc-download-buffers' );
file_put_contents( $wc_file, $argv[2] ?? '' );

foreach ( $wc_buffer_flags as $wc_level => $wc_flags ) {
	ob_start( null, 0, $wc_flags );
	echo "[junk-$wc_level]";
}

set_error_handler(
	function ( $errno, $errstr ) use ( &$wc_errors ) {
		$wc_errors[] = $errstr;
		return true;
	}
);

$wc_clean_buffers = new ReflectionMethod( WC_Download_Handler::class, 'clean_buffers' );
// Needed before PHP 8.1. PHP 8.5 deprecates it, and the error handler would record that.
if ( PHP_VERSION_ID < 80100 ) {
	$wc_clean_buffers->setAccessible( true );
}
$wc_clean_buffers->invoke( null );
WC_Download_Handler::readfile_chunked( $wc_file );

restore_error_handler();
unlink( $wc_file );

fwrite(
	STDERR,
	json_encode(
		array(
			'errors' => $wc_errors,
		)
	)
);
