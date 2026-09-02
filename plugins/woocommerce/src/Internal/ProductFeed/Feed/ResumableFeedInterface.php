<?php
/**
 * Resumable Feed Interface.
 *
 * @package Automattic\WooCommerce\Internal\ProductFeed
 */

declare(strict_types=1);

namespace Automattic\WooCommerce\Internal\ProductFeed\Feed;

/**
 * Contract for feeds that can be written across multiple processes (chunked generation).
 *
 * This is deliberately kept separate from {@see FeedInterface}: a feed backend opts into chunked,
 * resumable generation by implementing this interface *in addition to* FeedInterface. Existing
 * FeedInterface implementations (including those provided by third-party integrations) remain
 * valid and are unaffected, so adding this contract is a backwards-compatible change.
 *
 * @since 11.0.0
 */
interface ResumableFeedInterface extends FeedInterface {
	/**
	 * Start a feed fresh, or resume one that a previous chunk began.
	 *
	 * A feed may be written across separate processes (one Action Scheduler action per chunk), so it
	 * lives in a stable, shared location identified by the returned value. Pass that identifier back on
	 * a later chunk to keep appending to the same feed; pass nothing to begin a new one.
	 *
	 * @since 11.0.0
	 *
	 * @param string|null $resume_identifier Identifier of an existing feed to resume, or null to start fresh.
	 * @param int         $entries_written   The number of entries already written by previous chunks, so
	 *                                        separators are added correctly when resuming.
	 * @return string The identifier of the feed that was started, to be passed back by later chunks.
	 */
	public function open( ?string $resume_identifier = null, int $entries_written = 0 ): string;

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
	 * @param string $identifier The identifier returned by {@see open()}.
	 * @return void
	 */
	public function delete( string $identifier ): void;

	/**
	 * Get the number of entries that have been written to the feed.
	 *
	 * This reflects the rows actually written, which may be fewer than the number of products
	 * iterated, because the validator can silently drop entries before they are added.
	 *
	 * @since 11.0.0
	 *
	 * @return int Number of entries written to the feed.
	 */
	public function get_entry_count(): int;
}
