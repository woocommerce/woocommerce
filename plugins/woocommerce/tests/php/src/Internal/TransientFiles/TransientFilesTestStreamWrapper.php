<?php
declare( strict_types = 1 );

namespace Automattic\WooCommerce\Tests\Internal\TransientFiles;

/**
 * A stream wrapper backed by a local directory, for testing code that runs on installs where the
 * uploads directory is a stream wrapper path (the S3-Uploads plugin, WordPress VIP).
 *
 * Registering a real wrapper rather than mocking filesystem functions is what makes these tests
 * meaningful: wp_is_stream only recognizes schemes that have a wrapper actually registered, and
 * glob() fails on wrapper paths in a way no mock reproduces.
 *
 * Note that stream_metadata must be implemented even though it does nothing here, because
 * WP_Filesystem_Direct::put_contents() calls chmod() on every file it writes.
 */
class TransientFilesTestStreamWrapper {

	/**
	 * The stream context, set by PHP. Unused, but the wrapper protocol requires the property to exist.
	 *
	 * @var resource|null
	 */
	public $context;

	/**
	 * Local directory that the wrapper maps paths onto.
	 *
	 * @var string
	 */
	private static string $root = '';

	/**
	 * The scheme this wrapper is registered under, including the "://" separator.
	 *
	 * @var string
	 */
	private static string $scheme = '';

	/**
	 * The directory handle currently open. PHP only calls the dir_* methods after a successful dir_opendir.
	 *
	 * @var resource
	 */
	private $dir_handle;

	/**
	 * The file handle currently open. PHP only calls the stream_* methods after a successful stream_open.
	 *
	 * @var resource
	 */
	private $file_handle;

	/**
	 * Register the wrapper and point it at a local directory.
	 *
	 * @param string $scheme The scheme to register, without the "://" separator.
	 * @param string $root The local directory to map wrapper paths onto.
	 */
	public static function register( string $scheme, string $root ): void {
		self::$scheme = $scheme . '://';
		self::$root   = untrailingslashit( $root );

		if ( in_array( $scheme, stream_get_wrappers(), true ) ) {
			stream_wrapper_unregister( $scheme );
		}

		stream_wrapper_register( $scheme, self::class );
	}

	/**
	 * Unregister the wrapper.
	 *
	 * @param string $scheme The scheme to unregister, without the "://" separator.
	 */
	public static function unregister( string $scheme ): void {
		if ( in_array( $scheme, stream_get_wrappers(), true ) ) {
			stream_wrapper_unregister( $scheme );
		}

		self::$scheme = '';
		self::$root   = '';
	}

	/**
	 * Translate a wrapper path into the local path it maps onto.
	 *
	 * @param string $path The wrapper path.
	 * @return string The equivalent local path.
	 */
	private function local_path( string $path ): string {
		return self::$root . '/' . ltrim( substr( $path, strlen( self::$scheme ) ), '/' );
	}

	/**
	 * Open a directory.
	 *
	 * @param string $path The directory to open.
	 * @param int    $options Options, unused.
	 * @return bool True if the directory was opened.
	 */
	public function dir_opendir( string $path, int $options ): bool {
		unset( $options );

		// phpcs:ignore WordPress.PHP.NoSilencedErrors.Discouraged -- A missing directory is a valid outcome here.
		$handle = @opendir( $this->local_path( $path ) );
		if ( false === $handle ) {
			return false;
		}

		$this->dir_handle = $handle;
		return true;
	}

	/**
	 * Read the next entry from the open directory.
	 *
	 * @return string|false The entry name, or false when there are no more entries.
	 */
	public function dir_readdir() {
		return readdir( $this->dir_handle );
	}

	/**
	 * Close the open directory.
	 *
	 * @return bool Always true.
	 */
	public function dir_closedir(): bool {
		closedir( $this->dir_handle );
		return true;
	}

	/**
	 * Rewind the open directory.
	 *
	 * @return bool Always true.
	 */
	public function dir_rewinddir(): bool {
		rewinddir( $this->dir_handle );
		return true;
	}

	/**
	 * Get information about a path.
	 *
	 * @param string $path The path to stat.
	 * @param int    $flags Flags, unused.
	 * @return array|false The stat information, or false if the path doesn't exist.
	 */
	public function url_stat( string $path, int $flags ) {
		unset( $flags );

		// phpcs:ignore WordPress.PHP.NoSilencedErrors.Discouraged -- A missing path is a valid outcome here.
		return @stat( $this->local_path( $path ) );
	}

	/**
	 * Open a file.
	 *
	 * @param string $path The file to open.
	 * @param string $mode The mode to open the file with.
	 * @param int    $options Options, unused.
	 * @param string $opened_path Set to the opened path, unused.
	 * @return bool True if the file was opened.
	 */
	public function stream_open( string $path, string $mode, int $options, &$opened_path ): bool {
		unset( $options, $opened_path );

		// phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_fopen, WordPress.PHP.NoSilencedErrors.Discouraged -- This is the wrapper backing the filesystem, it can't route through WP_Filesystem.
		$handle = @fopen( $this->local_path( $path ), $mode );
		if ( false === $handle ) {
			return false;
		}

		$this->file_handle = $handle;
		return true;
	}

	/**
	 * Read from the open file.
	 *
	 * @param int $count Number of bytes to read.
	 * @return string|false The bytes read.
	 */
	public function stream_read( int $count ) {
		// phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_fread -- This is the wrapper backing the filesystem.
		return fread( $this->file_handle, $count );
	}

	/**
	 * Write to the open file.
	 *
	 * @param string $data The data to write.
	 * @return int The number of bytes written.
	 */
	public function stream_write( string $data ): int {
		// phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_fwrite -- This is the wrapper backing the filesystem.
		return (int) fwrite( $this->file_handle, $data );
	}

	/**
	 * Is the end of the open file reached?
	 *
	 * @return bool True if the end of the file is reached.
	 */
	public function stream_eof(): bool {
		return feof( $this->file_handle );
	}

	/**
	 * Get information about the open file.
	 *
	 * @return array|false The stat information.
	 */
	public function stream_stat() {
		return fstat( $this->file_handle );
	}

	/**
	 * Get the current position in the open file.
	 *
	 * @return int The current position.
	 */
	public function stream_tell(): int {
		return (int) ftell( $this->file_handle );
	}

	/**
	 * Move the position in the open file.
	 *
	 * @param int $offset The offset to move to.
	 * @param int $whence How the offset is interpreted.
	 * @return bool True if the position was changed.
	 */
	public function stream_seek( int $offset, int $whence = SEEK_SET ): bool {
		return 0 === fseek( $this->file_handle, $offset, $whence );
	}

	/**
	 * Flush the open file.
	 *
	 * @return bool True if the flush succeeded.
	 */
	public function stream_flush(): bool {
		return fflush( $this->file_handle );
	}

	/**
	 * Close the open file.
	 *
	 * @return bool Always true.
	 */
	public function stream_close(): bool {
		// phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_fclose -- This is the wrapper backing the filesystem.
		fclose( $this->file_handle );
		return true;
	}

	/**
	 * Set metadata on a path. Does nothing, but must exist: WP_Filesystem_Direct::put_contents()
	 * calls chmod() on every file it writes, and without this the call raises a PHP warning.
	 *
	 * @param string $path The path to act on.
	 * @param int    $option The metadata to set.
	 * @param mixed  $value The value to set.
	 * @return bool Always true.
	 */
	public function stream_metadata( string $path, int $option, $value ): bool {
		unset( $path, $option, $value );
		return true;
	}

	/**
	 * Delete a file.
	 *
	 * @param string $path The file to delete.
	 * @return bool True if the file was deleted.
	 */
	public function unlink( string $path ): bool {
		// phpcs:ignore WordPress.WP.AlternativeFunctions.unlink_unlink, WordPress.PHP.NoSilencedErrors.Discouraged -- This is the wrapper backing the filesystem.
		return @unlink( $this->local_path( $path ) );
	}

	/**
	 * Create a directory.
	 *
	 * @param string $path The directory to create.
	 * @param int    $mode The permissions for the new directory.
	 * @param int    $options Options, the recursive flag is honored.
	 * @return bool True if the directory was created.
	 */
	public function mkdir( string $path, int $mode, int $options ): bool {
		// phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_mkdir, WordPress.PHP.NoSilencedErrors.Discouraged
		return @mkdir( $this->local_path( $path ), $mode, (bool) ( $options & STREAM_MKDIR_RECURSIVE ) );
	}

	/**
	 * Delete a directory.
	 *
	 * @param string $path The directory to delete.
	 * @param int    $options Options, unused.
	 * @return bool True if the directory was deleted.
	 */
	public function rmdir( string $path, int $options ): bool {
		unset( $options );

		// phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_rmdir, WordPress.PHP.NoSilencedErrors.Discouraged -- This is the wrapper backing the filesystem.
		return @rmdir( $this->local_path( $path ) );
	}
}
