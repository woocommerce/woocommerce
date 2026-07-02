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
use Automattic\WooCommerce\Internal\ProductFeed\Feed\FeedLockException;
use Automattic\WooCommerce\Internal\ProductFeed\Feed\ResumableFeedInterface;
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
class JsonFileFeed implements ResumableFeedInterface {
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
	 * Only ever a resource or null: open_handle() throws instead of storing a failed fopen().
	 *
	 * @var resource|null
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
	 * Simple one-shot entry point for non-resumable generation. This is a thin adapter over the
	 * resumable {@see open()}: it starts a fresh feed and discards the returned identifier. It exists
	 * to honor the base {@see FeedInterface} contract; chunked callers use {@see open()} directly.
	 *
	 * @return void
	 * @throws Exception If the feed directory or file cannot be created/opened.
	 */
	public function start(): void {
		$this->open();
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
	 * @throws Exception If the feed directory or file cannot be created/opened, a resumed feed is missing,
	 *                   or the feed file is already locked by another generation process (FeedLockException).
	 */
	public function open( ?string $resume_identifier = null, int $entries_written = 0 ): string {
		$upload_dir = $this->get_upload_dir();

		$this->file_completed = false;
		$this->file_url       = null;

		if ( null !== $resume_identifier ) {
			if ( ! $this->is_valid_feed_identifier( $resume_identifier ) ) {
				throw new Exception(
					esc_html(
						sprintf(
							/* translators: %s: feed identifier */
							__( 'Invalid feed file identifier: %s', 'woocommerce' ),
							$resume_identifier
						)
					)
				);
			}

			$this->file_name = $resume_identifier;
			$this->file_path = $upload_dir['path'] . $resume_identifier;

			// The partial must still be there to append to. If it has vanished (e.g. cleaned up by the
			// host), fail rather than write a corrupt feed; the caller restarts generation from scratch.
			if ( ! is_file( $this->file_path ) ) {
				throw new Exception(
					esc_html(
						sprintf(
							/* translators: %s: file path */
							__( 'Cannot resume feed; file does not exist: %s', 'woocommerce' ),
							$this->file_path
						)
					)
				);
			}

			// Seed the entry count so add_entry()'s separator accounts for entries already written.
			$this->entry_count = $entries_written;
			$handle            = $this->open_handle( $this->file_path, 'a' );
			$this->acquire_lock( $handle );

			return $this->file_name;
		}

		$this->entry_count = 0;
		$this->file_name   = $this->generate_file_name();
		$this->file_path   = $upload_dir['path'] . $this->file_name;

		// Open with 'c' (create, do not truncate) rather than 'w' so the exclusive lock is acquired
		// *before* any existing content is cleared. An overlapping process that fails to get the lock
		// must not truncate a feed the lock holder is still writing; only once the lock is held is it
		// safe to clear stale content and write the array from the top.
		$handle = $this->open_handle( $this->file_path, 'c' );
		$this->acquire_lock( $handle );

		if ( ! ftruncate( $handle, 0 ) || ! rewind( $handle ) ) {
			fclose( $handle );
			$this->file_handle = null;
			throw new Exception(
				esc_html(
					sprintf(
						/* translators: %s: file path */
						__( 'Unable to reset feed file for writing: %s', 'woocommerce' ),
						$this->file_path
					)
				)
			);
		}

		fwrite( $handle, '[' );

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
	 */
	public function end(): void {
		if ( ! is_resource( $this->file_handle ) ) {
			return;
		}

		fwrite( $this->file_handle, ']' );
		// Do not unlock explicitly before closing: fclose() flushes PHP's userspace stream buffer to the
		// OS and only then releases the lock. Unlocking first would open a window in which another process
		// could acquire the lock and start writing while this process's buffered bytes are still pending,
		// interleaving output — the exact corruption the lock prevents.
		fclose( $this->file_handle );
		$this->file_handle    = null;
		$this->file_completed = true;
	}

	/**
	 * {@inheritDoc}
	 */
	public function flush(): void {
		if ( is_resource( $this->file_handle ) ) {
			// Rely on fclose() to release the lock: it flushes the userspace buffer first and only then
			// unlocks, so the lock stays held until the buffered bytes are durable. An explicit LOCK_UN
			// here would release it while pending bytes are still buffered (see end()).
			fclose( $this->file_handle );
			$this->file_handle = null;
		}
	}

	/**
	 * {@inheritDoc}
	 *
	 * @param string $identifier The identifier returned by open().
	 * @return void
	 */
	public function delete( string $identifier ): void {
		// Never turn an identifier that is actually a path into a delete outside the feed directory.
		if ( ! $this->is_valid_feed_identifier( $identifier ) ) {
			return;
		}

		$path = $this->feed_file_path( $identifier );
		if ( is_file( $path ) ) {
			wp_delete_file( $path );
		}
	}

	/**
	 * Checks that a feed identifier is a plain feed file name, not a path.
	 *
	 * Identifiers round-trip through the persisted status option and are accepted by the public
	 * {@see delete()}, so a corrupted or hostile value (e.g. containing `../`) must never be
	 * concatenated into a path that escapes the feed directory.
	 *
	 * @param string $identifier The feed file identifier to check.
	 * @return bool True if the identifier is a safe, plain `.json` file name.
	 */
	private function is_valid_feed_identifier( string $identifier ): bool {
		return '' !== $identifier
			&& wp_basename( $identifier ) === $identifier
			&& 'json' === strtolower( (string) pathinfo( $identifier, PATHINFO_EXTENSION ) );
	}

	/**
	 * Resolves a feed file's path from its identifier without creating the upload directory.
	 *
	 * Unlike {@see get_upload_dir()} (used when writing), this must not create the directory as a side effect.
	 * Callers must validate the identifier with {@see is_valid_feed_identifier()} first.
	 *
	 * @param string $identifier The feed file name.
	 * @return string The absolute path to the feed file.
	 */
	private function feed_file_path( string $identifier ): string {
		$upload_dir = wp_upload_dir( null, false );
		return $upload_dir['basedir'] . DIRECTORY_SEPARATOR . self::UPLOAD_DIR . DIRECTORY_SEPARATOR . $identifier;
	}

	/**
	 * Opens the feed file handle, throwing if it cannot be opened.
	 *
	 * @param string $path The file path to open.
	 * @param string $mode The fopen() mode.
	 * @return resource The opened file handle.
	 * @throws Exception If the file cannot be opened.
	 */
	private function open_handle( string $path, string $mode ) {
		$handle = fopen( $path, $mode );
		if ( false === $handle ) {
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

		$this->file_handle = $handle;
		return $handle;
	}

	/**
	 * Acquires an exclusive, non-blocking lock on the open feed file handle.
	 *
	 * A feed file is written across separate, short-lived processes (one Action Scheduler action per
	 * chunk). A stuck in-progress job can be treated as stuck and have a fresh generation enqueued
	 * while the original is still running, so two processes can end up writing the same file at once;
	 * without mutual exclusion their unbuffered writes interleave into malformed JSON. The lock makes
	 * each chunk's write to the shared file exclusive, so overlapping generations cannot interleave.
	 *
	 * The lock is advisory, which is sufficient because every writer goes through this class. It is
	 * released when the handle is closed in flush()/end(), including when a process is killed and the
	 * OS closes its descriptors — so a killed job leaves no stale lock, and its partial file is handled
	 * by the heartbeat-based stuck-job recovery instead.
	 *
	 * @param resource $handle The open feed file handle.
	 * @return void
	 * @throws FeedLockException If the lock is already held by another process.
	 */
	private function acquire_lock( $handle ): void {
		if ( ! flock( $handle, LOCK_EX | LOCK_NB ) ) {
			fclose( $handle );
			$this->file_handle = null;
			throw new FeedLockException(
				esc_html(
					sprintf(
						/* translators: %s: file path */
						__( 'Feed file is locked by another generation process: %s', 'woocommerce' ),
						$this->file_path
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
