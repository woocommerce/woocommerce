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
	 * The number of entries added to the feed.
	 *
	 * @var int
	 */
	private $entry_count = 0;

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
	 * Cached upload directory details (path and URL), resolved once per feed instance.
	 *
	 * @var array|null
	 */
	private $prepared_upload_dir = null;

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
		$this->entry_count    = 0;
		$this->file_completed = false;
		$this->file_url       = null;
		$this->file_name      = $this->generate_file_name();

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

		$json = wp_json_encode( $entry );
		if ( false === $json ) {
			return;
		}

		if ( $this->entry_count > 0 ) {
			fwrite( $this->file_handle, ',' );
		}

		fwrite( $this->file_handle, $json );
		++$this->entry_count;
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
	 *
	 * @throws Exception If the feed directory or file cannot be created.
	 */
	public function begin(): string {
		// Chunked feeds are written across separate processes (and possibly servers), so the
		// shared upload directory is used directly rather than a per-request temp directory.
		$upload_dir = $this->get_upload_dir();

		$this->entry_count      = 0;
		$this->file_completed   = false;
		$this->file_url         = null;
		$this->is_temp_filepath = false;
		$this->file_name        = $this->generate_file_name();
		$this->file_path        = $upload_dir['path'] . $this->file_name;

		$this->file_handle = fopen( $this->file_path, 'w' );
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

		return $this->file_name;
	}

	/**
	 * {@inheritDoc}
	 *
	 * @param string $identifier The identifier returned by begin().
	 * @return bool True if the feed exists and can be appended to.
	 */
	public function can_resume( string $identifier ): bool {
		// Resolve the path without creating the upload directory as a side effect.
		$upload_dir = wp_upload_dir( null, false );
		$path       = $upload_dir['basedir'] . DIRECTORY_SEPARATOR . self::UPLOAD_DIR . DIRECTORY_SEPARATOR . $identifier;

		return is_file( $path ) && filesize( $path ) > 0;
	}

	/**
	 * {@inheritDoc}
	 *
	 * @param string $identifier      The identifier returned by begin().
	 * @param int    $entries_written The number of entries already written by previous chunks.
	 * @throws Exception If the feed file cannot be opened for appending.
	 */
	public function resume( string $identifier, int $entries_written ): void {
		$upload_dir = $this->get_upload_dir();

		$this->file_name        = $identifier;
		$this->file_path        = $upload_dir['path'] . $identifier;
		$this->file_completed   = false;
		$this->file_url         = null;
		$this->is_temp_filepath = false;
		// Seed the entry count so the comma separator in add_entry() accounts for entries
		// already written by previous chunks.
		$this->entry_count = $entries_written;

		$this->file_handle = fopen( $this->file_path, 'a' );
		if ( false === $this->file_handle ) {
			throw new Exception(
				esc_html(
					sprintf(
						/* translators: %s: file path */
						__( 'Unable to open feed file for appending: %s', 'woocommerce' ),
						$this->file_path
					)
				)
			);
		}
	}

	/**
	 * {@inheritDoc}
	 */
	public function flush(): void {
		if ( is_resource( $this->file_handle ) ) {
			fclose( $this->file_handle );
			$this->file_handle = null;
		}
	}

	/**
	 * {@inheritDoc}
	 *
	 * @throws Exception If the feed file cannot be opened to be finalized.
	 */
	public function finalize(): void {
		// The handle may already be closed by a previous flush() (e.g. the last chunk runs in its
		// own process), so reopen the file for appending if needed.
		if ( ! is_resource( $this->file_handle ) ) {
			$this->file_handle = fopen( (string) $this->file_path, 'a' );
		}

		if ( ! is_resource( $this->file_handle ) ) {
			throw new Exception(
				esc_html(
					sprintf(
						/* translators: %s: file path */
						__( 'Unable to open feed file to finalize: %s', 'woocommerce' ),
						(string) $this->file_path
					)
				)
			);
		}

		// Close the array and the file.
		fwrite( $this->file_handle, ']' );
		fclose( $this->file_handle );
		$this->file_handle = null;

		$this->file_completed = true;
	}

	/**
	 * {@inheritDoc}
	 *
	 * @param string $identifier The identifier returned by begin().
	 * @return void
	 */
	public function delete( string $identifier ): void {
		// Resolve the path without creating the upload directory as a side effect.
		$upload_dir = wp_upload_dir( null, false );
		$path       = $upload_dir['basedir'] . DIRECTORY_SEPARATOR . self::UPLOAD_DIR . DIRECTORY_SEPARATOR . $identifier;

		if ( is_file( $path ) ) {
			wp_delete_file( $path );
		}
	}

	/**
	 * Generate the feed file name based on the base name and the current time.
	 *
	 * @return string The feed file name.
	 */
	private function generate_file_name(): string {
		/**
		 * Allows the current time to be overridden before a feed is stored.
		 *
		 * @param int           $time The current time.
		 * @param FeedInterface $feed The feed instance.
		 * @return int The current time.
		 * @since 10.5.0
		 */
		$current_time = apply_filters( 'woocommerce_product_feed_time', time(), $this );
		$hash_data    = $this->base_name . gmdate( 'r', $current_time );

		return sprintf(
			'%s-%s-%s.json',
			$this->base_name,
			gmdate( 'Y-m-d', $current_time ),
			wp_hash( $hash_data )
		);
	}

	/**
	 * {@inheritDoc}
	 */
	public function get_entry_count(): int {
		return $this->entry_count;
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
		// Resolve once per feed instance.
		if ( null !== $this->prepared_upload_dir ) {
			return $this->prepared_upload_dir;
		}

		$upload_dir     = wp_upload_dir( null, true );
		$directory_path = $upload_dir['basedir'] . DIRECTORY_SEPARATOR . self::UPLOAD_DIR . DIRECTORY_SEPARATOR;

		// Create the directory if it does not exist, allowing file access so the generated feed
		// files can be served by URL while directory listing stays disabled. If the directory
		// already exists, refresh its .htaccess in place so installs created before file access
		// was enabled also serve feeds correctly.
		if ( ! is_dir( $directory_path ) ) {
			FilesystemUtil::mkdir_p_not_indexable( $directory_path, true );
		} else {
			$this->ensure_feed_dir_file_access( $directory_path );
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
		$this->prepared_upload_dir = array(
			'path' => $directory_path,
			'url'  => $directory_url,
		);
		return $this->prepared_upload_dir;
	}

	/**
	 * Upgrades a legacy `deny from all` .htaccess in an existing feed directory to allow file access.
	 *
	 * Installs created before file access was enabled have a `deny from all` .htaccess here, which
	 * blocks feed downloads. This upgrades only that known legacy directive, in place. Anything else
	 * — an already-correct directive, custom rules a site or host added, a file we cannot read, or a
	 * missing file — is left untouched. (The directory's initial .htaccess is written when the
	 * directory is first created, by `mkdir_p_not_indexable()`.)
	 *
	 * Native file functions are used here (like the feed writes elsewhere in this class) rather
	 * than WP_Filesystem: the directory is local, and routing through a possibly FTP/SSH-backed
	 * filesystem could fail to initialize and leave the old `deny from all` in place even though
	 * the feed file itself was written natively. A failure is ignored (and logged) so it can never
	 * interrupt feed generation.
	 *
	 * @param string $directory_path The feed directory path (trailing-slashed).
	 * @return void
	 */
	private function ensure_feed_dir_file_access( string $directory_path ): void {
		$htaccess_path = $directory_path . '.htaccess';

		// Only act on an existing file. A missing .htaccess does not block downloads, so there is
		// nothing to fix — and we should not create a file the directory did not already have.
		if ( ! is_file( $htaccess_path ) ) {
			return;
		}

		// phpcs:ignore WordPress.PHP.NoSilencedErrors.Discouraged
		$current_content = @file_get_contents( $htaccess_path );

		// Upgrade only the known legacy `deny from all` directive. Leave anything else — already
		// correct, custom rules, or a file we cannot read — untouched, never clobbering content
		// we did not write.
		if ( false === $current_content || FilesystemUtil::HTACCESS_DENY_ALL !== trim( $current_content ) ) {
			return;
		}

		// Best effort: a failure must never interrupt feed generation, but log it — otherwise the
		// feed would silently stay 403 behind the stale rule.
		// phpcs:ignore WordPress.PHP.NoSilencedErrors.Discouraged
		if ( false === @file_put_contents( $htaccess_path, FilesystemUtil::HTACCESS_ALLOW_FILE_ACCESS ) ) {
			wc_get_logger()->warning(
				'Could not update the product feed .htaccess to allow file access; generated feeds may remain inaccessible.',
				array(
					'source' => 'product-feed',
					'path'   => $htaccess_path,
				)
			);
		}
	}
}
