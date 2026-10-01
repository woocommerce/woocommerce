<?php
/**
 * In-memory subject interface file.
 */

declare( strict_types=1 );

namespace Automattic\WooCommerce\Internal\AbilitiesApi;

defined( 'ABSPATH' ) || exit;

/**
 * An object an in-memory write changes and saves once. An object that does
 * not implement it can be adapted through the
 * `woocommerce_ability_in_memory_subject` filter.
 *
 * @since 11.3.0
 */
interface InMemorySubject {

	/**
	 * The object's current state as plain data. Never saves.
	 *
	 * @return array
	 *
	 * @since 11.3.0
	 */
	public function snapshot(): array;

	/**
	 * Persist the changes applied in memory.
	 *
	 * @return true|\WP_Error An error when nothing was saved.
	 *
	 * @since 11.3.0
	 */
	public function save();
}
