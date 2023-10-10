<?php

namespace Automattic\WooCommerce\Internal\Admin\Logging\FileV2;

class File {
	/**
	 * @var string The absolute path of the file.
	 */
	protected $path;

	/**
	 * @var string The source property of the file, derived from the filename.
	 */
	protected $source;

	/**
	 * @var int The date the file was created, as a Unix timestamp, derived from the filename.
	 */
	protected $created;

	/**
	 * @var string The key property of the file, derived from the filename.
	 */
	protected $key;

	/**
	 * Class File
	 *
	 * @param string $path The absolute path of the file.
	 */
	public function __construct( $path ) {
		$this->path = $path;
		$this->parse_filename();
	}

	/**
	 * Parse the log filename to derive certain properties of the file.
	 *
	 * This makes assumptions about the structure of the log file's name. Using `-` to separate the name into segments,
	 * if there are at least 5 segments, it assumes that the last segment is the "key" (i.e. hash), and the three
	 * segments before that make up the date when the file was created in YYYY-MM-DD format. Any segments left after
	 * that are the "source" that generated the log entries. If the filename doesn't have enough segments, it falls
	 * back to the source and the key both being the entire filename, and using the last modified time as the creation
	 * date.
	 *
	 * @return void
	 */
	protected function parse_filename() {
		$info     = pathinfo( $this->path );
		$filename = $info['filename'];
		$segments = explode( '-', $filename );

		if ( count( $segments ) >= 5 ) {
			$this->source  = implode( '-', array_slice( $segments, 0, -4 ) );
			$this->created = strtotime( implode( '-', array_slice( $segments, -4, 3 ) ) );
			$this->key     = array_slice( $segments, -1 )[0];
		} else {
			$this->source  = implode( '-', $segments );
			$this->created = filemtime( $this->path );
			$this->key     = $this->source;
		}
	}

	/**
	 * Get the file's source property.
	 *
	 * @return string
	 */
	public function get_source() {
		return $this->source;
	}

	/**
	 * Get the file's key property.
	 *
	 * @return string
	 */
	public function get_key() {
		return $this->key;
	}

	/**
	 * Get the file's created property.
	 *
	 * @return int
	 */
	public function get_created_timestamp() {
		return $this->created;
	}

	/**
	 * Get the time of the last modification of the file, as a Unix timestamp. Or false if the file isn't readable.
	 *
	 * @return int|false
	 */
	public function get_modified_timestamp() {
		return filemtime( $this->path );
	}

	/**
	 * Get the size of the file in bytes. Or false if the file isn't readable.
	 *
	 * @return int|false
	 */
	public function get_file_size() {
		if ( ! is_readable( $this->path ) ) {
			return false;
		}

		return filesize( $this->path );
	}
}
