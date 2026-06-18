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
	 * Start the feed.
	 * This can create an empty file, eventually put something in it, or add a database entry.
	 *
	 * @return void
	 */
	public function start(): void;

	/**
	 * Begin a new chunked feed.
	 *
	 * Unlike {@see start()}, a chunked feed is written across several separate processes
	 * (e.g. one Action Scheduler action per chunk). This creates the (empty) feed in a
	 * stable, shared location and returns an identifier that later chunks pass to
	 * {@see resume()} to keep appending to the same feed.
	 *
	 * @since 11.0.0
	 *
	 * @return string An identifier for the feed, to be passed to resume() by later chunks.
	 */
	public function begin(): string;

	/**
	 * Determines whether an existing chunked feed can still be resumed.
	 *
	 * @since 11.0.0
	 *
	 * @param string $identifier The identifier returned by {@see begin()}.
	 * @return bool True if the feed exists and can be appended to.
	 */
	public function can_resume( string $identifier ): bool;

	/**
	 * Resume appending to an existing chunked feed.
	 *
	 * @since 11.0.0
	 *
	 * @param string $identifier      The identifier returned by {@see begin()}.
	 * @param int    $entries_written The number of entries already written by previous chunks.
	 * @return void
	 */
	public function resume( string $identifier, int $entries_written ): void;

	/**
	 * Persist the current chunk without finalizing the feed.
	 *
	 * Called at the end of a chunk that is not the last one, so a later chunk can resume.
	 *
	 * @since 11.0.0
	 *
	 * @return void
	 */
	public function flush(): void;

	/**
	 * Finalize a chunked feed, marking it complete and ready to be served.
	 *
	 * @since 11.0.0
	 *
	 * @return void
	 */
	public function finalize(): void;

	/**
	 * Delete a feed (e.g. a partial feed left by an abandoned chunked generation).
	 *
	 * @since 11.0.0
	 *
	 * @param string $identifier The identifier returned by {@see begin()}.
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
	 * End the feed.
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

	/**
	 * Get the number of entries that have been added to the feed.
	 *
	 * This reflects the rows actually written to the feed, which may be fewer
	 * than the number of products iterated by `ProductWalker` because the
	 * validator can silently drop entries before they reach `add_entry()`.
	 *
	 * @since 10.9.0
	 * @return int Number of entries added to the feed.
	 */
	public function get_entry_count(): int;
}
