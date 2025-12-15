<?php
/**
 * JSON File Feed class.
 *
 * @package Automattic\WooCommerce\Internal\ProductFeed
 */

declare(strict_types=1);

namespace Automattic\WooCommerce\Internal\ProductFeed\Storage;

use Automattic\WooCommerce\Internal\Utilities\FilesystemUtil;
use Automattic\WooCommerce\Internal\ProductFeed\Feed\FeedInterface;
use Exception;

// This file works directly with local files. That's fine.
// phpcs:disable WordPress.WP.AlternativeFunctions

/**
 * File-backed JSON feed storage.
 *
 * This class writes JSON directly to a file, entry by entry, without keeping everything in memory.
 *
 * @since 10.5.0
 */
class JsonFileFeed implements FeedInterface {
	public const UPLOAD_DIR = 'product-feeds';

	/**
	 * Indicates if there are previous entries in the feed.
	 *
	 * @var bool
	 */
	private $has_entries = false;

	/**
	 * The base name of the feed file.
	 *
	 * @var string
	 */
	private $base_name;

	/**
	 * The name of the feed file, no directory.
	 *
	 * @var string
	 */
	private $file_name;

	/**
	 * The path to the feed file.
	 *
	 * @var string
	 */
	private $file_path;

	/**
	 * The file handle.
	 *
	 * @var resource|false|null
	 */
	private $file_handle = null;

	/**
	 * Indicates if the feed file has been completed.
	 *
	 * @var bool
	 */
	private $file_completed = false;

	/**
	 * The URL of the feed file.
	 *
	 * @var string|null
	 */
	private $file_url = null;

	/**
	 * Indicates if the feed file is in a temp directory.
	 *
	 * @var bool
	 */
	private $is_temp_filepath = false;

	/**
	 * Constructor.
	 *
	 * @param string $base_name The base name of the feed file.
	 */
	public function __construct( string $base_name ) {
		$this->base_name = $base_name;
	}

	/**
	 * Start the feed.
	 *
	 * @return void
	 * @throws Exception If the feed directory cannot be created.
	 */
	public function start(): void {
		/**
		 * Allows the current time to be overridden before a feed is stored.
		 *
		 * @param int           $time The current time.
		 * @param FeedInterface $feed The feed instance.
		 * @return int The current time.
		 * @since 10.5.0
		 */
		$current_time    = apply_filters( 'woocommerce_product_feed_time', time(), $this );
		$hash_data       = $this->base_name . gmdate( 'r', $current_time );
		$this->file_name = sprintf(
			'%s-%s-%s.json',
			$this->base_name,
			gmdate( 'Y-m-d', $current_time ),
			wp_hash( $hash_data )
		);

		// Start by trying to use a temp directory to generate the feed.
		$this->file_path   = get_temp_dir() . DIRECTORY_SEPARATOR . $this->file_name;
		$this->file_handle = fopen( $this->file_path, 'w' );
		if ( false === $this->file_handle ) {
			// Fall back to immediately using the upload directory for generation.
			$upload_dir        = $this->get_upload_dir();
			$this->file_path   = $upload_dir['path'] . $this->file_name;
			$this->file_handle = fopen( $this->file_path, 'w' );
		} else {
			$this->is_temp_filepath = true;
		}

		if ( false === $this->file_handle ) {
			throw new Exception(
				esc_html(
					sprintf(
						/* translators: %s: directory path */
						__( 'Unable to open feed file for writing: %s', 'woocommerce' ),
						$this->file_path
					)
				)
			);
		}

		// Open the array.
		fwrite( $this->file_handle, '[' );
	}

	/**
	 * Add an entry to the feed.
	 *
	 * @param array $entry The entry to add.
	 * @return void
	 */
	public function add_entry( array $entry ): void {
		if ( ! is_resource( $this->file_handle ) ) {
			return;
		}

		if ( ! $this->has_entries ) {
			$this->has_entries = true;
		} else {
			fwrite( $this->file_handle, ',' );
		}

		$json = wp_json_encode( $entry );
		if ( false !== $json ) {
			fwrite( $this->file_handle, $json );
		}
	}

	/**
	 * End the feed.
	 *
	 * @return void
	 */
	public function end(): void {
		if ( ! is_resource( $this->file_handle ) ) {
			return;
		}

		// Close the array and the file.
		fwrite( $this->file_handle, ']' );
		fclose( $this->file_handle );

		// Indicate that we have a complete file.
		$this->file_completed = true;
	}

	/**
	 * {@inheritDoc}
	 */
	public function get_file_path(): ?string {
		if ( ! $this->file_completed ) {
			return null;
		}

		return $this->file_path;
	}

	/**
	 * {@inheritDoc}
	 *
	 * @throws Exception If the feed file cannot be moved to the upload directory.
	 */
	public function get_file_url(): ?string {
		if ( ! $this->file_completed ) {
			return null;
		}

		$upload_dir = $this->get_upload_dir();

		// Move the file to the upload directory if it is in temp.
		if ( $this->is_temp_filepath ) {
			$tmp_path        = $this->file_path;
			$this->file_path = $upload_dir['path'] . $this->file_name;
			// phpcs:ignore WordPress.PHP.NoSilencedErrors.Discouraged
			if ( ! @copy( $tmp_path, $this->file_path ) ) {
				$error         = error_get_last();
				$error_message = is_array( $error ) ? $error['message'] : 'Unknown error';
				throw new Exception(
					esc_html(
						sprintf(
							/* translators: %1$s: file path, %2$s: error message */
							__( 'Unable to move feed file %1$s to upload directory: %2$s', 'woocommerce' ),
							$this->file_path,
							$error_message
						)
					)
				);
			}

			unlink( $tmp_path );

			$this->is_temp_filepath = false;
		}

		// Generate the URL.
		$this->file_url = $upload_dir['url'] . $this->file_name;

		return $this->file_url;
	}

	/**
	 * Get the upload directory for the feed.
	 *
	 * @return array {
	 *     The upload directory for the feed. Both fields end with the right trailing slash.
	 *
	 *     @type string $path The path to the upload directory.
	 *     @type string $url The URL to the upload directory.
	 * }
	 * @throws Exception If the upload directory cannot be created.
	 */
	private function get_upload_dir(): array {
		// Only generate everything once.
		static $prepared;
		if ( isset( $prepared ) ) {
			return $prepared;
		}

		$upload_dir     = wp_upload_dir( null, true );
		$directory_path = $upload_dir['basedir'] . DIRECTORY_SEPARATOR . self::UPLOAD_DIR . DIRECTORY_SEPARATOR;

		// Try to create the directory if it does not exist.
		if ( ! is_dir( $directory_path ) ) {
			FilesystemUtil::mkdir_p_not_indexable( $directory_path );
		}

		// `mkdir_p_not_indexable()` returns `void`, we have to check again.
		if ( ! is_dir( $directory_path ) ) {
			throw new Exception(
				esc_html(
					sprintf(
						/* translators: %s: directory path */
						__( 'Unable to create feed directory: %s', 'woocommerce' ),
						$directory_path
					)
				)
			);
		}

		$directory_url = $upload_dir['baseurl'] . '/' . self::UPLOAD_DIR . '/';

		// Follow the format, returned by `wp_upload_dir()`.
		$prepared = array(
			'path' => $directory_path,
			'url'  => $directory_url,
		);
		return $prepared;
	}
}
