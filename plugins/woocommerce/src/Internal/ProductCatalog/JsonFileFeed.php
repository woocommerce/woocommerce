<?php
/**
 * JSON File Feed class.
 *
 * @package WooCommerce\Internal\ProductCatalog
 * @since   10.4.0
 */

declare( strict_types = 1 );

namespace Automattic\WooCommerce\Internal\ProductCatalog;

use Automattic\WooCommerce\ProductCatalog\Interfaces\FeedInterface;
use RuntimeException;

defined( 'ABSPATH' ) || exit;

// This file works directly with local files. That's fine.
// phpcs:disable WordPress.WP.AlternativeFunctions

/**
 * File-backed JSON feed storage.
 *
 * This class writes JSON directly to a file, entry by entry, without keeping everything in memory.
 *
 * @internal This class is intended for internal use only and should not be used by extensions.
 * @package  WooCommerce\Internal\ProductCatalog
 */
class JsonFileFeed implements FeedInterface {
	/**
	 * Indicates if there are previous entries in the feed.
	 *
	 * @var bool
	 */
	private bool $has_entries = false;

	/**
	 * The path to the feed file.
	 *
	 * @var string
	 */
	private string $file_path = '';

	/**
	 * The file handle.
	 *
	 * @var resource|null
	 */
	private $file_handle = null;

	/**
	 * The base name of the feed file.
	 *
	 * @var string
	 */
	private string $base_name;

	/**
	 * Indicates if the feed file has been completed.
	 *
	 * @var bool
	 */
	private bool $file_completed = false;

	/**
	 * The URL of the feed file.
	 *
	 * @var string|null
	 */
	private ?string $file_url = null;

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
	 * @throws RuntimeException If the feed directory cannot be created.
	 */
	public function start(): void {
		$upload_dir = wp_upload_dir( null, true );
		$directory  = $upload_dir['basedir'] . DIRECTORY_SEPARATOR . 'wc-product-catalog' . DIRECTORY_SEPARATOR;

		if ( ! is_dir( $directory ) && ! wp_mkdir_p( $directory ) ) {
			throw new RuntimeException(
				esc_html(
					sprintf(
						/* translators: %s: directory path */
						__( 'Unable to create feed directory: %s', 'woocommerce' ),
						$directory
					)
				)
			);
		}

		$file_name         = wp_unique_filename( $directory, $this->base_name . '.json' );
		$this->file_path   = $directory . $file_name;
		$this->file_url    = $upload_dir['baseurl'] . '/wc-product-catalog/' . $file_name;
		$this->file_handle = fopen( $this->file_path, 'w' );

		if ( false === $this->file_handle ) {
			throw new RuntimeException(
				esc_html(
					sprintf(
						/* translators: %s: file path */
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
		if ( ! $this->has_entries ) {
			$this->has_entries = true;
		} else {
			fwrite( $this->file_handle, ',' );
		}

		fwrite( $this->file_handle, wp_json_encode( $entry ) );
	}

	/**
	 * End the feed.
	 *
	 * @return void
	 */
	public function end(): void {
		// Close the array and the file.
		fwrite( $this->file_handle, ']' );
		fclose( $this->file_handle );

		// Indicate that we have a complete file.
		$this->file_completed = true;
	}

	/**
	 * Get the path to the feed file.
	 *
	 * @return string The path to the feed file.
	 */
	public function get_file_path(): string {
		return $this->file_path;
	}

	/**
	 * Get the URL of the feed file.
	 *
	 * @return string|null The URL of the feed file, null if not completed.
	 */
	public function get_file_url(): ?string {
		if ( ! $this->file_completed ) {
			return null;
		}

		return $this->file_url;
	}
}
