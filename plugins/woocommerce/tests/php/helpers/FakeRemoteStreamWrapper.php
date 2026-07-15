<?php
/**
 * Fake remote stream helper.
 *
 * @package WooCommerce\Tests
 */

declare( strict_types = 1 );

/**
 * Minimal stream wrapper standing in for a reachable remote file, so download handling can be
 * exercised without network access. Register it over `http` for the duration of a test:
 *
 *     stream_wrapper_unregister( 'http' );
 *     stream_wrapper_register( 'http', FakeRemoteStreamWrapper::class );
 *     // ...
 *     stream_wrapper_restore( 'http' );
 *
 * Only the operations the download handler performs are implemented; the stream is always empty.
 * Parameter names and signatures are fixed by PHP's streamWrapper prototype, so unused ones are
 * expected: https://www.php.net/manual/en/class.streamwrapper.php
 *
 * Note that PHP reports `stream_get_meta_data()['wrapper_data']` as the wrapper instance itself for
 * user-space wrappers, never the array of raw header lines the built-in `http` wrapper provides.
 * Code reading response headers therefore sees none of them, so this double cannot stand in for a
 * remote server's `Content-Disposition` or `Content-Type`.
 *
 * phpcs:disable Generic.CodeAnalysis.UnusedFunctionParameter
 */
class FakeRemoteStreamWrapper {

	/**
	 * Stream context, assigned by PHP when the wrapper is instantiated.
	 *
	 * @var resource
	 */
	public $context;

	/**
	 * Open the stream.
	 *
	 * @param string $path        File path.
	 * @param string $mode        Mode the stream is opened in.
	 * @param int    $options     Stream options.
	 * @param string $opened_path Path actually opened.
	 * @return bool
	 */
	public function stream_open( $path, $mode, $options, &$opened_path ) {
		return true;
	}

	/**
	 * Read from the stream.
	 *
	 * @param int $count Bytes to read.
	 * @return string
	 */
	public function stream_read( $count ) {
		return '';
	}

	/**
	 * Whether the end of the stream has been reached.
	 *
	 * @return bool
	 */
	public function stream_eof() {
		return true;
	}

	/**
	 * Stat the open stream.
	 *
	 * @return array
	 */
	public function stream_stat() {
		return array();
	}

	/**
	 * Close the stream.
	 *
	 * @return bool
	 */
	public function stream_close() {
		return true;
	}

	/**
	 * Stat the given path.
	 *
	 * @param string $path  File path.
	 * @param int    $flags Stat flags.
	 * @return array
	 */
	public function url_stat( $path, $flags ) {
		return array();
	}
}
