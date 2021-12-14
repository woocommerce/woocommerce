<?php
/**
 * A class of utilities for dealing with file downloads.
 */

namespace Automattic\WooCommerce\Internal;

defined( 'ABSPATH' ) || exit;

/**
 * A class of utilities for dealing with file downloads.
 */
class DownloadUtil {

	/**
	 * Trigger content download as a file.
	 *
	 * @param string $filename File name for the "Content-Disposition" header.
	 * @param string $content_type Value for the "Content-Type" header.
	 * @param mixed  $content The content to download.
	 */
	public function download_as_attachment( string $filename, string $content_type, $content ) {
		// phpcs:disable WordPress.PHP.NoSilencedErrors.Discouraged, WordPress.PHP.IniSet.Risky
		if ( function_exists( 'gc_enable' ) ) {
			gc_enable();
		}
		if ( function_exists( 'apache_setenv' ) ) {
			// phpcs:ignore WordPress.PHP.DiscouragedPHPFunctions
			@apache_setenv( 'no-gzip', 1 );
		}
		@ini_set( 'zlib.output_compression', 'Off' );
		@ini_set( 'output_buffering', 'Off' );
		@ini_set( 'output_handler', '' );
		// phpcs:enable WordPress.PHP.NoSilencedErrors.Discouraged, WordPress.PHP.IniSet.Risky
		ignore_user_abort( true );
		wc_set_time_limit( 0 );
		wc_nocache_headers();
		header( "Content-Type: ${content_type}" );
		header( "Content-Disposition: attachment; filename=${filename}" );
		header( 'Pragma: no-cache' );
		header( 'Expires: 0' );
		// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
		echo $content;
	}
}
