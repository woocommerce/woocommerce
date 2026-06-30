<?php
/**
 * Feed Interface.
 *
 * @package Automattic\WooCommerce\Internal\ProductFeed
 */

declare(strict_types=1);

namespace Automattic\WooCommerce\Internal\ProductFeed\Feed;

/**
 * Feed Interface.
 *
 * @since 10.5.0
 */
interface FeedInterface {
	/**
	 * Start a feed, either fresh or by resuming one a previous chunk began.
	 *
	 * A feed may be written across separate processes (one Action Scheduler action per chunk), so it
	 * lives in a stable, shared location identified by the returned value. Pass that identifier back on
	 * a later chunk to keep appending to the same feed; pass nothing to begin a new one.
	 *
	 * @since 10.5.0
	 * @since 11.0.0 Added the `$resume_identifier` and `$entries_written` parameters.
	 *
	 * @param string|null $resume_identifier Identifier of an existing feed to resume, or null to start fresh.
	 * @param int         $entries_written   The number of entries already written by previous chunks, so
	 *                                        separators are added correctly when resuming.
	 * @return string The identifier of the feed that was started, to be passed back by later chunks.
	 */
	public function start( ?string $resume_identifier = null, int $entries_written = 0 ): string;

	/**
	 * Persist the current chunk and release the file handle without finalizing the feed.
	 *
	 * Called at the end of a chunk that is not the last one, so a later chunk can resume.
	 *
	 * @since 11.0.0
	 *
	 * @return void
	 */
	public function flush(): void;

	/**
	 * Delete a feed (e.g. a partial feed left by an abandoned chunked generation).
	 *
	 * @since 11.0.0
	 *
	 * @param string $identifier The identifier returned by {@see start()}.
	 * @return void
	 */
	public function delete( string $identifier ): void;

	/**
	 * Add an entry to the feed.
	 *
	 * @param array $entry The entry to add.
	 * @return void
	 */
	public function add_entry( array $entry ): void;

	/**
	 * End the feed, marking it complete and ready to be served.
	 *
	 * @return void
	 */
	public function end(): void;

	/**
	 * Get the file path of the feed.
	 *
	 * @return string|null The path to the feed file, null if not ready.
	 */
	public function get_file_path(): ?string;

	/**
	 * Get the URL of the feed file.
	 *
	 * @return string|null The URL of the feed file, null if not ready.
	 */
	public function get_file_url(): ?string;
}
