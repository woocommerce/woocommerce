<?php
declare( strict_types = 1 );

namespace Automattic\WooCommerce\Internal\Admin\Logging\FileV2;

use WP_Filesystem_Direct;

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
	 * @var int|false
	 */
	protected $created = false;

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
		global $wp_filesystem;
		if ( ! $wp_filesystem instanceof WP_Filesystem_Direct ) {
			WP_Filesystem();
		}

		$this->path = $path;
		$this->parse_filename();
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
	 * Parse the log filename to derive certain properties of the file.
	 *
	 * This makes assumptions about the structure of the log file's name. Using `-` to separate the name into segments,
	 * if there are at least 5 segments, it assumes that the last segment is the hash, and the three segments before
	 * that make up the date when the file was created in YYYY-MM-DD format. Any segments left after that are the
	 * "source" that generated the log entries. If the filename doesn't have enough segments, it falls back to the
	 * source and the hash both being the entire filename, and using the inode change time as the creation date.
	 *
	 * Example:
	 *     my-custom-plugin.2-2025-01-01-a1b2c3d4e5f.log
	 *           |          |       |         |
	 *   'my-custom-plugin' | '2025-01-01'    |
	 *        (source)      |   (created)     |
	 *                     '2'          'a1b2c3d4e5f'
	 *                 (rotation)           (hash)
	 *
	 * @return void
	 */
	protected function parse_filename(): void {
		$info     = pathinfo( $this->path );
		$filename = $info['filename'];
		$segments = explode( '-', $filename );

		if ( count( $segments ) >= 5 ) {
			$this->source  = implode( '-', array_slice( $segments, 0, -4 ) );
			$this->created = strtotime( implode( '-', array_slice( $segments, -4, 3 ) ) );
			$this->hash    = array_slice( $segments, -1 )[0];
		} else {
			$this->source  = implode( '-', $segments );
			$this->created = filectime( $this->path );
			$this->hash    = $this->source;
		}

		$rotation_marker = strrpos( $this->source, '.', -1 );
		if ( false !== $rotation_marker ) {
			$rotation = substr( $this->source, -1 );
			if ( is_numeric( $rotation ) ) {
				$this->rotation = intval( $rotation );
			}

			$this->source = substr( $this->source, 0, $rotation_marker );
			if ( count( $segments ) < 5 ) {
				$this->hash = $this->source;
			}
		}
	}

	/**
	 * Check if the file represented by the class instance is a file and is readable.
	 *
	 * @global WP_Filesystem_Direct $wp_filesystem
	 *
	 * @return bool
	 */
	public function is_readable(): bool {
		global $wp_filesystem;

		return $wp_filesystem->is_file( $this->path ) && $wp_filesystem->is_readable( $this->path );
	}

	/**
	 * Check if the file represented by the class instance is a file and is writable.
	 *
	 * @global WP_Filesystem_Direct $wp_filesystem
	 *
	 * @return bool
	 */
	public function is_writable(): bool {
		global $wp_filesystem;

		return $wp_filesystem->is_file( $this->path ) && $wp_filesystem->is_writable( $this->path );
	}

	/**
	 * Open a read-only stream file this file.
	 *
	 * @return resource|false
	 */
	public function get_stream() {
		if ( ! is_resource( $this->stream ) ) {
			// phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_read_fopen -- No suitable alternative.
			$this->stream = fopen( $this->path, 'rb' );
		}

		return $this->stream;
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
	 * The file ID is the basename of the file without the hash part. It allows us to identify a file without revealing
	 * its full name in the filesystem, so that it's difficult to access the file directly with an HTTP request.
	 *
	 * @return string
	 */
	public function get_file_id(): string {
		$file_id = $this->get_source();

		if ( ! is_null( $this->get_rotation() ) ) {
			$file_id .= '.' . $this->get_rotation();
		}

		if ( $this->get_source() !== $this->get_hash() ) {
			$file_id .= '-' . gmdate( 'Y-m-d', $this->get_created_timestamp() );
		}

		return $file_id;
	}

	/**
	 * Get the file's created property.
	 *
	 * @return int|false
	 */
	public function get_created_timestamp() {
		return $this->created;
	}

	/**
	 * Get the time of the last modification of the file, as a Unix timestamp. Or false if the file isn't readable.
	 *
	 * @global WP_Filesystem_Direct $wp_filesystem
	 *
	 * @return int|false
	 */
	public function get_modified_timestamp() {
		global $wp_filesystem;

		return $wp_filesystem->mtime( $this->path );
	}

	/**
	 * Get the size of the file in bytes. Or false if the file isn't readable.
	 *
	 * @global WP_Filesystem_Direct $wp_filesystem
	 *
	 * @return int|false
	 */
	public function get_file_size() {
		global $wp_filesystem;

		if ( ! $wp_filesystem->is_readable( $this->path ) ) {
			return false;
		}

		return $wp_filesystem->size( $this->path );
	}

	/**
	 * Delete the file from the filesystem.
	 *
	 * @global WP_Filesystem_Direct $wp_filesystem
	 *
	 * @return bool True on success, false on failure.
	 */
	public function delete(): bool {
		global $wp_filesystem;

		return $wp_filesystem->delete( $this->path, false, 'f' );
	}
}
