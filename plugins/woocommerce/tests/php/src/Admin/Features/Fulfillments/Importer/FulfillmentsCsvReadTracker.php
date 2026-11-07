<?php
/**
 * Records how far a capped CSV parse reads.
 *
 * @package Automattic\WooCommerce\Tests\Admin\Features\Fulfillments\Importer
 */

declare( strict_types=1 );

namespace Automattic\WooCommerce\Tests\Admin\Features\Fulfillments\Importer;

/**
 * Stream wrapper that counts the furthest byte read from a real file.
 */
class FulfillmentsCsvReadTracker {

	/**
	 * Highest file position read or seeked during the parse.
	 *
	 * @var int
	 */
	public static $max_position = 0;

	/**
	 * Underlying file handle.
	 *
	 * @var resource|null
	 */
	public $handle;

	/**
	 * Open the real file named after the wcimp:// prefix.
	 *
	 * @param string $path        Stream URL.
	 * @param string $mode        Open mode.
	 * @param int    $options     Stream options.
	 * @param string $opened_path Resolved path. Assigned by PHP when requested.
	 * @return bool
	 */
	public function stream_open( $path, $mode, $options, &$opened_path ) {
		unset( $mode, $options, $opened_path );
		$prefix = 'wcimp://';
		$file   = 0 === strpos( (string) $path, $prefix ) ? substr( (string) $path, strlen( $prefix ) ) : (string) $path;
		// phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_fopen -- The wrapper has to open the fixture the parser streams.
		$handle = fopen( $file, 'rb' );
		if ( false === $handle ) {
			return false;
		}
		$this->handle = $handle;
		return true;
	}

	/**
	 * Read and record the position.
	 *
	 * @param int $count Maximum bytes.
	 * @return string
	 */
	public function stream_read( $count ) {
		// phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_fread -- Reading the fixture handle opened above.
		$data = fread( $this->handle, (int) $count );
		$this->note_position();
		return is_string( $data ) ? $data : '';
	}

	/**
	 * Seek and record the position.
	 *
	 * @param int $offset Offset.
	 * @param int $whence Whence.
	 * @return bool
	 */
	public function stream_seek( $offset, $whence ) {
		$ok = 0 === fseek( $this->handle, (int) $offset, (int) $whence );
		$this->note_position();
		return $ok;
	}

	/**
	 * Current position.
	 *
	 * @return int
	 */
	public function stream_tell() {
		// phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_ftell -- Position of the fixture handle.
		$position = ftell( $this->handle );
		return false === $position ? 0 : (int) $position;
	}

	/**
	 * Whether the underlying file is at the end.
	 *
	 * @return bool
	 */
	public function stream_eof() {
		return feof( $this->handle );
	}

	/**
	 * Stat the underlying file.
	 *
	 * @return array<int|string, int>|false
	 */
	public function stream_stat() {
		$stat = fstat( $this->handle );
		return is_array( $stat ) ? $stat : false;
	}

	/**
	 * Close the underlying file.
	 */
	public function stream_close() {
		// phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_fclose -- Closing the fixture handle opened above.
		fclose( $this->handle );
	}

	/**
	 * Accept stream option changes. fgetcsv() does not depend on them.
	 *
	 * @param int $option Option.
	 * @param int $arg1   First argument.
	 * @param int $arg2   Second argument.
	 * @return bool
	 */
	public function stream_set_option( $option, $arg1, $arg2 ) {
		unset( $option, $arg1, $arg2 );
		return false;
	}

	/**
	 * Remember the furthest position reached.
	 */
	private function note_position() {
		// phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_ftell -- Position of the fixture handle.
		$position = ftell( $this->handle );
		if ( false !== $position && (int) $position > self::$max_position ) {
			self::$max_position = (int) $position;
		}
	}
}
