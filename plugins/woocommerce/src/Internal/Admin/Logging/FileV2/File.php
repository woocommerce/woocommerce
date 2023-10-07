<?php

namespace Automattic\WooCommerce\Internal\Admin\Logging\FileV2;

class File {
	/**
	 * @var string The absolute path of the file.
	 */
	protected $path;

	/**
	 * Class File
	 *
	 * @param string $path The absolute path of the file.
	 */
	public function __construct( $path ) {
		$this->path = $path;
	}

	/**
	 * Get the name of the file, sans path.
	 *
	 * @return string
	 */
	public function get_filename() {
		return basename( $this->path );
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

	/**
	 * Get the time of the last modification of the file, as a Unix timestamp. Or false if the file isn't readable.
	 *
	 * @return int|false
	 */
	public function get_modified_timestamp() {
		return filemtime( $this->path );
	}
}
