<?php
/**
 * Feed Interface.
 *
 * @package WooCommerce\ProductCatalog
 * @since   10.4.0
 */

declare( strict_types = 1 );

namespace Automattic\WooCommerce\ProductCatalog\Interfaces;

defined( 'ABSPATH' ) || exit;

/**
 * Interface for feed implementations.
 *
 * @package WooCommerce\ProductCatalog
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
	 * @return string
	 */
	public function get_file_path(): string;

	/**
	 * Get the URL of the feed file.
	 *
	 * @return string|null The URL of the feed file, null if not completed.
	 */
	public function get_file_url(): ?string;
}
