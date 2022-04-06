<?php
/**
 * CodeHacker class file.
 *
 * @package WooCommerce\Testing
 */

//phpcs:disable WordPress.WP.AlternativeFunctions, WordPress.PHP.NoSilencedErrors.Discouraged

namespace Automattic\WooCommerce\Testing\Tools\CodeHacking;

use \ReflectionObject;
use \ReflectionException;

/**
 * CodeHacker - allows to hack (alter on the fly) the content of PHP code files.
 *
 * Based on BypassFinals: https://github.com/dg/bypass-finals
 *
 * How to use:
 *
 * 1. Register hacks using CodeHacker::add_hack(hack). A hack is either:
 *    - A function with 'hack($code, $path)' signature, or
 *    - An object having a public 'hack($code, $path)' method.
 *
 *    Where $code is a string containing the code to hack, and $path is the full path of the file
 *    containing the code. The function/method must return a string with the code already hacked.
 *
 * 2. Run CodeHacker::enable()
 *
 * For using with PHPUnit, see CodeHackerTestHook.
 */
final class CodeHacker {

	const PROTOCOL                     = 'file';
	const HACK_CALLBACK_ARGUMENT_COUNT = 2;

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
	 * Optional white list of files to hack, if empty all the files will be hacked.
	 *
	 * @var array
	 */
	private static $paths_with_files_to_hack = array();

	/**
	 * Registered hacks.
	 *
	 * @var array
	 */
	private static $hacks = array();

	/**
	 * Is the code hacker enabled?.
	 *
	 * @var bool
	 */
	private static $enabled = false;

	/**
	 * Enable the code hacker.
	 */
	public static function enable() {
		if ( ! self::$enabled ) {
			stream_wrapper_unregister( self::PROTOCOL );
			stream_wrapper_register( self::PROTOCOL, __CLASS__ );
			self::$enabled = true;
		}
	}

	/**
	 * Disable the code hacker.
	 */
	public static function disable() {
		if ( self::$enabled ) {
			stream_wrapper_restore( self::PROTOCOL );
			self::$enabled = false;
		}
	}

	/**
	 * Check if the code hacker is enabled.
	 *
	 * @return bool True if the code hacker is enabled.
	 */
	public static function is_enabled() {
		return self::$enabled;
	}

	/**
	 * Execute the 'reset()' method in all the registered hacks.
	 */
	public static function reset_hacks() {
		foreach ( self::$hacks as $hack ) {
			call_user_func( array( $hack, 'reset' ) );
		}
	}

	/**
	 * Register a new hack.
	 *
	 * @param mixed $hack A function with signature "hack($code, $path)" or an object containing a method with that signature.
	 * @throws \Exception Invalid input.
	 */
	public static function add_hack( $hack ) {
		if ( ! self::is_valid_hack_object( $hack ) ) {
			$class = get_class( $hack );
			throw new \Exception( "CodeHacker::add_hack for instance of $class: Hacks must be objects having a 'process(\$text, \$path)' method and a 'reset()' method." );
		}

		self::$hacks[] = $hack;
	}

	/**
	 * Check if the supplied argument is a valid hack object (has a public "hack" method with two mandatory arguments).
	 *
	 * @param mixed $callback Argument to check.
	 *
	 * @return bool rue if the argument is a valid hack object, false otherwise.
	 */
	private static function is_valid_hack_object( $callback ) {
		if ( ! is_object( $callback ) ) {
			return false;
		}

		$ro = new ReflectionObject( ( $callback ) );
		try {
			$rm                    = $ro->getMethod( 'hack' );
			$has_valid_hack_method = $rm->isPublic() && ! $rm->isStatic() && 2 === $rm->getNumberOfRequiredParameters();

			$rm                     = $ro->getMethod( 'reset' );
			$has_valid_reset_method = $rm->isPublic() && ! $rm->isStatic() && 0 === $rm->getNumberOfRequiredParameters();

			return $has_valid_hack_method && $has_valid_reset_method;
		} catch ( ReflectionException $exception ) {
			return false;
		}
	}

	/**
	 * Initialize the code hacker.
	 *
	 * @param array $paths Paths of the directories containing the files to hack.
	 * @throws \Exception Invalid input.
	 */
	public static function initialize( array $paths ) {
		if ( ! is_array( $paths ) || empty( $paths ) ) {
			throw new \Exception( 'CodeHacker::initialize - $paths must be a non-empty array with the directories containing the files to be hacked.' );
		}
		self::$paths_with_files_to_hack = array_map(
			function( $path ) {
				return realpath( $path );
			},
			$paths
		);
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
	 *  Opens file or URL. Note that this is where the hacking actually happens.
	 *
	 * @param string $path Specifies the URL that was passed to the original function.
	 * @param string $mode The mode used to open the file, as detailed for fopen().
	 * @param int    $options Holds additional flags set by the streams API: STREAM_USE_PATH, STREAM_REPORT_ERRORS.
	 * @param string $opened_path If the path is opened successfully, and STREAM_USE_PATH is set in options, opened_path should be set to the full path of the file/resource that was actually opened.
	 *
	 * @return bool TRUE on success or FALSE on failure.
	 */
	public function stream_open( $path, $mode, $options, &$opened_path ) {
		$use_path = (bool) ( $options & STREAM_USE_PATH );
		if ( 'rb' === $mode && self::path_in_list_of_paths_to_hack( $path ) && 'php' === pathinfo( $path, PATHINFO_EXTENSION ) ) {
			$content = $this->native( 'file_get_contents', $path, $use_path, $this->context );
			if ( false === $content ) {
				return false;
			}
			$modified = self::hack( $content, $path );
			if ( $modified !== $content ) {
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

	/**
	 * Apply the reigstered hacks to the contents of a file.
	 *
	 * @param string $code Code content to hack.
	 * @param string $path Path of the file being hacked.
	 *
	 * @return string The code after applying all the registered hacks.
	 */
	private static function hack( $code, $path ) {
		foreach ( self::$hacks as $hack ) {
			if ( is_callable( $hack ) ) {
				$code = call_user_func( $hack, $code, $path );
			} else {
				$code = $hack->hack( $code, $path );
			}
		}

		return $code;
	}

	/**
	 * Check if a file path is in the white list.
	 *
	 * @param string $path File path to check.
	 *
	 * @return bool TRUE if there's an entry in the white list that ends with $path, FALSE otherwise.
	 *
	 * @throws \Exception The class is not initialized.
	 */
	private static function path_in_list_of_paths_to_hack( $path ) {
		if ( empty( self::$paths_with_files_to_hack ) ) {
			throw new \Exception( "CodeHacker is not initialized, it must initialized by invoking 'initialize'" );
		}
		foreach ( self::$paths_with_files_to_hack as $white_list_item ) {
			if ( substr( $path, 0, strlen( $white_list_item ) ) === $white_list_item ) {
				return true;
			}
		}
		return false;
	}
}

//phpcs:enable WordPress.WP.AlternativeFunctions, WordPress.PHP.NoSilencedErrors.Discouraged

