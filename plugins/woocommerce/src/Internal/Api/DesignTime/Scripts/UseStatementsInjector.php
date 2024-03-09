<?php

namespace Automattic\WooCommerce\Internal\Api\DesignTime\Scripts;

/**
 * This class is registered as the PHP file loader (with stream_wrapper_register) in order to temporarily inject
 * "use" statements for the attribute classes in the API classes during the API generation.
 * Without this the runtime API classes would have to hold references to attribute classes that exist only at design time.
 */
class UseStatementsInjector {
	// phpcs:disable WordPress.WP.AlternativeFunctions

	public const PROTOCOL = 'file';

	/**
	 * Value of "context" parameter to be passed to the native PHP filesystem related functions.
	 *
	 * @var mixed
	 */
	public $context;

	/**
	 * File handle of the file that is open.
	 *
	 * @var mixed
	 */
	private $handle;

	/**
	 * The "use" statements that need to be injected in API classes.
	 *
	 * @var string
	 */
	private static string $use_statements_to_inject;

	/**
	 * The names of the classes that need to be injected.
	 *
	 * @var array
	 */
	private static array $classes_to_inject;

	/**
	 * Register the classes that need to be injected.
	 *
	 * @param array $class_names Names of the classes that need to be injected.
	 */
	public static function register_classes_to_inject_with_use( array $class_names ) {
		self::$classes_to_inject = array_map( fn( $class_name) => ltrim( $class_name, '\\' ), $class_names );

		$use_statements = array_map(
			function( $class_name ) {
				$class_name_without_namespace = self::class_name_without_namespace( $class_name );
				return "use $class_name as " . ( str_ends_with( $class_name, 'Attribute' ) ? substr( $class_name_without_namespace, 0, -9 ) : $class_name_without_namespace ) . ';';
			},
			$class_names
		);

		self::$use_statements_to_inject = implode( "\n", $use_statements );
	}

	/**
	 * Gets a class name without the namespace.
	 *
	 * @param string $class_name Full class name.
	 * @return string Class name without the namespace.
	 */
	private static function class_name_without_namespace( string $class_name ) {
		// A '?:' would convert this to a one-liner, but WP coding standards disallow these :shrug:.
		$result = substr( strrchr( $class_name, '\\' ), 1 );
		return $result ? $result : $class_name;
	}

	/**
	 * Close directory handle.
	 */
	public function dir_closedir() {
		closedir( $this->handle );
	}

	/**
	 * Open directory handle.
	 *
	 * @param string $path Specifies the URL that was passed to opendir().
	 * @param int    $options Whether or not to enforce safe_mode (0x04).
	 *
	 * @return bool TRUE on success or FALSE on failure.
	 */
	public function dir_opendir( $path, $options ) {
		$this->handle = $this->context
			? $this->native( 'opendir', $path, $this->context )
			: $this->native( 'opendir', $path );
		return (bool) $this->handle;
	}

	/**
	 *  Read entry from directory handle.
	 *
	 * @return false|string string representing the next filename, or FALSE if there is no next file.
	 */
	public function dir_readdir() {
		return readdir( $this->handle );
	}

	/**
	 * Rewind directory handle.
	 *
	 * @return TRUE on success or FALSE on failure.
	 */
	public function dir_rewinddir() {
		return rewinddir( $this->handle );
	}

	/**
	 * Create a directory.
	 *
	 * @param string $path Directory which should be created.
	 * @param int    $mode The value passed to mkdir().
	 * @param int    $options A bitwise mask of values, such as STREAM_MKDIR_RECURSIVE.
	 *
	 * @return bool TRUE on success or FALSE on failure.
	 */
	public function mkdir( $path, $mode, $options ) {
		$recursive = (bool) ( $options & STREAM_MKDIR_RECURSIVE );
		return $this->native( 'mkdir', $path, $mode, $recursive, $this->context );
	}

	/**
	 * Renames a file or directory.
	 *
	 * @param string $path_from The URL to the current file.
	 * @param string $path_to The URL which the path_from should be renamed to.
	 *
	 * @return bool TRUE on success or FALSE on failure.
	 */
	public function rename( $path_from, $path_to ) {
		return $this->native( 'rename', $path_from, $path_to, $this->context );
	}

	/**
	 * Removes a directory.
	 *
	 * @param string $path The directory URL which should be removed.
	 * @param int    $options A bitwise mask of values, such as STREAM_MKDIR_RECURSIVE.
	 *
	 * @return bool TRUE on success or FALSE on failure.
	 */
	public function rmdir( $path, $options ) {
		return $this->native( 'rmdir', $path, $this->context );
	}

	/**
	 * Retrieve the underlying resource.
	 *
	 * @param mixed $cast_as Can be STREAM_CAST_FOR_SELECT when stream_select() is calling stream_cast() or STREAM_CAST_AS_STREAM when stream_cast() is called for other uses.
	 *
	 * @return mixed The underlying stream resource used by the wrapper, or FALSE.
	 */
	public function stream_cast( $cast_as ) {
		return $this->handle;
	}

	/**
	 * Close a resource.
	 */
	public function stream_close() {
		fclose( $this->handle );
	}

	/**
	 * Tests for end-of-file on a file pointer.
	 *
	 * @return bool TRUE if the read/write position is at the end of the stream and if no more data is available to be read, or FALSE otherwise.
	 */
	public function stream_eof() {
		return feof( $this->handle );
	}

	/**
	 * Flushes the output.
	 *
	 * @return bool TRUE if the cached data was successfully stored (or if there was no data to store), or FALSE if the data could not be stored.
	 */
	public function stream_flush() {
		return fflush( $this->handle );
	}

	/**
	 * Advisory file locking.
	 *
	 * @param int $operation LOCK_SH, LOCK_EX, LOCK_UN, or LOCK_NB.
	 *
	 * @return bool TRUE on success or FALSE on failure.
	 */
	public function stream_lock( $operation ) {
		return $operation
			? flock( $this->handle, $operation )
			: true;
	}

	/**
	 * Change stream metadata.
	 *
	 * @param string $path The file path or URL to set metadata. Note that in the case of a URL, it must be a :// delimited URL. Other URL forms are not supported.
	 * @param int    $option STREAM_META_TOUCH, STREAM_META_OWNER_NAME, STREAM_META_OWNER, STREAM_META_GROUP_NAME, STREAM_META_GROUP, or STREAM_META_ACCESS.
	 * @param mixed  $value Depends on $option.
	 *
	 * @return bool TRUE on success or FALSE on failure. If option is not implemented, FALSE should be returned.
	 */
	public function stream_metadata( $path, $option, $value ) {
		switch ( $option ) {
			case STREAM_META_TOUCH:
				$value += array( null, null );
				return $this->native( 'touch', $path, $value[0], $value[1] );
			case STREAM_META_OWNER_NAME:
			case STREAM_META_OWNER:
				return $this->native( 'chown', $path, $value );
			case STREAM_META_GROUP_NAME:
			case STREAM_META_GROUP:
				return $this->native( 'chgrp', $path, $value );
			case STREAM_META_ACCESS:
				return $this->native( 'chmod', $path, $value );
		}
	}

	/**
	 * Opens file or URL. Note that this is where the file hacking actually happens.
	 *
	 * @param string $path Specifies the URL that was passed to the original function.
	 * @param string $mode The mode used to open the file, as detailed for fopen().
	 * @param int    $options Holds additional flags set by the streams API: STREAM_USE_PATH, STREAM_REPORT_ERRORS.
	 * @param string $opened_path If the path is opened successfully, and STREAM_USE_PATH is set in options, opened_path should be set to the full path of the file/resource that was actually opened.
	 *
	 * @return bool TRUE on success or FALSE on failure.
	 * @throws \Exception Namespace declaration not found in the file being processed.
	 */
	public function stream_open( $path, $mode, $options, &$opened_path ) {
		$use_path = (bool) ( $options & STREAM_USE_PATH );
		if ( 'rb' === $mode && 'php' === pathinfo( $path, PATHINFO_EXTENSION ) ) {
			$content = $this->native( 'file_get_contents', $path, $use_path, $this->context );
			if ( false === $content ) {
				return false;
			}

			$use_statements_to_inject = self::get_use_statements_to_inject( $content );
			if ( ! empty( $use_statements_to_inject ) ) {
				$modified = preg_replace( '/^[ \t]*(namespace +[a-z0-9\\\\_]+;)[ \t]*$/im', "$0\n\n" . $use_statements_to_inject . "\n", $content );
				if ( $modified === $content ) {
					throw new \Exception( "Namespace declaration not found in file: $path" );
				}

				$this->handle = tmpfile();
				$this->native( 'fwrite', $this->handle, $modified );
				$this->native( 'fseek', $this->handle, 0 );
				return true;
			}
		}

		$this->handle = $this->context
			? $this->native( 'fopen', $path, $mode, $use_path, $this->context )
			: $this->native( 'fopen', $path, $mode, $use_path );
		return (bool) $this->handle;
	}

	/**
	 * Gets the "use" statements to inject in a file, based on the statements that the file already has.
	 *
	 * @param string $content File contents.
	 * @return string "use" statements to inject.
	 */
	private static function get_use_statements_to_inject( string $content ) {
		$use_statements = array();
		foreach ( self::$classes_to_inject as $class_name ) {
			if ( preg_match( '/^[ \\\\t]*use[ \\\\\t]+\\\\?' . str_replace( '\\', '\\\\', $class_name ) . '[ \\\\t;]/m', $content ) ) {
				continue;
			}

			$class_name_without_namespace = self::class_name_without_namespace( $class_name );
			$use_statements[]             = "use $class_name as " . ( str_ends_with( $class_name, 'Attribute' ) ? substr( $class_name_without_namespace, 0, -9 ) : $class_name_without_namespace ) . ';';
		}

		return implode( "\n", $use_statements );
	}

	/**
	 * Read from stream.
	 *
	 * @param int $count How many bytes of data from the current position should be returned.
	 *
	 * @return false|string If there are less than count bytes available, return as many as are available. If no more data is available, return either FALSE or an empty string.
	 */
	public function stream_read( $count ) {
		return fread( $this->handle, $count );
	}

	/**
	 * Seeks to specific location in a stream.
	 *
	 * @param int $offset The stream offset to seek to.
	 * @param int $whence SEEK_SET, SEEK_CUR, or SEEK_END.
	 *
	 * @return bool TRUE if the position was updated, FALSE otherwise.
	 */
	public function stream_seek( $offset, $whence = SEEK_SET ) {
		return fseek( $this->handle, $offset, $whence ) === 0;
	}

	/**
	 *  Change stream options.
	 *
	 * @param int $option STREAM_OPTION_BLOCKING, STREAM_OPTION_READ_TIMEOUT, or STREAM_OPTION_WRITE_BUFFER.
	 * @param int $arg1 Depends on $option.
	 * @param int $arg2 Depends on $option.
	 */
	public function stream_set_option( $option, $arg1, $arg2 ) {
	}

	/**
	 * Retrieve information about a file resource.
	 *
	 * @return array See stat().
	 */
	public function stream_stat() {
		return fstat( $this->handle );
	}

	/**
	 * Retrieve the current position of a stream.
	 *
	 * @return false|int The current position of the stream.
	 */
	public function stream_tell() {
		return ftell( $this->handle );
	}

	/**
	 * Truncate stream.
	 *
	 * @param int $new_size The new size.
	 *
	 * @return bool TRUE on success or FALSE on failure.
	 */
	public function stream_truncate( $new_size ) {
		return ftruncate( $this->handle, $new_size );
	}

	/**
	 * Write to stream.
	 *
	 * @param string $data Should be stored into the underlying stream.
	 *
	 * @return false|int The number of bytes that were successfully stored, or 0 if none could be stored.
	 */
	public function stream_write( $data ) {
		return fwrite( $this->handle, $data );
	}

	/**
	 * Delete a file.
	 *
	 * @param string $path The file URL which should be deleted.
	 *
	 * @return bool TRUE on success or FALSE on failure.
	 */
	public function unlink( $path ) {
		return $this->native( 'unlink', $path );
	}

	/**
	 * Retrieve information about a file.
	 *
	 * @param string $path The file path or URL to stat. Note that in the case of a URL, it must be a :// delimited URL. Other URL forms are not supported.
	 * @param int    $flags Holds additional flags set by the streams API. It can hold one or more of the following values OR'd together.
	 *
	 * @return mixed Should return as many elements as stat() does. Unknown or unavailable values should be set to a rational value (usually 0). Pay special attention to mode as documented under stat().
	 */
	public function url_stat( $path, $flags ) {
		$func = $flags & STREAM_URL_STAT_LINK ? 'lstat' : 'stat';
		return $flags & STREAM_URL_STAT_QUIET
			// phpcs:ignore WordPress.PHP.NoSilencedErrors.Discouraged
			? @$this->native( $func, $path )
			: $this->native( $func, $path );
	}

	/**
	 * Executes a native PHP function.
	 *
	 * @param string $func Name of the function to execute. Pass the arguments for the PHP function after this one.
	 *
	 * @return mixed Return value from the native PHP function.
	 */
	private function native( $func ) {
		stream_wrapper_restore( self::PROTOCOL );
		$res = call_user_func_array( $func, array_slice( func_get_args(), 1 ) );
		stream_wrapper_unregister( self::PROTOCOL );
		stream_wrapper_register( self::PROTOCOL, __CLASS__ );
		return $res;
	}

	// phpcs:enable WordPress.WP.AlternativeFunctions
}
