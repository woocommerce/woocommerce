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
	 * {@inheritDoc}
	 *
	 * A feed can be written across separate processes (and possibly servers), so it is created
	 * directly in the shared upload directory rather than a per-request temp directory.
	 *
	 * @param string|null $resume_identifier Identifier of an existing feed to resume, or null to start fresh.
	 * @param int         $entries_written   The number of entries already written by previous chunks.
	 * @return string The identifier of the feed that was started.
	 * @throws Exception If the feed directory or file cannot be created/opened.
	 */
	public function start( ?string $resume_identifier = null, int $entries_written = 0 ): string {
		$upload_dir = $this->get_upload_dir();

		$this->file_completed = false;
		$this->file_url       = null;

		// Resume an existing feed when asked to and its partial file is still present; otherwise start
		// a brand-new feed. The file may have vanished (e.g. cleaned up by the host), in which case we
		// fall back to a fresh feed.
		if ( null !== $resume_identifier && $this->partial_file_exists( $resume_identifier ) ) {
			$this->file_name = $resume_identifier;
			$this->file_path = $upload_dir['path'] . $resume_identifier;
			// Seed the entry count so add_entry()'s separator accounts for entries already written.
			$this->entry_count = $entries_written;
			$this->open_handle( $this->file_path, 'a' );

			return $this->file_name;
		}

		$this->entry_count = 0;
		$this->file_name   = $this->generate_file_name();
		$this->file_path   = $upload_dir['path'] . $this->file_name;
		$this->open_handle( $this->file_path, 'w' );

		// Open the array.
		fwrite( $this->file_handle, '[' );

		return $this->file_name;
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
	 * {@inheritDoc}
	 *
	 * @throws Exception If the feed file cannot be opened to be finalized.
	 */
	public function end(): void {
		// Nothing was ever started; keep the historical no-op behaviour.
		if ( ! is_resource( $this->file_handle ) && empty( $this->file_path ) ) {
			return;
		}

		// The handle may have been released by flush() between chunks (the last chunk can run in its
		// own process), so reopen the file for appending if needed.
		if ( ! is_resource( $this->file_handle ) ) {
			$this->open_handle( (string) $this->file_path, 'a' );
		}

		// Close the array and the file.
		fwrite( $this->file_handle, ']' );
		fclose( $this->file_handle );
		$this->file_handle = null;

		// Indicate that we have a complete file.
		$this->file_completed = true;
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
	 * @param string $identifier The identifier returned by start().
	 * @return void
	 */
	public function delete( string $identifier ): void {
		$path = $this->feed_file_path( $identifier );
		if ( is_file( $path ) ) {
			wp_delete_file( $path );
		}
	}

	/**
	 * Resolves a feed file's path from its identifier without creating the upload directory.
	 *
	 * Used for existence checks and deletion, where the directory should not be created as a side
	 * effect (unlike {@see get_upload_dir()}, which is used when actually writing a feed).
	 *
	 * @param string $identifier The feed file name.
	 * @return string The absolute path to the feed file.
	 */
	private function feed_file_path( string $identifier ): string {
		$upload_dir = wp_upload_dir( null, false );
		return $upload_dir['basedir'] . DIRECTORY_SEPARATOR . self::UPLOAD_DIR . DIRECTORY_SEPARATOR . $identifier;
	}

	/**
	 * Determines whether a partial feed file exists and can be appended to.
	 *
	 * @param string $identifier The feed file name.
	 * @return bool True if the feed file exists and has content.
	 */
	private function partial_file_exists( string $identifier ): bool {
		$path = $this->feed_file_path( $identifier );
		return is_file( $path ) && filesize( $path ) > 0;
	}

	/**
	 * Opens the feed file handle, throwing if it cannot be opened.
	 *
	 * @param string $path The file path to open.
	 * @param string $mode The fopen() mode.
	 * @return void
	 * @throws Exception If the file cannot be opened.
	 */
	private function open_handle( string $path, string $mode ): void {
		$this->file_handle = fopen( $path, $mode );
		if ( false === $this->file_handle ) {
			throw new Exception(
				esc_html(
					sprintf(
						/* translators: %s: file path */
						__( 'Unable to open feed file: %s', 'woocommerce' ),
						$path
					)
				)
			);
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
	 * @throws Exception If the upload directory cannot be created.
	 */
	public function get_file_url(): ?string {
		if ( ! $this->file_completed ) {
			return null;
		}

		// Resolve the upload directory (also refreshes its .htaccess for file access) and build the URL.
		$upload_dir     = $this->get_upload_dir();
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
