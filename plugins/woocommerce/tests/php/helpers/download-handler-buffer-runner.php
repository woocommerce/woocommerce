<?php
/**
 * Runs WC_Download_Handler's buffer handling against a given output buffer stack, in its own process.
 *
 * Buffers started without PHP_OUTPUT_HANDLER_REMOVABLE can't be removed until the process exits, which
 * would break every later PHPUnit test, so WC_Download_Handler_Tests runs this script with the PHP CLI
 * instead. Usage: `php download-handler-buffer-runner.php <buffer>[,<buffer>...] <file contents>`, listing buffers
 * from the bottom of the stack up as `<flags>` or `<flags>:<callback function>`. Each buffer starts with "[junk-<level>]" in it.
 *
 * The process stdout is what a client would receive. A JSON report of the errors raised (including
 * @-suppressed ones) and the warnings logged is written to stderr.
 *
 * @package WooCommerce\Tests
 */

declare( strict_types = 1 );

// phpcs:disable WordPress.WP.AlternativeFunctions, WordPress.PHP.DevelopmentFunctions, WordPress.Security.EscapeOutput

/**
 * Hook stub; the class registers its hooks when loaded, but WordPress isn't loaded in this process.
 */
function add_action() {}

/**
 * Translation stub.
 *
 * @param string $text Text.
 * @return string
 */
function __( $text ) {
	return $text;
}

/**
 * Logger stub that collects warnings for the report.
 *
 * @return object
 */
function wc_get_logger() {
	return new class() {
		/**
		 * Record a warning.
		 *
		 * @param string $message Message.
		 */
		public function warning( $message ): void {
			$GLOBALS['wc_warnings'][] = $message;
		}
	};
}

/**
 * Output buffer callback that changes what passes through it, standing in for a third-party handler.
 *
 * @param string $buffer Buffer contents.
 * @return string
 */
function wc_runner_reverse_output( $buffer ) {
	return strrev( $buffer );
}

define( 'ABSPATH', __DIR__ . '/' );

require dirname( __DIR__, 3 ) . '/includes/class-wc-download-handler.php';

$wc_buffers = '' === ( $argv[1] ?? '' ) ? array() : explode( ',', $argv[1] );
$wc_errors  = array();
$wc_file    = tempnam( sys_get_temp_dir(), 'wc-download-buffers' );
file_put_contents( $wc_file, $argv[2] ?? '' );

foreach ( $wc_buffers as $wc_level => $wc_buffer ) {
	list( $wc_flags, $wc_callback ) = array_pad( explode( ':', $wc_buffer, 2 ), 2, null );
	ob_start( $wc_callback, 0, (int) $wc_flags );
	echo "[junk-$wc_level]";
}

set_error_handler(
	function ( $errno, $errstr ) use ( &$wc_errors ) {
		$wc_errors[] = $errstr;
		return true;
	}
);

// Same order as WC_Download_Handler::download_file_force().
foreach ( array( 'clean_buffers', 'log_remaining_buffers' ) as $wc_method ) {
	$wc_method = new ReflectionMethod( WC_Download_Handler::class, $wc_method );
	$wc_method->setAccessible( true );
	$wc_method->invoke( null );
}
WC_Download_Handler::readfile_chunked( $wc_file );

restore_error_handler();
unlink( $wc_file );

fwrite(
	STDERR,
	json_encode(
		array(
			'errors'   => $wc_errors,
			'warnings' => $GLOBALS['wc_warnings'] ?? array(),
		)
	)
);
