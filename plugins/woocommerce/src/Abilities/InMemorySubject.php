<?php
/**
 * In-memory subject interface file.
 */

declare( strict_types=1 );

namespace Automattic\WooCommerce\Abilities;

defined( 'ABSPATH' ) || exit;

/**
 * An object an in-memory write changes that is not a WC_Data object.
 * InMemoryWrite::subject() may return one; the executor then validates,
 * applies, runs the validators registered for the write's subject_type(),
 * saves and responds as it does for WC_Data.
 *
 * @since 11.3.0
 */
interface InMemorySubject {

	/**
	 * The object's current state as plain data, used to compare it before and
	 * after a change. Never saves.
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
