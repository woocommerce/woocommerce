<?php
declare( strict_types = 1 );

namespace Automattic\WooCommerce\Internal\Admin\Logging\FileV2;

use Automattic\Jetpack\Constants;
use Automattic\WooCommerce\Internal\Utilities\FilesystemUtil;
use Exception;

/**
 * File class.
 *
 * An object representation of a single log file.
 */
class File {
	/**
	 * The absolute path of the file.
	 *
	 * @var string
	 */
	protected $path;

	/**
	 * The source property of the file, derived from the filename.
	 *
	 * @var string
	 */
	protected $source = '';

	/**
	 * The 0-based increment of the file, if it has been rotated. Derived from the filename. Can only be 0-9.
	 *
	 * @var int|null
	 */
	protected $rotation;

	/**
	 * The date the file was created, as a Unix timestamp, derived from the filename.
	 *
	 * @var int
	 */
	protected $created = 0;

	/**
	 * The hash property of the file, derived from the filename.
	 *
	 * @var string
	 */
	protected $hash = '';

	/**
	 * The file's resource handle when it is open.
	 *
	 * @var resource
	 */
	protected $stream;

	/**
	 * Class File
	 *
	 * @param string $path The absolute path of the file.
	 */
	public function __construct( $path ) {
		$this->path = $path;
		$this->ingest_path();
	}

	/**
	 * Make sure open streams are closed.
	 */
	public function __destruct() {
		if ( is_resource( $this->stream ) ) {
			// phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_read_fclose -- No suitable alternative.
			fclose( $this->stream );
		}
	}

	/**
	 * Parse a path to a log file to determine if it uses the standard filename structure and various properties.
	 *
	 * This makes assumptions about the structure of the log file's name. Using `-` to separate the name into segments,
	 *  if there are at least 5 segments, it assumes that the last segment is the hash, and the three segments before
	 *  that make up the date when the file was created in YYYY-MM-DD format. Any segments left after that are the
	 *  "source" that generated the log entries. If the filename doesn't have enough segments, it falls back to the
	 *  source and the hash both being the entire filename, and using the inode change time as the creation date.
	 *
	 *  Example:
	 *      my-custom-plugin.2-2025-01-01-a1b2c3d4e5f.log
	 *            |          |       |         |
	 *    'my-custom-plugin' | '2025-01-01'    |
	 *         (source)      |   (created)     |
	 *                      '2'          'a1b2c3d4e5f'
	 *                  (rotation)           (hash)
	 *
	 * @param string $path The full path of the log file.
	 *
	 * @return array {
	 *     @type string   $dirname   The directory structure containing the file. See pathinfo().
	 *     @type string   $basename  The filename with extension. See pathinfo().
	 *     @type string   $extension The file extension. See pathinfo().
	 *     @type string   $filename  The filename without extension. See pathinfo().
	 *     @type string   $source    The source of the log entries contained in the file.
	 *     @type int|null $rotation  The 0-based incremental rotation marker, if the file has been rotated.
	 *                               Should only be a single digit.
	 *     @type int      $created   The date the file was created, as a Unix timestamp.
	 *     @type string   $hash      The hash suffix of the filename that protects from direct access.
	 *     @type string   $file_id   The public ID of the log file (filename without the hash).
	 * }
	 */
	public static function parse_path( string $path ): array {
		$defaults = array(
			'dirname'   => '',
			'basename'  => '',
			'extension' => '',
			'filename'  => '',
			'source'    => '',
			'rotation'  => null,
			'created'   => 0,
			'hash'      => '',
			'file_id'   => '',
		);

		$parsed = array_merge( $defaults, pathinfo( $path ) );

		$segments  = explode( '-', $parsed['filename'] );
		$timestamp = strtotime( implode( '-', array_slice( $segments, -4, 3 ) ) );

		if ( count( $segments ) >= 5 && false !== $timestamp ) {
			$parsed['source']  = implode( '-', array_slice( $segments, 0, -4 ) );
			$parsed['created'] = $timestamp;
			$parsed['hash']    = array_slice( $segments, -1 )[0];
		} else {
			$parsed['source'] = implode( '-', $segments );
		}

		$rotation_marker = strrpos( $parsed['source'], '.', -1 );
		if ( false !== $rotation_marker ) {
			$rotation = substr( $parsed['source'], -1 );
			if ( is_numeric( $rotation ) ) {
				$parsed['rotation'] = intval( $rotation );
			}

			$parsed['source'] = substr( $parsed['source'], 0, $rotation_marker );
		}

		$parsed['file_id'] = static::generate_file_id(
			$parsed['source'],
			$parsed['rotation'],
			$parsed['created']
		);

		return $parsed;
	}

	/**
	 * Generate a public ID for a log file based on its properties.
	 *
	 * The file ID is the basename of the file without the hash part. It allows us to identify a file without revealing
	 * its full name in the filesystem, so that it's difficult to access the file directly with an HTTP request.
	 *
	 * @param string   $source   The source of the log entries contained in the file.
	 * @param int|null $rotation Optional. The 0-based incremental rotation marker, if the file has been rotated.
	 *                           Should only be a single digit.
	 * @param int      $created  Optional. The date the file was created, as a Unix timestamp.
	 *
	 * @return string
	 */
	public static function generate_file_id( string $source, ?int $rotation = null, int $created = 0 ): string {
		$file_id = static::sanitize_source( $source );

		if ( ! is_null( $rotation ) ) {
			$file_id .= '.' . $rotation;
		}

		if ( $created > 0 ) {
			$file_id .= '-' . gmdate( 'Y-m-d', $created );
		}

		return $file_id;
	}

	/**
	 * Generate a hash to use as the suffix on a log filename.
	 *
	 * @param string $file_id A file ID (file basename without the hash).
	 *
	 * @return string
	 */
	public static function generate_hash( string $file_id ): string {
		$key = Constants::get_constant( 'AUTH_SALT' ) ?? 'wc-logs';

		return hash_hmac( 'md5', $file_id, $key );
	}

	/**
	 * Sanitize the source property of a log file.
	 *
	 * @param string $source The source of the log entries contained in the file.
	 *
	 * @return string
	 */
	public static function sanitize_source( string $source ): string {
		return sanitize_file_name( $source );
	}

	/**
	 * Parse the log file path and assign various properties to this class instance.
	 *
	 * @return void
	 */
	protected function ingest_path(): void {
		$parsed_path    = static::parse_path( $this->path );
		$this->source   = $parsed_path['source'];
		$this->rotation = $parsed_path['rotation'];
		$this->created  = $parsed_path['created'];
		$this->hash     = $parsed_path['hash'];
	}

	/**
	 * Check if the filename structure is in the expected format.
	 *
	 * @see parse_path().
	 *
	 * @return bool
	 */
	public function has_standard_filename(): bool {
		return ! ! $this->get_hash();
	}

	/**
	 * Check if the file represented by the class instance is a file and is readable.
	 *
	 * @return bool
	 */
	public function is_readable(): bool {
		try {
			$filesystem  = FilesystemUtil::get_wp_filesystem();
			$is_readable = $filesystem->is_file( $this->path ) && $filesystem->is_readable( $this->path );
		} catch ( Exception $exception ) {
			return false;
		}

		return $is_readable;
	}

	/**
	 * Check if the file represented by the class instance is a file and is writable.
	 *
	 * @return bool
	 */
	public function is_writable(): bool {
		try {
			$filesystem  = FilesystemUtil::get_wp_filesystem();
			$is_writable = $filesystem->is_file( $this->path ) && $filesystem->is_writable( $this->path );
		} catch ( Exception $exception ) {
			return false;
		}

		return $is_writable;
	}

	/**
	 * Open a read-only stream for this file.
	 *
	 * @return resource|false
	 */
	public function get_stream() {
		if ( ! $this->is_readable() ) {
			return false;
		}

		if ( ! is_resource( $this->stream ) ) {
			// phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_read_fopen -- No suitable alternative.
			$this->stream = fopen( $this->path, 'rb' );
		}

		return $this->stream;
	}

	/**
	 * Close the stream for this file.
	 *
	 * The stream will also close automatically when the class instance destructs, but this can be useful for
	 * avoiding having a large number of streams open simultaneously.
	 *
	 * @return bool
	 */
	public function close_stream(): bool {
		// phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_read_fclose -- No suitable alternative.
		return fclose( $this->stream );
	}

	/**
	 * Get the full absolute path of the file.
	 *
	 * @return string
	 */
	public function get_path(): string {
		return $this->path;
	}

	/**
	 * Get the name of the file, with extension, but without full path.
	 *
	 * @return string
	 */
	public function get_basename(): string {
		return basename( $this->path );
	}

	/**
	 * Get the file's source property.
	 *
	 * @return string
	 */
	public function get_source(): string {
		return $this->source;
	}

	/**
	 * Get the file's rotation property.
	 *
	 * @return int|null
	 */
	public function get_rotation(): ?int {
		return $this->rotation;
	}

	/**
	 * Get the file's hash property.
	 *
	 * @return string
	 */
	public function get_hash(): string {
		return $this->hash;
	}

	/**
	 * Get the file's public ID.
	 *
	 * @return string
	 */
	public function get_file_id(): string {
		$created = 0;
		if ( $this->has_standard_filename() ) {
			$created = $this->get_created_timestamp();
		}

		$file_id = static::generate_file_id(
			$this->get_source(),
			$this->get_rotation(),
			$created
		);

		return $file_id;
	}

	/**
	 * Get the file's created property.
	 *
	 * @return int
	 */
	public function get_created_timestamp(): int {
		if ( ! $this->created && $this->is_readable() ) {
			$this->created = filectime( $this->path );
		}

		return $this->created;
	}

	/**
	 * Get the time of the last modification of the file, as a Unix timestamp. Or false if the file isn't readable.
	 *
	 * @return int|false
	 */
	public function get_modified_timestamp() {
		try {
			$filesystem = FilesystemUtil::get_wp_filesystem();
			$timestamp  = $filesystem->mtime( $this->path );
		} catch ( Exception $exception ) {
			return false;
		}

		return $timestamp;
	}

	/**
	 * Get the size of the file in bytes. Or false if the file isn't readable.
	 *
	 * @return int|false
	 */
	public function get_file_size() {
		try {
			$filesystem = FilesystemUtil::get_wp_filesystem();

			if ( ! $filesystem->is_readable( $this->path ) ) {
				return false;
			}

			$size = $filesystem->size( $this->path );
		} catch ( Exception $exception ) {
			return false;
		}

		return $size;
	}

	/**
	 * Create and set permissions on the file.
	 *
	 * @return bool
	 */
	protected function create(): bool {
		try {
			$filesystem = FilesystemUtil::get_wp_filesystem();
			$created    = $filesystem->touch( $this->path );
			$modded     = $filesystem->chmod( $this->path );
		} catch ( Exception $exception ) {
			return false;
		}

		return $created && $modded;
	}

	/**
	 * Write content to the file, appending it to the end.
	 *
	 * @param string $text The content to add to the file.
	 *
	 * @return bool
	 */
	public function write( string $text ): bool {
		if ( '' === $text ) {
			return false;
		}

		if ( ! $this->is_writable() ) {
			$created = $this->create();

			if ( ! $created || ! $this->is_writable() ) {
				return false;
			}
		}

		// Ensure content ends with a line ending.
		$eol_pos = strrpos( $text, PHP_EOL );
		if ( false === $eol_pos || strlen( $text ) !== $eol_pos + 1 ) {
			$text .= PHP_EOL;
		}

		// phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_read_fopen -- No suitable alternative.
		$resource = fopen( $this->path, 'ab' );

		mbstring_binary_safe_encoding();
		// phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_read_fwrite -- No suitable alternative.
		$bytes_written = fwrite( $resource, $text );
		reset_mbstring_encoding();

		// phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_read_fclose -- No suitable alternative.
		fclose( $resource );

		if ( strlen( $text ) !== $bytes_written ) {
			return false;
		}

		return true;
	}

	/**
	 * Rename this file with an incremented rotation number.
	 *
	 * @return bool True if the file was successfully rotated.
	 */
	public function rotate(): bool {
		if ( ! $this->is_writable() ) {
			return false;
		}

		$created = 0;
		if ( $this->has_standard_filename() ) {
			$created = $this->get_created_timestamp();
		}

		if ( is_null( $this->get_rotation() ) ) {
			$new_rotation = 0;
		} else {
			$new_rotation = $this->get_rotation() + 1;
		}

		$new_file_id = static::generate_file_id( $this->get_source(), $new_rotation, $created );

		$search  = array( $this->get_file_id() );
		$replace = array( $new_file_id );
		if ( $this->has_standard_filename() ) {
			$search[]  = $this->get_hash();
			$replace[] = static::generate_hash( $new_file_id );
		}

		$old_filename = $this->get_basename();
		$new_filename = str_replace( $search, $replace, $old_filename );
		$new_path     = str_replace( $old_filename, $new_filename, $this->path );

		try {
			$filesystem = FilesystemUtil::get_wp_filesystem();
			$moved      = $filesystem->move( $this->path, $new_path, true );
		} catch ( Exception $exception ) {
			return false;
		}

		if ( ! $moved ) {
			return false;
		}

		$this->path = $new_path;
		$this->ingest_path();

		return $this->is_readable();
	}

	/**
	 * Delete the file from the filesystem.
	 *
	 * @return bool True on success, false on failure.
	 */
	public function delete(): bool {
		try {
			$filesystem = FilesystemUtil::get_wp_filesystem();
			$deleted    = $filesystem->delete( $this->path, false, 'f' );
		} catch ( Exception $exception ) {
			return false;
		}

		return $deleted;
	}
}
